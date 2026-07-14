<?php

namespace Modules\Network\Services;

use App\Models\Closure;
use App\Models\Htb;
use App\Models\Odc;
use App\Models\Odp;
use App\Models\OLT;
use App\Models\OLTPort;
use Modules\Network\Services\TopologyService;

class CapacityService
{
    public const STATUS_NORMAL = 'normal';
    public const STATUS_WARNING = 'warning';
    public const STATUS_CRITICAL = 'critical';

    public function __construct(
        private TopologyService $topologyService
    ) {}

    /**
     * Get capacity status for all OLTs
     */
    public function getAllOltCapacity(): array
    {
        $olts = OLT::with(['ports'])->get();
        return $olts->map(function (OLT $olt) {
            return $this->getOltCapacity($olt);
        })->values()->toArray();
    }

    /**
     * Get capacity status for an OLT
     */
    public function getOltCapacity(OLT $olt): array
    {
        $olt->load(['ports']);
        $totalCapacity = $olt->ports->sum('max_onts') ?? 0;
        $totalUsed = $olt->ports->sum('registered_onts') ?? 0;
        $totalRemaining = max(0, $totalCapacity - $totalUsed);

        return [
            'olt_id' => $olt->id,
            'olt_name' => $olt->name,
            'total_capacity' => $totalCapacity,
            'total_used' => $totalUsed,
            'total_remaining' => $totalRemaining,
            'utilization_percent' => $this->calculateUtilization($totalUsed, $totalCapacity),
            'status' => $this->getStatus($totalUsed, $totalCapacity),
            'ports' => $olt->ports->map(function (OLTPort $port) {
                return $this->getOltPortCapacity($port);
            })->values()->toArray(),
        ];
    }

    /**
     * Get capacity status for an OLT Port
     */
    public function getOltPortCapacity(OLTPort $port): array
    {
        $capacity = $port->max_onts ?? 0;
        $used = $port->registered_onts ?? 0;
        $remaining = max(0, $capacity - $used);

        return [
            'port_id' => $port->id,
            'port_name' => $port->name,
            'capacity' => $capacity,
            'used' => $used,
            'remaining' => $remaining,
            'utilization_percent' => $this->calculateUtilization($used, $capacity),
            'status' => $this->getStatus($used, $capacity),
        ];
    }

    /**
     * Get capacity status for all ODCs
     */
    public function getAllOdcCapacity(): array
    {
        $odcs = Odc::with(['odps', 'closures'])->get();
        return $odcs->map(function (Odc $odc) {
            return $this->getOdcCapacity($odc);
        })->values()->toArray();
    }

    /**
     * Get capacity status for an ODC
     */
    public function getOdcCapacity(Odc $odc): array
    {
        $odc->load(['odps', 'closures']);
        $odpCapacity = $odc->odps->sum('capacity') ?? 0;
        $odpUsed = $odc->odps->sum('filled') ?? 0;
        $closureCapacity = $odc->closures->sum('capacity') ?? 0;
        $closureUsed = $odc->closures->sum('filled') ?? 0;
        
        $totalCapacity = $odc->capacity ?? $odpCapacity;
        $totalUsed = $odpUsed + $closureUsed;
        $totalRemaining = max(0, $totalCapacity - $totalUsed);

        return [
            'odc_id' => $odc->id,
            'odc_name' => $odc->name,
            'total_capacity' => $totalCapacity,
            'total_used' => $totalUsed,
            'total_remaining' => $totalRemaining,
            'utilization_percent' => $this->calculateUtilization($totalUsed, $totalCapacity),
            'status' => $this->getStatus($totalUsed, $totalCapacity),
            'odps' => $odc->odps->map(function (Odp $odp) {
                return $this->getOdpCapacity($odp);
            })->values()->toArray(),
            'closures' => $odc->closures->map(function (Closure $closure) {
                return $this->getClosureCapacity($closure);
            })->values()->toArray(),
        ];
    }

    /**
     * Get capacity status for an ODP
     */
    public function getOdpCapacity(Odp $odp): array
    {
        $odp->load(['htbs', 'customers']);
        $capacity = $odp->capacity ?? 0;
        $used = $odp->filled ?? 0;
        $remaining = max(0, $capacity - $used);

        return [
            'odp_id' => $odp->id,
            'odp_name' => $odp->name,
            'capacity' => $capacity,
            'used' => $used,
            'remaining' => $remaining,
            'utilization_percent' => $this->calculateUtilization($used, $capacity),
            'status' => $this->getStatus($used, $capacity),
            'htbs' => $odp->htbs->map(function (Htb $htb) {
                return $this->getHtbCapacity($htb);
            })->values()->toArray(),
            'customer_count' => $odp->customers->count(),
        ];
    }

    /**
     * Get capacity status for an HTB
     */
    public function getHtbCapacity(Htb $htb): array
    {
        $htb->load(['customers', 'children']);
        $capacity = $htb->capacity ?? 0;
        $used = $htb->filled ?? 0;
        $remaining = max(0, $capacity - $used);

        return [
            'htb_id' => $htb->id,
            'htb_name' => $htb->name,
            'capacity' => $capacity,
            'used' => $used,
            'remaining' => $remaining,
            'utilization_percent' => $this->calculateUtilization($used, $capacity),
            'status' => $this->getStatus($used, $capacity),
            'customer_count' => $htb->customers->count(),
            'children' => $htb->children->map(function (Htb $child) {
                return $this->getHtbCapacity($child);
            })->values()->toArray(),
        ];
    }

    /**
     * Get capacity status for a Closure
     */
    public function getClosureCapacity(Closure $closure): array
    {
        $capacity = $closure->capacity ?? 0;
        $used = $closure->filled ?? 0;
        $remaining = max(0, $capacity - $used);

        return [
            'closure_id' => $closure->id,
            'closure_name' => $closure->name,
            'capacity' => $capacity,
            'used' => $used,
            'remaining' => $remaining,
            'utilization_percent' => $this->calculateUtilization($used, $capacity),
            'status' => $this->getStatus($used, $capacity),
        ];
    }

    /**
     * Get all nodes with warning status
     */
    public function getWarningNodes(): array
    {
        $nodes = [];

        // OLT Ports
        $olts = OLT::with(['ports'])->get();
        foreach ($olts as $olt) {
            foreach ($olt->ports as $port) {
                if ($this->getStatus($port->registered_onts ?? 0, $port->max_onts ?? 0) === self::STATUS_WARNING) {
                    $nodes[] = array_merge(
                        $this->getOltPortCapacity($port),
                        ['node_type' => 'olt_port', 'olt_id' => $olt->id, 'olt_name' => $olt->name]
                    );
                }
            }
        }

        // ODPs
        $odps = Odp::all();
        foreach ($odps as $odp) {
            if ($this->getStatus($odp->filled ?? 0, $odp->capacity ?? 0) === self::STATUS_WARNING) {
                $nodes[] = array_merge(
                    $this->getOdpCapacity($odp),
                    ['node_type' => 'odp']
                );
            }
        }

        // HTBs
        $htbs = Htb::all();
        foreach ($htbs as $htb) {
            if ($this->getStatus($htb->filled ?? 0, $htb->capacity ?? 0) === self::STATUS_WARNING) {
                $nodes[] = array_merge(
                    $this->getHtbCapacity($htb),
                    ['node_type' => 'htb']
                );
            }
        }

        // Closures
        $closures = Closure::all();
        foreach ($closures as $closure) {
            if ($this->getStatus($closure->filled ?? 0, $closure->capacity ?? 0) === self::STATUS_WARNING) {
                $nodes[] = array_merge(
                    $this->getClosureCapacity($closure),
                    ['node_type' => 'closure']
                );
            }
        }

        return $nodes;
    }

    /**
     * Get all nodes with critical status
     */
    public function getCriticalNodes(): array
    {
        $nodes = [];

        // OLT Ports
        $olts = OLT::with(['ports'])->get();
        foreach ($olts as $olt) {
            foreach ($olt->ports as $port) {
                if ($this->getStatus($port->registered_onts ?? 0, $port->max_onts ?? 0) === self::STATUS_CRITICAL) {
                    $nodes[] = array_merge(
                        $this->getOltPortCapacity($port),
                        ['node_type' => 'olt_port', 'olt_id' => $olt->id, 'olt_name' => $olt->name]
                    );
                }
            }
        }

        // ODPs
        $odps = Odp::all();
        foreach ($odps as $odp) {
            if ($this->getStatus($odp->filled ?? 0, $odp->capacity ?? 0) === self::STATUS_CRITICAL) {
                $nodes[] = array_merge(
                    $this->getOdpCapacity($odp),
                    ['node_type' => 'odp']
                );
            }
        }

        // HTBs
        $htbs = Htb::all();
        foreach ($htbs as $htb) {
            if ($this->getStatus($htb->filled ?? 0, $htb->capacity ?? 0) === self::STATUS_CRITICAL) {
                $nodes[] = array_merge(
                    $this->getHtbCapacity($htb),
                    ['node_type' => 'htb']
                );
            }
        }

        // Closures
        $closures = Closure::all();
        foreach ($closures as $closure) {
            if ($this->getStatus($closure->filled ?? 0, $closure->capacity ?? 0) === self::STATUS_CRITICAL) {
                $nodes[] = array_merge(
                    $this->getClosureCapacity($closure),
                    ['node_type' => 'closure']
                );
            }
        }

        return $nodes;
    }

    /**
     * Get dashboard capacity summary
     */
    public function getCapacityDashboard(): array
    {
        $oltCapacity = $this->getAllOltCapacity();
        $odcCapacity = $this->getAllOdcCapacity();
        $warningNodes = $this->getWarningNodes();
        $criticalNodes = $this->getCriticalNodes();

        return [
            'olts' => $oltCapacity,
            'odcs' => $odcCapacity,
            'warning_nodes' => $warningNodes,
            'warning_count' => count($warningNodes),
            'critical_nodes' => $criticalNodes,
            'critical_count' => count($criticalNodes),
        ];
    }

    /**
     * Calculate utilization percentage
     */
    private function calculateUtilization(int $used, int $capacity): int
    {
        if ($capacity <= 0) {
            return 0;
        }
        return (int) round(($used / $capacity) * 100);
    }

    /**
     * Get status based on used vs capacity
     */
    private function getStatus(int $used, int $capacity): string
    {
        $utilization = $this->calculateUtilization($used, $capacity);
        
        if ($utilization >= 90) {
            return self::STATUS_CRITICAL;
        } elseif ($utilization >= 70) {
            return self::STATUS_WARNING;
        }
        return self::STATUS_NORMAL;
    }
}
