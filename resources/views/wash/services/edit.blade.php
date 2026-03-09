@extends('layouts.app')

@section('title', 'Edit Wash Service')

@section('content')
<div class="container-fluid wash-service-edit-page">
    <h1 class="h3 mb-4 text-gray-800">Edit Service</h1>

    <div class="card shadow mb-4">
        <div class="card-body">
            <form action="{{ route('wash.services.update', $service->id) }}" method="POST" enctype="multipart/form-data" id="editServiceForm">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label for="name" class="form-label">Service Name</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $service->name) }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="vehicle_type" class="form-label">Vehicle Type</label>
                    <select class="form-select @error('vehicle_type') is-invalid @enderror" id="vehicle_type" name="vehicle_type" required>
                        <option value="car" {{ old('vehicle_type', $service->vehicle_type) == 'car' ? 'selected' : '' }}>Car (Mobil)</option>
                        <option value="motor" {{ old('vehicle_type', $service->vehicle_type) == 'motor' ? 'selected' : '' }}>Motor (Motorcycle)</option>
                    </select>
                    @error('vehicle_type')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="price" class="form-label">Price</label>
                    <input type="number" class="form-control @error('price') is-invalid @enderror" id="price" name="price" value="{{ old('price', $service->price) }}" required min="0">
                    @error('price')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description', $service->description) }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="image" class="form-label">Service Image</label>
                    @if($service->image)
                        <div class="mb-2">
                            <img src="{{ Storage::url($service->image) }}" alt="Service Image" class="img-thumbnail" style="max-height: 150px;">
                        </div>
                    @endif
                    <input type="file" class="form-control @error('image') is-invalid @enderror" id="image" name="image" accept="image/*">
                    @error('image')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3 form-check">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" {{ old('is_active', $service->is_active) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>

                <button type="submit" class="btn btn-primary">Update Service</button>
                <a href="{{ route('wash.services.index') }}" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>
<div class="position-fixed bottom-0 start-0 end-0 bg-body border-top shadow d-md-none" style="z-index: 1030;">
    <div class="container py-2">
        <div class="d-flex gap-2">
            <a href="{{ route('wash.services.index') }}" class="btn btn-outline-secondary w-50">Cancel</a>
            <button type="submit" class="btn btn-primary w-50" form="editServiceForm">Update</button>
        </div>
    </div>
</div>
@push('styles')
<style>
    .wash-service-edit-page .form-control,
    .wash-service-edit-page .form-select {
        min-height: 44px;
    }

    @media (max-width: 767.98px) {
        .wash-service-edit-page {
            padding-left: 0.35rem;
            padding-right: 0.35rem;
            padding-bottom: 5rem !important;
        }

        .wash-service-edit-page .h3 {
            font-size: 1.1rem;
            margin-bottom: 0.9rem !important;
        }

        .wash-service-edit-page .card-body {
            padding: 0.9rem;
        }

        .wash-service-edit-page form > .btn,
        .wash-service-edit-page form > a.btn {
            display: none;
        }
    }
</style>
@endpush
@endsection
