@extends('layouts.app')

@section('title', 'Shift Wash')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Daftar Shift Wash & Caffe</h1>
        <a href="{{ route('wash.shifts.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Tambah Shift
        </a>
    </div>

    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>Nama Shift</th>
                            <th>Waktu Mulai</th>
                            <th>Waktu Selesai</th>
                            <th>Deskripsi</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($shifts as $shift)
                        <tr>
                            <td>{{ $shift->name }}</td>
                            <td>{{ $shift->start_time }}</td>
                            <td>{{ $shift->end_time }}</td>
                            <td>{{ $shift->description ?? '-' }}</td>
                            <td>
                                <span class="badge {{ $shift->is_active ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $shift->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="{{ route('wash.shifts.show', $shift) }}" class="btn btn-sm btn-outline-info" title="Detail">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('wash.shifts.edit', $shift) }}" class="btn btn-sm btn-outline-warning" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" action="{{ route('wash.shifts.destroy', $shift) }}" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus shift ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $shifts->links() }}
        </div>
    </div>
</div>
@endsection
