@extends('layouts.app')

@section('title', $doc->exists ? 'Edit Knowledge Base' : 'Tambah Knowledge Base')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h4 class="mb-0">{{ $doc->exists ? 'Edit Dokumen' : 'Tambah Dokumen' }}</h4>
            <div class="text-muted small">AI Knowledge Base</div>
        </div>
        <a href="{{ route('whatsapp.kb.index') }}" class="btn btn-sm btn-outline-secondary">Kembali</a>
    </div>

    @php
        $existingTags = [];
        if ($doc->relationLoaded('tags')) {
            $existingTags = $doc->tags->pluck('name')->all();
        } elseif (is_array($doc->tags)) {
            $existingTags = $doc->tags;
        }
        $tagsCsv = implode(', ', array_filter(array_map('trim', $existingTags)));
    @endphp

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ $doc->exists ? route('whatsapp.kb.update', $doc->id) : route('whatsapp.kb.store') }}">
                @csrf
                @if($doc->exists)
                    @method('PUT')
                @endif

                <div class="mb-3">
                    <label class="form-label">Judul</label>
                    <input type="text" name="title" value="{{ old('title', $doc->title) }}" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Konten</label>
                    <textarea name="content" rows="10" class="form-control" required>{{ old('content', $doc->content) }}</textarea>
                </div>

                <div class="row g-3">
                    <div class="col-12 col-lg-4">
                        <label class="form-label">Kategori (Master)</label>
                        <select name="category_id" class="form-select">
                            <option value="">-</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" @selected((string) old('category_id', $doc->category_id) === (string) $cat->id)>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-lg-4">
                        <label class="form-label">Kategori (Text)</label>
                        <input type="text" name="category" value="{{ old('category', $doc->category) }}" class="form-control" placeholder="FAQ / SOP / Panduan ONU ...">
                    </div>
                    <div class="col-12 col-lg-4">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" required>
                            @foreach(['draft','published','archived'] as $st)
                                <option value="{{ $st }}" @selected(old('status', $doc->status ?: 'draft') === $st)>{{ $st }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mt-3 mb-3">
                    <label class="form-label">Tag (pisahkan dengan koma)</label>
                    <input type="text" name="tags_csv" value="{{ old('tags_csv', $tagsCsv) }}" class="form-control" placeholder="onu, mikrotik, billing, sop">
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">{{ $doc->exists ? 'Simpan Perubahan' : 'Simpan' }}</button>
                    <a href="{{ route('whatsapp.kb.index') }}" class="btn btn-outline-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

