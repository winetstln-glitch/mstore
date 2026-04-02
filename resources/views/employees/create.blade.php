@extends('layouts.app')

@section('title', 'Tambah Karyawan')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0 fw-bold text-primary">Tambah Karyawan</h4>
    <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="fa-solid fa-arrow-left me-1"></i> Kembali
    </a>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <form method="POST" action="{{ route('employees.store') }}" enctype="multipart/form-data" class="row g-3">
            @csrf
            @include('employees.partials.form', ['employee' => null])
        </form>
    </div>
</div>
@endsection
