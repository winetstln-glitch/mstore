@props([
    'icon',
    'title',
    'value',
    'subtitle',
    'borderColor' => 'primary',
    'bgColor' => 'primary',
])

@php
    $borderColors = [
        'primary' => 'border-primary',
        'success' => 'border-success',
        'danger' => 'border-danger',
        'warning' => 'border-warning',
        'info' => 'border-info',
        'secondary' => 'border-secondary',
    ];
    $bgColors = [
        'primary' => 'bg-primary-subtle',
        'success' => 'bg-success-subtle',
        'danger' => 'bg-danger-subtle',
        'warning' => 'bg-warning-subtle',
        'info' => 'bg-info-subtle',
        'secondary' => 'bg-secondary-subtle',
    ];
    $textColors = [
        'primary' => 'text-primary',
        'success' => 'text-success',
        'danger' => 'text-danger',
        'warning' => 'text-warning',
        'info' => 'text-info',
        'secondary' => 'text-secondary',
    ];
@endphp

<div class="card h-100 shadow-sm border-0 border-start border-4 {{ $borderColors[$borderColor] }} transition-all duration-200 hover:translate-y-[-2px] hover:shadow-md">
    <div class="card-body">
        <div class="d-flex align-items-center mb-2">
            <div class="p-2 rounded-3 {{ $bgColors[$bgColor] }} me-3">
                <i class="fas {{ $icon }} {{ $textColors[$borderColor] }} fs-4"></i>
            </div>
            <div>
                <h6 class="mb-0 text-muted fw-semibold small">{{ $title }}</h6>
            </div>
        </div>
        <h4 class="mb-0 fw-bold">{{ $value }}</h4>
        <small class="text-muted mt-1 d-block">{{ $subtitle }}</small>
    </div>
</div>