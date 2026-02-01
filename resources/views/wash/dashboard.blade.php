@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('Wash Dashboard') }}</h1>
        @if(Auth::user()->hasPermission('wash.pos'))
        <a href="{{ route('wash.pos') }}" class="btn btn-primary">
            <i class="fas fa-cash-register me-2"></i>{{ __('Go to POS') }}
        </a>
        @endif
    </div>

    <div class="row">
        <!-- Earnings (Daily) Card Example -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                {{ __('Daily Sales') }}</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">Rp {{ number_format($dailySales, 0, ',', '.') }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Earnings (Monthly) Card Example -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                {{ __('Monthly Sales') }}</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">Rp {{ number_format($monthlySales, 0, ',', '.') }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transaction Count Card Example -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">{{ __('Transactions (Today)') }}
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $transactionCount }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('Top Services') }}</h6>
                </div>
                <div class="card-body">
                    @if($topServices->isEmpty())
                        <p class="text-center">{{ __('No transactions yet.') }}</p>
                    @else
                        <ul class="list-group">
                            @foreach($topServices as $service)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    {{ $service->service_name }}
                                    <span class="badge bg-primary rounded-pill">{{ $service->total_qty }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
