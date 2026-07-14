<?php
// app/Livewire/OntList.php

namespace App\Livewire;

use App\Models\OLT;
use App\Models\OLTPort;
use App\Models\ONT;
use App\Models\Alarm;
use Livewire\Component;
use Livewire\WithPagination;

class OntList extends Component
{
    use WithPagination;

    public $oltId = '';
    public $portId = '';
    public $statusFilter = '';
    public $vendorFilter = '';
    public $search = '';

    public $olts = [];
    public $ports = [];
    public $vendors = [];
    public $stats = [];
    public $selectedOnt = null;

    protected $queryString = [
        'oltId' => ['except' => ''],
        'portId' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'vendorFilter' => ['except' => ''],
        'search' => ['except' => ''],
    ];

    public function mount()
    {
        $this->olts = OLT::where('is_active', true)->get();
        $this->loadFilters();
        $this->loadOnts();
    }

    public function loadFilters()
    {
        if ($this->oltId) {
            $this->ports = OLTPort::where('olt_id', $this->oltId)
                ->where('type', 'pon')
                ->get();
        } else {
            $this->ports = collect();
        }

        $this->vendors = ONT::select('vendor')
            ->whereNotNull('vendor')
            ->distinct()
            ->orderBy('vendor')
            ->pluck('vendor');
    }

    public function loadOnts()
    {
        $this->resetPage();
        $this->loadFilters();
    }

    public function render()
    {
        $query = ONT::with(['olt', 'port']);

        if ($this->oltId) {
            $query->where('olt_id', $this->oltId);
        }

        if ($this->portId) {
            $query->where('olt_port_id', $this->portId);
        }

        if ($this->statusFilter) {
            $query->where('oper_status', $this->statusFilter);
        }

        if ($this->vendorFilter) {
            $query->where('vendor', $this->vendorFilter);
        }

        if ($this->search) {
            $query->where(function($q) {
                $search = '%' . $this->search . '%';
                $q->where('name', 'like', $search)
                  ->orWhere('ont_id', 'like', $search)
                  ->orWhere('serial_number', 'like', $search)
                  ->orWhere('mac_address', 'like', $search)
                  ->orWhere('vendor', 'like', $search)
                  ->orWhere('model', 'like', $search);
            });
        }

        $onts = $query->orderBy('olt_id')
            ->orderBy('ont_id')
            ->paginate(50);

        // Stats
        $baseQuery = ONT::query();
        if ($this->oltId) $baseQuery->where('olt_id', $this->oltId);

        $this->stats = [
            'total' => $baseQuery->count(),
            'online' => (clone $baseQuery)->where('oper_status', 'online')->count(),
            'offline' => (clone $baseQuery)->where('oper_status', 'offline')->count(),
            'dying_gasp' => (clone $baseQuery)->where('oper_status', 'dying_gasp')->count(),
            'total_olts' => OLT::where('is_active', true)->count(),
            'total_ports' => OLTPort::where('type', 'pon')->count(),
        ];

        return view('livewire.ont-list', [
            'onts' => $onts,
        ]);
    }

    public function detail($id)
    {
        $ont = ONT::with(['olt', 'port'])->find($id);
        if (!$ont) return;

        $alarms = Alarm::where('ont_id', $id)
            ->latest()
            ->take(20)
            ->get()
            ->toArray();

        $this->selectedOnt = array_merge($ont->toArray(), [
            'olt_name' => $ont->olt?->name,
            'port_name' => $ont->port?->name,
            'alarms' => $alarms,
        ]);

        $this->dispatch('open-detail');
    }

    public function closeDetail()
    {
        $this->selectedOnt = null;
    }

    public function reboot($id)
    {
        // Dispatch event untuk di-handle oleh service
        $this->dispatch('reboot-ont', ontId: $id);
        session()->flash('message', 'Perintah reboot telah dikirim');
    }

    public function export()
    {
        $query = ONT::with(['olt', 'port']);

        if ($this->oltId) $query->where('olt_id', $this->oltId);
        if ($this->portId) $query->where('olt_port_id', $this->portId);
        if ($this->statusFilter) $query->where('oper_status', $this->statusFilter);
        if ($this->vendorFilter) $query->where('vendor', $this->vendorFilter);

        $onts = $query->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="daftar-onu-' . date('Ymd-His') . '.csv"',
        ];

        $callback = function() use ($onts) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'OLT', 'IP OLT', 'Port', 'ONT ID', 'Nama Pelanggan',
                'Vendor', 'Model', 'Firmware', 'Serial', 'MAC',
                'Rx Power (dBm)', 'Tx Power (dBm)', 'Status', 'Last Active'
            ]);

            foreach ($onts as $ont) {
                fputcsv($file, [
                    $ont->olt?->name,
                    $ont->olt?->ip_address,
                    $ont->port?->name,
                    $ont->ont_id,
                    $ont->name,
                    $ont->vendor,
                    $ont->model,
                    $ont->firmware_version,
                    $ont->serial_number,
                    $ont->mac_address,
                    $ont->rx_power,
                    $ont->tx_power,
                    $ont->oper_status,
                    $ont->last_active_at,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}