<?php

namespace App\Http\Controllers;

use App\Services\TicketNotificationService;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Coordinator;
use App\Models\Customer;
use App\Models\InventoryItem;
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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class TicketWebController extends Controller implements HasMiddleware
{
    protected $ticketNotificationService;

    public function __construct(TicketNotificationService $ticketNotificationService)
    {
        $this->ticketNotificationService = $ticketNotificationService;
    }

    protected function canManageAllTickets(): bool
    {
        $user = Auth::user();

        return $user && ($user->hasRole('admin') || $user->hasRole('leader') || $user->hasRole('direktur'));
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

        if ($request->has('date_from') && $request->input('date_from') != '') {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->has('date_to') && $request->input('date_to') != '') {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        if (! $this->canManageAllTickets()) {
            $query->whereHas('technicians', function ($q) {
                $q->where('users.id', Auth::id());
            });
        }

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
        $inventoryItems = InventoryItem::orderBy('type_group', 'desc')->orderBy('name')->get();

        return view('tickets.create', compact('customers', 'technicians', 'odps', 'coordinators', 'inventoryItems'));
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
            // Inventory validation
            'tools' => 'nullable|array',
            'tools.*.inventory_item_id' => 'nullable|exists:inventory_items,id',
            'tools.*.quantity' => 'nullable|integer|min:1',
            'materials' => 'nullable|array',
            'materials.*.inventory_item_id' => 'nullable|exists:inventory_items,id',
            'materials.*.quantity' => 'nullable|integer|min:1',
            // Conditional validation
            'customer_id' => 'required_if:type,gangguan,other|nullable|exists:customers,id',
            'manual_customer_name' => 'nullable|string|max:255',
            'new_customer_name' => 'required_if:type,pasang_baru|nullable|string|max:255',
            'new_customer_modem_type' => 'nullable|string|max:100',
            'new_customer_onu_serial' => 'nullable|string|max:100',
            'new_customer_wan_mac' => ['nullable', 'string', 'max:20', 'regex:/^([0-9A-Fa-f]{2}[:-]){5}[0-9A-Fa-f]{2}$/'],
            'new_customer_address' => 'required_if:type,pasang_baru|nullable|string',
        ]);

        // Untuk tipe perbaikan: harus pilih customer_id ATAU isi manual_customer_name
        if ($request->type === 'perbaikan' && empty($request->customer_id) && empty($request->manual_customer_name)) {
            return back()
                ->withErrors(['manual_customer_name' => 'Pilih pelanggan atau isi nama pelanggan manual'])
                ->withInput();
        }

        if ($request->has('technicians')) {
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
            $customer = Customer::create([
                'name' => $request->new_customer_name,
                'address' => $request->new_customer_address,
                'phone' => $request->new_customer_phone,
                'device_model' => $request->new_customer_modem_type,
                'onu_serial' => $request->new_customer_onu_serial,
                'wan_mac' => $request->new_customer_wan_mac ? strtoupper(trim($request->new_customer_wan_mac)) : null,
                'latitude' => $request->new_customer_lat,
                'longitude' => $request->new_customer_lng,
                'status' => 'active',
            ]);
            $customerId = $customer->id;
        }

        // Get address from existing customer if selected
        $customerAddress = null;
        if ($customerId && $request->type !== 'pasang_baru') {
            $customer = \App\Models\Customer::find($customerId);
            if ($customer) {
                $customerAddress = $customer->address;
            }
        }

        $ticketData = [
            'ticket_number' => Ticket::generateNumber(),
            'customer_id' => $customerId,
            'manual_customer_name' => $request->manual_customer_name,
            'subject' => $request->subject,
            'description' => $request->description,
            'priority' => $request->priority,
            'status' => $request->has('technicians') && count($request->technicians) > 0 ? 'assigned' : 'open',
            'location' => $request->location ?? ($request->type === 'pasang_baru' && $request->new_customer_lat && $request->new_customer_lng ? "{$request->new_customer_lat}, {$request->new_customer_lng}" : ($customerId ? optional(\App\Models\Customer::find($customerId))->latitude . ', ' . optional(\App\Models\Customer::find($customerId))->longitude : null)),
            'address' => $request->address ?? ($request->type === 'pasang_baru' ? $request->new_customer_address : $customerAddress),
            'odp_id' => $request->odp_id,
            'coordinator_id' => $request->coordinator_id,
            'type' => $request->type,
        ];
        if (Schema::hasColumn('tickets', 'estimated_duration_minutes')) {
            $ticketData['estimated_duration_minutes'] = $request->filled('estimated_duration_minutes')
                ? (int) $request->estimated_duration_minutes
                : null;
        }

        $notificationWarnings = [];

        DB::transaction(function () use ($request, $ticketData, &$notificationWarnings) {
            $ticket = Ticket::create($ticketData);

            $items = [];
            if ($request->has('tools')) {
                foreach ($request->tools as $tool) {
                    if (!empty($tool['inventory_item_id']) && !empty($tool['quantity'])) {
                        $items[] = $tool;
                    }
                }
            }
            if ($request->has('materials')) {
                foreach ($request->materials as $material) {
                    if (!empty($material['inventory_item_id']) && !empty($material['quantity'])) {
                        $items[] = $material;
                    }
                }
            }

            if (!empty($items)) {
                $usage = match($request->type) {
                    'pasang_baru' => 'pemasangan_baru',
                    'perbaikan' => 'perbaikan_maintenance',
                    'maintenance' => 'perbaikan_maintenance',
                    default => 'perbaikan_maintenance',
                };

                $usageLabels = [
                    'pemasangan_baru' => 'Pemasangan Baru',
                    'perbaikan_maintenance' => 'Perbaikan / Maintenance',
                    'stok_tim' => 'Stok Tim / Coordinator',
                    'penggantian_material' => 'Penggantian Material',
                    'penggantian_alat' => 'Penggantian Alat',
                ];
                $usageLabel = $usageLabels[$usage] ?? $usage;
                $finalDescription = '['.$usageLabel.'] Tiket: '.$ticket->ticket_number.' - '.($request->description ?? '');

                $totals = [];
                foreach ($items as $row) {
                    $itemId = $row['inventory_item_id'];
                    $qty = $row['quantity'];
                    if (!isset($totals[$itemId])) {
                        $totals[$itemId] = 0;
                    }
                    $totals[$itemId] += $qty;
                }

                foreach ($totals as $itemId => $qty) {
                    $item = InventoryItem::find($itemId);
                    if (!$item || $item->stock < $qty) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'items' => [__('Stok tidak cukup untuk :item. Stok tersedia: :stock', ['item' => $item->name, 'stock' => $item->stock])],
                        ]);
                    }
                }

                foreach ($items as $row) {
                    $item = InventoryItem::find($row['inventory_item_id']);

                    $inventoryTransaction = \App\Models\InventoryTransaction::create([
                        'user_id' => Auth::id(),
                        'coordinator_id' => $request->coordinator_id ?? null,
                        'inventory_item_id' => $row['inventory_item_id'],
                        'type' => 'out',
                        'quantity' => $row['quantity'],
                        'unit_cost' => $item?->price ?? 0,
                        'total_cost' => ($item?->price ?? 0) * $row['quantity'],
                        'source_type' => 'ticket',
                        'source_id' => $ticket->id,
                        'proof_image' => null,
                        'description' => $finalDescription,
                    ]);

                    if ($item) {
                        $item->decrement('stock', $row['quantity']);

                        if (!empty($request->coordinator_id) && $item->price > 0) {
                            \App\Models\Transaction::create([
                                'user_id' => Auth::id(),
                                'coordinator_id' => $request->coordinator_id,
                                'type' => 'expense',
                                'category' => 'Pengeluaran Pengurus',
                                'amount' => $item->price * $row['quantity'],
                                'transaction_date' => now()->toDateString(),
                                'description' => 'Pengurus mengambil '.$row['quantity'].' '.$item->unit.' '.$item->name.' untuk tiket '.$ticket->ticket_number,
                                'reference_number' => 'INV-OUT-'.$inventoryTransaction->id,
                            ]);
                            Cache::forget('inventory.total_sales');
                        }

                        if ($item->type_group === 'tool') {
                            $holderType = !empty($request->coordinator_id) ? Coordinator::class : User::class;
                            $holderId = $request->coordinator_id ?? Auth::id();

                            for ($i = 0; $i < $row['quantity']; $i++) {
                                \App\Models\Asset::create([
                                    'inventory_item_id' => $item->id,
                                    'asset_code' => 'TOOL-'.$item->id.'-'.time().'-'.uniqid(),
                                    'status' => 'deployed',
                                    'condition' => 'good',
                                    'holder_type' => $holderType,
                                    'holder_id' => $holderId,
                                    'latitude' => $request->location ? (explode(',', $request->location)[0] ?? null) : null,
                                    'longitude' => $request->location ? (explode(',', $request->location)[1] ?? null) : null,
                                    'purchase_date' => now(),
                                    'meta_data' => ['source_transaction_id' => $inventoryTransaction->id, 'ticket_id' => $ticket->id],
                                ]);
                            }
                        }
                    }
                }
            }

            if ($request->has('technicians')) {
                $ticket->technicians()->sync($request->technicians);

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



            $customerName = $ticket->customer?->name ?? $request->new_customer_name ?? $ticket->manual_customer_name ?? '-';
            $priorityLabel = match($ticket->priority) {
                'high' => '🔴 TINGGI',
                'medium' => '🟡 SEDANG',
                'low' => '🟢 RENDAH',
                default => strtoupper($ticket->priority)
            };
            $typeLabel = strtoupper(str_replace('_', ' ', $ticket->type));

            // Get technician names if any are assigned
            $technicianNames = $ticket->technicians->pluck('name')->join(', ') ?: '-';
            
            $notificationResult = $this->ticketNotificationService->sendTicketCreatedNotification($ticket, [
                'ticket_number' => $ticket->ticket_number,
                'ticket_type' => $typeLabel,
                'customer_name' => $customerName,
                'ticket_subject' => $ticket->subject,
                'ticket_priority' => $priorityLabel,
                'ticket_address' => $ticket->address ?? '-',
                'ticket_url' => route('tickets.show', $ticket),
                'technician_names' => $technicianNames,
            ]);
            $this->ticketNotificationService->collectWhatsAppNotificationWarning($notificationResult, $notificationWarnings);
        });

        return $this->ticketNotificationService->redirectWithNotificationWarning(
            redirect()->route('tickets.index')->with('success', __('Ticket created successfully.')),
            $notificationWarnings
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(Ticket $ticket)
    {
        $ticket->load(['customer', 'technicians', 'logs.user', 'odp', 'coordinator.region', 'inventoryTransactions.item']);
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
            'address' => 'nullable|string',
            'customer_id' => 'nullable|exists:customers,id',
            'manual_customer_name' => 'nullable|string|max:255',
            'odp_id' => 'nullable|exists:odps,id',
            'coordinator_id' => 'nullable|exists:coordinators,id',
        ]);

        // Jika tipe perbaikan, pastikan salah satu customer_id atau manual_customer_name diisi
        if (isset($validated['type']) && $validated['type'] === 'perbaikan') {
            if (empty($validated['customer_id']) && empty($validated['manual_customer_name'])) {
                return back()
                    ->withErrors(['manual_customer_name' => 'Pilih pelanggan atau isi nama pelanggan manual'])
                    ->withInput();
            }
        }

        $oldStatus = $ticket->status;
        $oldTechnicianIds = $ticket->technicians->pluck('id')->toArray();
        $notificationWarnings = [];

        $ticketUpdateData = collect($validated)->except('technicians')->toArray();
        if (! Schema::hasColumn('tickets', 'estimated_duration_minutes')) {
            unset($ticketUpdateData['estimated_duration_minutes']);
        }
        $ticket->update($ticketUpdateData);

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

            if ($ticket->wasChanged('status')) {
                $statusLabel = match($ticket->status) {
                    'open' => 'BUKA 🔓',
                    'assigned' => 'DITUGASKAN 👤',
                    'in_progress' => 'PROSES 🛠️',
                    'pending' => 'PENDING ⏳',
                    'solved' => 'SELESAI ✅',
                    'closed' => 'DITUTUP 🔒',
                    default => strtoupper($ticket->status)
                };



                $notificationResult = $this->ticketNotificationService->sendTicketStatusUpdatedNotification($ticket, [
                    'ticket_number' => $ticket->ticket_number,
                    'ticket_subject' => $ticket->subject,
                    'new_status' => $statusLabel,
                    'updated_by' => Auth::user()->name,
                    'ticket_url' => route('tickets.show', $ticket),
                ], in_array($ticket->status, ['solved', 'closed']) ? ['whatsapp', 'telegram'] : ['whatsapp', 'telegram']);
                $this->ticketNotificationService->collectWhatsAppNotificationWarning($notificationResult, $notificationWarnings);
            }
        }

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

            sort($oldTechnicianIds);
            $sortedNewIds = $newTechnicianIds;
            sort($sortedNewIds);

            if ($oldTechnicianIds !== $sortedNewIds) {
                $ticket->technicians()->sync($newTechnicianIds);

                $addedTechnicianIds = array_diff($newTechnicianIds, $oldTechnicianIds);

                if (! empty($addedTechnicianIds)) {
                    $newTechNames = User::whereIn('id', $addedTechnicianIds)->pluck('name')->join(', ');

                    TicketLog::create([
                        'ticket_id' => $ticket->id,
                        'user_id' => Auth::id(),
                        'action' => 'assigned',
                        'description' => "Assigned to: {$newTechNames}",
                    ]);

                    // Send notification to new technicians
                    $this->ticketNotificationService->sendTicketAssignedToTechnicians($ticket, $addedTechnicianIds);

                    // Send notification to group
                    $notificationResult = $this->ticketNotificationService->sendTicketAssignedToGroupNotification($ticket, [
                        'ticket_number' => $ticket->ticket_number,
                        'ticket_subject' => $ticket->subject,
                        'technician_names' => $newTechNames,
                        'updated_by' => Auth::user()->name,
                        'ticket_url' => route('tickets.show', $ticket),
                    ]);
                    $this->ticketNotificationService->collectWhatsAppNotificationWarning($notificationResult, $notificationWarnings);
                }
            }
        }

        return $this->ticketNotificationService->redirectWithNotificationWarning(
            redirect()->route('tickets.show', $ticket)->with('success', __('Ticket updated successfully.')),
            $notificationWarnings
        );
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



        $notificationWarnings = [];

        try {
            $notificationResult = $this->ticketNotificationService->sendTicketSolvedNotification($ticket, [
                'ticket_number' => $ticket->ticket_number,
                'customer_name' => $ticket->customer?->name ?? '-',
                'ticket_subject' => $ticket->subject,
                'updated_by' => Auth::user()->name,
                'ticket_note' => $request->description ?? 'Telah diperbaiki',
            ]);
            $this->ticketNotificationService->collectWhatsAppNotificationWarning($notificationResult, $notificationWarnings);
        } catch (\Exception $e) {
            Log::error('Ticket Solved WA Notification Error: ' . $e->getMessage());
        }

        DatabaseNotification::where('data->ticket_id', $ticket->id)->delete();

        return $this->ticketNotificationService->redirectWithNotificationWarning(
            redirect()->route('tickets.show', $ticket)->with('success', __('Ticket marked as solved successfully.')),
            $notificationWarnings
        );
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

        if ($ticket->customer) {
            $parts = explode(',', $request->location);
            if (count($parts) >= 2) {
                $lat = trim($parts[0]);
                $lng = trim($parts[1]);

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

        if ($ticket->technicians->isEmpty()) {
            return back()->with('error', __('No technicians assigned to this ticket.'));
        }

        $successCount = 0;
        $failCount = 0;
        $errors = [];

        foreach ($ticket->technicians as $technician) {
            try {
                $message = TicketAssignedNotification::buildMessage($ticket, $technician, 'whatsapp');

                if (empty($technician->phone)) {
                    $failCount++;
                    $errors[] = "Technician {$technician->name} has no phone number.";

                    continue;
                }

                $whatsappService->sendMessage($technician->phone, $message, 'ticket_assignment', null);

                $successCount++;
            } catch (\Exception $e) {
                $failCount++;
                $msg = $e->getMessage();
                if (str_contains($msg, 'Configuration missing')) {
                    $msg = 'WhatsApp Configuration missing (.env)';
                }
                $errors[] = $msg;

                Log::error("Failed to send manual notification to user {$technician->id}: ".$e->getMessage());
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
