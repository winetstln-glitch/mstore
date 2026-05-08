<?php

namespace App\Http\Controllers;

use App\Traits\SendsNotifications;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Coordinator;
use App\Models\Customer;
use App\Models\Odp;
use App\Models\Ticket;
use App\Models\TicketLog;
use App\Models\User;
use App\Notifications\TicketAssignedNotification;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class TicketWebController extends Controller implements HasMiddleware
{
    use SendsNotifications;

    protected function canManageAllTickets(): bool
    {
        $user = Auth::user();

        return $user && ($user->hasRole('admin') || $user->hasRole('leader'));
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:ticket.view', only: ['index', 'show', 'sopPdf']),
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
        $query = Ticket::query()
            ->with([
                'customer:id,name,address',
                'technicians:id,name',
            ]);

        if ($request->has('status') && $request->input('status') != '') {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('priority') && $request->input('priority') != '') {
            $query->where('priority', $request->input('priority'));
        }

        if ($request->has('search') && $request->input('search') != '') {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        if (! $this->canManageAllTickets()) {
            $query->whereHas('technicians', function ($q) {
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
        $technicians = $this->availableTechnicians();
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
            'estimated_duration_minutes' => 'nullable|integer|min:15|max:1440',
            'technicians' => 'nullable|array',
            'technicians.*' => 'exists:users,id',
            'location' => 'nullable|string',
            'address' => 'nullable|string',
            'odp_id' => 'nullable|exists:odps,id',
            'coordinator_id' => 'nullable|exists:coordinators,id',
            // Conditional validation
            'customer_id' => 'required_if:type,gangguan,other|nullable|exists:customers,id',
            'new_customer_name' => 'required_if:type,pasang_baru|nullable|string|max:255',
            'new_customer_modem_type' => 'nullable|string|max:100',
            'new_customer_onu_serial' => 'nullable|string|max:100',
            'new_customer_wan_mac' => ['nullable', 'string', 'max:20', 'regex:/^([0-9A-Fa-f]{2}[:-]){5}[0-9A-Fa-f]{2}$/'],
            'new_customer_address' => 'required_if:type,pasang_baru|nullable|string',
        ]);

        if ($request->filled('technicians')) {
            $allowedIds = $this->availableTechnicianIds();

            $invalid = array_diff($request->technicians, $allowedIds);
            if (! empty($invalid) && ! $this->canManageAllTickets()) {
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
                'device_model' => $request->new_customer_modem_type,
                'onu_serial' => $request->new_customer_onu_serial,
                'wan_mac' => $request->new_customer_wan_mac ? strtoupper(trim($request->new_customer_wan_mac)) : null,
                // Assuming latitude/longitude columns exist on customers table as per view usage
                'latitude' => $request->new_customer_lat,
                'longitude' => $request->new_customer_lng,
                'status' => 'active',
            ]);
            $customerId = $customer->id;
        }

        $ticketData = [
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
        ];
        if (Schema::hasColumn('tickets', 'estimated_duration_minutes')) {
            $ticketData['estimated_duration_minutes'] = $request->filled('estimated_duration_minutes')
                ? (int) $request->estimated_duration_minutes
                : null;
        }
        $ticket = Ticket::create($ticketData);

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
        app(\App\Services\TelegramService::class)->sendTicketNotification($ticket, 'created');

        // Notify Group via WhatsApp (Fonnte) & Telegram
        $customerName = $ticket->customer?->name ?? $request->new_customer_name ?? '-';
        $priorityLabel = match($ticket->priority) {
            'high' => '🔴 TINGGI',
            'medium' => '🟡 SEDANG',
            'low' => '🟢 RENDAH',
            default => strtoupper($ticket->priority)
        };
        $typeLabel = strtoupper(str_replace('_', ' ', $ticket->type));
        
        $waMessage = "🎫 *TIKET BARU: {$ticket->ticket_number}*\n\n" .
                     "📌 *Tipe:* {$typeLabel}\n" .
                     "👤 *Pelanggan:* {$customerName}\n" .
                     "📝 *Subjek:* {$ticket->subject}\n" .
                     "⚡ *Prioritas:* {$priorityLabel}\n" .
                     "📍 *Alamat:* " . ($ticket->address ?? '-') . "\n\n" .
                     "🔗 *Detail:* " . route('tickets.show', $ticket) . "\n\n" .
                     "🚀 _Sistem M-Store_";
        
        $this->sendGroupNotification($waMessage, 'ticket', ['whatsapp']);

        return redirect()->route('tickets.index')->with('success', __('Ticket created successfully.'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Ticket $ticket)
    {
        $ticket->load(['customer', 'technicians', 'logs.user', 'odp', 'coordinator.region']);
        $technicians = User::query()
            ->whereIn('id', $this->presentTechnicianIds())
            ->orderBy('name')
            ->get();
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
        $availableIds = $this->availableTechnicianIds($ticket->id);
        $editableTechIds = array_values(array_unique(array_merge($availableIds, $currentTechIds)));
        $technicians = User::query()
            ->whereIn('id', $editableTechIds)
            ->orderBy('name')
            ->get();
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
        if (! ($isAdmin || $canEdit || $canComplete || $isAssigned)) {
            abort(403);
        }
        $validated = $request->validate([
            'technicians' => 'nullable|array',
            'technicians.*' => 'exists:users,id',
            'type' => 'sometimes|required|string|max:100',
            'subject' => 'sometimes|required|string|max:255',
            'priority' => 'sometimes|required|in:low,medium,high',
            'estimated_duration_minutes' => 'nullable|integer|min:15|max:1440',
            'status' => 'sometimes|required|in:open,assigned,in_progress,pending,solved,closed',
            'description' => 'nullable|string',
            'location' => 'nullable|string',
            'odp_id' => 'nullable|exists:odps,id',
            'coordinator_id' => 'nullable|exists:coordinators,id',
        ]);

        $oldStatus = $ticket->status;
        $oldTechnicianIds = $ticket->technicians->pluck('id')->toArray();

        // Update ticket fields (excluding technicians which is pivot)
        $ticketUpdateData = collect($validated)->except('technicians')->toArray();
        if (! Schema::hasColumn('tickets', 'estimated_duration_minutes')) {
            unset($ticketUpdateData['estimated_duration_minutes']);
        }
        $ticket->update($ticketUpdateData);

        // Log status change
        if ($ticket->wasChanged('status')) {
            TicketLog::create([
                'ticket_id' => $ticket->id,
                'user_id' => Auth::id(),
                'action' => 'status_updated',
                'description' => "Status changed from {$oldStatus} to {$ticket->status}",
            ]);

            if ($ticket->status === 'closed' && ! $ticket->closed_at) {
                $ticket->update(['closed_at' => now()]);
            }

            // Notify Technician Group via Telegram if solved or closed
            if (in_array($ticket->status, ['solved', 'closed'])) {
                app(\App\Services\TelegramService::class)->sendTicketNotification($ticket, 'solved', "Status changed to " . ucfirst($ticket->status));
            }

            // Notify Group via WhatsApp & Telegram
            $statusLabel = match($ticket->status) {
                'open' => 'BUKA 🔓',
                'assigned' => 'DITUGASKAN 👤',
                'in_progress' => 'PROSES 🛠️',
                'pending' => 'PENDING ⏳',
                'solved' => 'SELESAI ✅',
                'closed' => 'DITUTUP 🔒',
                default => strtoupper($ticket->status)
            };
            
            $waMessage = "🎫 *UPDATE STATUS TIKET: {$ticket->ticket_number}*\n\n" .
                         "📝 *Subjek:* {$ticket->subject}\n" .
                         "📊 *Status:* {$statusLabel}\n" .
                         "👤 *Oleh:* " . Auth::user()->name . "\n" .
                         "🔗 *Detail:* " . route('tickets.show', $ticket) . "\n\n" .
                         "🚀 _Sistem M-Store_";
            
            // If we already sent a specialized Telegram notification (for solved/closed), only send to WhatsApp here
            if (in_array($ticket->status, ['solved', 'closed'])) {
                $this->sendGroupNotification($waMessage, 'ticket', ['whatsapp']);
            } else {
                $this->sendGroupNotification($waMessage, 'ticket');
            }
        }

        // Handle Technician Assignment
        if ($canEdit && $request->has('technicians')) {
            if (! empty($request->technicians)) {
                $currentTechIds = $ticket->technicians->pluck('id')->toArray();
                $presentAndFreeIds = $this->availableTechnicianIds($ticket->id);

                $allowedIds = array_unique(array_merge($presentAndFreeIds, $currentTechIds));
                $invalid = array_diff($request->technicians, $allowedIds);
                if (! empty($invalid) && ! $this->canManageAllTickets()) {
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

                if (! empty($addedTechnicianIds)) {
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

                    // Notify Group via WhatsApp & Telegram
                    $waMessage = "🎫 *PENUGASAN TIKET: {$ticket->ticket_number}*\n\n" .
                                 "📝 *Subjek:* {$ticket->subject}\n" .
                                 "👷 *Teknisi:* {$newTechNames}\n" .
                                 "👤 *Oleh:* " . Auth::user()->name . "\n" .
                                 "🔗 *Detail:* " . route('tickets.show', $ticket) . "\n\n" .
                                 "🚀 _Sistem M-Store_";
                    
                    // If we already sent a status update notification in this request, only send to WhatsApp here
                    // to avoid duplicate Telegram messages in the same group.
                    if ($ticket->wasChanged('status')) {
                        $this->sendGroupNotification($waMessage, 'ticket', ['whatsapp']);
                    } else {
                        $this->sendGroupNotification($waMessage, 'ticket');
                    }
                }
            }
        }

        return redirect()->route('tickets.show', $ticket)->with('success', __('Ticket updated successfully.'));
    }

    /**
     * Technician IDs that clocked in today and are marked present/late.
     *
     * @return array<int, int>
     */
    protected function presentTechnicianIds(): array
    {
        return User::query()
            ->whereHas('role', static function ($q) {
                $q->where('name', 'technician');
            })
            ->whereIn('id', function ($q) {
                $q->select('ta.user_id')
                    ->from('technician_attendances as ta')
                    ->whereDate('ta.clock_in', today())
                    ->whereIn('ta.status', ['present', 'late'])
                    ->distinct();
            })
            ->pluck('id')
            ->all();
    }

    /**
     * Available technician IDs (present today and without active assignment).
     *
     * @return array<int, int>
     */
    protected function availableTechnicianIds(?int $excludeTicketId = null): array
    {
        $presentIds = $this->presentTechnicianIds();
        if (empty($presentIds)) {
            return [];
        }

        $busyQuery = DB::table('ticket_user as tt')
            ->join('tickets as t', 't.id', '=', 'tt.ticket_id')
            ->whereIn('tt.user_id', $presentIds)
            ->whereIn('t.status', ['assigned', 'in_progress', 'pending']);

        if ($excludeTicketId) {
            $busyQuery->where('t.id', '<>', $excludeTicketId);
        }

        $busyIds = $busyQuery
            ->distinct()
            ->pluck('tt.user_id')
            ->all();

        return array_values(array_diff($presentIds, $busyIds));
    }

    protected function availableTechnicians(?int $excludeTicketId = null)
    {
        $availableIds = $this->availableTechnicianIds($excludeTicketId);
        if (empty($availableIds)) {
            return collect();
        }

        return User::query()
            ->whereIn('id', $availableIds)
            ->orderBy('name')
            ->get();
    }

    /**
     * Complete the ticket with photo proof.
     */
    public function complete(Request $request, Ticket $ticket)
    {
        $isAdmin = Auth::user()->hasRole('admin');
        $hasPermission = Auth::user()->hasPermission('ticket.complete');
        $isAssigned = $ticket->technicians()->whereKey(Auth::id())->exists();
        if (! ($isAdmin || $hasPermission || $isAssigned)) {
            abort(403);
        }
        $validated = $request->validate([
            'photo_before' => 'nullable|image|mimes:jpeg,png,jpg|max:10240',
            'photo_proof' => 'required|image|mimes:jpeg,png,jpg|max:10240',
            'description' => 'nullable|string',
            'completion_onu_serial' => 'nullable|string|max:100',
            'completion_wan_mac' => ['nullable', 'string', 'max:20', 'regex:/^([0-9A-Fa-f]{2}[:-]){5}[0-9A-Fa-f]{2}$/'],
        ]);

        $typeNormalized = strtolower((string) $ticket->type);
        $isOnuProvisioningTask = $typeNormalized === 'pasang_baru'
            || str_contains($typeNormalized, 'pergantian')
            || str_contains($typeNormalized, 'pergati')
            || str_contains($typeNormalized, 'ganti_onu')
            || str_contains($typeNormalized, 'penggantian_onu');

        if ($ticket->customer && $isOnuProvisioningTask) {
            $finalOnuSerial = trim((string) ($validated['completion_onu_serial'] ?? $ticket->customer->onu_serial ?? ''));
            $finalWanMac = trim((string) ($validated['completion_wan_mac'] ?? $ticket->customer->wan_mac ?? ''));

            if ($finalOnuSerial === '' || $finalWanMac === '') {
                $errorBag = [];
                if ($finalOnuSerial === '') {
                    $errorBag['completion_onu_serial'] = __('SN ONU wajib diisi untuk tiket instalasi baru/pergantian.');
                }
                if ($finalWanMac === '') {
                    $errorBag['completion_wan_mac'] = __('WAN MAC wajib diisi untuk tiket instalasi baru/pergantian.');
                }

                return back()->withErrors($errorBag)->withInput();
            }
        }

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

        $updatedOnuSerial = null;
        $updatedWanMac = null;
        if ($ticket->customer && $isOnuProvisioningTask) {
            $customerUpdates = [];

            $completionOnuSerial = trim((string) $request->input('completion_onu_serial', ''));
            if ($completionOnuSerial !== '') {
                $customerUpdates['onu_serial'] = $completionOnuSerial;
                $updatedOnuSerial = $completionOnuSerial;
            }

            $completionWanMac = trim((string) $request->input('completion_wan_mac', ''));
            if ($completionWanMac !== '') {
                $normalizedWanMac = strtoupper(str_replace('-', ':', $completionWanMac));
                $customerUpdates['wan_mac'] = $normalizedWanMac;
                $updatedWanMac = $normalizedWanMac;
            }

            if (! empty($customerUpdates)) {
                $ticket->customer->update($customerUpdates);
            }
        }

        $completionNote = $request->description ? " Note: {$request->description}" : '';
        $deviceNoteParts = [];
        if ($updatedOnuSerial) {
            $deviceNoteParts[] = "ONU SN: {$updatedOnuSerial}";
        }
        if ($updatedWanMac) {
            $deviceNoteParts[] = "WAN MAC: {$updatedWanMac}";
        }
        if (! empty($deviceNoteParts)) {
            $completionNote .= ' Device: '.implode(', ', $deviceNoteParts);
        }

        TicketLog::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'action' => 'completed',
            'description' => 'Ticket marked as solved with photos.'.$completionNote,
        ]);

        // Notify Technician Group via Telegram for Solved Ticket
            app(\App\Services\TelegramService::class)->sendTicketNotification($ticket, 'solved', $request->description);

            // Notify Group via WhatsApp (Fonnte) for Solved Ticket
            try {
                $waMessage = "✅ *TIKET SELESAI: {$ticket->ticket_number}*\n\n" .
                             "👤 *Pelanggan:* " . ($ticket->customer?->name ?? '-') . "\n" .
                             "📝 *Subjek:* {$ticket->subject}\n" .
                             "🛠️ *Oleh:* " . Auth::user()->name . "\n" .
                             "🗒️ *Hasil:* " . ($request->description ?? 'Telah diperbaiki') . "\n\n" .
                             "🚀 _Sistem M-Store_";
                
                app(\App\Services\WhatsAppService::class)->sendGroupNotification($waMessage);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Ticket Solved WA Notification Error: ' . $e->getMessage());
            }

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
        if (! ($isAdmin || $canEdit || $canComplete || $isAssigned)) {
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
            'description' => 'Ticket location updated to: '.$request->location,
        ]);

        return redirect()->route('tickets.show', $ticket)->with('success', __('Location updated successfully.'));
    }

    public function updateCustomer(Request $request, Ticket $ticket)
    {
        $isAdmin = Auth::user()->hasRole('admin');
        $canEdit = Auth::user()->hasPermission('ticket.edit');
        $canComplete = Auth::user()->hasPermission('ticket.complete');
        $isAssigned = $ticket->technicians()->whereKey(Auth::id())->exists();
        if (! ($isAdmin || $canEdit || $canComplete || $isAssigned)) {
            abort(403);
        }
        $typeNormalized = strtolower((string) $ticket->type);
        $isOnuProvisioningTask = $typeNormalized === 'pasang_baru'
            || str_contains($typeNormalized, 'pergantian')
            || str_contains($typeNormalized, 'pergati')
            || str_contains($typeNormalized, 'ganti_onu')
            || str_contains($typeNormalized, 'penggantian_onu');
        if (! $isOnuProvisioningTask || ! $ticket->customer) {
            abort(403);
        }
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'package' => 'nullable|string|max:100',
            'pppoe_user' => 'nullable|string|max:100',
            'pppoe_password' => 'nullable|string|max:100',
            'onu_serial' => 'required|string|max:100',
            'wan_mac' => ['required', 'string', 'max:20', 'regex:/^([0-9A-Fa-f]{2}[:-]){5}[0-9A-Fa-f]{2}$/'],
            'device_model' => 'nullable|string|max:100',
            'ssid_name' => 'nullable|string|max:100',
            'ssid_password' => 'nullable|string|max:100',
        ]);
        if (isset($validated['wan_mac']) && is_string($validated['wan_mac']) && trim($validated['wan_mac']) !== '') {
            $validated['wan_mac'] = strtoupper(str_replace('-', ':', $validated['wan_mac']));
        }
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
     * Manually send WhatsApp notification to assigned technicians.
     */
    public function sendNotification(Request $request, Ticket $ticket, \App\Services\WhatsAppService $whatsappService)
    {
        if (! Auth::user()->hasRole('admin')) {
            abort(403);
        }

        $request->validate([
            'notification_template' => 'nullable|string',
        ]);

        if ($ticket->technicians->isEmpty()) {
            return back()->with('error', __('No technicians assigned to this ticket.'));
        }

        $successCount = 0;
        $failCount = 0;
        $errors = [];

        $customTemplate = $request->filled('notification_template')
            ? (string) $request->input('notification_template')
            : null;

        foreach ($ticket->technicians as $technician) {
            try {
                // Build personalized message for each assigned technician.
                $message = TicketAssignedNotification::buildMessage($ticket, $technician, $customTemplate);

                if (empty($technician->phone)) {
                    $failCount++;
                    $errors[] = "Technician {$technician->name} has no phone number.";

                    continue;
                }

                // Send directly via service to get immediate feedback
                // sendMessage throws exception if config missing or API error
                $whatsappService->sendMessage($technician->phone, $message, 'ticket_assignment', null);

                $successCount++;

            } catch (\Exception $e) {
                $failCount++;
                // Clean up error message for user display
                $msg = $e->getMessage();
                if (str_contains($msg, 'Configuration missing')) {
                    $msg = 'WhatsApp Configuration Missing (.env)';
                }
                $errors[] = $msg;

                \Log::error("Failed to send manual notification to user {$technician->id}: ".$e->getMessage());
            }
        }

        if ($successCount == 0 && $failCount > 0) {
            return back()->with('error', __('Failed to send notifications. Errors: ').implode(', ', array_unique($errors)));
        } elseif ($failCount > 0) {
            return back()->with('warning', __('Sent to :success technicians, but failed for :fail. Errors: :errors', [
                'success' => $successCount,
                'fail' => $failCount,
                'errors' => implode(', ', array_unique($errors)),
            ]));
        }

        return back()->with('success', __('Notification sent to :count assigned technicians via WhatsApp.', ['count' => $successCount]));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Ticket $ticket)
    {
        $ticket->delete();

        return redirect()->route('tickets.index')->with('success', __('Ticket deleted successfully.'));
    }

    public function sopPdf(Ticket $ticket)
    {
        $pdf = Pdf::loadView('tickets.sop_pdf', [
            'ticket' => $ticket,
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('sop-teknisi-'.$ticket->ticket_number.'.pdf');
    }
}
