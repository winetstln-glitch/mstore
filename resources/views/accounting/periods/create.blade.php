@extends('layouts.app')
@section('title', 'Buat Periode')
@section('content')
<div class="container-fluid py-3">
    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="mb-0 font-weight-bold text-primary">Buat Periode Akuntansi</h5>
        </div>
        <div class="card-body">
            <form method="post" action="{{ route('accounting.periods.store') }}" class="row g-3">
                @csrf
                <div class="col-md-4">
                    <label class="form-label fw-bold small text-muted">Nama (YYYY-MM)</label>
                    <input type="text" name="name" value="{{ old('name') }}" class="form-control form-control-lg" placeholder="2026-02">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold small text-muted">Tanggal Mulai</label>
                    <input type="date" name="start_date" value="{{ old('start_date') }}" class="form-control form-control-lg">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold small text-muted">Tanggal Selesai</label>
                    <input type="date" name="end_date" value="{{ old('end_date') }}" class="form-control form-control-lg">
                </div>
                <div class="col-12 mt-4">
                    <button class="btn btn-primary btn-lg w-100 w-md-auto me-md-2 mb-2 mb-md-0">
                        <i class="fas fa-save me-1"></i> Simpan
                    </button>
                    <a href="{{ route('accounting.periods.index') }}" class="btn btn-secondary btn-lg w-100 w-md-auto">
                        <i class="fas fa-arrow-left me-1"></i> Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
