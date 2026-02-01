<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\AtkProduct;
use App\Models\WashService;
use Illuminate\Http\Request;

class LandingController extends Controller
{
    public function index()
    {
        $packages = Package::where('is_active', true)->get();
        
        // Fetch featured/latest ATK Products (limit 8)
        $atkProducts = AtkProduct::with('category')->latest()->take(8)->get();

        // Fetch Wash Services (Active ones)
        $washServices = WashService::where('is_active', true)->with('category')->get();

        return view('landing.index', compact('packages', 'atkProducts', 'washServices'));
    }
}
