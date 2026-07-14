@props([
    'icon' => 'fa-inbox',
    'title' => 'Belum Ada Data',
    'subtitle' => null,
])

<div class="text-center py-8">
    <i class="fas {{ $icon }} fa-4x text-muted mb-4"></i>
    <h5 class="fw-semibold text-muted">{{ $title }}</h5>
    @if($subtitle)
        <p class="text-muted">{{ $subtitle }}</p>
    @endif
</div>