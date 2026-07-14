<?php

namespace App\Jobs\Vouchers;

use App\Jobs\WhatsApp\SendWhatsAppMessageJob;
use App\Models\Voucher;
use App\Models\VoucherPayment;
use App\Services\VoucherService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FulfillVoucherPaymentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $voucherPaymentId
    ) {
        $this->onQueue('payments');
    }

    public function handle(VoucherService $voucherService): void
    {
        $payment = null;
        $voucher = null;
        $templateName = null;
        $durationSeconds = null;
        $quotaMb = null;
        $phoneNumber = null;

        try {
            DB::transaction(function () use ($voucherService, &$payment, &$voucher, &$templateName, &$durationSeconds, &$quotaMb, &$phoneNumber) {
                $payment = VoucherPayment::query()
                    ->with('voucherTemplate')
                    ->lockForUpdate()
                    ->findOrFail($this->voucherPaymentId);

                if ($payment->status !== 'paid') {
                    return;
                }

                if ($payment->voucher_id) {
                    $voucher = Voucher::query()->find($payment->voucher_id);
                    $templateName = $payment->voucherTemplate?->name;
                    $durationSeconds = $payment->voucherTemplate?->duration_seconds;
                    $quotaMb = $payment->voucherTemplate?->quota_mb;
                    $phoneNumber = $payment->phone_number;
                    return;
                }

                $template = $payment->voucherTemplate;
                if (! $template) {
                    return;
                }

                $batch = $voucherService->generateBatch(
                    $template->rate_limit,
                    $template->duration_seconds,
                    $template->quota_mb,
                    1,
                    true
                );

                $voucher = Voucher::query()->where('batch_id', $batch->id)->first();
                if (! $voucher) {
                    return;
                }

                $payment->update([
                    'voucher_id' => $voucher->id,
                ]);

                $templateName = $template->name;
                $durationSeconds = $template->duration_seconds;
                $quotaMb = $template->quota_mb;
                $phoneNumber = $payment->phone_number;
            });

            if (! $payment || $payment->status !== 'paid' || ! $voucher || ! is_string($phoneNumber) || $phoneNumber === '') {
                return;
            }

            $message = "*🎉 Pembayaran Berhasil!*\n\n";
            $message .= "*Paket:* {$templateName}\n";
            if ($durationSeconds) {
                $message .= "*Durasi:* " . $this->formatDuration((int) $durationSeconds) . "\n";
            }
            if ($quotaMb) {
                $message .= "*Kuota:* " . number_format((float) $quotaMb, 0, ',', '.') . " MB\n";
            }
            $message .= "\n";
            $message .= "*Username:* `{$voucher->username}`\n";
            $message .= "*Password:* `{$voucher->password}`\n";
            $message .= "\n";
            $message .= "Gunakan username dan password di atas untuk login ke hotspot!";

            SendWhatsAppMessageJob::dispatch($phoneNumber, $message);
        } catch (\Throwable $e) {
            Log::error('FulfillVoucherPaymentJob failed', [
                'voucher_payment_id' => $this->voucherPaymentId,
                'error' => $e->getMessage(),
            ]);
            $this->release(120);
        }
    }

    private function formatDuration(int $seconds): string
    {
        if ($seconds >= 86400) {
            $days = floor($seconds / 86400);
            return $days . ' hari';
        }
        if ($seconds >= 3600) {
            $hours = floor($seconds / 3600);
            return $hours . ' jam';
        }
        if ($seconds >= 60) {
            $minutes = floor($seconds / 60);
            return $minutes . ' menit';
        }
        return $seconds . ' detik';
    }
}

