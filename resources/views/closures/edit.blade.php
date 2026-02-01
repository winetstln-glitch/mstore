@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm border-0 border-top border-4 border-primary">
            <div class="card-header bg-body-tertiary py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-body-emphasis">{{ __('Edit Closure') }}</h5>
                <a href="{{ route('closures.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fa-solid fa-arrow-left me-1"></i> {{ __('Back') }}
                </a>
            </div>

            <div class="card-body">
                <form action="{{ route('closures.update', $closure->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">{{ __('Closure Name') }}</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $closure->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="odc_id" class="form-label">{{ __('ODC') }}</label>
                            <select class="form-select @error('odc_id') is-invalid @enderror" id="odc_id" name="odc_id">
                                <option value="">{{ __('Select ODC') }}</option>
                                @foreach($odcs as $odc)
                                    <option value="{{ $odc->id }}" {{ old('odc_id', $closure->odc_id) == $odc->id ? 'selected' : '' }}>{{ $odc->name }}</option>
                                @endforeach
                            </select>
                            @error('odc_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="region_id" class="form-label">{{ __('Region') }}</label>
                            <select class="form-select @error('region_id') is-invalid @enderror" id="region_id" name="region_id">
                                <option value="">{{ __('Select Region') }}</option>
                                @foreach($regions as $region)
                                    <option value="{{ $region->id }}" {{ old('region_id', $closure->region_id) == $region->id ? 'selected' : '' }}>{{ $region->name }}</option>
                                @endforeach
                            </select>
                            @error('region_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="capacity" class="form-label">{{ __('Capacity (Ports)') }}</label>
                            <input type="number" class="form-control @error('capacity') is-invalid @enderror" id="capacity" name="capacity" value="{{ old('capacity', $closure->capacity) }}" min="0">
                            @error('capacity')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="filled" class="form-label">{{ __('Filled') }}</label>
                            <input type="number" class="form-control @error('filled') is-invalid @enderror" id="filled" name="filled" value="{{ old('filled', $closure->filled) }}" min="0">
                            @error('filled')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="latitude" class="form-label">{{ __('Latitude') }}</label>
                            <input type="text" class="form-control @error('latitude') is-invalid @enderror" id="latitude" name="latitude" value="{{ old('latitude', $closure->latitude) }}">
                            @error('latitude')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="longitude" class="form-label">{{ __('Longitude') }}</label>
                            <input type="text" class="form-control @error('longitude') is-invalid @enderror" id="longitude" name="longitude" value="{{ old('longitude', $closure->longitude) }}">
                            @error('longitude')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">{{ __('Description') }}</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description', $closure->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="image" class="form-label">{{ __('Image') }}</label>
                        @if($closure->image)
                            <div class="mb-2">
                                <img src="{{ Storage::url($closure->image) }}" alt="{{ $closure->name }}" width="100" class="img-thumbnail">
                            </div>
                        @endif
                        <input type="file" class="form-control @error('image') is-invalid @enderror" id="image" name="image" accept="image/*">
                        @error('image')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('closures.index') }}" class="btn btn-light">{{ __('Cancel') }}</a>
                        <button type="submit" class="btn btn-primary">{{ __('Update Closure') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
