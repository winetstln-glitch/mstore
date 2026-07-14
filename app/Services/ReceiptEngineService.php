<?php

namespace App\Services;

use App\Models\Receipt;
use App\Models\ReceiptTemplate;
use App\Models\ReceiptActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ReceiptEngineService
{
    public function __construct() {}

    /**
     * Create a new receipt for a transaction
     */
    public function createReceipt(string $transactionType, int $transactionId, ?int $templateId = null): Receipt
    {
        $template = $templateId ? ReceiptTemplate::find($templateId) : ReceiptTemplate::getActiveForType($transactionType);

        $receipt = Receipt::create([
            'receipt_number' => Receipt::generateReceiptNumber(),
            'transaction_type' => $transactionType,
            'transaction_id' => $transactionId,
            'receipt_template_id' => $template?->id,
            'status' => 'valid',
            'verification_url' => route('receipt.verify', ['id' => '']), // will be filled later
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        $receipt->verification_url = route('receipt.verify', ['id' => $receipt->id]);
        $receipt->save();

        $this->logActivity($receipt, 'print');

        return $receipt;
    }

    /**
     * Log receipt activity
     */
    public function logActivity(Receipt $receipt, string $action, ?string $channel = null): ReceiptActivityLog
    {
        return ReceiptActivityLog::create([
            'receipt_id' => $receipt->id,
            'action' => $action,
            'channel' => $channel,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'user_id' => Auth::id(),
        ]);
    }

    /**
     * Verify a receipt
     */
    public function verifyReceipt(int $receiptId): array
    {
        $receipt = Receipt::with(['template', 'activityLogs'])->find($receiptId);

        if (!$receipt) {
            return [
                'success' => false,
                'message' => 'Receipt not found',
            ];
        }

        $this->logActivity($receipt, 'verify');

        return [
            'success' => true,
            'receipt' => $receipt,
        ];
    }
}
