@extends('layouts.app')

@section('title', 'Edit Supplier')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Edit Supplier</h1>
        <a href="{{ route('wash.suppliers.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="card shadow">
        <div class="card-body">
            <form method="POST" action="{{ route('wash.suppliers.update', $supplier) }}">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label">Nama Supplier <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" class="form-control" value="{{ $supplier->name }}" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="phone" class="form-label">Telepon</label>
                        <input type="text" name="phone" id="phone" class="form-control" value="{{ $supplier->phone }}">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" name="email" id="email" class="form-control" value="{{ $supplier->email }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="pic" class="form-label">PIC</label>
                        <input type="text" name="pic" id="pic" class="form-control" value="{{ $supplier->pic }}">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="address" class="form-label">Alamat</label>
                    <textarea name="address" id="address" class="form-control" rows="3">{{ $supplier->address }}</textarea>
                </div>

                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" {{ $supplier->is_active ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">
                            Aktif
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
