@props([
    'status',
    'text' => null,
])

@php
    $statusConfig = [
        'pending' => ['bg' => 'bg-warning-subtle', 'text' => 'text-warning', 'border' => 'border-warning-subtle', 'text' => $text ?? 'Belum Diproses'],
        'processed' => ['bg' => 'bg-success-subtle', 'text' => 'text-success', 'border' => 'border-success-subtle', 'text' => $text ?? 'Sudah Diproses'],
        'active' => ['bg' => 'bg-warning-subtle', 'text' => 'text-warning', 'border' => 'border-warning-subtle', 'text' => $text ?? 'Aktif'],
        'closed' => ['bg' => 'bg-success-subtle', 'text' => 'text-success', 'border' => 'border-success-subtle', 'text' => $text ?? 'Selesai'],
    ];
    $config = $statusConfig[$status] ?? $statusConfig['pending'];
@endphp

<span class="badge {{ $config['bg'] }} {{ $config['text'] }} border {{ $config['border'] }} px-2 py-1 fw-semibold">
    {{ $config['text'] }}
</span>