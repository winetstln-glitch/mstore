<?php

namespace App\Http\Controllers;

use App\Models\WashService;
use App\Models\WashStockItem;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class WashController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:wash.manage'),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (WashService::query()->count() === 0) {
            $this->syncDefaultServices();
        }
        $services = WashService::query()
            ->orderBy('vehicle_type')
            ->orderBy('service_category')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('wash.services.index', compact('services'));
    }

    protected function syncDefaultServices(): void
    {
        $catalog = [
            [
                'name' => 'Motor Kecil',
                'vehicle_type' => 'motor',
                'price' => 15000,
                'description' => "Cocok untuk motor harian yang lincah.\nBeat\nScoopy\nMio\nSupra\nDan sejenisnya...",
                'service_category' => 'main',
                'size_tier' => 'kecil',
                'package_type' => 'express',
                'sort_order' => 2113,
            ],
            [
                'name' => 'Motor Sedang',
                'vehicle_type' => 'motor',
                'price' => 18000,
                'description' => "Perawatan ekstra untuk kenyamanan berkendara.\nPCX / NMAX\nStylo\nVespa Matic (Vesmet) 125cc\nDan sejenisnya...",
                'service_category' => 'main',
                'size_tier' => 'sedang',
                'package_type' => 'express',
                'sort_order' => 2123,
            ],
            [
                'name' => 'Motor Besar',
                'vehicle_type' => 'motor',
                'price' => 23000,
                'description' => "Pembersihan detail untuk medan berat dan moge.\nKLX / CRF\nMegapro\nVixion\nDan sejenisnya...",
                'service_category' => 'main',
                'size_tier' => 'besar',
                'package_type' => 'express',
                'sort_order' => 2133,
            ],
            [
                'name' => 'Mobil Kecil',
                'vehicle_type' => 'car',
                'price' => 45000,
                'description' => "Agya / Ayla\nBrio\nDan sejenisnya...",
                'service_category' => 'main',
                'size_tier' => 'kecil',
                'package_type' => 'full_clean',
                'sort_order' => 1122,
            ],
            [
                'name' => 'Mobil Sedang',
                'vehicle_type' => 'car',
                'price' => 50000,
                'description' => "Avanza / Xenia\nSigra / Calya\nPick Up\nDan sejenisnya...",
                'service_category' => 'main',
                'size_tier' => 'sedang',
                'package_type' => 'full_clean',
                'sort_order' => 1132,
            ],
            [
                'name' => 'Mobil Besar',
                'vehicle_type' => 'car',
                'price' => 60000,
                'description' => "Pajero / Fortuner\nAlphard\nTriton / Hilux (Hulk)\nDan sejenisnya...",
                'service_category' => 'main',
                'size_tier' => 'besar',
                'package_type' => 'full_clean',
                'sort_order' => 1142,
            ],
            [
                'name' => 'Mobil Extra Besar',
                'vehicle_type' => 'car',
                'price' => 70000,
                'description' => "Khusus kendaraan angkutan dan logistik.\nTruk Engkel\nColt Diesel\nMobil Angkutan Barang",
                'service_category' => 'main',
                'size_tier' => 'extra_besar',
                'package_type' => 'full_clean',
                'sort_order' => 1152,
            ],
            [
                'name' => 'Es Kopi Susu',
                'vehicle_type' => 'coffee',
                'price' => 12000,
                'description' => "Espresso\nSusu Segar\nGula Aren",
                'service_category' => 'main',
                'size_tier' => 'none',
                'package_type' => 'general',
                'sort_order' => 4106,
            ],
            [
                'name' => 'Americano',
                'vehicle_type' => 'coffee',
                'price' => 10000,
                'description' => "Espresso\nAir Mineral\nTanpa Gula",
                'service_category' => 'main',
                'size_tier' => 'none',
                'package_type' => 'general',
                'sort_order' => 4116,
            ],
            [
                'name' => 'Cappuccino',
                'vehicle_type' => 'coffee',
                'price' => 15000,
                'description' => "Espresso\nSusu\nFoam Lembut",
                'service_category' => 'main',
                'size_tier' => 'none',
                'package_type' => 'general',
                'sort_order' => 4126,
            ],
            [
                'name' => 'Cafe Latte',
                'vehicle_type' => 'coffee',
                'price' => 16000,
                'description' => "Espresso\nSusu Steamed\nFoam Tipis",
                'service_category' => 'main',
                'size_tier' => 'none',
                'package_type' => 'general',
                'sort_order' => 4136,
            ],
            [
                'name' => 'Mocha',
                'vehicle_type' => 'coffee',
                'price' => 17000,
                'description' => "Espresso\nSusu\nCokelat",
                'service_category' => 'main',
                'size_tier' => 'none',
                'package_type' => 'general',
                'sort_order' => 4146,
            ],
            [
                'name' => 'Kopi Tubruk',
                'vehicle_type' => 'coffee',
                'price' => 9000,
                'description' => "Kopi Bubuk Lokal\nAir Panas\nGula Opsional",
                'service_category' => 'main',
                'size_tier' => 'none',
                'package_type' => 'general',
                'sort_order' => 4156,
            ],
        ];

        foreach ($catalog as $service) {
            WashService::firstOrCreate(
                [
                    'name' => $service['name'],
                    'vehicle_type' => $service['vehicle_type'],
                ],
                [
                    'price' => $service['price'],
                    'description' => $service['description'],
                    'service_category' => $service['service_category'] ?? 'main',
                    'size_tier' => $service['size_tier'] ?? 'none',
                    'package_type' => $service['package_type'] ?? 'general',
                    'sort_order' => $service['sort_order'] ?? 0,
                    'is_active' => true,
                ]
            );
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $stockItems = WashStockItem::where('is_active', true)->orderBy('name')->get();
        return view('wash.services.create', compact('stockItems'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'holiday_price' => 'nullable|numeric',
            'vehicle_type' => 'required|in:car,motor,coffee',
            'service_category' => 'required|in:main,addon,skincare',
            'size_tier' => 'required|in:none,kecil,sedang,besar,extra_besar',
            'package_type' => 'required|in:general,body_only,full_clean,express,engine_cleaner,leather_cleaner',
            'sort_order' => 'nullable|integer|min:0|max:999999',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'image' => 'nullable|image|max:2048',
            'wash_stock_item_id' => 'nullable|exists:wash_stock_items,id',
            'rule_size_tier' => 'nullable|array',
            'rule_size_tier.*' => 'nullable|in:none,kecil,sedang,besar,extra_besar',
            'rule_package_type' => 'nullable|array',
            'rule_package_type.*' => 'nullable|in:general,body_only,full_clean,express,engine_cleaner,leather_cleaner',
            'rule_vehicle_type' => 'nullable|array',
            'rule_vehicle_type.*' => 'nullable|in:car,motor,coffee,all',
            'rule_price' => 'nullable|array',
            'rule_price.*' => 'nullable|numeric|min:0',
            'rule_sort_order' => 'nullable|array',
            'rule_sort_order.*' => 'nullable|integer|min:0|max:999999',
            'rule_is_active' => 'nullable|array',
        ]);

        $data = $request->all();
        $data['holiday_price'] = $request->filled('holiday_price') ? $request->holiday_price : null;
        $data['cost_price'] = $request->filled('cost_price') ? $request->cost_price : 0;
        $data['sort_order'] = $request->filled('sort_order') ? (int) $request->sort_order : 0;

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('wash-services', 'public');
        }

        $service = WashService::create($data);
        $this->syncPriceRules($service, $request);

        return redirect()->route('wash.services.index')
            ->with('success', 'Service created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(WashService $service)
    {
        $service->load('priceRules');
        $stockItems = WashStockItem::where('is_active', true)->orderBy('name')->get();

        return view('wash.services.edit', compact('service', 'stockItems'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, WashService $service)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'holiday_price' => 'nullable|numeric',
            'vehicle_type' => 'required|in:car,motor,coffee',
            'service_category' => 'required|in:main,addon,skincare',
            'size_tier' => 'required|in:none,kecil,sedang,besar,extra_besar',
            'package_type' => 'required|in:general,body_only,full_clean,express,engine_cleaner,leather_cleaner',
            'sort_order' => 'nullable|integer|min:0|max:999999',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'image' => 'nullable|image|max:2048',
            'wash_stock_item_id' => 'nullable|exists:wash_stock_items,id',
            'rule_size_tier' => 'nullable|array',
            'rule_size_tier.*' => 'nullable|in:none,kecil,sedang,besar,extra_besar',
            'rule_package_type' => 'nullable|array',
            'rule_package_type.*' => 'nullable|in:general,body_only,full_clean,express,engine_cleaner,leather_cleaner',
            'rule_vehicle_type' => 'nullable|array',
            'rule_vehicle_type.*' => 'nullable|in:car,motor,coffee,all',
            'rule_price' => 'nullable|array',
            'rule_price.*' => 'nullable|numeric|min:0',
            'rule_sort_order' => 'nullable|array',
            'rule_sort_order.*' => 'nullable|integer|min:0|max:999999',
            'rule_is_active' => 'nullable|array',
        ]);

        $data = $request->all();
        $data['holiday_price'] = $request->filled('holiday_price') ? $request->holiday_price : null;
        $data['cost_price'] = $request->filled('cost_price') ? $request->cost_price : 0;
        $data['sort_order'] = $request->filled('sort_order') ? (int) $request->sort_order : 0;

        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($service->image) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($service->image);
            }
            $data['image'] = $request->file('image')->store('wash-services', 'public');
        }

        $service->update($data);
        $this->syncPriceRules($service, $request);

        return redirect()->route('wash.services.index')
            ->with('success', 'Service updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(WashService $service)
    {
        if ($service->image) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($service->image);
        }

        $service->delete();

        return redirect()->route('wash.services.index')
            ->with('success', 'Service deleted successfully.');
    }

    private function syncPriceRules(WashService $service, Request $request): void
    {
        $sizeTiers = $request->input('rule_size_tier', []);
        $packageTypes = $request->input('rule_package_type', []);
        $vehicleTypes = $request->input('rule_vehicle_type', []);
        $prices = $request->input('rule_price', []);
        $sortOrders = $request->input('rule_sort_order', []);
        $activeIndexes = collect($request->input('rule_is_active', []))->map(fn ($idx) => (string) $idx)->all();

        $service->priceRules()->delete();

        foreach ($prices as $index => $price) {
            $price = is_null($price) || $price === '' ? null : (float) $price;
            if (is_null($price)) {
                continue;
            }
            $sizeTier = (string) ($sizeTiers[$index] ?? 'none');
            $packageType = (string) ($packageTypes[$index] ?? 'general');
            $vehicleType = (string) ($vehicleTypes[$index] ?? 'all');
            $sortOrder = (int) ($sortOrders[$index] ?? 0);

            $service->priceRules()->create([
                'vehicle_type' => $vehicleType === 'all' ? null : $vehicleType,
                'size_tier' => $sizeTier,
                'package_type' => $packageType,
                'price' => $price,
                'sort_order' => $sortOrder,
                'is_active' => in_array((string) $index, $activeIndexes, true),
            ]);
        }
    }
}
