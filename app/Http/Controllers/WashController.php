<?php

namespace App\Http\Controllers;

use App\Models\WashService;
use Illuminate\Http\Request;

class WashController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (WashService::query()->count() === 0) {
            $this->syncDefaultServices();
        }
        $services = WashService::all();

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
            ],
            [
                'name' => 'Motor Sedang',
                'vehicle_type' => 'motor',
                'price' => 18000,
                'description' => "Perawatan ekstra untuk kenyamanan berkendara.\nPCX / NMAX\nStylo\nVespa Matic (Vesmet) 125cc\nDan sejenisnya...",
            ],
            [
                'name' => 'Motor Besar',
                'vehicle_type' => 'motor',
                'price' => 23000,
                'description' => "Pembersihan detail untuk medan berat dan moge.\nKLX / CRF\nMegapro\nVixion\nDan sejenisnya...",
            ],
            [
                'name' => 'Mobil Kecil',
                'vehicle_type' => 'car',
                'price' => 45000,
                'description' => "Agya / Ayla\nBrio\nDan sejenisnya...",
            ],
            [
                'name' => 'Mobil Sedang',
                'vehicle_type' => 'car',
                'price' => 50000,
                'description' => "Avanza / Xenia\nSigra / Calya\nPick Up\nDan sejenisnya...",
            ],
            [
                'name' => 'Mobil Besar',
                'vehicle_type' => 'car',
                'price' => 60000,
                'description' => "Pajero / Fortuner\nAlphard\nTriton / Hilux (Hulk)\nDan sejenisnya...",
            ],
            [
                'name' => 'Mobil Extra Besar',
                'vehicle_type' => 'car',
                'price' => 70000,
                'description' => "Khusus kendaraan angkutan dan logistik.\nTruk Engkel\nColt Diesel\nMobil Angkutan Barang",
            ],
            [
                'name' => 'Es Kopi Susu',
                'vehicle_type' => 'coffee',
                'price' => 12000,
                'description' => "Espresso\nSusu Segar\nGula Aren",
            ],
            [
                'name' => 'Americano',
                'vehicle_type' => 'coffee',
                'price' => 10000,
                'description' => "Espresso\nAir Mineral\nTanpa Gula",
            ],
            [
                'name' => 'Cappuccino',
                'vehicle_type' => 'coffee',
                'price' => 15000,
                'description' => "Espresso\nSusu\nFoam Lembut",
            ],
            [
                'name' => 'Cafe Latte',
                'vehicle_type' => 'coffee',
                'price' => 16000,
                'description' => "Espresso\nSusu Steamed\nFoam Tipis",
            ],
            [
                'name' => 'Mocha',
                'vehicle_type' => 'coffee',
                'price' => 17000,
                'description' => "Espresso\nSusu\nCokelat",
            ],
            [
                'name' => 'Kopi Tubruk',
                'vehicle_type' => 'coffee',
                'price' => 9000,
                'description' => "Kopi Bubuk Lokal\nAir Panas\nGula Opsional",
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
        return view('wash.services.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'holiday_price' => 'nullable|numeric',
            'vehicle_type' => 'required|in:car,motor,coffee',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'image' => 'nullable|image|max:2048',
        ]);

        $data = $request->all();
        $data['holiday_price'] = $request->filled('holiday_price') ? $request->holiday_price : null;

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('wash-services', 'public');
        }

        WashService::create($data);

        return redirect()->route('wash.services.index')
            ->with('success', 'Service created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(WashService $service)
    {
        return view('wash.services.edit', compact('service'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, WashService $service)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'holiday_price' => 'nullable|numeric',
            'vehicle_type' => 'required|in:car,motor,coffee',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'image' => 'nullable|image|max:2048',
        ]);

        $data = $request->all();
        $data['holiday_price'] = $request->filled('holiday_price') ? $request->holiday_price : null;

        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($service->image) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($service->image);
            }
            $data['image'] = $request->file('image')->store('wash-services', 'public');
        }

        $service->update($data);

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
}
