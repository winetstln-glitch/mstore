@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Create Hotspot Profile') }}</div>

                <div class="card-body">
                    @if (session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('hotspot.store_profile') }}">
                        @csrf
                        
                        <div class="form-group row mb-3">
                            <label for="router_id" class="col-md-4 col-form-label text-md-end">{{ __('Router') }}</label>
                            <div class="col-md-6">
                                <select id="router_id" class="form-control @error('router_id') is-invalid @enderror" name="router_id" required>
                                    @foreach($routers as $r)
                                        <option value="{{ $r->id }}" {{ (isset($router) && $router->id == $r->id) ? 'selected' : '' }}>
                                            {{ $r->name }} ({{ $r->ip_address }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('router_id')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row mb-3">
                            <label for="name" class="col-md-4 col-form-label text-md-end">{{ __('Profile Name') }}</label>
                            <div class="col-md-6">
                                <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" required>
                                @error('name')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row mb-3">
                            <label for="shared_users" class="col-md-4 col-form-label text-md-end">{{ __('Shared Users') }}</label>
                            <div class="col-md-6">
                                <input id="shared_users" type="number" class="form-control @error('shared_users') is-invalid @enderror" name="shared_users" value="1" required min="1">
                                @error('shared_users')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row mb-3">
                            <label for="rate_limit" class="col-md-4 col-form-label text-md-end">{{ __('Rate Limit') }}</label>
                            <div class="col-md-6">
                                <input id="rate_limit" type="text" class="form-control @error('rate_limit') is-invalid @enderror" name="rate_limit" placeholder="1M/1M">
                                <small class="text-muted">Upload/Download (e.g. 512k/1M)</small>
                                @error('rate_limit')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row mb-3">
                            <label for="price" class="col-md-4 col-form-label text-md-end">{{ __('Price') }}</label>
                            <div class="col-md-6">
                                <input id="price" type="number" class="form-control @error('price') is-invalid @enderror" name="price" required min="0">
                                @error('price')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row mb-3">
                            <label for="validity" class="col-md-4 col-form-label text-md-end">{{ __('Validity (Session Timeout)') }}</label>
                            <div class="col-md-6">
                                <input id="validity" type="text" class="form-control @error('validity') is-invalid @enderror" name="validity" placeholder="1d">
                                <small class="text-muted">Format: 1h, 1d, 30d</small>
                                @error('validity')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row mb-0">
                            <div class="col-md-6 offset-md-4">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Create Profile') }}
                                </button>
                                <a href="{{ route('hotspot.index') }}" class="btn btn-secondary">
                                    {{ __('Cancel') }}
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
