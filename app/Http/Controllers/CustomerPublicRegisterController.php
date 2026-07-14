<?php

namespace App\Http\Controllers;

use App\Models\Coordinator;
use App\Models\Customer;
use App\Models\Installation;
use App\Models\Package;
use App\Models\Ticket;
use App\Services\TicketNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class CustomerPublicRegisterController extends Controller
{
    public function create()
    {
        $packages = Package::where('is_active', true)->orderBy('name')->get(['id', 'name', 'price']);
        $coordinators = Coordinator::orderBy('name')->get(['id', 'name']);

        return view('customers.register', compact('packages', 'coordinators'));
    }

    public function store(Request $request, TicketNotificationService $ticketNotificationService)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'package_id' => 'nullable|exists:packages,id',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'coordinator_id' => 'nullable|exists:coordinators,id',
            'ssid_name' => 'nullable|string|max:100',
            'ssid_password' => 'nullable|string|max:100',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $phone = $this->normalizePhone((string) $validated['phone']);
        if ($phone === '') {
            return back()->withInput()->withErrors([
                'phone' => 'Nomor HP tidak valid.',
            ]);
        }

        $packageId = isset($validated['package_id']) ? (int) $validated['package_id'] : null;
        $packageName = null;
        if (! empty($packageId)) {
            $packageName = Package::where('id', $packageId)->value('name');
        }

        DB::transaction(function () use ($validated, $phone, $packageId, $packageName, $ticketNotificationService): void {
            $customerData = [
                'name' => trim((string) $validated['name']),
                'phone' => $phone,
                'address' => isset($validated['address']) ? trim((string) $validated['address']) : null,
                'status' => 'active',
            ];

            if (Schema::hasColumn('customers', 'package_id')) {
                $customerData['package_id'] = $packageId ?: null;
            }
            if (Schema::hasColumn('customers', 'package')) {
                $customerData['package'] = $packageName;
            }
            if (Schema::hasColumn('customers', 'email')) {
                $customerData['email'] = isset($validated['email']) ? trim((string) $validated['email']) : null;
            }
            if (Schema::hasColumn('customers', 'ssid_name')) {
                $customerData['ssid_name'] = isset($validated['ssid_name']) ? trim((string) $validated['ssid_name']) : null;
            }
            if (Schema::hasColumn('customers', 'ssid_password')) {
                $customerData['ssid_password'] = isset($validated['ssid_password']) ? trim((string) $validated['ssid_password']) : null;
            }
            if (Schema::hasColumn('customers', 'latitude')) {
                $customerData['latitude'] = $validated['latitude'] ?? null;
            }
            if (Schema::hasColumn('customers', 'longitude')) {
                $customerData['longitude'] = $validated['longitude'] ?? null;
            }

            $customer = Customer::create($customerData);

            $coordinates = null;
            if (isset($validated['latitude'], $validated['longitude'])) {
                $coordinates = $validated['latitude'].', '.$validated['longitude'];
            }

            Installation::create([
                'customer_id' => $customer->id,
                'status' => 'registered',
                'plan_date' => now()->toDateString(),
                'notes' => 'Dibuat dari register pelanggan publik.',
                'coordinates' => $coordinates,
            ]);

            $ticket = Ticket::create([
                'ticket_number' => Ticket::generateNumber(),
                'subject' => 'Pemasangan Baru - '.$customer->name,
                'customer_id' => $customer->id,
                'type' => 'pasang_baru',
                'priority' => 'medium',
                'status' => 'open',
                'description' => 'Tiket otomatis dari register pelanggan publik.',
                'location' => $coordinates,
                'coordinator_id' => $validated['coordinator_id'] ?? null,
            ]);

            $customerName = $customer->name ?? '-';
            $priorityLabel = match($ticket->priority) {
                'high' => '🔴 TINGGI',
                'medium' => '🟡 SEDANG',
                'low' => '🟢 RENDAH',
                default => strtoupper($ticket->priority)
            };
            $typeLabel = strtoupper(str_replace('_', ' ', $ticket->type));

            try {
                $ticketNotificationService->sendTicketCreatedNotification($ticket, [
                    'ticket_number' => $ticket->ticket_number,
                    'ticket_type' => $typeLabel,
                    'customer_name' => $customerName,
                    'ticket_subject' => $ticket->subject,
                    'ticket_priority' => $priorityLabel,
                    'ticket_address' => $ticket->location ?? '-',
                    'ticket_url' => route('tickets.show', $ticket),
                ]);
            } catch (\Exception $e) {
                Log::error('Customer Public Register Ticket Notification Error: ' . $e->getMessage());
            }
        });

        return back()->with('success', 'Registrasi berhasil, data masuk ke pemasangan baru dan menunggu proses teknisi/admin.');
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if (! is_string($digits) || $digits === '') {
            return '';
        }

        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        } elseif (! str_starts_with($digits, '62')) {
            $digits = '62'.$digits;
        }

        return $digits;
    }
}
