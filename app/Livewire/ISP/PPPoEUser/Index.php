<?php

namespace App\Livewire\ISP\PPPoEUser;

use App\Models\Customer;
use App\Models\Router;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public $search = '';
    public $status = '';
    public $routerId = '';
    public $selectedIds = [];
    public $selectAll = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => ''],
        'routerId' => ['except' => ''],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedIds = $this->customers->pluck('id')->map(fn ($id) => (string)$id)->toArray();
        } else {
            $this->selectedIds = [];
        }
    }

    public function suspend($id)
    {
        $customer = Customer::findOrFail($id);
        $customer->update(['status' => 'suspend']);
        session()->flash('success', 'Pelanggan berhasil disuspend!');
    }

    public function activate($id)
    {
        $customer = Customer::findOrFail($id);
        $customer->update(['status' => 'active']);
        session()->flash('success', 'Pelanggan berhasil diaktifkan!');
    }

    public function bulkSuspend()
    {
        Customer::whereIn('id', $this->selectedIds)->update(['status' => 'suspend']);
        $this->selectedIds = [];
        $this->selectAll = false;
        session()->flash('success', 'Pelanggan terpilih berhasil disuspend!');
    }

    public function bulkActivate()
    {
        Customer::whereIn('id', $this->selectedIds)->update(['status' => 'active']);
        $this->selectedIds = [];
        $this->selectAll = false;
        session()->flash('success', 'Pelanggan terpilih berhasil diaktifkan!');
    }

    public function bulkDelete()
    {
        Customer::whereIn('id', $this->selectedIds)->delete();
        $this->selectedIds = [];
        $this->selectAll = false;
        session()->flash('success', 'Pelanggan terpilih berhasil dihapus!');
    }

    public function getCustomersProperty()
    {
        return Customer::with(['router', 'package'])
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('pppoe_user', 'like', '%' . $this->search . '%')
                      ->orWhere('phone', 'like', '%' . $this->search . '%');
            })
            ->when($this->status, function ($query) {
                $query->where('status', $this->status);
            })
            ->when($this->routerId, function ($query) {
                $query->where('router_id', $this->routerId);
            })
            ->latest()
            ->paginate(10);
    }

    public function render()
    {
        return view('livewire.isp.pppoe-user.index', [
            'customers' => $this->customers,
            'routers' => Router::where('is_active', true)->get(),
        ]);
    }
}
