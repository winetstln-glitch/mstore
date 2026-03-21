@extends('layouts.app')

@section('title', __('Detail Transaksi'))

@php
// Logic as per receipt requirements
$generalStoreName = \App\Models\Setting::getValue('store_name', config('app.name', 'MStore'));
$generalStoreAddress = \App\Models\Setting::getValue('store_address', 'Jl. Contoh No. 1');
$generalStorePhone = \App\Models\Setting::getValue('store_phone', '081234567890');
$generalStoreLogo = \App\Models\Setting::getValue('store_logo', '');
$generalStoreLogo = $generalStoreLogo && !str_starts_with($generalStoreLogo, 'http') && !str_starts_with($generalStoreLogo, 'data:') && !str_starts_with($generalStoreLogo, '/')
? asset($generalStoreLogo)
: $generalStoreLogo;

$receiptStoreName = \App\Models\Setting::getValue('wash_store_name', $generalStoreName ?: 'AUTO WASH');
$receiptStoreAddress = \App\Models\Setting::getValue('wash_store_address', $generalStoreAddress ?: 'Jl. Contoh Bersih No. 123');
$receiptStoreLogo = \App\Models\Setting::getValue('wash_store_logo', $generalStoreLogo);
$receiptStoreLogo = $receiptStoreLogo && !str_starts_with($receiptStoreLogo, 'http') && !str_starts_with($receiptStoreLogo, 'data:') && !str_starts_with($receiptStoreLogo, '/')
? asset($receiptStoreLogo)
: $receiptStoreLogo;
$cashierName = trim((string) ($transaction->user->name ?? '-'));
$cashierName = $cashierName !== '' ? $cashierName : '-';

$customerName = trim((string) ($transaction->customer_name ?? ''));
$customerName = $customerName !== '' ? $customerName : '-';
$vehiclePlate = strtoupper(trim((string) ($transaction->vehicle_plate ?? '')));
$vehiclePlate = $vehiclePlate !== '' ? $vehiclePlate : '-';
$discountLabel = __('Diskon');
if (($transaction->notes ?? null) === 'bonus_cuci_10x') {
    $discountLabel = 'Bonus Cuci 10x';
} elseif (($transaction->notes ?? null) === 'voucher_free_wash') {
    $discountLabel = 'Voucher Cuci Gratis';
}
$showDiscountType = !empty($transaction->notes) || (($transaction->discount_amount ?? 0) > 0);


@endphp

@section('content')

<div class="container-fluid py-3 py-md-4 wash-transaction-show-page">
    <div class="receipt-shell mx-auto">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 receipt-top-actions">
            <a href="{{ route('wash.transactions.index') }}" class="btn btn-outline-secondary btn-sm px-3">
                <i class="fa-solid fa-arrow-left me-1"></i>{{ __('Kembali') }}
            </a>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('wash.transactions.receipt', $transaction) }}" target="_blank" class="btn btn-primary btn-sm px-3">
                    <i class="fa-solid fa-print me-1"></i>{{ __('Cetak Struk') }}
                </a>
                <button type="button" class="btn btn-share-dana btn-sm px-3" onclick="shareWashReceiptNow(this)">
                    <i class="fa-solid fa-share-nodes me-1"></i>{{ __('Bagikan') }}
                </button>
            </div>
        </div>

        <div class="receipt-card" id="washReceiptCapture">
            <div class="receipt-watermark" aria-hidden="true">
                @if($receiptStoreLogo)
                    <img src="{{ $receiptStoreLogo }}" alt="{{ $receiptStoreName }}">
                @else
                    <span>{{ strtoupper($receiptStoreName) }}</span>
                @endif
            </div>
            <div class="receipt-brand">
                <div class="receipt-brand-logo">
                    @if($receiptStoreLogo)
                        <img src="{{ $receiptStoreLogo }}" alt="{{ $receiptStoreName }}">
                    @else
                        <span>{{ strtoupper(mb_substr($receiptStoreName, 0, 2)) }}</span>
                    @endif
                </div>
                <div class="receipt-brand-meta">
                    <div class="receipt-brand-name">{{ $receiptStoreName }}</div>
                    <div class="receipt-brand-address">{{ $receiptStoreAddress }}</div>
                </div>
            </div>
            <div class="receipt-premium-badge">{{ __('Kasir') }}: {{ $cashierName }}</div>
            <div class="receipt-status text-center">
                <div class="receipt-status-icon mx-auto mb-2">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <div class="receipt-status-title">{{ __('Transaksi Berhasil') }}</div>
                <div class="receipt-status-subtitle">{{ __('Struk Digital') }}</div>
            </div>

            <div class="receipt-highlight">
                <div class="receipt-code">
                    <div class="receipt-label">{{ __('ID Transaksi') }}</div>
                    <div class="receipt-id">#{{ $transaction->transaction_number }}</div>
                    <div class="receipt-meta">{{ $transaction->created_at->format('d M Y, H:i') }}</div>
                </div>
                <div class="receipt-total">
                    <div class="receipt-label">{{ __('Total Pembayaran') }}</div>
                    <div class="receipt-amount">Rp {{ number_format($transaction->total_amount, 0, ',', '.') }}</div>
                </div>
            </div>

            <div class="receipt-context">
                <div class="context-line"><span>{{ __('Toko') }}</span><span>{{ $receiptStoreName }}</span></div>
                <div class="context-line"><span>{{ __('Pelanggan / Kendaraan') }}</span><span>{{ $customerName }} / {{ $vehiclePlate }}</span></div>
                @if(!empty($transaction->queue_number))
                <div class="context-line"><span>{{ __('Nomor Antrean') }}</span><span>#{{ $transaction->queue_number }}</span></div>
                @endif
                @if($showDiscountType)
                <div class="context-line"><span>Jenis Diskon</span><span>{{ $discountLabel }}</span></div>
                @endif
            </div>

            <div class="receipt-item-panel">
                <div class="receipt-item-title">{{ __('Detail Item') }}</div>
                <div class="table-responsive table-responsive-mobile">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('Layanan') }}</th>
                                <th class="text-center">{{ __('Qty') }}</th>
                                <th class="text-end">{{ __('Harga Satuan') }}</th>
                                <th class="text-end">{{ __('Subtotal') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($transaction->items as $item)
                            <tr>
                                <td data-label="{{ __('Layanan') }}">
                                    <div class="fw-semibold">{{ $item->service_name }}</div>
                                    <div class="small text-muted">#{{ $item->id }}</div>
                                </td>
                                <td class="text-center" data-label="{{ __('Qty') }}">{{ (float) $item->quantity }}</td>
                                <td class="text-end" data-label="{{ __('Harga Satuan') }}">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                                <td class="text-end fw-bold" data-label="{{ __('Subtotal') }}">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="receipt-summary">
                @if(($transaction->discount_amount ?? 0) > 0)
                <div class="summary-line">
                    <span>{{ $discountLabel }}</span>
                    <span class="text-danger">-Rp {{ number_format($transaction->discount_amount, 0, ',', '.') }}</span>
                </div>
                @endif
                <div class="summary-line">
                    <span>{{ __('Metode Pembayaran') }}</span>
                    <span>{{ strtoupper($transaction->payment_method ?? 'CASH') }}</span>
                </div>
                @if(($transaction->cash_amount ?? 0) > 0)
                <div class="summary-line">
                    <span>{{ __('Uang Diterima') }}</span>
                    <span>Rp {{ number_format($transaction->cash_amount, 0, ',', '.') }}</span>
                </div>
                <div class="summary-line">
                    <span>{{ __('Kembalian') }}</span>
                    <span>Rp {{ number_format($transaction->change_amount, 0, ',', '.') }}</span>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
<style>
    .wash-transaction-show-page .receipt-shell {
        max-width: 860px;
    }

    .wash-transaction-show-page .btn-share-dana {
        border: 1px solid rgba(59, 130, 246, 0.25);
        background: linear-gradient(135deg, rgba(37, 99, 235, 0.14), rgba(37, 99, 235, 0.04));
        color: #1d4ed8;
        font-weight: 600;
        border-radius: 10px;
    }

    .wash-transaction-show-page .receipt-card {
        background: var(--bs-body-bg);
        border: 1px solid var(--bs-border-color);
        border-radius: 1rem 1rem 0 0;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
        padding: 1.1rem 1.1rem 1.4rem;
        position: relative;
        overflow: visible;
        font-family: Inter, 'Segoe UI', Roboto, Arial, sans-serif;
        color: #0f172a;
        text-rendering: geometricPrecision;
        -webkit-font-smoothing: antialiased;
    }

    .wash-transaction-show-page .receipt-card > * {
        position: relative;
        z-index: 2;
    }

    .wash-transaction-show-page .receipt-card.capture-mode {
        --bs-body-bg: #ffffff;
        --bs-body-color: #0f172a;
        --bs-emphasis-color: #0f172a;
        --bs-secondary-color: #475569;
        --bs-border-color: #cbd5e1;
        --bs-tertiary-bg: #f8fafc;
        --bs-table-bg: transparent;
        --bs-table-color: #0f172a;
        --bs-table-border-color: #cbd5e1;
        --bs-table-striped-bg: #f8fafc;
        --bs-table-striped-color: #0f172a;
        background: radial-gradient(circle at 100% -20%, rgba(37, 99, 235, 0.15), transparent 42%),
                    radial-gradient(circle at -10% 115%, rgba(14, 165, 233, 0.14), transparent 40%),
                    linear-gradient(165deg, #ffffff 0%, #f8fbff 45%, #f3f7ff 100%);
    }

    .wash-transaction-show-page .receipt-card.capture-mode .table,
    .wash-transaction-show-page .receipt-card.capture-mode .table th,
    .wash-transaction-show-page .receipt-card.capture-mode .table td {
        background: transparent !important;
        color: #0f172a !important;
        border-color: #cbd5e1 !important;
    }

    .wash-transaction-show-page .receipt-card.capture-mode .text-muted {
        color: #64748b !important;
    }

    .wash-transaction-show-page .receipt-card.capture-mode.capture-thermal {
        width: 80mm;
        max-width: 80mm;
        min-width: 80mm;
        margin: 0 auto;
        border-radius: 0.75rem 0.75rem 0 0;
        padding: 0.8rem 0.72rem 1rem;
    }

    .wash-transaction-show-page .receipt-card.capture-mode::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 6px;
        border-radius: 0.75rem 0.75rem 0 0;
        background: linear-gradient(90deg, #2563eb, #0ea5e9, #22c55e);
        z-index: 3;
    }

    .wash-transaction-show-page .receipt-brand {
        display: grid;
        grid-template-columns: auto 1fr auto;
        align-items: center;
        gap: 0.75rem;
        padding: 0.6rem 0.7rem;
        border: 1px solid rgba(59, 130, 246, 0.2);
        border-radius: 0.8rem;
        margin-bottom: 0.9rem;
        background: rgba(255, 255, 255, 0.78);
    }

    .wash-transaction-show-page .receipt-brand-logo {
        width: 44px;
        height: 44px;
        border-radius: 0.7rem;
        overflow: hidden;
        border: 1px solid rgba(148, 163, 184, 0.35);
        background: #ffffff;
        display: grid;
        place-items: center;
        flex-shrink: 0;
    }

    .wash-transaction-show-page .receipt-brand-logo img {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }

    .wash-transaction-show-page .receipt-brand-logo span {
        font-weight: 800;
        color: #1d4ed8;
        font-size: 0.95rem;
        letter-spacing: 0.08em;
    }

    .wash-transaction-show-page .receipt-brand-name {
        font-size: 0.9rem;
        font-weight: 800;
        line-height: 1.2;
    }

    .wash-transaction-show-page .receipt-brand-address {
        font-size: 0.72rem;
        color: #334155;
        line-height: 1.35;
    }

    .wash-transaction-show-page .receipt-cashier-chip {
        display: grid;
        justify-items: end;
        align-content: center;
        gap: 0.1rem;
        border-radius: 10px;
        padding: 0.33rem 0.68rem;
        background: rgba(37, 99, 235, 0.1);
        border: 1px solid rgba(37, 99, 235, 0.22);
    }

    .wash-transaction-show-page .receipt-cashier-chip span {
        font-size: 0.62rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #1e3a8a;
        font-weight: 700;
    }

    .wash-transaction-show-page .receipt-cashier-chip strong {
        font-size: 0.78rem;
        line-height: 1.1;
        color: #0f172a;
    }

    .wash-transaction-show-page .receipt-premium-badge {
        margin: 0 0 0.8rem;
        font-size: 0.63rem;
        font-weight: 800;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        color: #1e40af;
        background: rgba(219, 234, 254, 0.7);
        border: 1px solid rgba(59, 130, 246, 0.25);
        border-radius: 10px;
        width: fit-content;
        padding: 0.25rem 0.55rem;
    }

    .wash-transaction-show-page .receipt-watermark {
        position: absolute;
        inset: 0;
        display: grid;
        place-items: center;
        pointer-events: none;
        z-index: 1;
        opacity: 0;
        transition: opacity 0.2s ease;
    }

    .wash-transaction-show-page .receipt-card.capture-mode .receipt-watermark {
        opacity: 0.1;
    }

    .wash-transaction-show-page .receipt-watermark img {
        width: min(240px, 58%);
        max-height: 180px;
        object-fit: contain;
        filter: grayscale(0.1);
    }

    .wash-transaction-show-page .receipt-watermark span {
        font-size: clamp(1.3rem, 3.2vw, 2rem);
        font-weight: 800;
        letter-spacing: 0.2em;
        color: #1d4ed8;
        transform: rotate(-18deg);
    }

    .wash-transaction-show-page .receipt-card::after {
        content: '';
        position: absolute;
        left: 0;
        right: 0;
        bottom: -14px;
        height: 14px;
        background: linear-gradient(-45deg, transparent 10px, var(--bs-body-bg) 0) 0 0 / 14px 14px,
                    linear-gradient(45deg, transparent 10px, var(--bs-body-bg) 0) 0 0 / 14px 14px;
    }

    .wash-transaction-show-page .receipt-status {
        border-bottom: 1px dashed var(--bs-border-color);
        padding-bottom: 0.9rem;
        margin-bottom: 1rem;
    }

    .wash-transaction-show-page .receipt-status-icon {
        width: 78px;
        height: 78px;
        border-radius: 10px;
        display: grid;
        place-items: center;
        color: #16a34a;
        background: rgba(22, 163, 74, 0.14);
        font-size: 2.3rem;
    }

    .wash-transaction-show-page .receipt-status-title {
        font-weight: 700;
        font-size: 1.1rem;
    }

    .wash-transaction-show-page .receipt-status-subtitle {
        font-size: 0.84rem;
        color: var(--bs-secondary-color);
    }

    .wash-transaction-show-page .receipt-highlight {
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 0.75rem;
        margin-bottom: 0.8rem;
        padding: 0.85rem;
        border-radius: 0.85rem;
        border: 1px solid rgba(59, 130, 246, 0.25);
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.08), rgba(59, 130, 246, 0.02));
    }

    .wash-transaction-show-page .receipt-label {
        font-size: 0.72rem;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: var(--bs-secondary-color);
        font-weight: 600;
    }

    .wash-transaction-show-page .receipt-id {
        font-size: 1.2rem;
        font-weight: 800;
        line-height: 1.2;
    }

    .wash-transaction-show-page .receipt-meta {
        color: #334155;
        font-size: 0.82rem;
        font-weight: 600;
    }

    .wash-transaction-show-page .receipt-amount {
        font-size: 1.52rem;
        font-weight: 800;
        color: #2563eb;
        white-space: nowrap;
    }

    .wash-transaction-show-page .receipt-context {
        border: 1px dashed var(--bs-border-color);
        border-radius: 0.75rem;
        padding: 0.65rem 0.75rem;
        margin-bottom: 0.9rem;
        display: grid;
        gap: 0.4rem;
    }

    .wash-transaction-show-page .context-line {
        display: flex;
        justify-content: space-between;
        gap: 0.75rem;
        font-size: 0.84rem;
    }

    .wash-transaction-show-page .context-line span:last-child {
        text-align: right;
        font-weight: 700;
    }

    .wash-transaction-show-page .receipt-item-panel {
        border: 1px solid var(--bs-border-color);
        border-radius: 0.85rem;
        overflow: hidden;
        margin-bottom: 0.9rem;
    }

    .wash-transaction-show-page .receipt-item-title {
        padding: 0.7rem 0.85rem;
        font-weight: 800;
        font-size: 0.9rem;
        border-bottom: 1px solid var(--bs-border-color);
        background: rgba(148, 163, 184, 0.08);
    }

    .wash-transaction-show-page .table th {
        font-size: 0.76rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #334155;
        font-weight: 800;
    }

    .wash-transaction-show-page .table td,
    .wash-transaction-show-page .table th {
        border-color: var(--bs-border-color);
        padding: 0.7rem 0.8rem;
        vertical-align: middle;
        line-height: 1.35;
        word-break: break-word;
    }

    .wash-transaction-show-page .receipt-item-panel .table {
        width: 100%;
        table-layout: fixed;
    }

    .wash-transaction-show-page .receipt-item-panel .table th:nth-child(1),
    .wash-transaction-show-page .receipt-item-panel .table td:nth-child(1) {
        width: 38%;
    }

    .wash-transaction-show-page .receipt-summary {
        border-top: 1px dashed var(--bs-border-color);
        padding-top: 0.8rem;
        display: grid;
        gap: 0.38rem;
    }

    .wash-transaction-show-page .summary-line {
        display: flex;
        justify-content: space-between;
        gap: 0.75rem;
        font-size: 0.92rem;
        color: #1e293b;
    }

    .wash-transaction-show-page .summary-line span:last-child {
        font-weight: 700;
        text-align: right;
    }

    .wash-transaction-show-page .receipt-card.capture-mode.capture-thermal .receipt-brand-name {
        font-size: 0.76rem;
    }

    .wash-transaction-show-page .receipt-card.capture-mode.capture-thermal .receipt-brand-address {
        font-size: 0.6rem;
    }

    .wash-transaction-show-page .receipt-card.capture-mode.capture-thermal .receipt-id {
        font-size: 0.9rem;
    }

    .wash-transaction-show-page .receipt-card.capture-mode.capture-thermal .receipt-amount {
        font-size: 1.08rem;
    }

    .wash-transaction-show-page .receipt-card.capture-mode.capture-thermal .receipt-status-title {
        font-size: 0.96rem;
    }

    .wash-transaction-show-page .receipt-card.capture-mode.capture-thermal .receipt-status-subtitle,
    .wash-transaction-show-page .receipt-card.capture-mode.capture-thermal .receipt-label,
    .wash-transaction-show-page .receipt-card.capture-mode.capture-thermal .receipt-meta {
        font-size: 0.68rem;
    }

    .wash-transaction-show-page .receipt-card.capture-mode.capture-thermal .receipt-item-title {
        font-size: 0.78rem;
    }

    .wash-transaction-show-page .receipt-card.capture-mode.capture-thermal .table th {
        font-size: 0.58rem;
    }

    .wash-transaction-show-page .receipt-card.capture-mode.capture-thermal .table td {
        font-size: 0.68rem;
        padding: 0.45rem 0.38rem;
    }

    .wash-transaction-show-page .receipt-card.capture-mode.capture-thermal .receipt-item-panel .table td:nth-child(2),
    .wash-transaction-show-page .receipt-card.capture-mode.capture-thermal .receipt-item-panel .table td:nth-child(3),
    .wash-transaction-show-page .receipt-card.capture-mode.capture-thermal .receipt-item-panel .table td:nth-child(4) {
        font-size: 0.62rem;
    }

    .wash-transaction-show-page .receipt-card.capture-mode.capture-thermal .receipt-item-panel .table {
        width: 100%;
        table-layout: fixed;
    }

    .wash-transaction-show-page .receipt-card.capture-mode.capture-thermal .receipt-item-panel .table th,
    .wash-transaction-show-page .receipt-card.capture-mode.capture-thermal .receipt-item-panel .table td {
        line-height: 1.24;
        white-space: normal;
        word-break: break-word;
        vertical-align: top;
    }

    .wash-transaction-show-page .receipt-card.capture-mode.capture-thermal .receipt-item-panel .table th:nth-child(1),
    .wash-transaction-show-page .receipt-card.capture-mode.capture-thermal .receipt-item-panel .table td:nth-child(1) {
        width: 42%;
    }

    .wash-transaction-show-page .receipt-card.capture-mode.capture-thermal .summary-line,
    .wash-transaction-show-page .receipt-card.capture-mode.capture-thermal .context-line {
        font-size: 0.7rem;
    }

    .wash-transaction-show-page .receipt-card.capture-mode.capture-thermal .receipt-cashier-chip span {
        font-size: 0.56rem;
    }

    .wash-transaction-show-page .receipt-card.capture-mode.capture-thermal .receipt-cashier-chip strong {
        font-size: 0.7rem;
    }

    [data-bs-theme="dark"] .wash-transaction-show-page .btn-share-dana {
        border-color: rgba(96, 165, 250, 0.38);
        background: linear-gradient(135deg, rgba(37, 99, 235, 0.28), rgba(30, 64, 175, 0.24));
        color: #dbeafe;
    }

    [data-bs-theme="dark"] .wash-transaction-show-page .receipt-card {
        background: #111827;
        border-color: #334155;
        color: #e2e8f0;
        box-shadow: 0 16px 34px rgba(2, 6, 23, 0.45);
    }

    [data-bs-theme="dark"] .wash-transaction-show-page .receipt-brand {
        background: rgba(15, 23, 42, 0.86);
        border-color: rgba(96, 165, 250, 0.36);
    }

    [data-bs-theme="dark"] .wash-transaction-show-page .receipt-brand-logo {
        background: rgba(15, 23, 42, 0.95);
        border-color: rgba(148, 163, 184, 0.5);
    }

    [data-bs-theme="dark"] .wash-transaction-show-page .receipt-cashier-chip {
        background: rgba(30, 64, 175, 0.34);
        border-color: rgba(147, 197, 253, 0.52);
    }

    [data-bs-theme="dark"] .wash-transaction-show-page .receipt-cashier-chip span {
        color: #bfdbfe;
    }

    [data-bs-theme="dark"] .wash-transaction-show-page .receipt-cashier-chip strong {
        color: #f8fafc;
    }

    [data-bs-theme="dark"] .wash-transaction-show-page .receipt-brand-address,
    [data-bs-theme="dark"] .wash-transaction-show-page .receipt-meta,
    [data-bs-theme="dark"] .wash-transaction-show-page .table th,
    [data-bs-theme="dark"] .wash-transaction-show-page .summary-line,
    [data-bs-theme="dark"] .wash-transaction-show-page .context-line,
    [data-bs-theme="dark"] .wash-transaction-show-page .receipt-label,
    [data-bs-theme="dark"] .wash-transaction-show-page .receipt-status-subtitle {
        color: #cbd5e1;
    }

    [data-bs-theme="dark"] .wash-transaction-show-page .receipt-item-title,
    [data-bs-theme="dark"] .wash-transaction-show-page .receipt-item-panel,
    [data-bs-theme="dark"] .wash-transaction-show-page .table td,
    [data-bs-theme="dark"] .wash-transaction-show-page .table th,
    [data-bs-theme="dark"] .wash-transaction-show-page .receipt-context,
    [data-bs-theme="dark"] .wash-transaction-show-page .receipt-summary,
    [data-bs-theme="dark"] .wash-transaction-show-page .receipt-status {
        border-color: #334155;
    }

    [data-bs-theme="dark"] .wash-transaction-show-page .receipt-item-title {
        background: rgba(30, 41, 59, 0.8);
    }

    [data-bs-theme="dark"] .wash-transaction-show-page .receipt-highlight {
        border-color: rgba(96, 165, 250, 0.45);
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.28), rgba(15, 23, 42, 0.35));
    }

    [data-bs-theme="dark"] .wash-transaction-show-page .receipt-card.capture-mode {
        background: radial-gradient(circle at 100% -20%, rgba(37, 99, 235, 0.15), transparent 42%),
                    radial-gradient(circle at -10% 115%, rgba(14, 165, 233, 0.14), transparent 40%),
                    linear-gradient(165deg, #ffffff 0%, #f8fbff 45%, #f3f7ff 100%);
        border-color: var(--bs-border-color);
        color: #0f172a;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
    }

    [data-bs-theme="dark"] .wash-transaction-show-page .receipt-card.capture-mode .receipt-brand {
        background: rgba(255, 255, 255, 0.78);
        border-color: rgba(59, 130, 246, 0.2);
    }

    [data-bs-theme="dark"] .wash-transaction-show-page .receipt-card.capture-mode .receipt-brand-logo {
        background: #ffffff;
        border-color: rgba(148, 163, 184, 0.35);
    }

    [data-bs-theme="dark"] .wash-transaction-show-page .receipt-card.capture-mode .receipt-cashier-chip {
        background: rgba(37, 99, 235, 0.1);
        border-color: rgba(37, 99, 235, 0.22);
    }

    [data-bs-theme="dark"] .wash-transaction-show-page .receipt-card.capture-mode .receipt-cashier-chip span {
        color: #1e3a8a;
    }

    [data-bs-theme="dark"] .wash-transaction-show-page .receipt-card.capture-mode .receipt-cashier-chip strong {
        color: #0f172a;
    }

    [data-bs-theme="dark"] .wash-transaction-show-page .receipt-card.capture-mode .receipt-brand-address,
    [data-bs-theme="dark"] .wash-transaction-show-page .receipt-card.capture-mode .receipt-meta,
    [data-bs-theme="dark"] .wash-transaction-show-page .receipt-card.capture-mode .table th,
    [data-bs-theme="dark"] .wash-transaction-show-page .receipt-card.capture-mode .summary-line,
    [data-bs-theme="dark"] .wash-transaction-show-page .receipt-card.capture-mode .context-line,
    [data-bs-theme="dark"] .wash-transaction-show-page .receipt-card.capture-mode .receipt-label {
        color: #334155;
    }

    [data-bs-theme="dark"] .wash-transaction-show-page .receipt-card.capture-mode .receipt-item-title,
    [data-bs-theme="dark"] .wash-transaction-show-page .receipt-card.capture-mode .receipt-item-panel,
    [data-bs-theme="dark"] .wash-transaction-show-page .receipt-card.capture-mode .table td,
    [data-bs-theme="dark"] .wash-transaction-show-page .receipt-card.capture-mode .table th,
    [data-bs-theme="dark"] .wash-transaction-show-page .receipt-card.capture-mode .receipt-context,
    [data-bs-theme="dark"] .wash-transaction-show-page .receipt-card.capture-mode .receipt-summary,
    [data-bs-theme="dark"] .wash-transaction-show-page .receipt-card.capture-mode .receipt-status {
        border-color: var(--bs-border-color);
    }

    [data-bs-theme="dark"] .wash-transaction-show-page .receipt-card.capture-mode .receipt-item-title {
        background: rgba(148, 163, 184, 0.08);
    }

    [data-bs-theme="dark"] .wash-transaction-show-page .receipt-card.capture-mode .receipt-highlight {
        border-color: rgba(59, 130, 246, 0.25);
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.08), rgba(59, 130, 246, 0.02));
    }

    @media (max-width: 767.98px) {
        .wash-transaction-show-page {
            padding-left: 0;
            padding-right: 0;
        }

        .wash-transaction-show-page .receipt-shell {
            max-width: 100%;
        }

        .wash-transaction-show-page .receipt-top-actions {
            position: sticky;
            top: 0;
            z-index: 20;
            padding: 0.6rem 0.75rem 0.55rem;
            margin-bottom: 0.65rem !important;
            background: color-mix(in oklab, var(--bs-body-bg) 90%, transparent);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--bs-border-color);
        }

        .wash-transaction-show-page .receipt-top-actions > div {
            width: 100%;
        }

        .wash-transaction-show-page .receipt-top-actions .btn {
            flex: 1 1 0;
            justify-content: center;
            display: inline-flex;
            align-items: center;
            min-height: 40px;
            border-radius: 0.8rem;
            font-size: 0.84rem;
            font-weight: 700;
        }

        .wash-transaction-show-page .receipt-card {
            border-radius: 1rem 1rem 0 0;
            border-left: 0;
            border-right: 0;
            padding: 1rem 0.86rem 1.2rem;
            box-shadow: 0 6px 24px rgba(15, 23, 42, 0.12);
        }

        .wash-transaction-show-page .receipt-card::after {
            height: 10px;
            bottom: -10px;
            background: linear-gradient(-45deg, transparent 8px, var(--bs-body-bg) 0) 0 0 / 10px 10px,
                        linear-gradient(45deg, transparent 8px, var(--bs-body-bg) 0) 0 0 / 10px 10px;
        }

        .wash-transaction-show-page .receipt-brand {
            grid-template-columns: 1fr;
        }

        .wash-transaction-show-page .receipt-cashier-chip {
            justify-items: start;
            width: fit-content;
        }

        .wash-transaction-show-page .receipt-highlight {
            grid-template-columns: 1fr;
        }

        .wash-transaction-show-page .receipt-amount {
            font-size: 1.2rem;
        }

        .wash-transaction-show-page .table-responsive-mobile .table {
            width: 100%;
            table-layout: fixed;
        }

        .wash-transaction-show-page .table-responsive-mobile .table thead {
            display: none;
        }

        .wash-transaction-show-page .table-responsive-mobile .table tbody,
        .wash-transaction-show-page .table-responsive-mobile .table tr,
        .wash-transaction-show-page .table-responsive-mobile .table td {
            display: block;
            width: 100%;
        }

        .wash-transaction-show-page .table-responsive-mobile .table tr {
            border-top: 1px solid var(--bs-border-color);
            padding: 0.42rem 0.5rem 0.45rem;
        }

        .wash-transaction-show-page .table-responsive-mobile .table tbody tr:first-child {
            border-top: 0;
        }

        .wash-transaction-show-page .table-responsive-mobile .table td {
            border: 0;
            padding: 0.22rem 0;
            line-height: 1.3;
            font-size: 0.73rem;
            word-break: break-word;
            white-space: normal;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 0.6rem;
            text-align: right !important;
        }

        .wash-transaction-show-page .table-responsive-mobile .table td::before {
            content: attr(data-label);
            flex: 0 0 42%;
            max-width: 42%;
            text-align: left;
            font-size: 0.62rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #64748b;
        }

        .wash-transaction-show-page .table-responsive-mobile .table td:first-child {
            display: block;
            text-align: left !important;
            padding: 0 0 0.36rem;
            margin-bottom: 0.34rem;
            border-bottom: 1px dashed var(--bs-border-color);
        }

        .wash-transaction-show-page .table-responsive-mobile .table td:first-child::before {
            display: none;
        }

        .wash-transaction-show-page .table-responsive-mobile .table td:first-child .small {
            font-size: 0.64rem;
        }

        .wash-transaction-show-page .receipt-card.capture-mode.capture-thermal .table thead {
            display: table-header-group;
        }

        .wash-transaction-show-page .receipt-card.capture-mode.capture-thermal .table tbody {
            display: table-row-group;
        }

        .wash-transaction-show-page .receipt-card.capture-mode.capture-thermal .table tr {
            display: table-row;
            border-top: 0;
            padding: 0;
        }

        .wash-transaction-show-page .receipt-card.capture-mode.capture-thermal .table td,
        .wash-transaction-show-page .receipt-card.capture-mode.capture-thermal .table th {
            display: table-cell;
            width: auto;
            border: 1px solid var(--bs-border-color);
            padding: 0.5rem 0.45rem;
        }

        .wash-transaction-show-page .receipt-card.capture-mode.capture-thermal .table td::before {
            display: none;
        }

        .wash-transaction-show-page .receipt-card.capture-mode.capture-thermal .table td:first-child {
            display: table-cell;
            text-align: left !important;
            padding: 0.5rem 0.45rem;
            margin-bottom: 0;
            border-bottom: 1px solid var(--bs-border-color);
        }
    }

@media print {
    @page {
        size: 80mm auto;
        margin: 4mm;
    }

    .wash-transaction-show-page {
        padding: 0 !important;
    }

    .wash-transaction-show-page .receipt-shell {
        max-width: none;
    }

    .wash-transaction-show-page .receipt-top-actions {
        display: none !important;
    }

    .wash-transaction-show-page .btn,
    .wash-transaction-show-page a.btn {
        display: none !important;
    }

    .wash-transaction-show-page .receipt-card {
        box-shadow: none;
        border: 0;
        border-radius: 0;
        padding: 0;
    }

    .wash-transaction-show-page .receipt-card::after {
        display: none;
    }

    .wash-transaction-show-page .receipt-amount {
        font-size: 1.1rem;
    }
}

</style>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
    const washReceiptPngName = @json('struk-wash-' . $transaction->transaction_number . '.png');

    async function shareWashReceiptNow(button) {
        const defaultLabel = button ? button.innerHTML : '';
        if (button) {
            button.disabled = true;
            button.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i>Mempersiapkan PNG';
        }

        try {
            const receiptFile = await buildWashReceiptFile();
            if (navigator.share && navigator.canShare && navigator.canShare({ files: [receiptFile] })) {
                await navigator.share({ files: [receiptFile] });
                return;
            }

            downloadWashReceiptFile(receiptFile);
            alert('{{ __('PNG struk diunduh.') }}');
        } catch (error) {
            alert('{{ __('Gagal menyiapkan PNG struk.') }}');
        } finally {
            if (button) {
                button.disabled = false;
                button.innerHTML = defaultLabel;
            }
        }
    }

    async function buildWashReceiptFile() {
        const captureTarget = document.getElementById('washReceiptCapture');
        if (!captureTarget || typeof html2canvas === 'undefined') {
            throw new Error('penangkapan struk tidak tersedia');
        }

        const htmlElement = document.documentElement;
        const currentTheme = htmlElement.getAttribute('data-bs-theme');
        if (currentTheme) {
            htmlElement.removeAttribute('data-bs-theme');
        }

        captureTarget.classList.add('capture-mode', 'capture-thermal');
        await new Promise((resolve) => window.requestAnimationFrame(() => window.requestAnimationFrame(resolve)));

        let canvas;
        try {
            canvas = await html2canvas(captureTarget, {
                useCORS: true,
                scale: 2,
                backgroundColor: null
            });
        } finally {
            captureTarget.classList.remove('capture-mode', 'capture-thermal');
            if (currentTheme) {
                htmlElement.setAttribute('data-bs-theme', currentTheme);
            }
        }

        const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/png'));
        if (!blob) {
            throw new Error('gagal membuat berkas gambar');
        }

        return new File([blob], washReceiptPngName, { type: 'image/png' });
    }

    function downloadWashReceiptFile(file) {
        const downloadUrl = URL.createObjectURL(file);
        const link = document.createElement('a');
        link.href = downloadUrl;
        link.download = file.name;
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.setTimeout(() => URL.revokeObjectURL(downloadUrl), 1000);
    }
</script>
@endsection
