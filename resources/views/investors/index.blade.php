@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>{{ __('Investors') }}</h2>
        <a href="{{ route('investors.create') }}" class="btn btn-primary">{{ __('Add Investor') }}</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Coordinator') }}</th>
                            <th>{{ __('Total Investment') }}</th>
                            <th>{{ __('Balance') }}</th>
                            <th>{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($investors as $investor)
                            <tr>
                                <td>{{ $investor->name }}</td>
                                <td>{{ $investor->coordinator->name }}</td>
                                <td>Rp {{ number_format($investor->income_transactions_sum_amount ?? 0, 0, ',', '.') }}</td>
                                <td class="{{ ($investor->income_transactions_sum_amount - $investor->expense_transactions_sum_amount) >= 0 ? 'text-success' : 'text-danger' }}">
                                    Rp {{ number_format(($investor->income_transactions_sum_amount ?? 0) - ($investor->expense_transactions_sum_amount ?? 0), 0, ',', '.') }}
                                </td>
                                <td>
                                    <a href="{{ route('investors.show', $investor) }}" class="btn btn-sm btn-info">{{ __('Details') }}</a>
                                    <a href="{{ route('investors.edit', $investor) }}" class="btn btn-sm btn-warning">{{ __('Edit') }}</a>
                                    <form action="{{ route('investors.destroy', $investor) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('{{ __('Are you sure?') }}')">{{ __('Delete') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">{{ __('No investors found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $investors->links() }}
        </div>
    </div>
</div>
@endsection
