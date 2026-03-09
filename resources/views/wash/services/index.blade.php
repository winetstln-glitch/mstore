@extends('layouts.app')

@section('title', 'Manage Wash Services')

@section('content')
<div class="col-12 pb-5 pb-md-0 wash-services-page">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div class="d-flex align-items-center gap-2">
            <h1 class="h3 mb-0 text-gray-800">Wash Services</h1>
            <a href="{{ route('wash.services.create') }}" class="btn btn-primary d-inline d-sm-none rounded-circle" aria-label="Add Wash Service" style="width:40px;height:40px;display:inline-flex;align-items:center;justify-content:center;">
                <i class="fas fa-plus"></i>
            </a>
        </div>
        <a href="{{ route('wash.services.create') }}" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-plus fa-sm text-white-50"></i> Add New Service
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Service List</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive table-responsive-mobile">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Vehicle Type</th>
                            <th>Price</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($services as $service)
                            <tr>
                                <td>
                                    @if($service->image)
                                        <img src="{{ Storage::url($service->image) }}" alt="{{ $service->name }}" width="50" height="50" class="img-thumbnail object-fit-cover">
                                    @else
                                        <div class=" d-flex align-items-center justify-content-center text-muted border rounded" style="width: 50px; height: 50px;">
                                            <i class="fas fa-image"></i>
                                        </div>
                                    @endif
                                </td>
                                <td>{{ $service->name }}</td>
                                <td>
                                    @if($service->vehicle_type === 'car')
                                        <span class="badge bg-primary">Car</span>
                                    @else
                                        <span class="badge bg-success">Motor</span>
                                    @endif
                                </td>
                                <td>Rp {{ number_format($service->price, 0, ',', '.') }}</td>
                                <td>{{ $service->description ?? '-' }}</td>
                                <td>
                                    @if($service->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-danger">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('wash.services.edit', $service->id) }}" class="btn btn-sm btn-info">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('wash.services.destroy', $service->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this service?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">No services found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@push('styles')
<style>
    @media (max-width: 767.98px) {
        .wash-services-page {
            padding-left: 0.35rem;
            padding-right: 0.35rem;
        }

        .wash-services-page .h3 {
            font-size: 1.15rem;
        }

        .wash-services-page .card-header,
        .wash-services-page .card-body {
            padding-left: 0.85rem;
            padding-right: 0.85rem;
        }

        .wash-services-page .table-responsive {
            border: 1px solid var(--bs-border-color);
            border-radius: 0.9rem;
            padding: 0.25rem;
        }

        .wash-services-page .table-responsive-mobile td {
            align-items: flex-start;
            gap: 0.55rem;
        }

        .wash-services-page .table-responsive-mobile td[data-label="Image"] img,
        .wash-services-page .table-responsive-mobile td[data-label="Image"] .text-muted {
            width: 42px !important;
            height: 42px !important;
        }

        .wash-services-page .table-responsive-mobile td[data-label="Actions"] {
            display: block;
            text-align: left;
        }

        .wash-services-page .table-responsive-mobile td[data-label="Actions"]::before {
            display: block;
            margin-bottom: 0.45rem;
        }

        .wash-services-page .table-responsive-mobile td[data-label="Actions"] .btn {
            min-height: 34px;
            min-width: 34px;
            border-radius: 0.65rem;
            padding: 0.32rem 0.48rem;
        }
    }
</style>
@endpush
@endsection
