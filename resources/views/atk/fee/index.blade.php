@extends('layouts.app')

@section('title', __('Manajemen Fee ATK'))

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0 border-top border-4 border-primary">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">{{ __('Manajemen Fee ATK') }}</h5>
                <a href="{{ route('atk.fee.create') }}" class="btn btn-primary">
                    <i class="fa-solid fa-plus me-1"></i> {{ __('Buat Fee Profile') }}
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>{{ __('Nama') }}</th>
                                <th>{{ __('Tipe Transaksi') }}</th>
                                <th>{{ __('Mode Fee') }}</th>
                                <th>{{ __('Aktif') }}</th>
                                <th>{{ __('Dibuat Pada') }}</th>
                                <th>{{ __('Aksi') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($profiles as $profile)
                            <tr>
                                <td>{{ $profile->name }}</td>
                                <td>
                                    @php
                                    $types = [
                                        'bank' => 'Transfer Bank',
                                        'cash_out' => 'Tarik Tunai',
                                        'top_up' => 'Top Up',
                                        'ppob' => 'PPOB',
                                        'qris' => 'QRIS',
                                        'custom' => 'Custom'
                                    ];
                                    @endphp
                                    {{ $types[$profile->transaction_type] ?? $profile->transaction_type }}
                                </td>
                                <td>
                                    @php
                                    $modes = [
                                        'fixed' => 'Fixed Fee',
                                        'percentage' => 'Percentage Fee',
                                        'fixed_percentage' => 'Fixed + Percentage',
                                        'tier' => 'Tier Fee',
                                        'cost_plus' => 'Cost Plus Markup',
                                        'custom' => 'Custom Formula'
                                    ];
                                    @endphp
                                    {{ $modes[$profile->fee_mode] ?? $profile->fee_mode }}
                                </td>
                                <td>
                                    @if($profile->is_active)
                                        <span class="badge bg-success">{{ __('Aktif') }}</span>
                                    @else
                                        <span class="badge bg-secondary">{{ __('Nonaktif') }}</span>
                                    @endif
                                </td>
                                <td>{{ $profile->created_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('atk.fee.show', $profile->id) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>
                                        <a href="{{ route('atk.fee.edit', $profile->id) }}" class="btn btn-sm btn-outline-warning">
                                            <i class="fa-solid fa-edit"></i>
                                        </a>
                                        <form action="{{ route('atk.fee.destroy', $profile->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Yakin ingin menghapus fee profile ini?')">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
