<?php

namespace App\Http\Controllers;

use App\Models\Odc;
use App\Models\Olt;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

class OdcController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:odc.view', only: ['index', 'show']),
            new Middleware('permission:odc.create', only: ['create', 'store']),
            new Middleware('permission:odc.edit', only: ['edit', 'update']),
            new Middleware('permission:odc.delete', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $odcs = Odc::with('olt')->latest()->paginate(10);
        return view('odcs.index', compact('odcs'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $olts = Olt::all();
        return view('odcs.create', compact('olts'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255|unique:odcs',
            'olt_id' => 'required|exists:olts,id',
            'pon_port' => 'required|string',
            'area' => 'required|string',
            'color' => 'required|string',
            'cable_no' => 'required|string',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'capacity' => 'required|integer|min:0',
            'description' => 'nullable|string',
        ]);

        if (empty($validated['name'])) {
            $validated['name'] = $this->generateOdcName($validated);
        }

        $odc = Odc::create($validated);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $odc]);
        }

        return redirect()->route('odcs.index')->with('success', __('ODC created successfully.'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Odc $odc)
    {
        if (request()->wantsJson()) {
            return response()->json($odc);
        }
        return view('odcs.show', compact('odc'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Odc $odc)
    {
        $olts = Olt::all();
        return view('odcs.edit', compact('odc', 'olts'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Odc $odc)
    {
        $validated = $request->validate([
            'name' => 'sometimes|nullable|string|max:255|unique:odcs,name,' . $odc->id,
            'olt_id' => 'sometimes|required|exists:olts,id',
            'pon_port' => 'sometimes|required|string',
            'area' => 'sometimes|required|string',
            'color' => 'sometimes|required|string',
            'cable_no' => 'sometimes|required|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'capacity' => 'sometimes|required|integer|min:0',
            'description' => 'nullable|string',
        ]);

        if (array_key_exists('name', $validated) && empty($validated['name'])) {
            // Merge with existing data to ensure all fields for name generation are present
            $dataForName = array_merge($odc->toArray(), $validated);
            $validated['name'] = $this->generateOdcName($dataForName);
        }

        $odc->update($validated);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $odc]);
        }

        return redirect()->route('odcs.index')->with('success', __('ODC updated successfully.'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Odc $odc)
    {
        if ($odc->odps()->exists()) {
            return back()->with('error', __('Cannot delete ODC because it has associated ODPs.'));
        }

        $odc->delete();

        return redirect()->route('odcs.index')->with('success', __('ODC deleted successfully.'));
    }

    public function exportExcel()
    {
        $odcs = Odc::with('olt')->latest()->get();

        return response()->streamDownload(function () use ($odcs) {
            $writer = new Writer();
            $writer->openToFile('php://output');

            $writer->addRow(Row::fromValues([
                'Name',
                'OLT',
                'PON Port',
                'Area',
                'Color',
                'Cable No',
                'Latitude',
                'Longitude',
                'Capacity',
                'Description',
            ]));

            foreach ($odcs as $odc) {
                $writer->addRow(Row::fromValues([
                    $odc->name,
                    $odc->olt->name ?? '-',
                    $odc->pon_port,
                    $odc->area,
                    $odc->color,
                    $odc->cable_no,
                    $odc->latitude,
                    $odc->longitude,
                    $odc->capacity,
                    $odc->description,
                ]));
            }

            $writer->close();
        }, 'odcs_' . date('Y-m-d_H-i-s') . '.xlsx');
    }

    private function generateOdcName($data)
    {
        // Format: ODC-[PORT]-[AREA_2]-[COLOR_1]-[CABLE]
        // Example: ODC-01-CI-L-01
        
        // PON Port: PON 01 -> 01
        $ponRaw = preg_replace('/[^0-9]/', '', $data['pon_port']);
        $pon = str_pad($ponRaw, 2, '0', STR_PAD_LEFT);

        // Area: Take First, Middle, and Last characters
        $areaRaw = strtoupper(preg_replace('/\s+/', '', $data['area']));
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

        // Color: Take first 1 character
        $colorRaw = strtoupper(preg_replace('/\s+/', '', $data['color']));
        $color = substr($colorRaw, 0, 1);

        // Cable: 01 -> 01
        $cableRaw = preg_replace('/[^0-9]/', '', $data['cable_no']);
        $cable = str_pad($cableRaw, 2, '0', STR_PAD_LEFT);
        
        return "ODC-{$pon}-{$area}-{$color}-{$cable}";
    }
}
