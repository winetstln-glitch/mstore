<?php

namespace App\Http\Controllers;

use App\Models\CctvBooking;
use App\Models\CctvPackage;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class CctvBookingController extends Controller implements HasMiddleware
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

    public function index(Request $request)
    {
        $query = CctvBooking::query()->with('package')->latest();

        if ($request->filled('q')) {
            $q = (string) $request->q;
            $query->where(function ($sub) use ($q) {
                $sub->where('booking_number', 'like', '%'.$q.'%')
                    ->orWhere('customer_name', 'like', '%'.$q.'%')
                    ->orWhere('customer_whatsapp', 'like', '%'.$q.'%')
                    ->orWhere('address', 'like', '%'.$q.'%');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', (string) $request->status);
        }

        $bookings = $query->paginate(20)->withQueryString();

        return view('cctv.bookings.index', compact('bookings'));
    }

    public function create()
    {
        $packages = CctvPackage::query()->where('is_active', true)->orderBy('name')->get();
        return view('cctv.bookings.create', compact('packages'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_whatsapp' => ['required', 'string', 'max:30'],
            'address' => ['required', 'string'],
            'cctv_package_id' => ['required', 'exists:cctv_packages,id'],
            'notes' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'max:50'],
            'quotation_amount' => ['nullable', 'integer', 'min:0'],
            'dp_amount' => ['nullable', 'integer', 'min:0'],
        ]);

        $booking = CctvBooking::create([
            'customer_name' => $validated['customer_name'],
            'customer_whatsapp' => $validated['customer_whatsapp'],
            'address' => $validated['address'],
            'cctv_package_id' => (int) $validated['cctv_package_id'],
            'notes' => $validated['notes'] ?? null,
            'status' => $validated['status'] ?? 'pending',
            'quotation_amount' => $validated['quotation_amount'] ?? null,
            'dp_amount' => $validated['dp_amount'] ?? null,
        ]);

        $this->auditLogService->logAction('cctv.booking.created', $booking, [], $booking->toArray());

        return redirect()->route('cctv.bookings.index')->with('success', 'Booking berhasil dibuat.');
    }

    public function show(CctvBooking $booking)
    {
        $booking->loadMissing(['package', 'surveys', 'installation.technician', 'payments.paymentTransaction']);
        return view('cctv.bookings.show', compact('booking'));
    }

    public function edit(CctvBooking $booking)
    {
        $packages = CctvPackage::query()->orderBy('name')->get();
        return view('cctv.bookings.edit', compact('booking', 'packages'));
    }

    public function update(Request $request, CctvBooking $booking)
    {
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_whatsapp' => ['required', 'string', 'max:30'],
            'address' => ['required', 'string'],
            'cctv_package_id' => ['required', 'exists:cctv_packages,id'],
            'notes' => ['nullable', 'string'],
            'status' => ['required', 'string', 'max:50'],
            'quotation_amount' => ['nullable', 'integer', 'min:0'],
            'dp_amount' => ['nullable', 'integer', 'min:0'],
        ]);

        $old = $booking->toArray();
        $booking->update([
            'customer_name' => $validated['customer_name'],
            'customer_whatsapp' => $validated['customer_whatsapp'],
            'address' => $validated['address'],
            'cctv_package_id' => (int) $validated['cctv_package_id'],
            'notes' => $validated['notes'] ?? null,
            'status' => $validated['status'],
            'quotation_amount' => $validated['quotation_amount'] ?? null,
            'dp_amount' => $validated['dp_amount'] ?? null,
        ]);

        $this->auditLogService->logAction('cctv.booking.updated', $booking, $old, $booking->toArray());

        return redirect()->route('cctv.bookings.show', $booking)->with('success', 'Booking berhasil diperbarui.');
    }

    public function destroy(CctvBooking $booking)
    {
        $old = $booking->toArray();
        $booking->delete();
        $this->auditLogService->logAction('cctv.booking.deleted', $booking, $old, []);

        return redirect()->route('cctv.bookings.index')->with('success', 'Booking berhasil dihapus.');
    }
}

