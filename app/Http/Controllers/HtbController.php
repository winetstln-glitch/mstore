<?php

namespace App\Http\Controllers;

use App\Models\Htb;
use App\Models\Odp;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class HtbController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:htb.view', only: ['index', 'show']),
            new Middleware('permission:htb.create', only: ['create', 'store']),
            new Middleware('permission:htb.edit', only: ['edit', 'update']),
            new Middleware('permission:htb.delete', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $isSuperAdmin = $user->hasAnyRole(config('auth.super_admin_roles', ['admin', 'direktur', 'hrd-manager']));

        $query = Htb::with(['odp', 'parent'])->forUserArea($user);

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('odp', function ($subQ) use ($search) {
                        $subQ->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $htbs = $query->latest()->paginate(10)->withQueryString();

        return view('htbs.index', compact('htbs', 'isSuperAdmin'))
            ->with('scopeRegion', $user?->coordinator?->region)
            ->with('scopeCompany', $user?->company);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $user = $request->user();
        $isSuperAdmin = $user->hasAnyRole(config('auth.super_admin_roles', ['admin', 'direktur', 'hrd-manager']));

        $odps = Odp::query()->forUserArea($user)->orderBy('name')->get();
        $parentHtbs = Htb::with('odp')->forUserArea($user)->get();

        return view('htbs.create', compact('odps', 'parentHtbs', 'isSuperAdmin'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255|unique:htbs',
            'uplink_type' => 'required|in:odp,htb',
            'uplink_id' => 'required|integer',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'capacity' => 'nullable|integer|min:1',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:20',
        ]);

        $odpId = null;
        $parentHtbId = null;

        if ($validated['uplink_type'] === 'odp') {
            $odp = Odp::find($validated['uplink_id']);
            if (! $odp) {
                return back()->withInput()->withErrors(['uplink_id' => __('Selected ODP not found.')]);
            }
            if ($odp->isFull()) {
                return back()->withInput()->withErrors(['uplink_id' => __('Selected ODP is full.')]);
            }
            $odpId = $odp->id;
        } else {
            $parentHtb = Htb::find($validated['uplink_id']);
            if (! $parentHtb) {
                return back()->withInput()->withErrors(['uplink_id' => __('Selected Parent HTB not found.')]);
            }
            if ($parentHtb->isFull()) {
                return back()->withInput()->withErrors(['uplink_id' => __('Selected Parent HTB is full.')]);
            }
            $parentHtbId = $parentHtb->id;
            $odpId = $parentHtb->odp_id;
        }

        $data = [
            'name' => $validated['name'],
            'odp_id' => $odpId,
            'parent_htb_id' => $parentHtbId,
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'capacity' => $validated['capacity'],
            'description' => $validated['description'],
            'color' => $validated['color'],
        ];

        if (empty($data['name'])) {
            $data['name'] = $this->generateHtbName($data);
        }

        $htb = Htb::create($data);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('HTB created successfully.'),
                'data' => $htb,
            ]);
        }

        return redirect()->route('htbs.index')->with('success', __('HTB created successfully.'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Htb $htb)
    {
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $htb,
            ]);
        }

        return view('htbs.show', compact('htb'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Htb $htb)
    {
        $user = $request->user();
        $isSuperAdmin = $user->hasAnyRole(config('auth.super_admin_roles', ['admin', 'direktur', 'hrd-manager']));

        $odps = Odp::query()->forUserArea($user)->orderBy('name')->get();
        $parentHtbs = Htb::with('odp')->forUserArea($user)->where('id', '!=', $htb->id)->get();

        return view('htbs.edit', compact('htb', 'odps', 'parentHtbs', 'isSuperAdmin'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Htb $htb)
    {
        $validated = $request->validate([
            'name' => 'sometimes|nullable|string|max:255|unique:htbs,name,'.$htb->id,
            'uplink_type' => 'required|in:odp,htb',
            'uplink_id' => 'required|integer',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'capacity' => 'nullable|integer|min:1',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:20',
        ]);

        $odpId = null;
        $parentHtbId = null;

        if ($validated['uplink_type'] === 'odp') {
            $odp = Odp::find($validated['uplink_id']);
            if (! $odp) {
                return back()->withInput()->withErrors(['uplink_id' => __('Selected ODP not found.')]);
            }

            // Check capacity only if changing uplink
            if ($htb->odp_id != $odp->id || $htb->parent_htb_id != null) {
                if ($odp->isFull()) {
                    return back()->withInput()->withErrors(['uplink_id' => __('Selected ODP is full.')]);
                }
            }

            $odpId = $odp->id;
        } else {
            // Check for circular dependency: Parent cannot be one of its own children
            // For now, just check if parent is self
            if ($validated['uplink_id'] == $htb->id) {
                return back()->withInput()->withErrors(['uplink_id' => __('Cannot select self as parent.')]);
            }

            $parentHtb = Htb::find($validated['uplink_id']);
            if (! $parentHtb) {
                return back()->withInput()->withErrors(['uplink_id' => __('Selected Parent HTB not found.')]);
            }

            if ($htb->parent_htb_id != $parentHtb->id) {
                if ($parentHtb->isFull()) {
                    return back()->withInput()->withErrors(['uplink_id' => __('Selected Parent HTB is full.')]);
                }
            }

            $parentHtbId = $parentHtb->id;
            $odpId = $parentHtb->odp_id;
        }

        $data = [
            'odp_id' => $odpId,
            'parent_htb_id' => $parentHtbId,
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'capacity' => $validated['capacity'],
            'description' => $validated['description'],
            'color' => $validated['color'],
        ];

        if (array_key_exists('name', $validated)) {
            $data['name'] = $validated['name'];
        }

        if (empty($data['name']) && empty($htb->name)) {
            $data['name'] = $this->generateHtbName(array_merge($htb->toArray(), $data));
        }

        $htb->update($data);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('HTB updated successfully.'),
                'data' => $htb,
            ]);
        }

        return redirect()->route('htbs.index')->with('success', __('HTB updated successfully.'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Htb $htb)
    {
        $htb->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('HTB deleted successfully.'),
            ]);
        }

        return redirect()->route('htbs.index')->with('success', __('HTB deleted successfully.'));
    }

    private function generateHtbName($data)
    {
        $odp = Odp::find($data['odp_id']);
        if (! $odp) {
            return 'HTB-'.time();
        }

        $sequence = $this->calculateSequence($odp->id);

        // Format: HTB-{ODP_NAME}-{SEQ}
        return "HTB-{$odp->name}-{$sequence}";
    }

    private function calculateSequence($odpId)
    {
        $maxSequence = 0;
        $existingHtbs = Htb::where('odp_id', $odpId)->get();
        foreach ($existingHtbs as $htb) {
            if (preg_match('/(\d+)$/', $htb->name, $matches)) {
                $seq = intval($matches[1]);
                if ($seq > $maxSequence) {
                    $maxSequence = $seq;
                }
            }
        }

        return str_pad($maxSequence + 1, 2, '0', STR_PAD_LEFT);
    }
}
