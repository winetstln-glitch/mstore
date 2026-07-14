<?php

namespace Modules\Network\Services;

use App\Models\Customer;
use App\Models\Odc;
use App\Models\Odp;
use App\Models\OLT;
use App\Models\Region;
use App\Models\Invoice;
use Carbon\Carbon;

class GisAnalyticsService
{
    public function getCustomerDensity(): array
    {
        // Customer density per ODP
        $odpDensity = Odp::with('customers')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get()
            ->map(function ($odp) {
                $customerCount = $odp->customers()->count();
                return [
                    'type' => 'odp',
                    'id' => $odp->id,
                    'name' => $odp->name,
                    'latitude' => $odp->latitude,
                    'longitude' => $odp->longitude,
                    'customer_count' => $customerCount,
                    'capacity' => $odp->capacity,
                    'utilization' => $odp->capacity > 0 ? min(100, ($customerCount / $odp->capacity) * 100) : 0,
                ];
            });

        // Customer density per Region
        $regionDensity = Region::with(['odps', 'customers'])
            ->get()
            ->map(function ($region) {
                $customerCount = $region->customers()->count();
                $odpCount = $region->odps()->count();
                return [
                    'type' => 'region',
                    'id' => $region->id,
                    'name' => $region->name,
                    'customer_count' => $customerCount,
                    'odp_count' => $odpCount,
                    'customers_per_odp' => $odpCount > 0 ? $customerCount / $odpCount : 0,
                ];
            });

        return [
            'odp_density' => $odpDensity,
            'region_density' => $regionDensity,
        ];
    }

    public function getFiberCoverage(): array
    {
        $totalCustomers = Customer::count();
        $customersWithGps = Customer::whereNotNull('latitude')->whereNotNull('longitude')->count();
        $customersWithOdp = Customer::whereNotNull('odp_id')->count();

        // Coverage by OLT
        $oltCoverage = OLT::with(['odcs' => function ($q) {
            $q->with(['odps' => function ($q2) {
                $q2->withCount('customers');
            }]);
        }])
            ->get()
            ->map(function ($olt) {
                $totalCustomers = 0;
                $totalOdpCapacity = 0;
                $totalOdpUsed = 0;

                foreach ($olt->odcs as $odc) {
                    foreach ($odc->odps as $odp) {
                        $totalCustomers += $odp->customers_count;
                        $totalOdpCapacity += $odp->capacity;
                        $totalOdpUsed += $odp->filled;
                    }
                }

                return [
                    'olt_id' => $olt->id,
                    'olt_name' => $olt->name,
                    'latitude' => $olt->latitude,
                    'longitude' => $olt->longitude,
                    'customer_count' => $totalCustomers,
                    'odp_capacity' => $totalOdpCapacity,
                    'odp_used' => $totalOdpUsed,
                    'utilization' => $totalOdpCapacity > 0 ? min(100, ($totalOdpUsed / $totalOdpCapacity) * 100) : 0,
                ];
            });

        return [
            'coverage_summary' => [
                'total_customers' => $totalCustomers,
                'customers_with_gps' => $customersWithGps,
                'customers_with_odp' => $customersWithOdp,
                'gps_coverage_percent' => $totalCustomers > 0 ? ($customersWithGps / $totalCustomers) * 100 : 0,
            ],
            'olt_coverage' => $oltCoverage,
        ];
    }

    public function getCapacityHeatmap(): array
    {
        // ODP Capacity heatmap points
        $odpHeatmap = Odp::whereNotNull('latitude')->whereNotNull('longitude')
            ->get()
            ->map(function ($odp) {
                $utilization = $odp->capacity > 0 ? min(100, ($odp->filled / $odp->capacity) * 100) : 0;

                $status = 'normal';
                if ($utilization >= 90) {
                    $status = 'critical';
                } elseif ($utilization >= 70) {
                    $status = 'warning';
                }

                return [
                    'id' => $odp->id,
                    'name' => $odp->name,
                    'latitude' => $odp->latitude,
                    'longitude' => $odp->longitude,
                    'capacity' => $odp->capacity,
                    'used' => $odp->filled,
                    'remaining' => max(0, $odp->capacity - $odp->filled),
                    'utilization_percent' => $utilization,
                    'status' => $status,
                ];
            });

        // OLT Port Capacity heatmap
        $oltPortHeatmap = OLT::with(['ports'])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get()
            ->map(function ($olt) {
                $totalCapacity = $olt->ports->sum('max_onts') ?? 0;
                $totalUsed = $olt->ports->sum('registered_onts') ?? 0;
                $utilization = $totalCapacity > 0 ? min(100, ($totalUsed / $totalCapacity) * 100) : 0;

                $status = 'normal';
                if ($utilization >= 90) {
                    $status = 'critical';
                } elseif ($utilization >= 70) {
                    $status = 'warning';
                }

                return [
                    'id' => $olt->id,
                    'name' => $olt->name,
                    'latitude' => $olt->latitude,
                    'longitude' => $olt->longitude,
                    'capacity' => $totalCapacity,
                    'used' => $totalUsed,
                    'remaining' => max(0, $totalCapacity - $totalUsed),
                    'utilization_percent' => $utilization,
                    'status' => $status,
                ];
            });

        return [
            'odp_heatmap' => $odpHeatmap,
            'olt_heatmap' => $oltPortHeatmap,
        ];
    }

    public function getRevenuePerRegion(): array
    {
        $regions = Region::with(['customers.invoices'])->get();

        $regionRevenue = $regions->map(function ($region) {
            $totalRevenue = 0;
            $customers = $region->customers;

            foreach ($customers as $customer) {
                foreach ($customer->invoices as $invoice) {
                    if ($invoice->status === 'paid') {
                        $totalRevenue += $invoice->total;
                    }
                }
            }

            return [
                'region_id' => $region->id,
                'region_name' => $region->name,
                'customer_count' => $customers->count(),
                'total_revenue' => $totalRevenue,
                'avg_revenue_per_customer' => $customers->count() > 0 ? $totalRevenue / $customers->count() : 0,
            ];
        })->sortByDesc('total_revenue')->values();

        $totalAllRegions = $regionRevenue->sum('total_revenue');

        return [
            'region_revenue' => $regionRevenue,
            'total_revenue' => $totalAllRegions,
        ];
    }

    public function getGrowthAnalytics($months = 12): array
    {
        $monthsArray = [];
        $startDate = Carbon::now()->subMonths($months - 1)->startOfMonth();

        for ($i = 0; $i < $months; $i++) {
            $month = $startDate->copy()->addMonths($i);
            $monthKey = $month->format('Y-m');

            // Customer growth
            $customerCount = Customer::where('created_at', '<=', $month->endOfMonth())->count();
            $newCustomers = Customer::whereBetween('created_at', [
                $month->startOfMonth(),
                $month->endOfMonth()
            ])->count();

            // Revenue growth
            $monthRevenue = Invoice::where('status', 'paid')
                ->whereBetween('created_at', [
                    $month->startOfMonth(),
                    $month->endOfMonth()
                ])->sum('total');

            $monthsArray[] = [
                'month' => $monthKey,
                'total_customers' => $customerCount,
                'new_customers' => $newCustomers,
                'revenue' => $monthRevenue,
            ];
        }

        return [
            'growth_history' => $monthsArray,
            'total_customers_now' => end($monthsArray)['total_customers'] ?? 0,
        ];
    }

    public function getGisDashboard(): array
    {
        return [
            'customer_density' => $this->getCustomerDensity(),
            'fiber_coverage' => $this->getFiberCoverage(),
            'capacity_heatmap' => $this->getCapacityHeatmap(),
            'revenue_per_region' => $this->getRevenuePerRegion(),
            'growth_analytics' => $this->getGrowthAnalytics(),
        ];
    }
}
