@props([
    'value',
    'max',
    'color' => 'primary',
])

@php
    $percentage = $max > 0 ? min(100, ($value / $max) * 100) : 0;
@endphp

<div class="progress" style="height: 8px;">
    <div class="progress-bar bg-{{ $color }} progress-bar-striped progress-bar-animated" 
         role="progressbar" 
         style="width: {{ $percentage }}%" 
         aria-valuenow="{{ $value }}" 
         aria-valuemin="0" 
         aria-valuemax="{{ $max }}">
    </div>
</div>

<small class="text-muted mt-1 d-block">
    {{ number_format($value, 0, ',', '.') }} / {{ number_format($max, 0, ',', '.') }}
</small>