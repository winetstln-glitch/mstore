@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card shadow-sm border-0 border-top border-4 border-primary">
            <div class="card-header py-3">
                <h5 class="mb-0 fw-bold">{{ __('Add Package') }}</h5>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('packages.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="name" class="form-label">{{ __('Name') }}</label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="price" class="form-label">{{ __('Price') }}</label>
                        <input type="number" name="price" id="price" value="{{ old('price') }}" class="form-control @error('price') is-invalid @enderror" min="0" step="1000" required>
                        @error('price')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="speed" class="form-label">{{ __('Speed') }}</label>
                        <input type="text" name="speed" id="speed" value="{{ old('speed') }}" class="form-control @error('speed') is-invalid @enderror">
                        @error('speed')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">{{ __('Description') }}</label>
                        <textarea name="description" id="description" rows="3" class="form-control @error('description') is-invalid @enderror">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', 1) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">{{ __('Active') }}</label>
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('packages.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                        <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

