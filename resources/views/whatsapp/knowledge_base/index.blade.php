@extends('layouts.app')

@section('title', 'AI Knowledge Base')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h4 class="mb-0">AI Knowledge Base</h4>
            <div class="text-muted small">WhatsApp Center</div>
        </div>
        <a href="{{ route('whatsapp.kb.create') }}" class="btn btn-sm btn-primary">Tambah Dokumen</a>
    </div>

    <div class="card">
        <div class="card-header">Dokumen</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Judul</th>
                            <th>Kategori</th>
                            <th>Status</th>
                            <th>Updated</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($docs as $doc)
                            <tr>
                                <td>{{ $doc->title }}</td>
                                <td>{{ $doc->categoryModel?->name ?? ($doc->category ?? '-') }}</td>
                                <td>{{ $doc->status }}</td>
                                <td>{{ $doc->updated_at?->format('d M Y H:i') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('whatsapp.kb.edit', $doc->id) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                    <form action="{{ route('whatsapp.kb.destroy', $doc->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus dokumen ini?')">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-muted">Belum ada dokumen.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            {{ $docs->links() }}
        </div>
    </div>
</div>
@endsection
