@extends('layouts.app')
@section('title', 'Buat Periode')
@section('content')
<div class="container py-3">
    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="mb-0">Buat Periode Akuntansi</h5>
        </div>
        <div class="card-body">
            <form method="post" action="{{ route('accounting.periods.store') }}" class="row g-3">
                @csrf
                <div class="col-md-4">
                    <label class="form-label">Nama (YYYY-MM)</label>
                    <input type="text" name="name" value="{{ old('name') }}" class="form-control" placeholder="2026-02">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Tanggal Mulai</label>
                    <input type="date" name="start_date" value="{{ old('start_date') }}" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Tanggal Selesai</label>
                    <input type="date" name="end_date" value="{{ old('end_date') }}" class="form-control">
                </div>
                <div class="col-12">
                    <button class="btn btn-primary">Simpan</button>
                    <a href="{{ route('accounting.periods.index') }}" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
