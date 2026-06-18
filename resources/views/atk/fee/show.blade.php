@extends('layouts.app')

@section('title', __('Detail Fee Profile ATK'))

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0 border-top border-4 border-primary">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">{{ __('Detail Fee Profile ATK') }}</h5>
                <a href="{{ route('atk.fee.index') }}" class="btn btn-secondary">
                    <i class="fa-solid fa-arrow-left me-1"></i> {{ __('Kembali') }}
                </a>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <h6 class="fw-bold text-primary text-uppercase mb-3">{{ __('Informasi Umum') }}</h6>
                    <table class="table table-striped">
                        <tr>
                            <th style="width: 30%">{{ __('Nama Profile') }}</th>
                            <td>{{ $profile->name }}</td>
                        </tr>
                        <tr>
                            <th>{{ __('Tipe Transaksi') }}</th>
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
                        </tr>
                        <tr>
                            <th>{{ __('Mode Fee') }}</th>
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
                        </tr>
                        <tr>
                            <th>{{ __('Aktif') }}</th>
                            <td>
                                @if($profile->is_active)
                                    <span class="badge bg-success">{{ __('Ya') }}</span>
                                @else
                                    <span class="badge bg-secondary">{{ __('Tidak') }}</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>{{ __('Izinkan Override') }}</th>
                            <td>
                                @if($profile->allow_override)
                                    <span class="badge bg-success">{{ __('Ya') }}</span>
                                @else
                                    <span class="badge bg-secondary">{{ __('Tidak') }}</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>{{ __('Dibuat Oleh') }}</th>
                            <td>{{ $profile->createdBy->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>{{ __('Dibuat Pada') }}</th>
                            <td>{{ $profile->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                        <tr>
                            <th>{{ __('Diperbarui Pada') }}</th>
                            <td>{{ $profile->updated_at->format('d/m/Y H:i') }}</td>
                        </tr>
                    </table>
                </div>

                @if($profile->tiers->count() > 0)
                <div class="mb-4">
                    <h6 class="fw-bold text-primary text-uppercase mb-3">{{ __('Tiers') }}</h6>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Min Amount</th>
                                    <th>Max Amount</th>
                                    <th>Tipe Fee</th>
                                    <th>Fee Value</th>
                                    <th>Fixed Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($profile->tiers as $tier)
                                <tr>
                                    <td>{{ number_format($tier->min_amount, 2, ',', '.') }}</td>
                                    <td>{{ $tier->max_amount ? number_format($tier->max_amount, 2, ',', '.') : '∞' }}</td>
                                    <td>{{ ucfirst($tier->fee_type) }}</td>
                                    <td>{{ $tier->fee_value }}</td>
                                    <td>{{ $tier->fixed_value ?? '-' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

                @if($profile->custom_formula)
                <div class="mb-4">
                    <h6 class="fw-bold text-primary text-uppercase mb-3">{{ __('Custom Formula') }}</h6>
                    <code>{{ $profile->custom_formula }}</code>
                </div>
                @endif

                @if($profile->cost_price !== null)
                <div class="mb-4">
                    <h6 class="fw-bold text-primary text-uppercase mb-3">{{ __('Cost Plus Markup') }}</h6>
                    <table class="table table-striped">
                        <tr>
                            <th style="width: 30%">{{ __('Harga Cost') }}</th>
                            <td>{{ number_format($profile->cost_price, 2, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <th>{{ __('Tipe Markup') }}</th>
                            <td>{{ ucfirst($profile->markup_type) }}</td>
                        </tr>
                        <tr>
                            <th>{{ __('Nilai Markup') }}</th>
                            <td>{{ $profile->markup_value }}</td>
                        </tr>
                    </table>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
