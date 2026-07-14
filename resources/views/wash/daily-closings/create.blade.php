@extends('layouts.app')

@section('title', 'Buat Penutupan Harian')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Buat Penutupan Harian</h1>
        <a href="{{ route('wash.daily-closings.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="card shadow">
        <div class="card-body">
            <form method="POST" action="{{ route('wash.daily-closings.store') }}">
                @csrf

                <div class="mb-3">
                    <label for="closing_date" class="form-label">Tanggal Penutupan <span class="text-danger">*</span></label>
                    <input type="date" name="closing_date" id="closing_date" class="form-control" value="{{ $today }}" required>
                </div>

                <div class="mb-3">
                    <label for="notes" class="form-label">Catatan</label>
                    <textarea name="notes" id="notes" class="form-control" rows="3"></textarea>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Buat Penutupan
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
