@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Edit Investor') }}</div>

                <div class="card-body">
                    <form method="POST" action="{{ route('investors.update', $investor) }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="coordinator_id" class="form-label">{{ __('Coordinator') }}</label>
                            <select class="form-select @error('coordinator_id') is-invalid @enderror" id="coordinator_id" name="coordinator_id" required>
                                <option value="">{{ __('Select Coordinator') }}</option>
                                @foreach($coordinators as $coordinator)
                                    <option value="{{ $coordinator->id }}" {{ old('coordinator_id', $investor->coordinator_id) == $coordinator->id ? 'selected' : '' }}>
                                        {{ $coordinator->name }} ({{ $coordinator->region->name ?? '-' }})
                                    </option>
                                @endforeach
                            </select>
                            @error('coordinator_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="name" class="form-label">{{ __('Name') }}</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $investor->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">{{ __('Phone') }}</label>
                            <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $investor->phone) }}">
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">{{ __('Description') }}</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description', $investor->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">{{ __('Update Investor') }}</button>
                            <a href="{{ route('investors.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
