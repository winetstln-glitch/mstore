<?php

namespace App\Http\Controllers;

use App\Models\Coordinator;
use App\Models\Customer;
use App\Models\Installation;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class InstallationWebController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:installation.view', only: ['index', 'show']),
            new Middleware('permission:installation.create', only: ['create', 'store']),
            new Middleware('permission:installation.edit', only: ['edit', 'update']),
            new Middleware('permission:installation.delete', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Installation::query()->with(['customer', 'technician', 'modemRecord.user']);

        if ($request->has('search') && $request->input('search') != '') {
            $search = $request->input('search');
            $query->whereHas('customer', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%")
                    ->orWhere('onu_serial', 'like', "%{$search}%")
                    ->orWhere('wan_mac', 'like', "%{$search}%");
            });
        }

        if ($request->has('status') && $request->input('status') != '') {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('technician_id') && $request->input('technician_id') != '') {
            $query->where('technician_id', $request->input('technician_id'));
        }

        if ($request->has('coordinator_id') && $request->input('coordinator_id') != '') {
            $coordinatorId = (int) $request->input('coordinator_id');
            $query->whereHas('customer.tickets', function ($q) use ($coordinatorId) {
                $q->where('type', 'pasang_baru')->where('coordinator_id', $coordinatorId);
            });
        }

        if ($request->has('date') && $request->input('date') != '') {
            $query->whereDate('plan_date', $request->input('date'));
        }

        $installations = $query->latest()->paginate(10)->withQueryString();
        $technicians = User::where('role_id', 3)->get(); // Assuming role_id 3 is technician
        $coordinators = Coordinator::orderBy('name')->get(['id', 'name']);
        $ticketCoordinatorsByCustomer = $this->ticketCoordinatorsByCustomer($installations->pluck('customer_id')->filter()->unique()->values()->all());

        return view('installations.index', compact('installations', 'technicians', 'coordinators', 'ticketCoordinatorsByCustomer'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $customers = Customer::all();
        $technicians = User::where('role_id', 3)->get();
        $coordinators = Coordinator::orderBy('name')->get(['id', 'name']);
        $selected_customer_id = $request->input('customer_id');

        return view('installations.create', compact('customers', 'technicians', 'coordinators', 'selected_customer_id'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'technician_id' => 'nullable|exists:users,id',
            'coordinator_id' => 'nullable|exists:coordinators,id',
            'plan_date' => 'required|date',
            'status' => 'required|in:registered,survey,approved,installation,completed,cancelled',
            'notes' => 'nullable|string',
            'coordinates' => 'nullable|string',
        ]);

        Installation::create([
            'customer_id' => $validated['customer_id'],
            'technician_id' => $validated['technician_id'] ?? null,
            'plan_date' => $validated['plan_date'],
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
            'coordinates' => $validated['coordinates'] ?? null,
        ]);
        $this->syncOpenInstallationTicketCoordinator((int) $validated['customer_id'], $validated['coordinator_id'] ?? null);

        return redirect()->route('installations.index')->with('success', __('Installation created successfully.'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Installation $installation)
    {
        $installation->load(['customer', 'technician', 'modemRecord.user']);
        $selectedCoordinator = $this->latestInstallationTicketCoordinator((int) $installation->customer_id);

        return view('installations.show', compact('installation', 'selectedCoordinator'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Installation $installation)
    {
        $installation->load(['customer', 'technician', 'modemRecord.user']);
        $customers = Customer::all();
        $technicians = User::where('role_id', 3)->get();
        $coordinators = Coordinator::orderBy('name')->get(['id', 'name']);
        $selectedCoordinator = $this->latestInstallationTicketCoordinator((int) $installation->customer_id);
        $selectedCoordinatorId = $selectedCoordinator?->id;

        return view('installations.edit', compact('installation', 'customers', 'technicians', 'coordinators', 'selectedCoordinator', 'selectedCoordinatorId'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Installation $installation)
    {
        $validated = $request->validate([
            'technician_id' => 'nullable|exists:users,id',
            'coordinator_id' => 'nullable|exists:coordinators,id',
            'plan_date' => 'required|date',
            'status' => 'required|in:registered,survey,approved,installation,completed,cancelled',
            'notes' => 'nullable|string',
            'coordinates' => 'nullable|string',
        ]);

        $installation->update([
            'technician_id' => $validated['technician_id'] ?? null,
            'plan_date' => $validated['plan_date'],
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
            'coordinates' => $validated['coordinates'] ?? null,
        ]);
        $this->syncOpenInstallationTicketCoordinator((int) $installation->customer_id, $validated['coordinator_id'] ?? null);

        return redirect()->route('installations.index')->with('success', __('Installation updated successfully.'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Installation $installation)
    {
        $installation->delete();

        return redirect()->route('installations.index')->with('success', __('Installation deleted successfully.'));
    }

    private function ticketCoordinatorsByCustomer(array $customerIds)
    {
        if ($customerIds === []) {
            return collect();
        }

        return Ticket::with('coordinator')
            ->whereIn('customer_id', $customerIds)
            ->where('type', 'pasang_baru')
            ->orderByDesc('id')
            ->get()
            ->groupBy('customer_id')
            ->map(fn ($rows) => $rows->first()?->coordinator);
    }

    private function latestInstallationTicketCoordinator(int $customerId): ?Coordinator
    {
        return Ticket::with('coordinator')
            ->where('customer_id', $customerId)
            ->where('type', 'pasang_baru')
            ->latest('id')
            ->first()?->coordinator;
    }

    private function syncOpenInstallationTicketCoordinator(int $customerId, ?int $coordinatorId): void
    {
        Ticket::query()
            ->where('customer_id', $customerId)
            ->where('type', 'pasang_baru')
            ->whereIn('status', ['open', 'assigned', 'in_progress', 'pending', 'solved'])
            ->latest('id')
            ->first()
            ?->update(['coordinator_id' => $coordinatorId]);
    }
}
