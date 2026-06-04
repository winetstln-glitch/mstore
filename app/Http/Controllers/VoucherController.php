<?php

namespace App\Http\Controllers;

use App\Models\Voucher;
use App\Models\VoucherBatch;
use App\Models\VoucherTemplate;
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
        $batches = VoucherBatch::query()->latest('id')->limit(10)->get();
        $templates = VoucherTemplate::query()->orderByDesc('is_active')->orderBy('name')->get();

        return view('vouchers.index', compact('vouchers', 'batches', 'search', 'status', 'templates'));
    }

    public function generate(Request $request, VoucherService $service)
    {
        $validated = $request->validate([
            'voucher_template_id' => ['nullable', 'exists:voucher_templates,id'],
            'profile' => ['nullable', 'string', 'max:255'],
            'duration' => ['nullable', 'string', 'max:20'],
            'quota_mb' => ['nullable', 'integer', 'min:0'],
            'count' => ['required', 'integer', 'min:1', 'max:2000'],
            'password_same' => ['boolean'],
        ]);

        $template = null;
        if (! empty($validated['voucher_template_id'])) {
            $template = VoucherTemplate::query()->find($validated['voucher_template_id']);
        }

        $durationSeconds = $template?->duration_seconds ?? $this->parseDurationToSeconds($validated['duration'] ?? null);
        $profile = $template?->rate_limit ?? ($validated['profile'] ?? null);
        $quotaMb = $template?->quota_mb ?? ($validated['quota_mb'] ?? null);
        $batch = $service->generateBatch(
            $profile,
            $durationSeconds,
            $quotaMb,
            (int) $validated['count'],
            (bool) ($validated['password_same'] ?? true),
            auth()->id()
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

    public function storeTemplate(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'rate_limit' => ['nullable', 'string', 'max:255'],
            'duration_value' => ['nullable', 'integer', 'min:0'],
            'duration_unit' => ['nullable', 'in:menit,jam,hari,bulan'],
            'quota_mb' => ['nullable', 'integer', 'min:0'],
            'price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ]);
        $validated['is_active'] = (bool) ($validated['is_active'] ?? true);
        
        // Calculate duration in seconds
        $durationSeconds = null;
        if (isset($validated['duration_value']) && $validated['duration_value'] > 0) {
            $unitMultipliers = [
                'menit' => 60,
                'jam' => 3600,
                'hari' => 86400,
                'bulan' => 2592000, // 30 days
            ];
            $durationSeconds = $validated['duration_value'] * $unitMultipliers[$validated['duration_unit'] ?? 'jam'];
        }
        
        $validated['duration_seconds'] = $durationSeconds;
        unset($validated['duration_value'], $validated['duration_unit']);

        VoucherTemplate::query()->create($validated);

        return back()->with('success', 'Profile paket voucher berhasil ditambahkan.');
    }

    public function deleteTemplate(VoucherTemplate $voucherTemplate)
    {
        $voucherTemplate->delete();

        return back()->with('success', 'Profile paket voucher berhasil dihapus.');
    }

    public function exportCsv(Request $request)
    {
        $vouchers = $this->filtered($request)->orderBy('id', 'desc')->get();

        return response()->streamDownload(function () use ($vouchers) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Username', 'Password', 'Profile', 'Duration', 'Quota(MB)', 'Status', 'Batch']);
            foreach ($vouchers as $v) {
                fputcsv($out, [
                    $v->username,
                    $v->password,
                    $v->profile,
                    $v->duration_seconds,
                    $v->quota_mb,
                    $v->status,
                    $v->batch_id,
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
            $writer->addRow(Row::fromValues(['Username', 'Password', 'Profile', 'Duration', 'Quota(MB)', 'Status', 'Batch']));
            foreach ($vouchers as $v) {
                $writer->addRow(Row::fromValues([
                    $v->username,
                    $v->password,
                    $v->profile,
                    $v->duration_seconds,
                    $v->quota_mb,
                    $v->status,
                    $v->batch_id,
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
