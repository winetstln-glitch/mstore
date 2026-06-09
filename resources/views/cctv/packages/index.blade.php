@extends('layouts.app')

@section('title', __('Paket CCTV'))

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Paket CCTV</h4>
        <a href="{{ route('cctv.packages.create') }}" class="btn btn-primary">Tambah Paket</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Kamera</th>
                            <th>Harga</th>
                            <th>Garansi</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($packages as $p)
                            <tr>
                                <td>{{ $p->name }}</td>
                                <td>{{ $p->camera_count ?? 'Custom' }}</td>
                                <td>Rp {{ number_format((int) $p->price, 0, ',', '.') }}</td>
                                <td>{{ $p->warranty_months }} bulan</td>
                                <td>
                                    @if($p->is_active)
                                        <span class="badge bg-success">Aktif</span>
                                    @else
                                        <span class="badge bg-secondary">Nonaktif</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('cctv.packages.edit', $p) }}">Edit</a>
                                    <form action="{{ route('cctv.packages.destroy', $p) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $packages->links() }}
        </div>
    </div>
</div>
@endsection

