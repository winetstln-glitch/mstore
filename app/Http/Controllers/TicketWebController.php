<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Ticket;
use App\Models\TicketLog;
use App\Models\User;
use App\Models\Odp;
use App\Models\Coordinator;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Notifications\DatabaseNotification;

use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

use App\Notifications\TicketAssignedNotification;

class TicketWebController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:ticket.view', only: ['index', 'show']),
            new Middleware('permission:ticket.create', only: ['create', 'store']),
            new Middleware('permission:ticket.edit', only: ['edit']),
            new Middleware('permission:ticket.delete', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Ticket::with(['customer', 'technicians']);

        if ($request->has('status') && $request->input('status') != '') {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('priority') && $request->input('priority') != '') {
            $query->where('priority', $request->input('priority'));
        }
        
        if ($request->has('search') && $request->input('search') != '') {
             $search = $request->input('search');
             $query->where(function($q) use ($search) {
                 $q->where('ticket_number', 'like', "%{$search}%")
                   ->orWhere('subject', 'like', "%{$search}%");
             });
        }

        if (!Auth::user()->hasRole('admin')) {
            $query->whereHas('technicians', function($q) {
                $q->where('users.id', Auth::id());
            });
        }

        // If technician, show only assigned tickets? Or all? 
        // For MVP let's assume technicians see all or maybe filtered.
        // But dashboard handles "My Tickets". Here is global list.
        
        $tickets = $query->latest()->paginate(10)->withQueryString();

        return view('tickets.index', compact('tickets'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $customers = Customer::where('status', 'active')->orderBy('name')->get();
        $technicians = User::whereHas('role', function($q) {
            $q->where('name', 'technician');
        })->whereExists(function ($q) {
            $q->selectRaw(1)
              ->from('technician_attendances as ta')
              ->whereColumn('ta.user_id', 'users.id')
              ->whereDate('ta.clock_in', today())
              ->where('ta.status', 'present');
        })->whereDoesntHave('tickets', function($q) {
            $q->whereIn('status', ['assigned', 'in_progress', 'pending']);
        })->get();
        $odps = Odp::all();
        $coordinators = Coordinator::with('region')->get();
        return view('tickets.create', compact('customers', 'technicians', 'odps', 'coordinators'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|string',
            'subject' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|in:low,medium,high',
            'technicians' => 'nullable|array',
            'technicians.*' => 'exists:users,id',
            'location' => 'nullable|string',
            'address' => 'nullable|string',
            'odp_id' => 'nullable|exists:odps,id',
            'coordinator_id' => 'nullable|exists:coordinators,id',
            // Conditional validation
            'customer_id' => 'required_if:type,gangguan,maintenance,other|nullable|exists:customers,id',
            'new_customer_name' => 'required_if:type,pasang_baru|nullable|string|max:255',
            'new_customer_address' => 'required_if:type,pasang_baru|nullable|string',
        ]);

        if ($request->filled('technicians')) {
            $allowedIds = User::whereHas('role', function($q) {
                $q->where('name', 'technician');
            })->whereExists(function ($q) {
                $q->selectRaw(1)
                  ->from('technician_attendances as ta')
                  ->whereColumn('ta.user_id', 'users.id')
                  ->whereDate('ta.clock_in', today())
                  ->where('ta.status', 'present');
            })->whereDoesntHave('tickets', function($q) {
                $q->whereIn('status', ['assigned', 'in_progress', 'pending']);
            })->pluck('id')->toArray();

            $invalid = array_diff($request->technicians, $allowedIds);
            if (!empty($invalid) && !Auth::user()->hasRole('admin')) {
                return back()
                    ->withErrors(['technicians' => __('Only available and present technicians can be assigned today.')])
                    ->withInput();
            }
        }

        $customerId = $request->customer_id;

        if ($request->type === 'pasang_baru') {
            // Create new customer
            $customer = Customer::create([
                'name' => $request->new_customer_name,
                'address' => $request->new_customer_address,
                'phone' => $request->new_customer_phone,
                // Assuming latitude/longitude columns exist on customers table as per view usage
                'latitude' => $request->new_customer_lat, 
                'longitude' => $request->new_customer_lng,
                'status' => 'active',
            ]);
            $customerId = $customer->id;
        }

        $ticket = Ticket::create([
            'ticket_number' => Ticket::generateNumber(),
            'customer_id' => $customerId,
            'subject' => $request->subject,
            'description' => $request->description,
            'priority' => $request->priority,
            'status' => $request->has('technicians') && count($request->technicians) > 0 ? 'assigned' : 'open',
            'location' => $request->location ?? ($request->type === 'pasang_baru' && $request->new_customer_lat && $request->new_customer_lng ? "{$request->new_customer_lat}, {$request->new_customer_lng}" : null),
            'address' => $request->address ?? ($request->type === 'pasang_baru' ? $request->new_customer_address : null),
            'odp_id' => $request->odp_id,
            'coordinator_id' => $request->coordinator_id,
            'type' => $request->type,
        ]);

        if ($request->has('technicians')) {
            $ticket->technicians()->sync($request->technicians);
            
            // Notify each assigned technician
            foreach ($ticket->technicians as $technician) {
                $technician->notify(new TicketAssignedNotification($ticket));
            }
        }

        TicketLog::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'action' => 'created',
            'description' => 'Ticket created.',
        ]);

        // Notify Technician Group via Telegram
        try {
            $telegramService = new \App\Services\TelegramService();
            $customerName = $ticket->customer ? $ticket->customer->name : 'N/A';
            // Clean location string for link
            $locationLink = $ticket->location ? "https://maps.google.com/?q=" . urlencode($ticket->location) : '#';
            
            // Get Template from Settings
            $templateSetting = \App\Models\Setting::where('key', 'telegram_ticket_template')->first();
            $template = $templateSetting ? $templateSetting->value : null;

            // Get Assigned Technicians
            $technicianNames = $ticket->technicians->pluck('name')->join(', ');
            if (empty($technicianNames)) {
                $technicianNames = '-';
            }

            // Get Coordinator Name
            $coordinatorName = $ticket->coordinator ? $ticket->coordinator->name : '-';

            if (empty($template)) {
                // Fallback if template is missing
                $template = "🔔 *TIKET BARU (NEW TICKET)*\n\n" .
                           "🆔 *No:* `{ticket_number}`\n" .
                           "📝 *Subject:* `{subject}`\n" .
                           "👤 *Customer:* `{customer_name}`\n" .
                           "👷 *Teknisi:* `{technicians}`\n" .
                           "👔 *Koordinator:* `{coordinator}`\n" .
                           "📍 *Lokasi:* `{location}`\n" .
                           "⚠️ *Prioritas:* `{priority}`\n" .
                           "📄 *Deskripsi:* `{description}`\n\n" .
                           "Silakan cek aplikasi untuk detail lebih lanjut.\n" .
                           "[Lihat Lokasi]({location_link})";
            }

            // Replace Placeholders
            $replacements = [
                '{ticket_number}' => "`{$ticket->ticket_number}`",
                '{subject}' => $ticket->subject,
                '{customer_name}' => $customerName,
                '{technicians}' => $technicianNames,
                '{coordinator}' => $coordinatorName,
                '{location}' => $ticket->location ?? '-',
                '{priority}' => ucfirst($ticket->priority),
                '{description}' => $ticket->description ?? '-',
                '{location_link}' => $locationLink
            ];

            $message = str_replace(array_keys($replacements), array_values($replacements), $template);
            
            $telegramService->sendToTechnicianGroup($message);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to send Telegram notification: " . $e->getMessage());
        }

        return redirect()->route('tickets.index')->with('success', __('Ticket created successfully.'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Ticket $ticket)
    {
        $ticket->load(['customer', 'technicians', 'logs.user', 'odp', 'coordinator.region']);
        $technicians = User::whereHas('role', function($q) {
            $q->where('name', 'technician');
        })->whereExists(function ($q) {
            $q->selectRaw(1)
              ->from('technician_attendances as ta')
              ->whereColumn('ta.user_id', 'users.id')
              ->whereDate('ta.clock_in', today())
              ->where('ta.status', 'present');
        })->get();
        $odps = Odp::all();
        $coordinators = Coordinator::with('region')->get();
        
        return view('tickets.show', compact('ticket', 'technicians', 'odps', 'coordinators'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Ticket $ticket)
    {
        $customers = Customer::all();
        $currentTechIds = $ticket->technicians->pluck('id')->toArray();
        $technicians = User::whereHas('role', function($q) {
            $q->where('name', 'technician');
        })->where(function($query) use ($currentTechIds, $ticket) {
            $query->where(function($q) use ($ticket) {
                $q->whereExists(function ($sub) {
                    $sub->selectRaw(1)
                        ->from('technician_attendances as ta')
                        ->whereColumn('ta.user_id', 'users.id')
                        ->whereDate('ta.clock_in', today())
                        ->where('ta.status', 'present');
                })->whereDoesntHave('tickets', function($sub) use ($ticket) {
                    $sub->whereIn('status', ['assigned', 'in_progress', 'pending'])
                        ->where('tickets.id', '<>', $ticket->id);
                });
            })->orWhereIn('users.id', $currentTechIds);
        })->get();
        $odps = Odp::all();
        $coordinators = Coordinator::with('region')->get();

        return view('tickets.edit', compact('ticket', 'customers', 'technicians', 'odps', 'coordinators'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Ticket $ticket)
    {
        $isAdmin = Auth::user()->hasRole('admin');
        $canEdit = Auth::user()->hasPermission('ticket.edit');
        $canComplete = Auth::user()->hasPermission('ticket.complete');
        $isAssigned = $ticket->technicians()->whereKey(Auth::id())->exists();
        if (!($isAdmin || $canEdit || $canComplete || $isAssigned)) {
            abort(403);
        }
        $validated = $request->validate([
            'technicians' => 'nullable|array',
            'technicians.*' => 'exists:users,id',
            'subject' => 'sometimes|required|string|max:255',
            'priority' => 'sometimes|required|in:low,medium,high',
            'status' => 'sometimes|required|in:open,assigned,in_progress,pending,solved,closed',
            'description' => 'nullable|string',
            'location' => 'nullable|string',
            'odp_id' => 'nullable|exists:odps,id',
            'coordinator_id' => 'nullable|exists:coordinators,id',
        ]);

        $oldStatus = $ticket->status;
        $oldTechnicianIds = $ticket->technicians->pluck('id')->toArray();

        // Update ticket fields (excluding technicians which is pivot)
        $ticket->update(collect($validated)->except('technicians')->toArray());

        // Log status change
        if ($ticket->wasChanged('status')) {
            TicketLog::create([
                'ticket_id' => $ticket->id,
                'user_id' => Auth::id(),
                'action' => 'status_updated',
                'description' => "Status changed from {$oldStatus} to {$ticket->status}",
            ]);

            if ($ticket->status === 'closed' && !$ticket->closed_at) {
                $ticket->update(['closed_at' => now()]);
            }
        }
        
        // Handle Technician Assignment
        if ($canEdit && $request->has('technicians')) {
            if (!empty($request->technicians)) {
                $currentTechIds = $ticket->technicians->pluck('id')->toArray();
                $presentAndFreeIds = User::whereHas('role', function($q) {
                    $q->where('name', 'technician');
                })->whereExists(function ($q) {
                    $q->selectRaw(1)
                      ->from('technician_attendances as ta')
                      ->whereColumn('ta.user_id', 'users.id')
                      ->whereDate('ta.clock_in', today())
                      ->where('ta.status', 'present');
                })->whereDoesntHave('tickets', function($q) use ($ticket) {
                    $q->whereIn('status', ['assigned', 'in_progress', 'pending'])
                      ->where('tickets.id', '<>', $ticket->id);
                })->pluck('id')->toArray();

                $allowedIds = array_unique(array_merge($presentAndFreeIds, $currentTechIds));
                $invalid = array_diff($request->technicians, $allowedIds);
                if (!empty($invalid) && !Auth::user()->hasRole('admin')) {
                    return back()
                        ->withErrors(['technicians' => __('Only available and present technicians can be assigned today.')])
                        ->withInput();
                }
            }

            $newTechnicianIds = $request->technicians ?? [];
            
            // Check if assignment changed
            sort($oldTechnicianIds);
            $sortedNewIds = $newTechnicianIds;
            sort($sortedNewIds);

            if ($oldTechnicianIds !== $sortedNewIds) {
                $ticket->technicians()->sync($newTechnicianIds);
                
                // Determine added technicians to notify
                $addedTechnicianIds = array_diff($newTechnicianIds, $oldTechnicianIds);
                
                if (!empty($addedTechnicianIds)) {
                     $newTechNames = User::whereIn('id', $addedTechnicianIds)->pluck('name')->join(', ');
                     
                     TicketLog::create([
                        'ticket_id' => $ticket->id,
                        'user_id' => Auth::id(),
                        'action' => 'assigned',
                        'description' => "Assigned to: {$newTechNames}",
                    ]);

                    // Notify only new technicians
                    foreach ($addedTechnicianIds as $techId) {
                        $tech = User::find($techId);
                        if ($tech) {
                            $tech->notify(new TicketAssignedNotification($ticket));
                        }
                    }
                }
            }
        }

        return redirect()->route('tickets.show', $ticket)->with('success', __('Ticket updated successfully.'));
    }

    /**
     * Complete the ticket with photo proof.
     */
    public function complete(Request $request, Ticket $ticket)
    {
        $isAdmin = Auth::user()->hasRole('admin');
        $hasPermission = Auth::user()->hasPermission('ticket.complete');
        $isAssigned = $ticket->technicians()->whereKey(Auth::id())->exists();
        if (!($isAdmin || $hasPermission || $isAssigned)) {
            abort(403);
        }
        $request->validate([
            'photo_before' => 'nullable|image|mimes:jpeg,png,jpg|max:10240',
            'photo_proof' => 'required|image|mimes:jpeg,png,jpg|max:10240',
            'description' => 'nullable|string',
        ]);

        if ($request->hasFile('photo_before')) {
            $pathBefore = $request->file('photo_before')->store('ticket-proofs', 'public');
            $ticket->photo_before = $pathBefore;
        }

        if ($request->hasFile('photo_proof')) {
            $pathAfter = $request->file('photo_proof')->store('ticket-proofs', 'public');
            $ticket->photo_proof = $pathAfter;
        }

        $ticket->status = 'solved';
        $ticket->closed_at = now();
        $ticket->save();

        TicketLog::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'action' => 'completed',
            'description' => 'Ticket marked as solved with photos.' . ($request->description ? " Note: {$request->description}" : ''),
        ]);

        DatabaseNotification::where('data->ticket_id', $ticket->id)->delete();

        return redirect()->route('tickets.show', $ticket)->with('success', __('Ticket marked as solved successfully.'));
    }

    /**
     * Update the ticket location.
     */
    public function updateLocation(Request $request, Ticket $ticket)
    {
        $isAdmin = Auth::user()->hasRole('admin');
        $canEdit = Auth::user()->hasPermission('ticket.edit');
        $canComplete = Auth::user()->hasPermission('ticket.complete');
        $isAssigned = $ticket->technicians()->whereKey(Auth::id())->exists();
        if (!($isAdmin || $canEdit || $canComplete || $isAssigned)) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'location' => 'required|string|max:255',
        ]);

        $ticket->location = $request->location;
        $ticket->save();

        // Update Customer Location if ticket has a customer
        if ($ticket->customer) {
            $parts = explode(',', $request->location);
            if (count($parts) >= 2) {
                $lat = trim($parts[0]);
                $lng = trim($parts[1]);
                
                // Basic validation for coordinates
                if (is_numeric($lat) && is_numeric($lng)) {
                    $ticket->customer->update([
                        'latitude' => $lat,
                        'longitude' => $lng,
                    ]);
                }
            }
        }

        TicketLog::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'action' => 'location_updated',
            'description' => 'Ticket location updated to: ' . $request->location,
        ]);

        return redirect()->route('tickets.show', $ticket)->with('success', __('Location updated successfully.'));
    }

    public function updateCustomer(Request $request, Ticket $ticket)
    {
        $isAdmin = Auth::user()->hasRole('admin');
        $canEdit = Auth::user()->hasPermission('ticket.edit');
        $canComplete = Auth::user()->hasPermission('ticket.complete');
        $isAssigned = $ticket->technicians()->whereKey(Auth::id())->exists();
        if (!($isAdmin || $canEdit || $canComplete || $isAssigned)) {
            abort(403);
        }
        if ($ticket->type !== 'pasang_baru' || !$ticket->customer) {
            abort(403);
        }
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'package' => 'nullable|string|max:100',
            'pppoe_user' => 'nullable|string|max:100',
            'pppoe_password' => 'nullable|string|max:100',
            'onu_serial' => 'nullable|string|max:100',
            'device_model' => 'nullable|string|max:100',
            'ssid_name' => 'nullable|string|max:100',
            'ssid_password' => 'nullable|string|max:100',
        ]);
        $ticket->customer->update($validated);
        TicketLog::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'action' => 'customer_updated',
            'description' => 'Customer data updated during installation.',
        ]);
        return redirect()->route('tickets.show', $ticket)->with('success', __('Customer updated successfully.'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Ticket $ticket)
    {
        $ticket->delete();

        return redirect()->route('tickets.index')->with('success', __('Ticket deleted successfully.'));
    }
}
