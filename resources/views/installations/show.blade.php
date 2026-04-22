@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ __('Detail Pemasangan') }} #{{ $installation->id }}</h5>
                    <div class="btn-group">
                        <a href="{{ route('installations.edit', $installation) }}" class="btn btn-warning btn-sm text-white">
                            <i class="fa-solid fa-pen-to-square"></i> {{ __('Ubah') }}
                        </a>
                        <a href="{{ route('installations.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fa-solid fa-arrow-left"></i> {{ __('Kembali ke Daftar') }}
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3">{{ __('Informasi Pelanggan') }}</h6>
                            <div class="p-3  rounded border dark:bg-dark dark:border-secondary">
                                <p class="mb-2">
                                    <span class="fw-bold">{{ __('Nama:') }}</span> 
                                    <a href="{{ route('customers.show', $installation->customer) }}" class="text-decoration-none">
                                        {{ $installation->customer->name }}
                                    </a>
                                </p>
                                <p class="mb-2">
                                    <span class="fw-bold">{{ __('Alamat:') }}</span> 
                                    {{ $installation->customer->address }}
                                </p>
                                <p class="mb-2">
                                    <span class="fw-bold">{{ __('Nomor HP:') }}</span> 
                                    {{ $installation->customer->phone }}
                                </p>
                                <p class="mb-0">
                                    <span class="fw-bold">{{ __('Paket:') }}</span> 
                                    {{ $installation->customer->package }}
                                </p>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3">{{ __('Status Pemasangan') }}</h6>
                            <div class="p-3  rounded border dark:bg-dark dark:border-secondary">
                                <p class="mb-2">
                                    <span class="fw-bold">{{ __('Status:') }}</span> 
                                    <span class="badge 
                                        @if($installation->status === 'completed') text-bg-success 
                                        @elseif($installation->status === 'cancelled') text-bg-danger 
                                        @elseif($installation->status === 'installation') text-bg-primary
                                        @elseif($installation->status === 'survey') text-bg-warning
                                        @else text-bg-secondary @endif">
                                        {{ ucfirst($installation->status) }}
                                    </span>
                                </p>
                                <p class="mb-2">
                                    <span class="fw-bold">{{ __('Tanggal Rencana:') }}</span> 
                                    {{ $installation->plan_date ? $installation->plan_date->format('Y-m-d') : __('Belum Diatur') }}
                                </p>
                                <p class="mb-2">
                                    <span class="fw-bold">{{ __('Teknisi:') }}</span> 
                                    {{ $installation->technician ? $installation->technician->name : __('Belum Ditugaskan') }}
                                </p>
                                <p class="mb-2">
                                    <span class="fw-bold">Pengurus:</span>
                                    {{ $selectedCoordinator?->name ?: '-' }}
                                </p>
                                <p class="mb-0">
                                    <span class="fw-bold">{{ __('Koordinat:') }}</span> 
                                    {{ $installation->coordinates ?? __('Belum Diatur') }}
                                </p>
                            </div>
                        </div>

                        <div class="col-12">
                            <h6 class="fw-bold mb-3">{{ __('Catatan') }}</h6>
                            <div class="p-3  rounded border dark:bg-dark dark:border-secondary">
                                <p class="mb-0 text-break" style="white-space: pre-line;">{{ $installation->notes ?? __('Belum ada catatan.') }}</p>
                            </div>
                        </div>
                        
                        @if($installation->photo_before || $installation->photo_after)
                        <div class="col-12">
                            <h6 class="fw-bold mb-3">{{ __('Foto') }}</h6>
                            <div class="row g-3">
                                @if($installation->photo_before)
                                <div class="col-md-6">
                                    <p class="fw-bold mb-2">{{ __('Sebelum:') }}</p>
                                    <img src="{{ asset('storage/' . $installation->photo_before) }}" alt="{{ __('Foto sebelum pemasangan') }}" class="img-fluid rounded shadow-sm border">
                                </div>
                                @endif
                                
                                @if($installation->photo_after)
                                <div class="col-md-6">
                                    <p class="fw-bold mb-2">{{ __('Sesudah:') }}</p>
                                    <img src="{{ asset('storage/' . $installation->photo_after) }}" alt="{{ __('Foto sesudah pemasangan') }}" class="img-fluid rounded shadow-sm border">
                                </div>
                                @endif
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
