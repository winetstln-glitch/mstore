@extends('layouts.app')

@section('title', __('Hotspot Management'))

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">{{ __('Hotspot Management') }}</h1>
            <div class="text-muted small">{{ __('Manage Hotspot Vouchers and Users') }}</div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body text-center py-5">
            <div class="mb-3">
                <i class="fa-solid fa-wifi fa-3x text-muted"></i>
            </div>
            <h4 class="text-muted">{{ __('Module Under Development') }}</h4>
            <p class="text-muted">{{ __('This feature is coming soon.') }}</p>
        </div>
    </div>
@endsection
