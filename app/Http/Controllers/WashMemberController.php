<?php

namespace App\Http\Controllers;

use App\Models\WashMember;
use App\Models\WashMemberCard;
use App\Models\WashMemberLevel;
use App\Services\Wash\WashMembershipService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class WashMemberController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:wash.member.view', only: ['index', 'show', 'levels', 'cardPdf']),
            new Middleware('permission:wash.member.manage', only: ['update']),
        ];
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $status = trim((string) $request->get('status', ''));
        $level = trim((string) $request->get('level', ''));

        $members = WashMember::query()
            ->with(['level', 'vehicles', 'card'])
            ->when($q !== '', function ($query) use ($q) {
                $query->where('member_number', 'like', '%'.$q.'%')
                    ->orWhere('name', 'like', '%'.$q.'%')
                    ->orWhere('whatsapp', 'like', '%'.$q.'%')
                    ->orWhereHas('vehicles', function ($vehicleQuery) use ($q) {
                        $vehicleQuery->where('vehicle_plate', 'like', '%'.strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $q)).'%');
                    });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($level !== '', function ($query) use ($level) {
                $query->whereHas('level', fn ($levelQuery) => $levelQuery->where('code', $level));
            })
            ->orderByDesc('last_transaction_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->appends($request->query());

        $levels = WashMemberLevel::query()->where('is_active', true)->orderBy('min_transactions')->get();

        return view('wash.members.index', compact('members', 'levels', 'q', 'status', 'level'));
    }

    public function show(WashMember $member, WashMembershipService $membershipService)
    {
        $member->load(['level', 'vehicles', 'card', 'transactions' => fn ($q) => $q->latest()->limit(10)]);
        $card = $membershipService->ensureMemberCard($member);
        $verificationUrl = $membershipService->memberVerificationUrl($card);
        $qrUrl = 'https://quickchart.io/qr?size=220&text='.urlencode($verificationUrl);

        return view('wash.members.show', compact('member', 'card', 'verificationUrl', 'qrUrl'));
    }

    public function update(Request $request, WashMember $member)
    {
        $validated = $request->validate([
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:2000',
            'status' => 'required|in:active,inactive,blacklist',
        ]);

        $member->update($validated);

        return redirect()
            ->route('wash.members.show', $member)
            ->with('success', 'Data member berhasil diperbarui.');
    }

    public function levels()
    {
        $levels = WashMemberLevel::query()->where('is_active', true)->orderBy('min_transactions')->get();

        return view('wash.members.levels', compact('levels'));
    }

    public function cardPdf(WashMember $member, WashMembershipService $membershipService)
    {
        $member->load(['level', 'vehicles', 'card']);
        $card = $membershipService->ensureMemberCard($member);
        $verificationUrl = $membershipService->memberVerificationUrl($card);
        $qrUrl = 'https://quickchart.io/qr?size=220&text='.urlencode($verificationUrl);

        $pdf = Pdf::loadView('wash.members.card-pdf', compact('member', 'card', 'verificationUrl', 'qrUrl'))
            ->setPaper('a4', 'portrait')
            ->setOption(['isRemoteEnabled' => true]);

        return $pdf->download('member-card-'.$member->member_number.'.pdf');
    }

    public function verify(string $token)
    {
        $card = WashMemberCard::query()
            ->with(['member.level', 'member.vehicles'])
            ->where('verification_token', $token)
            ->firstOrFail();

        $member = $card->member;

        return view('wash.members.verify', compact('card', 'member'));
    }
}

