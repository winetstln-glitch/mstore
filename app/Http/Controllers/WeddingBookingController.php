<?php

namespace App\Http\Controllers;

use App\Models\WeddingBooking;
use App\Models\WeddingPackage;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class WeddingBookingController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:wedding.booking'),
        ];
    }

    public function index(Request $request)
    {
        $query = WeddingBooking::query()->with('package')->latest();

        if ($request->filled('q')) {
            $q = (string) $request->q;
            $query->where(function ($sub) use ($q) {
                $sub->where('booking_number', 'like', '%'.$q.'%')
                    ->orWhere('customer_name', 'like', '%'.$q.'%')
                    ->orWhere('customer_whatsapp', 'like', '%'.$q.'%')
                    ->orWhere('location', 'like', '%'.$q.'%');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', (string) $request->status);
        }

        $bookings = $query->paginate(20)->withQueryString();

        return view('wedding.bookings.index', compact('bookings'));
    }

    public function create()
    {
        $packages = WeddingPackage::query()->where('is_active', true)->orderBy('name')->get();
        return view('wedding.bookings.create', compact('packages'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_whatsapp' => ['required', 'string', 'max:30'],
            'event_date' => ['required', 'date'],
            'location' => ['required', 'string', 'max:255'],
            'wedding_package_id' => ['required', 'exists:wedding_packages,id'],
            'notes' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'max:50'],
            'quotation_amount' => ['nullable', 'integer', 'min:0'],
            'dp_amount' => ['nullable', 'integer', 'min:0'],
        ]);

        $booking = WeddingBooking::create([
            'customer_name' => $validated['customer_name'],
            'customer_whatsapp' => $validated['customer_whatsapp'],
            'event_date' => $validated['event_date'],
            'location' => $validated['location'],
            'wedding_package_id' => (int) $validated['wedding_package_id'],
            'notes' => $validated['notes'] ?? null,
            'status' => $validated['status'] ?? 'pending',
            'quotation_amount' => $validated['quotation_amount'] ?? null,
            'dp_amount' => $validated['dp_amount'] ?? null,
        ]);

        $this->auditLogService->logAction('wedding.booking.created', $booking, [], $booking->toArray());

        return redirect()->route('wedding.bookings.index')->with('success', 'Booking berhasil dibuat.');
    }

    public function show(WeddingBooking $booking)
    {
        $booking->loadMissing(['package', 'payments.paymentTransaction']);
        return view('wedding.bookings.show', compact('booking'));
    }

    public function edit(WeddingBooking $booking)
    {
        $packages = WeddingPackage::query()->orderBy('name')->get();
        return view('wedding.bookings.edit', compact('booking', 'packages'));
    }

    public function update(Request $request, WeddingBooking $booking)
    {
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_whatsapp' => ['required', 'string', 'max:30'],
            'event_date' => ['required', 'date'],
            'location' => ['required', 'string', 'max:255'],
            'wedding_package_id' => ['required', 'exists:wedding_packages,id'],
            'notes' => ['nullable', 'string'],
            'status' => ['required', 'string', 'max:50'],
            'quotation_amount' => ['nullable', 'integer', 'min:0'],
            'dp_amount' => ['nullable', 'integer', 'min:0'],
        ]);

        $old = $booking->toArray();
        $booking->update([
            'customer_name' => $validated['customer_name'],
            'customer_whatsapp' => $validated['customer_whatsapp'],
            'event_date' => $validated['event_date'],
            'location' => $validated['location'],
            'wedding_package_id' => (int) $validated['wedding_package_id'],
            'notes' => $validated['notes'] ?? null,
            'status' => $validated['status'],
            'quotation_amount' => $validated['quotation_amount'] ?? null,
            'dp_amount' => $validated['dp_amount'] ?? null,
        ]);
        $this->auditLogService->logAction('wedding.booking.updated', $booking, $old, $booking->toArray());

        return redirect()->route('wedding.bookings.show', $booking)->with('success', 'Booking berhasil diperbarui.');
    }

    public function destroy(WeddingBooking $booking)
    {
        $old = $booking->toArray();
        $booking->delete();
        $this->auditLogService->logAction('wedding.booking.deleted', $booking, $old, []);

        return redirect()->route('wedding.bookings.index')->with('success', 'Booking berhasil dihapus.');
    }
}

