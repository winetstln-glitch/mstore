<?php

namespace App\Http\Controllers;

use App\Models\Odp;
use App\Models\Region;
use App\Models\Odc;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

class OdpController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:odp.view', only: ['index', 'show']),
            new Middleware('permission:odp.create', only: ['create', 'store']),
            new Middleware('permission:odp.edit', only: ['edit', 'update']),
            new Middleware('permission:odp.delete', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $odps = Odp::with('odc')->latest()->paginate(10);
        return view('odps.index', compact('odps'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $regions = Region::all();
        $odcs = Odc::all();
        return view('odps.create', compact('regions', 'odcs'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255|unique:odps',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'capacity' => 'nullable|integer|min:1',
            'description' => 'nullable|string',
            'region_id' => 'required|exists:regions,id',
            'odc_id' => 'required|exists:odcs,id',
            'color' => 'required|string|max:20',
            'kampung' => 'required|string|max:255',
            'odp_area' => 'nullable|string|max:10',
            'odp_cable' => 'nullable|string|max:10',
        ]);

        if (empty($validated['name'])) {
            $validated['name'] = $this->generateOdpName($validated);
        }

        // Remove virtual fields before creation
        $data = collect($validated)->except(['odp_area', 'odp_cable'])->toArray();

        $odp = Odp::create($data);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('ODP created successfully.'),
                'data' => $odp
            ]);
        }

        return redirect()->route('odps.index')->with('success', __('ODP created successfully.'));
    }

    public function getNextSequence(Odc $odc)
    {
        $sequence = $this->calculateSequence($odc->id);
        return response()->json(['sequence' => $sequence]);
    }

    private function calculateSequence($odcId)
    {
        $maxSequence = 0;
        $existingOdps = Odp::where('odc_id', $odcId)->get();
        
        foreach ($existingOdps as $existingOdp) {
            // Assume format .../{SEQ}
            $parts = explode('/', $existingOdp->name);
            if (count($parts) > 1) {
                $seq = intval(end($parts));
                if ($seq > $maxSequence) {
                    $maxSequence = $seq;
                }
            }
        }
        
        $count = $maxSequence + 1;
        return str_pad($count, 2, '0', STR_PAD_LEFT);
    }

    private function generateOdpName($data)
    {
        // Area: Take first 2 characters from Input Area (fallback to ODC if not provided, though inputs are preferred now)
        $areaRaw = isset($data['odp_area']) ? $data['odp_area'] : '';
        if (empty($areaRaw)) {
             // Fallback to ODC Area if input is missing (backward compatibility or safety)
             $odc = Odc::find($data['odc_id']);
             $areaRaw = $odc->area;
        }
        $areaRaw = strtoupper(preg_replace('/\s+/', '', $areaRaw));
        
        // Take First, Middle, and Last characters
        $length = strlen($areaRaw);
        if ($length <= 3) {
            $area = $areaRaw;
        } else {
            $first = substr($areaRaw, 0, 1);
            $last = substr($areaRaw, -1);
            $middleIndex = floor($length / 2);
            $middle = substr($areaRaw, $middleIndex, 1);
            $area = $first . $middle . $last;
        }

        // Cable: 2 digits from Input Cable (fallback to ODC)
        $cableRaw = isset($data['odp_cable']) ? $data['odp_cable'] : '';
        if (empty($cableRaw)) {
             if (!isset($odc)) $odc = Odc::find($data['odc_id']);
             $cableRaw = $odc->cable_no;
        }
        $cableRaw = preg_replace('/[^0-9]/', '', $cableRaw);
        $cable = str_pad($cableRaw, 2, '0', STR_PAD_LEFT);

        // Color: Take first 1 character from Input Color
        // Note: $data['color'] is from the request input
        $colorRaw = strtoupper(preg_replace('/\s+/', '', $data['color']));
        $color = substr($colorRaw, 0, 1);

        // Sequence: Find max existing sequence for this ODC to prevent duplicates on delete
        $sequence = $this->calculateSequence($data['odc_id']);

        // Format: ODP-[AREA]-[CABLE]-[COLOR]/[SEQ]
        // Example: ODP-CI-01-L/01
        return "ODP-{$area}-{$cable}-{$color}/{$sequence}";
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Odp $odp)
    {
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $odp
            ]);
        }
        return view('odps.show', compact('odp'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Odp $odp)
    {
        $regions = Region::all();
        $odcs = Odc::all();
        return view('odps.edit', compact('odp', 'regions', 'odcs'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Odp $odp)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255|unique:odps,name,' . $odp->id,
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'capacity' => 'nullable|integer|min:1',
            'description' => 'nullable|string',
            'region_id' => 'sometimes|required|exists:regions,id',
            'odc_id' => 'sometimes|required|exists:odcs,id',
            'color' => 'sometimes|required|string|max:20',
            'kampung' => 'sometimes|required|string|max:255',
        ]);

        $odp->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('ODP updated successfully.'),
                'data' => $odp
            ]);
        }

        return redirect()->route('odps.index')->with('success', __('ODP updated successfully.'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Odp $odp)
    {
        $odp->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('ODP deleted successfully.')
            ]);
        }

        return redirect()->route('odps.index')->with('success', __('ODP deleted successfully.'));
    }

    public function exportExcel()
    {
        return response()->streamDownload(function () {
            if (ob_get_length()) {
                ob_end_clean();
            }

            $writer = new Writer();
            $writer->openToFile('php://output');

            $writer->addRow(Row::fromValues([
                'Name',
                'ODC',
                'Region',
                'Kampung',
                'Color',
                'Latitude',
                'Longitude',
                'Capacity',
                'Description',
            ]));

            Odp::with(['odc', 'region'])->latest()->chunk(200, function ($odps) use ($writer) {
                foreach ($odps as $odp) {
                    $writer->addRow(Row::fromValues([
                        $odp->name,
                        $odp->odc?->name ?? '-',
                        $odp->region?->name ?? '-',
                        $odp->kampung,
                        $odp->color,
                        $odp->latitude,
                        $odp->longitude,
                        $odp->capacity,
                        $odp->description,
                    ]));
                }
            });

            $writer->close();
        }, 'odps_' . date('Y-m-d_H-i-s') . '.xlsx');
    }
}
