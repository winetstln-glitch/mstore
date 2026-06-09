<?php

namespace App\Http\Controllers;

use App\Models\CctvBooking;
use App\Models\CctvInstallation;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class CctvInstallationController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:cctv.booking'),
        ];
    }

    public function create(CctvBooking $booking)
    {
        $technicians = User::query()
            ->whereHas('role', fn ($q) => $q->where('name', 'technician'))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('cctv.installations.create', compact('booking', 'technicians'));
    }

    public function store(Request $request, CctvBooking $booking)
    {
        $validated = $request->validate([
            'technician_id' => ['nullable', 'exists:users,id'],
            'scheduled_at' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        $installation = CctvInstallation::create([
            'cctv_booking_id' => $booking->id,
            'technician_id' => $validated['technician_id'] ?? null,
            'scheduled_at' => $validated['scheduled_at'] ?? null,
            'status' => $validated['status'] ?? 'scheduled',
            'notes' => $validated['notes'] ?? null,
            'progress_percent' => 0,
        ]);

        $this->auditLogService->logAction('cctv.installation.created', $installation, [], $installation->toArray());

        return redirect()->route('cctv.bookings.show', $booking)->with('success', 'Jadwal teknisi berhasil dibuat.');
    }

    public function edit(CctvInstallation $installation)
    {
        $installation->loadMissing('booking');
        $technicians = User::query()
            ->whereHas('role', fn ($q) => $q->where('name', 'technician'))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('cctv.installations.edit', compact('installation', 'technicians'));
    }

    public function update(Request $request, CctvInstallation $installation)
    {
        $validated = $request->validate([
            'technician_id' => ['nullable', 'exists:users,id'],
            'scheduled_at' => ['nullable', 'date'],
            'status' => ['required', 'string', 'max:50'],
            'progress_percent' => ['required', 'integer', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        $old = $installation->toArray();
        $installation->update([
            'technician_id' => $validated['technician_id'] ?? null,
            'scheduled_at' => $validated['scheduled_at'] ?? null,
            'status' => $validated['status'],
            'progress_percent' => (int) $validated['progress_percent'],
            'notes' => $validated['notes'] ?? null,
        ]);
        $this->auditLogService->logAction('cctv.installation.updated', $installation, $old, $installation->toArray());

        if ($installation->status === 'completed' && ! $installation->completed_at) {
            $installation->update(['completed_at' => now()]);
            $this->auditLogService->logAction('cctv.installation.completed', $installation, $old, $installation->toArray());
        }

        return redirect()->route('cctv.bookings.show', $installation->booking)->with('success', 'Jadwal teknisi berhasil diperbarui.');
    }
}

