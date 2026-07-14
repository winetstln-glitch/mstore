@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>{{ $investor->name }}</h2>
        <div>
            <a href="{{ route('investors.edit', $investor) }}" class="btn btn-warning">{{ __('Edit') }}</a>
            <a href="{{ route('investors.index') }}" class="btn btn-secondary">{{ __('Back') }}</a>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-white bg-primary mb-3">
                <div class="card-header">{{ __('Net Balance') }}</div>
                <div class="card-body">
                    <h3 class="card-title">Rp {{ number_format($balance, 0, ',', '.') }}</h3>
                    <p class="card-text">{{ __('Current active capital + profit share') }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-success mb-3">
                <div class="card-header">{{ __('Total Capital In') }}</div>
                <div class="card-body">
                    <h3 class="card-title">Rp {{ number_format($totalCapital, 0, ',', '.') }}</h3>
                    <p class="card-text">{{ __('Total investments made') }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-danger mb-3">
                <div class="card-header">{{ __('Total Withdrawals') }}</div>
                <div class="card-body">
                    <h3 class="card-title">Rp {{ number_format($totalWithdrawal, 0, ',', '.') }}</h3>
                    <p class="card-text">{{ __('Capital withdrawals / Profit sharing') }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">{{ __('Investor Details') }}</div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>{{ __('Coordinator') }}:</strong> {{ $investor->coordinator->name }}</p>
                    <p><strong>{{ __('Phone') }}:</strong> {{ $investor->phone ?? '-' }}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>{{ __('Description') }}:</strong> {{ $investor->description ?? '-' }}</p>
                    <p><strong>{{ __('Joined Date') }}:</strong> {{ $investor->created_at->format('d M Y') }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">{{ __('Transaction History') }}</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead>
                        <tr>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Type') }}</th>
                            <th>{{ __('Category') }}</th>
                            <th>{{ __('Description') }}</th>
                            <th class="text-end">{{ __('Amount') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($transactions as $transaction)
                            <tr>
                                <td>{{ $transaction->transaction_date->format('d M Y') }}</td>
                                <td>
                                    <span class="badge bg-{{ $transaction->type === 'income' ? 'success' : 'danger' }}">
                                        {{ ucfirst($transaction->type) }}
                                    </span>
                                </td>
                                <td><span class="badge bg-secondary">{{ $transaction->category }}</span></td>
                                <td>{{ $transaction->description }}</td>
                                <td class="text-end fw-bold {{ $transaction->type === 'income' ? 'text-success' : 'text-danger' }}">
                                    {{ $transaction->type === 'income' ? '+' : '-' }} Rp {{ number_format($transaction->amount, 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">{{ __('No transactions found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $transactions->links() }}
        </div>
    </div>
</div>
@endsection
