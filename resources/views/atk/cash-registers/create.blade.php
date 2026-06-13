@extends('layouts.app')
@section('title', 'Buka Shift Kasir')
@section('content')
<div class="container-fluid py-3">
    <h5 class="mb-3">Buka Shift Kasir</h5>
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('atk.cash-registers.store') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Nama Shift</label>
                            <input type="text" name="name" class="form-control" placeholder="Contoh: Shift Pagi, Shift Siang" value="{{ old('name') }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Saldo Awal</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" name="opening_balance" class="form-control" placeholder="0" value="{{ old('opening_balance', 0) }}" required>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('atk.dashboard') }}" class="btn btn-light">Batal</a>
                            <button class="btn btn-primary">Buka Shift</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
