<?php

namespace App\Http\Controllers;

use App\Models\HotspotProfile;
use App\Models\Voucher;
use App\Services\VoucherService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

class VoucherController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('permission:voucher.view', only: ['index', 'exportCsv', 'exportExcel', 'exportPdf']),
            new Middleware('permission:voucher.create', only: ['generate']),
            new Middleware('permission:voucher.edit', only: ['disconnect']),
        ];
    }

    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', ''));
        $vouchers = Voucher::query()
            ->when($search !== '', fn ($q) => $q->where('username', 'like', '%'.$search.'%'))
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        $hotspotProfiles = HotspotProfile::query()
            ->active()
            ->vouchers()
            ->orderBy('sort_order')
            ->orderBy('price')
            ->get();

        $profileDataJson = $hotspotProfiles->mapWithKeys(function ($p) {
            return [
                (string) $p->id => [
                    'mikrotik_profile_name' => $p->mikrotik_profile_name,
                    'formatted_uptime'      => $p->formatted_uptime,
                    'quota_mb'              => $p->quota_mb,
                ],
            ];
        })->toJson();

        return view('vouchers.index', compact('vouchers', 'search', 'status', 'hotspotProfiles', 'profileDataJson'));
    }

    public function generate(Request $request, VoucherService $service)
    {
        $validated = $request->validate([
            'hotspot_profile_id' => ['nullable', 'exists:hotspot_profiles,id'],
            'profile' => ['nullable', 'string', 'max:255'],
            'duration' => ['nullable', 'string', 'max:20'],
            'quota_mb' => ['nullable', 'integer', 'min:0'],
            'count' => ['required', 'integer', 'min:1', 'max:2000'],
            'password_same' => ['boolean'],
        ]);

        $profile = null;
        $durationSeconds = null;
        $quotaMb = null;

        if (! empty($validated['hotspot_profile_id'])) {
            $hp = HotspotProfile::find($validated['hotspot_profile_id']);
            if ($hp) {
                $profile = $hp->mikrotik_profile_name ?? $validated['profile'];
                $durationSeconds = $hp->duration_seconds;
                $quotaMb = $hp->quota_mb;
            }
        }

        if (empty($durationSeconds)) {
            $durationSeconds = $this->parseDurationToSeconds($validated['duration'] ?? null);
        }
        if (empty($quotaMb) && isset($validated['quota_mb'])) {
            $quotaMb = $validated['quota_mb'];
        }
        if (empty($profile)) {
            $profile = $validated['profile'] ?? null;
        }

        $hotspotProfileId = $validated['hotspot_profile_id'] ?? null;
        $batch = $service->generateBatch(
            $profile,
            $durationSeconds,
            $quotaMb,
            (int) $validated['count'],
            (bool) ($validated['password_same'] ?? true),
            auth()->id(),
            $hotspotProfileId
        );

        return redirect()->route('vouchers.index')->with('success', 'Voucher batch '.$batch->batch_code.' dibuat.');
    }

    public function disconnect(Request $request, VoucherService $service)
    {
        $validated = $request->validate([
            'username' => ['required', 'string'],
        ]);
        $ok = $service->disconnectUser($validated['username']);

        return back()->with($ok ? 'success' : 'error', $ok ? 'User disconnected.' : 'Disconnect gagal.');
    }

    public function exportCsv(Request $request)
    {
        $vouchers = $this->filtered($request)->orderBy('id', 'desc')->get();

        return response()->streamDownload(function () use ($vouchers) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Username', 'Password', 'Profile', 'Duration', 'Quota(MB)', 'Status', 'Batch', 'Paket']);
            foreach ($vouchers as $v) {
                fputcsv($out, [
                    $v->username,
                    $v->password,
                    $v->profile,
                    $v->duration_seconds,
                    $v->quota_mb,
                    $v->status,
                    $v->batch_id,
                    $v->hotspot_profile_id,
                ]);
            }
            fclose($out);
        }, 'vouchers_'.now()->format('Ymd_His').'.csv');
    }

    public function exportExcel(Request $request)
    {
        $vouchers = $this->filtered($request)->orderBy('id', 'desc')->get();

        return response()->streamDownload(function () use ($vouchers) {
            $writer = new Writer;
            $writer->openToFile('php://output');
            $writer->addRow(Row::fromValues(['Username', 'Password', 'Profile', 'Duration', 'Quota(MB)', 'Status', 'Batch', 'Paket']));
            foreach ($vouchers as $v) {
                $writer->addRow(Row::fromValues([
                    $v->username,
                    $v->password,
                    $v->profile,
                    $v->duration_seconds,
                    $v->quota_mb,
                    $v->status,
                    $v->batch_id,
                    $v->hotspot_profile_id,
                ]));
            }
            $writer->close();
        }, 'vouchers_'.now()->format('Ymd_His').'.xlsx');
    }

    public function exportPdf(Request $request)
    {
        $vouchers = $this->filtered($request)->orderBy('id', 'desc')->get();
        $pdf = Pdf::loadView('vouchers.pdf', compact('vouchers'))->setPaper('a4', 'portrait');

        return $pdf->download('vouchers_'.now()->format('Ymd_His').'.pdf');
    }

    private function filtered(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', ''));

        return Voucher::query()
            ->when($search !== '', fn ($q) => $q->where('username', 'like', '%'.$search.'%'))
            ->when($status !== '', fn ($q) => $q->where('status', $status));
    }

    private function parseDurationToSeconds(?string $duration): ?int
    {
        if (! $duration) {
            return null;
        }
        $d = strtolower(trim($duration));
        if (str_contains($d, 'hari')) {
            $num = (int) filter_var($d, FILTER_SANITIZE_NUMBER_INT);

            return $num * 86400;
        }
        if (str_contains($d, 'jam')) {
            $num = (int) filter_var($d, FILTER_SANITIZE_NUMBER_INT);

            return $num * 3600;
        }
        if (str_contains($d, 'menit')) {
            $num = (int) filter_var($d, FILTER_SANITIZE_NUMBER_INT);

            return $num * 60;
        }
        if (is_numeric($d)) {
            return (int) $d;
        }

        return null;
    }
}
