<?php

namespace App\Http\Controllers;

use App\Models\AtkProduct;
use App\Models\Odp;
use App\Models\Package;
use App\Models\Setting;
use App\Models\TechnicianAttendance;
use App\Models\VoucherTemplate;
use App\Models\WashService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class LandingController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $canAttendanceFromLanding = $user &&
            $user->hasPermission('attendance.create');
        $todayAttendance = null;

        if ($canAttendanceFromLanding) {
            $todayAttendance = TechnicianAttendance::where('user_id', $user->id)
                ->whereDate('clock_in', today())
                ->first();
        }

        // Safely fetch Packages
        try {
            $packages = Package::where('is_active', true)->orderBy('price')->get();
        } catch (\Exception $e) {
            $packages = collect([]);
        }

        // Safely fetch ATK Products
        try {
            if (class_exists(AtkProduct::class) && Schema::hasTable('atk_products')) {
                $atkProducts = AtkProduct::where('stock', '>', 0)->latest()->take(4)->get();
            } else {
                $atkProducts = collect([]);
            }
        } catch (\Exception $e) {
            $atkProducts = collect([]);
        }

        // Safely fetch Wash Services
        $washMainServices = collect([]);
        $washAddonServices = collect([]);
        try {
            if (class_exists(WashService::class) && Schema::hasTable('wash_services')) {
                $baseWashQuery = WashService::query()
                    ->with(['priceRules' => function ($query) {
                        $query->where('is_active', true)->orderBy('sort_order')->orderBy('id');
                    }]);
                if (Schema::hasColumn('wash_services', 'is_active')) {
                    $baseWashQuery->where('is_active', true);
                }
                $washServices = (clone $baseWashQuery)
                    ->orderBy('vehicle_type')
                    ->orderBy('service_category')
                    ->orderBy('size_tier')
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->get();
                $washMainServices = $washServices->filter(function ($service) {
                    return in_array((string) ($service->service_category ?? 'main'), ['main', ''], true);
                })->values();
                $washAddonServices = $washServices->filter(function ($service) {
                    return in_array((string) ($service->service_category ?? ''), ['addon', 'skincare'], true);
                })->values();
            } else {
                $washServices = collect([]);
                $washMainServices = collect([]);
                $washAddonServices = collect([]);
            }
        } catch (\Exception $e) {
            $washServices = collect([]);
            $washMainServices = collect([]);
            $washAddonServices = collect([]);
        }

        // Get Store Identity from settings or default
        try {
            $waNumber = Setting::getValue('whatsapp_number', '6281234567890');
            $storeName = Setting::getValue('store_name', config('app.name', 'MStore'));
            $storeEmail = Setting::getValue('store_email', 'support@mstore.id');
            $storePhone = Setting::getValue('store_phone', '081234567890');
            $storeAddress = Setting::getValue('store_address', 'Jl. Raya Perjuangan No. 12a, Kebon Jeruk, Jakarta Barat');
        } catch (\Exception $e) {
            $waNumber = '6281234567890';
            $storeName = config('app.name', 'MStore');
            $storeEmail = 'support@mstore.id';
            $storePhone = '081234567890';
            $storeAddress = 'Jl. Raya Perjuangan No. 12a, Kebon Jeruk, Jakarta Barat';
        }

        // Safely fetch ODPs for Map
        try {
            if (class_exists(Odp::class) && Schema::hasTable('odps')) {
                $odps = Odp::select('name', 'latitude', 'longitude', 'capacity', 'filled')
                    ->whereNotNull('latitude')
                    ->whereNotNull('longitude')
                    ->get();
            } else {
                $odps = collect([]);
            }
        } catch (\Exception $e) {
            $odps = collect([]);
        }

        // Safely fetch Voucher Profiles
        try {
            if (class_exists(VoucherTemplate::class) && Schema::hasTable('voucher_templates')) {
                $voucherTemplates = VoucherTemplate::query()
                    ->where('is_active', true)
                    ->orderBy('price')
                    ->orderBy('name')
                    ->get();
            } else {
                $voucherTemplates = collect([]);
            }
        } catch (\Exception $e) {
            $voucherTemplates = collect([]);
        }

        try {
            $clockInStart = Setting::getValue('attendance_clock_in_start', '07:00');
            $clockInEnd = Setting::getValue('attendance_clock_in_end', '13:00');
            $clockOutStart = Setting::getValue('attendance_clock_out_start', '20:00');
            $clockOutEnd = Setting::getValue('attendance_clock_out_end', '01:00');
        } catch (\Exception $e) {
            $clockInStart = '07:00';
            $clockInEnd = '13:00';
            $clockOutStart = '20:00';
            $clockOutEnd = '01:00';
        }

        return view('landing.index', compact('packages', 'atkProducts', 'washServices', 'washMainServices', 'washAddonServices', 'waNumber', 'odps', 'voucherTemplates', 'canAttendanceFromLanding', 'todayAttendance', 'clockInStart', 'clockInEnd', 'clockOutStart', 'clockOutEnd', 'storeName', 'storeEmail', 'storePhone', 'storeAddress'));
    }
}
