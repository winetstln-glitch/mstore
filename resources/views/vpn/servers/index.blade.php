@extends('layouts.app')

@section('title', 'VPN Servers')

@section('content')
<div class="container py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">VPN Servers</h5>
        <a href="{{ route('vpn.servers.create') }}" class="btn btn-primary btn-sm">Tambah Server</a>
    </div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="card">
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Lokasi</th>
                        <th>IP</th>
                        <th>Port</th>
                        <th>Protocol</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($servers as $server)
                    <tr>
                        <td>{{ $server->name }}</td>
                        <td>{{ $server->location ?: '-' }}</td>
                        <td>{{ $server->ip_public }}</td>
                        <td>{{ $server->port }}</td>
                        <td class="text-uppercase">{{ $server->protocol }}</td>
                        <td>
                            <span class="badge {{ $server->status === 'active' ? 'bg-success' : 'bg-warning' }}">
                                {{ $server->status }}
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('vpn.servers.edit', $server) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                            <form action="{{ route('vpn.servers.destroy', $server) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus server ini?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted">Belum ada server</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body">
            {{ $servers->links() }}
        </div>
    </div>
</div>
@endsection
