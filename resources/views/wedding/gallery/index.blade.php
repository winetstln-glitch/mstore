@extends('layouts.app')

@section('title', __('Galeri Wedding'))

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Galeri Wedding</h4>
        <a href="{{ route('wedding.gallery.create') }}" class="btn btn-primary">Tambah Foto</a>
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
                            <th style="width: 90px;">Foto</th>
                            <th>Caption</th>
                            <th>Urutan</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $it)
                            <tr>
                                <td>
                                    <img src="{{ asset('storage/'.$it->image_path) }}" alt="Wedding Gallery" class="img-thumbnail" style="max-height: 64px;">
                                </td>
                                <td>{{ $it->caption ?: '-' }}</td>
                                <td>{{ $it->sort_order }}</td>
                                <td>
                                    @if($it->is_active)
                                        <span class="badge bg-success">Aktif</span>
                                    @else
                                        <span class="badge bg-secondary">Nonaktif</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('wedding.gallery.edit', $it) }}">Edit</a>
                                    <form action="{{ route('wedding.gallery.destroy', $it) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">Belum ada foto galeri.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $items->links() }}
        </div>
    </div>
</div>
@endsection

