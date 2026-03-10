@extends('layouts.app')
@section('title', 'Edit Akun')
@section('content')
<div class="container-fluid py-3">
    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="mb-0 font-weight-bold text-primary">Edit Akun</h5>
        </div>
        <div class="card-body">
            <form method="post" action="{{ route('accounting.accounts.update', $account) }}" class="row g-3">
                @csrf
                @method('PUT')
                <div class="col-md-4">
                    <label class="form-label fw-bold small text-muted">Kode Akun</label>
                    <input type="text" name="code" value="{{ old('code', $account->code) }}" class="form-control @error('code') is-invalid @enderror" placeholder="Contoh: 1101">
                    @error('code')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-8">
                    <label class="form-label fw-bold small text-muted">Nama Akun</label>
                    <input type="text" name="name" value="{{ old('name', $account->name) }}" class="form-control @error('name') is-invalid @enderror" placeholder="Contoh: Kas di Tangan">
                    @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold small text-muted">Jenis</label>
                    <select name="type" class="form-select @error('type') is-invalid @enderror">
                        @foreach($types as $typeKey => $typeLabel)
                            <option value="{{ $typeKey }}" @selected(old('type', $account->type) === $typeKey)>{{ $typeLabel }}</option>
                        @endforeach
                    </select>
                    @error('type')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold small text-muted">Parent Akun</label>
                    <select name="parent_id" class="form-select @error('parent_id') is-invalid @enderror">
                        <option value="">Tanpa Parent</option>
                        @foreach($parents as $parent)
                            <option value="{{ $parent->id }}" @selected((string) old('parent_id', $account->parent_id) === (string) $parent->id)>{{ $parent->code }} - {{ $parent->name }}</option>
                        @endforeach
                    </select>
                    @error('parent_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold small text-muted d-block">Status</label>
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', $account->is_active))>
                        <label class="form-check-label" for="is_active">Aktif</label>
                    </div>
                </div>
                <div class="col-12 mt-4">
                    <button class="btn btn-primary btn-lg w-100 w-md-auto me-md-2 mb-2 mb-md-0">
                        <i class="fas fa-save me-1"></i> Update
                    </button>
                    <a href="{{ route('accounting.accounts.index') }}" class="btn btn-secondary btn-lg w-100 w-md-auto">
                        <i class="fas fa-arrow-left me-1"></i> Kembali
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
