<?php

namespace App\Http\Controllers;

use App\Models\MapConnection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MapConnectionController extends Controller
{
    public function save(Request $request)
    {
        $request->validate([
            'from_type' => 'required|string',
            'from_id' => 'required|integer',
            'to_type' => 'required|string',
            'to_id' => 'required|integer',
            'waypoints' => 'nullable|array',
        ]);

        try {
            $connection = MapConnection::updateOrCreate(
                [
                    'from_type' => $request->from_type,
                    'from_id' => $request->from_id,
                    'to_type' => $request->to_type,
                    'to_id' => $request->to_id,
                ],
                [
                    'waypoints' => $request->waypoints,
                ]
            );

            return response()->json(['success' => true, 'data' => $connection]);
        } catch (\Exception $e) {
            Log::error('Error saving map connection: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to save connection'], 500);
        }
    }

    public function index()
    {
        $connections = MapConnection::all();
        return response()->json($connections);
    }
}
