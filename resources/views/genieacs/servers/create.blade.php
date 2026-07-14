@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-md-8 col-lg-6 mx-auto">
        <div class="card shadow-sm border-0 border-top border-4 border-primary">
            <div class="card-header py-3">
                <h5 class="mb-0 fw-bold">{{ __('Add GenieACS Server') }}</h5>
            </div>

            <div class="card-body">
                <form action="{{ route('genieacs.servers.store') }}" method="POST">
                    @csrf
                    
                    <div class="mb-3">
                        <label for="name" class="form-label fw-bold">{{ __('Server Name') }}</label>
                        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="url" class="form-label fw-bold">{{ __('Server URL') }}</label>
                        <input type="url" name="url" id="url" class="form-control @error('url') is-invalid @enderror" placeholder="http://192.168.1.10:7557" required>
                        @error('url')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <input type="hidden" name="is_active" value="0">
                            <input class="form-check-input" type="checkbox" role="switch" name="is_active" value="1" id="isActive" checked>
                            <label class="form-check-label" for="isActive">{{ __('Active') }}</label>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('genieacs.servers.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-save me-1"></i> {{ __('Save Server') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection