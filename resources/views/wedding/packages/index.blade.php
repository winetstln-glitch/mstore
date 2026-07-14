@extends('layouts.app')

@section('title', __('Paket Wedding'))

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Paket Wedding</h4>
        <a href="{{ route('wedding.packages.create') }}" class="btn btn-primary">Tambah Paket</a>
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
                            <th style="width: 90px;">Gambar</th>
                            <th>Nama</th>
                            <th>Harga</th>
                            <th>Kapasitas</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($packages as $p)
                            <tr>
                                <td>
                                    @if(!empty($p->image_path))
                                        <img src="{{ asset('storage/'.$p->image_path) }}" alt="Paket Wedding" class="img-thumbnail" style="max-height: 56px;">
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>
                                <td>{{ $p->name }}</td>
                                <td>Rp {{ number_format((int) $p->price, 0, ',', '.') }}</td>
                                <td>{{ $p->capacity ?? '-' }}</td>
                                <td>
                                    @if($p->is_active)
                                        <span class="badge bg-success">Aktif</span>
                                    @else
                                        <span class="badge bg-secondary">Nonaktif</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('wedding.packages.edit', $p) }}">Edit</a>
                                    <form action="{{ route('wedding.packages.destroy', $p) }}" method="POST" class="d-inline">
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
