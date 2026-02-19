@extends('layouts.app')

@section('title', 'Pembayaran Invoice')

@section('content')
<div class="container py-4">
    <div class="card shadow-sm">
        <div class="card-body">
            <h5 class="card-title">Bayar Invoice</h5>
            <p class="mb-1"><strong>Kode:</strong> {{ $invoice->code }}</p>
            <p class="mb-1"><strong>Jumlah:</strong> Rp {{ number_format($invoice->amount, 0, ',', '.') }}</p>
            <hr>
            <button id="pay-button" class="btn btn-primary">
                <i class="fa-solid fa-credit-card me-1"></i> Lanjutkan Pembayaran
            </button>
            <a href="{{ route('client.invoices.index') }}" class="btn btn-link">Kembali</a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ $snapJs }}" data-client-key="{{ $clientKey }}"></script>
<script>
document.getElementById('pay-button').addEventListener('click', function () {
    window.snap.pay(@json($token), {
        onSuccess: function (result) {
            window.location.href = "{{ route('client.invoices.index') }}";
        },
        onPending: function (result) {
            window.location.href = "{{ route('client.invoices.index') }}";
        },
        onError: function (result) {
            alert('Pembayaran gagal. Silakan coba lagi.');
        },
        onClose: function () {
            // Window closed without payment
        }
    });
});
</script>
@endpush
