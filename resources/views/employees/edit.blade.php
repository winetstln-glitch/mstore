@extends('layouts.app')

@section('title', 'Edit Karyawan')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0 fw-bold text-primary">Edit Karyawan</h4>
    <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="fa-solid fa-arrow-left me-1"></i> Kembali
    </a>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <form method="POST" action="{{ route('employees.update', $employee) }}" enctype="multipart/form-data" class="row g-3">
            @csrf
            @method('PUT')
            @include('employees.partials.form', ['employee' => $employee])
        </form>
    </div>
</div>
@endsection
