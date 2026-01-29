@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Generate Hotspot Vouchers') }}</div>

                <div class="card-body">
                    @if (session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('hotspot.store_generate') }}">
                        @csrf
                        
                        <div class="form-group row mb-3">
                            <label for="router_id" class="col-md-4 col-form-label text-md-end">{{ __('Router') }}</label>
                            <div class="col-md-6">
                                <select id="router_id" class="form-control @error('router_id') is-invalid @enderror" name="router_id" required>
                                    @if(isset($routers))
                                        @foreach($routers as $r)
                                            <option value="{{ $r->id }}" {{ (isset($router) && $router->id == $r->id) ? 'selected' : '' }}>
                                                {{ $r->name }} ({{ $r->ip_address }})
                                            </option>
                                        @endforeach
                                    @else
                                        <option value="{{ $router->id }}" selected>{{ $router->name }}</option>
                                    @endif
                                </select>
                                @error('router_id')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row mb-3">
                            <label for="profile" class="col-md-4 col-form-label text-md-end">{{ __('User Profile') }}</label>
                            <div class="col-md-6">
                                <select id="profile" class="form-control @error('profile') is-invalid @enderror" name="profile" required>
                                    <option value="">-- Select Profile --</option>
                                    @foreach($profiles as $prof)
                                        <option value="{{ $prof['name'] }}">{{ $prof['name'] }}</option>
                                    @endforeach
                                </select>
                                @error('profile')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row mb-3">
                            <label for="quantity" class="col-md-4 col-form-label text-md-end">{{ __('Quantity') }}</label>
                            <div class="col-md-6">
                                <input id="quantity" type="number" class="form-control @error('quantity') is-invalid @enderror" name="quantity" value="10" required min="1" max="500">
                                @error('quantity')
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
                            <label for="print_fee" class="col-md-4 col-form-label text-md-end">{{ __('Biaya Cetak/lbr') }}</label>
                            <div class="col-md-6">
                                <input id="print_fee" type="number" class="form-control @error('print_fee') is-invalid @enderror" name="print_fee" value="200" required min="0">
                                <small class="text-muted">Biaya jasa cetak per lembar voucher (masuk ke pendapatan Jasa Print)</small>
                                @error('print_fee')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row mb-3">
                            <label for="length" class="col-md-4 col-form-label text-md-end">{{ __('Code Length') }}</label>
                            <div class="col-md-6">
                                <input id="length" type="number" class="form-control @error('length') is-invalid @enderror" name="length" value="6" required min="4" max="20">
                                @error('length')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row mb-3">
                            <label for="prefix" class="col-md-4 col-form-label text-md-end">{{ __('Prefix') }}</label>
                            <div class="col-md-6">
                                <input id="prefix" type="text" class="form-control @error('prefix') is-invalid @enderror" name="prefix" placeholder="VC-">
                                @error('prefix')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row mb-3">
                            <label for="time_limit" class="col-md-4 col-form-label text-md-end">{{ __('Time Limit') }}</label>
                            <div class="col-md-6">
                                <input id="time_limit" type="text" class="form-control @error('time_limit') is-invalid @enderror" name="time_limit" placeholder="1h or 1d">
                                <small class="text-muted">Format: 1h, 2h, 1d, 30d</small>
                                @error('time_limit')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row mb-0">
                            <div class="col-md-6 offset-md-4">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Generate') }}
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
