@extends('layouts.app')

@section('title', __('Manajemen API Key'))

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('Manajemen API Key') }}</h1>
    </div>

    <div class="row">
        <!-- Buat key dan daftar -->
        <div class="col-lg-12 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('Integrasikan sistem eksternal dengan NMS Anda') }}</h6>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <form action="{{ route('apikeys.store') }}" method="POST" class="form-inline">
                                @csrf
                                <label class="sr-only" for="appName">{{ __('Deskripsi Aplikasi') }}</label>
                                <div class="input-group mb-2 mr-sm-2 w-100">
                                    <div class="input-group-prepend">
                                        <div class="input-group-text">{{ __('Deskripsi') }}</div>
                                    </div>
                                    <input type="text" class="form-control" id="appName" name="name" placeholder="{{ __('Contoh: Billing WHMCS') }}" required>
                                    <div class="input-group-append">
                                        <button type="submit" class="btn btn-primary">{{ __('Buat Key') }}</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>{{ __('Deskripsi') }}</th>
                                    <th>{{ __('API Key (Tersamarkan)') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Dibuat Pada') }}</th>
                                    <th>{{ __('Aksi') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($keys as $key)
                                <tr>
                                    <td>{{ $key->name }}</td>
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <input type="text" class="form-control" value="{{ Str::mask($key->key, '*', 10) }}" readonly>
                                            <div class="input-group-append">
                                                <button class="btn btn-outline-secondary copy-btn" type="button" data-key="{{ $key->key }}">
                                                    <i class="fa fa-copy"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if($key->is_active)
                                            <span class="badge badge-success">{{ __('Aktif') }}</span>
                                        @else
                                            <span class="badge badge-secondary">{{ __('Tidak Aktif') }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $key->created_at->translatedFormat('d M Y') }}</td>
                                    <td>
                                        <form action="{{ route('apikeys.toggle', $key) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm {{ $key->is_active ? 'btn-warning' : 'btn-success' }}">
                                                {{ $key->is_active ? __('Nonaktifkan') : __('Aktifkan') }}
                                            </button>
                                        </form>
                                        <form action="{{ route('apikeys.destroy', $key) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Hapus key ini? Aplikasi yang memakai key ini akan kehilangan akses.') }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center">{{ __('Belum ada API key yang dibuat.') }}</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dokumentasi -->
        <div class="col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('Dokumentasi Singkat') }}</h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <strong>Base URL:</strong> <code>{{ url('/api/integration') }}</code>
                    </div>
                    
                    <h5 class="mt-4">1. {{ __('Ambil Semua Perangkat') }}</h5>
                    <p>{{ __('Ambil daftar semua perangkat yang terdaftar (OLT dan Mikrotik).') }}</p>
                    <div class=" p-3 rounded mb-3">
                        <code>GET {{ url('/api/integration') }}?api_key=YOUR_KEY&endpoint=devices</code>
                    </div>

                    <h5 class="mt-4">2. {{ __('Ambil Status OLT (Filter PON)') }}</h5>
                    <p>{{ __('Ambil data ONU pada PON tertentu. Membutuhkan device_id dan pon.') }}</p>
                    <div class=" p-3 rounded mb-3">
                        <code>GET {{ url('/api/integration') }}?api_key=YOUR_KEY&endpoint=olt/status&device_id=10&pon=1</code>
                    </div>

                    <h5 class="mt-4">3. {{ __('Ambil Status OLT (Semua PON)') }}</h5>
                    <p>{{ __('Ambil semua data ONU dari seluruh port PON. Hanya membutuhkan device_id.') }}</p>
                    <div class=" p-3 rounded mb-3">
                        <code>GET {{ url('/api/integration') }}?api_key=YOUR_KEY&endpoint=olt/status&device_id=10</code>
                    </div>

                    <h5 class="mt-4">4. {{ __('Ambil Status Mikrotik') }}</h5>
                    <p>{{ __('Ambil status resource (CPU, uptime, memori) serta statistik pengguna PPPoE dan hotspot.') }}</p>
                    <div class=" p-3 rounded mb-3">
                        <code>GET {{ url('/api/integration') }}?api_key=YOUR_KEY&endpoint=mikrotik/status&device_id=2</code>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.copy-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const key = this.getAttribute('data-key');
                navigator.clipboard.writeText(key).then(() => {
                    alert("{{ __('API key berhasil disalin ke clipboard!') }}");
                });
            });
        });
    });
</script>
@endsection
