@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex gap-2 align-items-center">
            <a href="{{ route('whatsapp.index') }}" class="btn btn-outline-secondary">
                <i class="fa-solid fa-arrow-left"></i> Kembali
            </a>
            <h1 class="h3 mb-0">
                <i class="fab fa-whatsapp text-success"></i> WhatsApp Bot Builder
            </h1>
        </div>
        <a href="{{ route('whatsapp.builder.create') }}" class="btn btn-success">
            <i class="fa-solid fa-plus"></i> Tambah Menu
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Keyword</th>
                            <th>Tipe</th>
                            <th>Priority</th>
                            <th>Aktif</th>
                            <th>Hits</th>
                            <th>Dibuat</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($menus as $menu)
                        <tr>
                            <td>
                                <code class="text-success">{{ $menu->keyword }}</code>
                            </td>
                            <td>
                                <span class="badge bg-{{ $menu->type === 'text' ? 'primary' : ($menu->type === 'image' ? 'info' : 'warning') }}">
                                    {{ strtoupper($menu->type) }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-secondary">{{ $menu->priority }}</span>
                            </td>
                            <td>
                                <span class="badge bg-{{ $menu->is_active ? 'success' : 'danger' }}">
                                    {{ $menu->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-info">{{ number_format($menu->hits_count) }}</span>
                            </td>
                            <td>
                                {{ $menu->created_at->translatedFormat('d M Y H:i') }}
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="{{ route('whatsapp.builder.edit', $menu) }}" class="btn btn-sm btn-primary">
                                        <i class="fa-solid fa-edit"></i>
                                    </a>
                                    <form method="POST" action="{{ route('whatsapp.builder.destroy', $menu) }}" onsubmit="return confirm('Yakin ingin menghapus menu ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $menus->links() }}
        </div>
    </div>
</div>
@endsection
