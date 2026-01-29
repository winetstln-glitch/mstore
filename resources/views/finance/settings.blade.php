@extends('layouts.app')

@section('title', __('Finance Settings'))

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('Finance Settings') }}</h1>
        <a href="{{ route('finance.index') }}" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left me-1"></i> {{ __('Back to Dashboard') }}
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">{{ __('Commission & Allocation Rules') }}</h6>
        </div>
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <form action="{{ route('finance.settings.update') }}" method="POST">
                @csrf
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="commission_coordinator_percent" class="form-label">{{ __('Coordinator Commission (%)') }}</label>
                        <input type="number" step="0.01" class="form-control" id="commission_coordinator_percent" name="commission_coordinator_percent" value="{{ $coordRate }}" required>
                        <small class="text-muted">Percentage of Gross Revenue given to Coordinators.</small>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="commission_isp_percent" class="form-label">{{ __('ISP Allocation (%)') }}</label>
                        <input type="number" step="0.01" class="form-control" id="commission_isp_percent" name="commission_isp_percent" value="{{ $ispRate }}" required>
                        <small class="text-muted">Allocated for Internet Service Provider costs.</small>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="commission_tool_percent" class="form-label">{{ __('Tool/Management Fund (%)') }}</label>
                        <input type="number" step="0.01" class="form-control" id="commission_tool_percent" name="commission_tool_percent" value="{{ $toolRate }}" required>
                        <small class="text-muted">Allocated for Tools and Management expenses.</small>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="investor_cash_percent" class="form-label">{{ __('Investor Cash Fund (%)') }}</label>
                        <input type="number" step="0.01" class="form-control" id="investor_cash_percent" name="investor_cash_percent" value="{{ $investorCashRate }}" required>
                        <small class="text-muted">Percentage taken from Net Profit for Investor Cash Fund.</small>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="excluded_categories" class="form-label">{{ __('Excluded Expense Categories (Allocations)') }}</label>
                    <textarea class="form-control" id="excluded_categories" name="excluded_categories" rows="3" required>{{ $excludedCategoriesStr }}</textarea>
                    <small class="text-muted">Comma-separated list of expense categories that are treated as Allocations (not operational expenses). E.g., ISP Payment, Tool Fund.</small>
                </div>

                <div class="alert alert-info">
                    <i class="fa-solid fa-circle-info me-2"></i>
                    <strong>Note:</strong> 
                    Changing these percentages will affect future transactions. Past transactions are immutable.
                    Excluded categories affect how the Dashboard calculates "Total Expense" and "Net Balance".
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-save me-1"></i> {{ __('Save Settings') }}
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
