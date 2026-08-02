<?php

namespace Modules\Network\Services;

use App\Models\Customer;
use App\Models\Htb;
use App\Models\Odc;
use App\Models\Odp;
use App\Models\OLT;
use App\Models\ONT;
use App\Models\OLTPort;
use Illuminate\Support\Facades\Log;

class TopologyService
{
    /**
     * Get full network topology tree for an OLT
     */
    public function getOltTopologyTree(OLT $olt): array
    {
        $olt->load(['ports.onts']);

        $tree = [
            'id' => $olt->id,
            'type' => 'olt',
            'name' => $olt->name,
            'latitude' => $olt->latitude,
            'longitude' => $olt->longitude,
            'status' => $olt->status,
            'is_active' => $olt->is_active,
            'last_polled_at' => $olt->last_polled_at,
            'ports' => $olt->ports->map(function (OLTPort $port) {
                return $this->getOltPortTopology($port);
            })->values()->toArray(),
            'odcs' => $this->getOdcTopologyByOlt($olt->id),
        ];

        return $tree;
    }

    /**
     * Get OLTPort topology
     */
    public function getOltPortTopology(OLTPort $port): array
    {
        $port->load(['onts']);

        return [
            'id' => $port->id,
            'node_type' => 'olt_port',
            'name' => $port->name,
            'port_type' => $port->type,
            'admin_status' => $port->admin_status,
            'oper_status' => $port->oper_status,
            'max_onts' => $port->max_onts,
            'registered_onts' => $port->registered_onts,
            'optical_info' => $port->optical_info,
            'onts' => $port->onts->map(function (ONT $ont) {
                return $this->getOntTopology($ont);
            })->values()->toArray(),
        ];
    }

    /**
     * Get ONT topology
     */
    public function getOntTopology(ONT $ont): array
    {
        $ont->load(['opticalHistory' => fn($q) => $q->latest()->take(20)]);

        // Find customer by ONT serial number
        $customer = Customer::where('onu_serial', $ont->serial_number)->first();

        return [
            'id' => $ont->id,
            'type' => 'ont',
            'name' => $ont->name,
            'serial_number' => $ont->serial_number,
            'mac_address' => $ont->mac_address,
            'vendor' => $ont->vendor,
            'model' => $ont->model,
            'admin_status' => $ont->admin_status,
            'oper_status' => $ont->oper_status,
            'rx_power' => $ont->rx_power,
            'tx_power' => $ont->tx_power,
            'voltage' => $ont->voltage,
            'temperature' => $ont->temperature,
            'distance' => $ont->distance,
            'last_active_at' => $ont->last_active_at,
            'last_polled_at' => $ont->last_polled_at,
            'customer' => $customer ? $this->getCustomerTopology($customer) : null,
            'optical_history' => $ont->opticalHistory->toArray(),
        ];
    }

    /**
     * Get ODC topology by OLT ID
     */
    public function getOdcTopologyByOlt(int $oltId): array
    {
        $odcs = Odc::where('olt_id', $oltId)
            ->with(['odps.htbs', 'odps.customers', 'closures'])
            ->get();

        return $odcs->map(function (Odc $odc) {
            return $this->getOdcTopology($odc);
        })->values()->toArray();
    }

    /**
     * Get ODC topology
     */
    public function getOdcTopology(Odc $odc): array
    {
        $odc->load(['region', 'olt', 'odps.htbs', 'odps.customers', 'closures']);

        return [
            'id' => $odc->id,
            'type' => 'odc',
            'name' => $odc->name,
            'latitude' => $odc->latitude,
            'longitude' => $odc->longitude,
            'pon_port' => $odc->pon_port,
            'capacity' => $odc->capacity,
            'odps' => $odc->odps->map(function (Odp $odp) {
                return $this->getOdpTopology($odp);
            })->values()->toArray(),
            'closures' => $odc->closures->map(function ($closure) {
                return [
                    'id' => $closure->id,
                    'type' => 'closure',
                    'name' => $closure->name,
                    'latitude' => $closure->latitude,
                    'longitude' => $closure->longitude,
                    'capacity' => $closure->capacity,
                    'filled' => $closure->filled,
                ];
            })->values()->toArray(),
        ];
    }

    /**
     * Get ODP topology
     */
    public function getOdpTopology(Odp $odp): array
    {
        $odp->load(['region', 'odc', 'htbs', 'customers']);

        return [
            'id' => $odp->id,
            'type' => 'odp',
            'name' => $odp->name,
            'latitude' => $odp->latitude,
            'longitude' => $odp->longitude,
            'capacity' => $odp->capacity,
            'filled' => $odp->filled,
            'kampung' => $odp->kampung,
            'is_full' => $odp->isFull(),
            'htbs' => $odp->htbs->map(function (Htb $htb) {
                return $this->getHtbTopology($htb);
            })->values()->toArray(),
            'customers' => $odp->customers->map(function (Customer $customer) {
                return $this->getCustomerTopology($customer);
            })->values()->toArray(),
        ];
    }

    /**
     * Get HTB topology
     */
    public function getHtbTopology(Htb $htb): array
    {
        $htb->load(['odp', 'parent', 'children', 'customers']);

        return [
            'id' => $htb->id,
            'type' => 'htb',
            'name' => $htb->name,
            'latitude' => $htb->latitude,
            'longitude' => $htb->longitude,
            'capacity' => $htb->capacity,
            'filled' => $htb->filled,
            'is_full' => $htb->isFull(),
            'parent_htb_id' => $htb->parent_htb_id,
            'children' => $htb->children->map(function (Htb $child) {
                return $this->getHtbTopology($child);
            })->values()->toArray(),
            'customers' => $htb->customers->map(function (Customer $customer) {
                return $this->getCustomerTopology($customer);
            })->values()->toArray(),
        ];
    }

    /**
     * Get Customer topology
     */
    public function getCustomerTopology(Customer $customer): array
    {
        $customer->load(['odp', 'htb', 'invoices', 'tickets']);

        // Find ONT by serial number
        $ont = ONT::where('serial_number', $customer->onu_serial)->first();

        return [
            'id' => $customer->id,
            'type' => 'customer',
            'name' => $customer->name,
            'address' => $customer->address,
            'phone' => $customer->phone,
            'status' => $customer->status,
            'latitude' => $customer->latitude,
            'longitude' => $customer->longitude,
            'ip_address' => $customer->ip_address,
            'package' => $customer->package,
            'onu_serial' => $customer->onu_serial,
            'ssid_name' => $customer->ssid_name,
            'odp_id' => $customer->odp_id,
            'htb_id' => $customer->htb_id,
            'ont' => $ont ? $this->getOntTopology($ont) : null,
        ];
    }

    /**
     * Get customers affected by a node (fault isolation)
     */
    public function getAffectedCustomers(string $nodeType, int $nodeId): array
    {
        $customers = collect();

        switch ($nodeType) {
            case 'olt':
                $olt = OLT::find($nodeId);
                if ($olt) {
                    $odcs = Odc::where('olt_id', $olt->id)->get();
                    $odpIds = Odp::whereIn('odc_id', $odcs->pluck('id'))->pluck('id');
                    $htbIds = Htb::whereIn('odp_id', $odpIds)->pluck('id');
                    $customers = Customer::whereIn('odp_id', $odpIds)->orWhereIn('htb_id', $htbIds)->get();
                }
                break;

            case 'odc':
                $odc = Odc::find($nodeId);
                if ($odc) {
                    $odpIds = Odp::where('odc_id', $odc->id)->pluck('id');
                    $htbIds = Htb::whereIn('odp_id', $odpIds)->pluck('id');
                    $customers = Customer::whereIn('odp_id', $odpIds)->orWhereIn('htb_id', $htbIds)->get();
                }
                break;

            case 'odp':
                $odp = Odp::find($nodeId);
                if ($odp) {
                    $htbIds = Htb::where('odp_id', $odp->id)->pluck('id');
                    $customers = Customer::where('odp_id', $odp->id)->orWhereIn('htb_id', $htbIds)->get();
                }
                break;

            case 'htb':
                $htb = Htb::find($nodeId);
                if ($htb) {
                    $customers = Customer::where('htb_id', $htb->id)->get();
                }
                break;
        }

        return $customers->toArray();
    }

    /**
     * Get orphan customers (without ODP/HTB)
     */
    public function getOrphanCustomers(): array
    {
        return Customer::whereNull('odp_id')->whereNull('htb_id')->get()->toArray();
    }

    /**
     * Get orphan ODPs (without ODC)
     */
    public function getOrphanOdps(): array
    {
        return Odp::whereNull('odc_id')->get()->toArray();
    }

    /**
     * Get orphan HTBs (without ODP/parent HTB)
     */
    public function getOrphanHtbs(): array
    {
        return Htb::whereNull('odp_id')->whereNull('parent_htb_id')->get()->toArray();
    }

    /**
     * Get capacity utilization for a node
     */
    public function getCapacityUtilization(string $nodeType, int $nodeId): array
    {
        $utilization = [
            'used' => 0,
            'capacity' => 0,
            'remaining' => 0,
            'utilization_percent' => 0,
        ];

        switch ($nodeType) {
            case 'olt':
                $olt = OLT::find($nodeId);
                if ($olt) {
                    $ports = OLTPort::where('olt_id', $olt->id)->get();
                    $utilization['capacity'] = $ports->sum('max_onts');
                    $utilization['used'] = $ports->sum('registered_onts');
                    $utilization['remaining'] = $utilization['capacity'] - $utilization['used'];
                }
                break;

            case 'olt_port':
                $port = OLTPort::find($nodeId);
                if ($port) {
                    $utilization['capacity'] = $port->max_onts ?? 0;
                    $utilization['used'] = $port->registered_onts ?? 0;
                    $utilization['remaining'] = $utilization['capacity'] - $utilization['used'];
                }
                break;

            case 'odc':
                $odc = Odc::find($nodeId);
                if ($odc) {
                    $utilization['capacity'] = $odc->capacity ?? 0;
                    $odps = Odp::where('odc_id', $odc->id)->get();
                    $utilization['used'] = $odps->sum('filled');
                    $utilization['remaining'] = $utilization['capacity'] - $utilization['used'];
                }
                break;

            case 'odp':
                $odp = Odp::find($nodeId);
                if ($odp) {
                    $utilization['capacity'] = $odp->capacity ?? 0;
                    $utilization['used'] = $odp->filled ?? 0;
                    $utilization['remaining'] = $utilization['capacity'] - $utilization['used'];
                }
                break;

            case 'htb':
                $htb = Htb::find($nodeId);
                if ($htb) {
                    $utilization['capacity'] = $htb->capacity ?? 0;
                    $utilization['used'] = $htb->filled ?? 0;
                    $utilization['remaining'] = $utilization['capacity'] - $utilization['used'];
                }
                break;
        }

        if ($utilization['capacity'] > 0) {
            $utilization['utilization_percent'] = (int) round(($utilization['used'] / $utilization['capacity']) * 100);
        }

        return $utilization;
    }

    /**
     * Get full topology summary
     */
    public function getTopologySummary(): array
    {
        return [
            'olts' => OLT::count(),
            'olt_ports' => OLTPort::count(),
            'onts' => ONT::count(),
            'odcs' => Odc::count(),
            'odps' => Odp::count(),
            'htbs' => Htb::count(),
            'customers' => Customer::count(),
            'orphan_customers' => count($this->getOrphanCustomers()),
            'orphan_odps' => count($this->getOrphanOdps()),
            'orphan_htbs' => count($this->getOrphanHtbs()),
        ];
    }
}
