<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\AtkProduct;
use App\Models\WashService;
use App\Models\Setting;
use App\Models\Odp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class LandingController extends Controller
{
    public function index()
    {
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
        try {
            if (class_exists(WashService::class) && Schema::hasTable('wash_services')) {
                // Check if is_active column exists
                if (Schema::hasColumn('wash_services', 'is_active')) {
                    $washServices = WashService::where('is_active', true)->get();
                } else {
                    $washServices = WashService::all();
                }
            } else {
                $washServices = collect([]);
            }
        } catch (\Exception $e) {
            $washServices = collect([]);
        }
            
        // Get WA Number from settings or default
        try {
            $waNumber = Setting::getValue('whatsapp_number', '6281234567890');
        } catch (\Exception $e) {
            $waNumber = '6281234567890';
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

        return view('landing.index', compact('packages', 'atkProducts', 'washServices', 'waNumber', 'odps'));
    }
}
