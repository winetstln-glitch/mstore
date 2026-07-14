@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card shadow-sm border-0 border-top border-4 border-primary">
                <div class="card-header bg-body border-0 py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-body-emphasis">{{ __('Ubah Pemasangan') }} #{{ $installation->id }}</h5>
                    <a href="{{ route('installations.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fa-solid fa-arrow-left me-1"></i> {{ __('Kembali ke Daftar') }}
                    </a>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('installations.update', $installation) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="row g-3">
                            <!-- Pelanggan (hanya baca) -->
                            <div class="col-md-6">
                                <label class="form-label">{{ __('Pelanggan') }}</label>
                                <input type="text" value="{{ $installation->customer->name }}" class="form-control" disabled>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">{{ __('Nomor HP') }}</label>
                                <input type="text" value="{{ $installation->customer->phone ?: '-' }}" class="form-control" disabled>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">{{ __('Email') }}</label>
                                <input type="text" value="{{ $installation->customer->email ?: '-' }}" class="form-control" disabled>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">{{ __('Paket') }}</label>
                                <input type="text" value="{{ $installation->customer->package ?: '-' }}" class="form-control" disabled>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">{{ __('Nama WiFi') }}</label>
                                <input type="text" value="{{ $installation->customer->ssid_name ?: '-' }}" class="form-control" disabled>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">{{ __('Password WiFi') }}</label>
                                <input type="text" value="{{ $installation->customer->ssid_password ?: '-' }}" class="form-control" disabled>
                            </div>

                            <!-- Tanggal rencana -->
                            <div class="col-md-6">
                                <label for="plan_date" class="form-label">{{ __('Tanggal Rencana') }}</label>
                                <input type="date" name="plan_date" id="plan_date" value="{{ old('plan_date', $installation->plan_date ? $installation->plan_date->format('Y-m-d') : '') }}" class="form-control @error('plan_date') is-invalid @enderror" required>
                                @error('plan_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Status -->
                            <div class="col-md-6">
                                <label for="status" class="form-label">{{ __('Status') }}</label>
                                <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
                                    @foreach(['registered', 'survey', 'approved', 'installation', 'completed', 'cancelled'] as $status)
                                        <option value="{{ $status }}" {{ old('status', $installation->status) == $status ? 'selected' : '' }}>
                                            {{ __(ucfirst($status)) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Penugasan teknisi -->
                            <div class="col-md-6">
                                <label for="technician_id" class="form-label">{{ __('Teknisi') }}</label>
                                <select name="technician_id" id="technician_id" class="form-select @error('technician_id') is-invalid @enderror">
                                    <option value="">{{ __('Belum Ditugaskan') }}</option>
                                    @foreach($technicians as $tech)
                                        <option value="{{ $tech->id }}" {{ old('technician_id', $installation->technician_id) == $tech->id ? 'selected' : '' }}>
                                            {{ $tech->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('technician_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                @if($installation->modemRecord && $installation->modemRecord->user)
                                    <div class="form-text text-success">
                                        <i class="fa-solid fa-microchip me-1"></i>{{ __('Pendata Modem:') }} {{ $installation->modemRecord->user->name }}
                                    </div>
                                @endif
                            </div>

                            <div class="col-md-6">
                                <label for="coordinator_id" class="form-label">{{ __('Pengurus') }}</label>
                                <select name="coordinator_id" id="coordinator_id" class="form-select @error('coordinator_id') is-invalid @enderror">
                                    <option value="">{{ __('Pilih Pengurus') }}</option>
                                    @foreach(($coordinators ?? []) as $coordinator)
                                        <option value="{{ $coordinator->id }}" {{ old('coordinator_id', $selectedCoordinatorId ?? null) == $coordinator->id ? 'selected' : '' }}>
                                            {{ $coordinator->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('coordinator_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Koordinat -->
                            <div class="col-md-6">
                                <label for="coordinates" class="form-label">{{ __('Koordinat (Lintang, Bujur)') }}</label>
                                <input type="text" name="coordinates" id="coordinates" value="{{ old('coordinates', $installation->coordinates) }}" class="form-control @error('coordinates') is-invalid @enderror" placeholder="-6.2088, 106.8456">
                                @error('coordinates')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Catatan -->
                            <div class="col-12">
                                <label for="notes" class="form-label">{{ __('Catatan') }}</label>
                                <textarea name="notes" id="notes" rows="3" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $installation->notes) }}</textarea>
                                @error('notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mt-4 text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-save"></i> {{ __('Perbarui Pemasangan') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
