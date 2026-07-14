@extends('layouts.app')

@section('title', 'Edit VPN Server')

@section('content')
<div class="container py-3">
    <div class="card">
        <div class="card-header">Edit VPN Server</div>
        <div class="card-body">
            <form method="POST" action="{{ route('vpn.servers.update', $server) }}">
                @csrf @method('PUT')
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nama Server</label>
                        <input name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $server->name) }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Lokasi</label>
                        <input name="location" class="form-control @error('location') is-invalid @enderror" value="{{ old('location', $server->location) }}">
                        @error('location')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">IP Public</label>
                        <input name="ip_public" class="form-control @error('ip_public') is-invalid @enderror" value="{{ old('ip_public', $server->ip_public) }}" required>
                        @error('ip_public')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Port</label>
                        <input type="number" name="port" class="form-control @error('port') is-invalid @enderror" value="{{ old('port', $server->port) }}" required>
                        @error('port')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Protocol</label>
                        <select name="protocol" class="form-select @error('protocol') is-invalid @enderror" required>
                            @foreach(['l2tp'=>'L2TP','pptp'=>'PPTP','sstp'=>'SSTP','openvpn'=>'OpenVPN'] as $k=>$v)
                                <option value="{{ $k }}" @if(old('protocol', $server->protocol)===$k) selected @endif>{{ $v }}</option>
                            @endforeach
                        </select>
                        @error('protocol')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                            @foreach(['active'=>'Active','maintenance'=>'Maintenance'] as $k=>$v)
                                <option value="{{ $k }}" @if(old('status', $server->status)===$k) selected @endif>{{ $v }}</option>
                            @endforeach
                        </select>
                        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="mt-3">
                    <button class="btn btn-primary">Simpan</button>
                    <a href="{{ route('vpn.servers.index') }}" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
