@props([
    'amount',
    'symbol' => 'Rp',
    'decimals' => 0,
])

<span class="fw-bold">
    {{ $symbol }} {{ number_format($amount, $decimals, ',', '.') }}
</span>