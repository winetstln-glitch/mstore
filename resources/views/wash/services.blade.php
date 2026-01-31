@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h1 class="h3 mb-0 text-gray-800">Kelola Layanan Cuci</h1>
        </div>
        <div class="col-auto">
            <a href="{{ route('wash.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addServiceModal">
                <i class="fas fa-plus"></i> Tambah Layanan
            </button>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Nama Layanan</th>
                            <th>Kategori</th>
                            <th>Gambar</th>
                            <th>Tipe</th>
                            <th>Harga</th>
                            <th>Modal</th>
                            <th>Stok</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($services as $service)
                        <tr>
                            <td>{{ $service->name }}</td>
                            <td>{{ $service->category->name ?? '-' }}</td>
                            <td>
                                @if($service->image)
                                    <img src="{{ asset('storage/' . $service->image) }}" alt="{{ $service->name }}" width="50" height="50" class="img-thumbnail">
                                @else
                                    <span class="badge bg-secondary">No Image</span>
                                @endif
                            </td>
                            <td>
                                @if($service->vehicle_type == 'car')
                                    <i class="fas fa-car"></i> Mobil
                                @else
                                    <i class="fas fa-motorcycle"></i> Motor
                                @endif
                                <br>
                                <small class="text-muted">{{ ucfirst($service->type) }}</small>
                            </td>
                            <td>Rp {{ number_format($service->price, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format($service->cost_price, 0, ',', '.') }}</td>
                            <td>
                                @if($service->type == 'physical')
                                    {{ $service->stock }}
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                @if($service->is_active)
                                    <span class="badge badge-success">Aktif</span>
                                @else
                                    <span class="badge badge-secondary">Non-Aktif</span>
                                @endif
                            </td>
                            <td>
                                <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#editServiceModal{{ $service->id }}">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form action="{{ route('wash.services.destroy', $service->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-danger" onclick="return confirm('Hapus layanan ini?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>

                        <!-- Edit Modal -->
                        <div class="modal fade" id="editServiceModal{{ $service->id }}" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form action="{{ route('wash.services.update', $service->id) }}" method="POST" enctype="multipart/form-data">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit Layanan</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="form-group mb-3 text-center">
                                                @if($service->image)
                                                    <img src="{{ asset('storage/' . $service->image) }}" alt="Preview" style="max-width: 150px;" class="img-thumbnail mb-2">
                                                @endif
                                            </div>
                                            <div class="form-group mb-3">
                                                <label>Ganti Gambar</label>
                                                <input type="file" name="image" class="form-control" accept="image/*">
                                            </div>
                                            <div class="form-group mb-3">
                                                <label>Nama Layanan</label>
                                                <input type="text" name="name" class="form-control" value="{{ $service->name }}" required>
                                            </div>
                                            <div class="form-group mb-3">
                                                <label>Kategori</label>
                                                <select name="category_id" class="form-control">
                                                    <option value="">-- Pilih Kategori --</option>
                                                    @foreach($categories as $category)
                                                        <option value="{{ $category->id }}" {{ $service->category_id == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="form-group mb-3">
                                                <label>Tipe Kendaraan</label>
                                                <select name="vehicle_type" class="form-control" required>
                                                    <option value="car" {{ $service->vehicle_type == 'car' ? 'selected' : '' }}>Mobil</option>
                                                    <option value="motor" {{ $service->vehicle_type == 'motor' ? 'selected' : '' }}>Motor</option>
                                                </select>
                                            </div>
                                            <div class="form-group mb-3">
                                                <label>Jenis</label>
                                                <select name="type" class="form-control" onchange="toggleStock(this, 'edit_stock_{{ $service->id }}')" required>
                                                    <option value="service" {{ $service->type == 'service' ? 'selected' : '' }}>Jasa</option>
                                                    <option value="physical" {{ $service->type == 'physical' ? 'selected' : '' }}>Barang Fisik</option>
                                                </select>
                                            </div>
                                            <div class="form-group mb-3">
                                                <label>Modal</label>
                                                <input type="number" name="cost_price" class="form-control" value="{{ $service->cost_price }}" required>
                                            </div>
                                            <div class="form-group mb-3">
                                                <label>Harga Jual</label>
                                                <input type="number" name="price" class="form-control" value="{{ $service->price }}" required>
                                            </div>
                                            <div class="form-group mb-3" id="edit_stock_{{ $service->id }}" style="display: {{ $service->type == 'physical' ? 'block' : 'none' }};">
                                                <label>Stok</label>
                                                <input type="number" name="stock" class="form-control" value="{{ $service->stock ?? 0 }}">
                                            </div>
                                            <div class="form-group mb-3">
                                                <label>Status</label>
                                                <select name="is_active" class="form-control">
                                                    <option value="1" {{ $service->is_active ? 'selected' : '' }}>Aktif</option>
                                                    <option value="0" {{ !$service->is_active ? 'selected' : '' }}>Non-Aktif</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" class="btn btn-primary">Simpan</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addServiceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('wash.services.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Layanan Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label>Gambar Layanan</label>
                        <input type="file" name="image" class="form-control" accept="image/*">
                    </div>
                    <div class="form-group mb-3">
                        <label>Nama Layanan</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="form-group mb-3">
                        <label>Kategori</label>
                        <select name="category_id" class="form-control">
                            <option value="">-- Pilih Kategori --</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label>Tipe Kendaraan</label>
                        <select name="vehicle_type" class="form-control" required>
                            <option value="car">Mobil</option>
                            <option value="motor">Motor</option>
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label>Jenis</label>
                        <select name="type" class="form-control" onchange="toggleStock(this, 'add_stock')" required>
                            <option value="service" selected>Jasa</option>
                            <option value="physical">Barang Fisik</option>
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label>Modal</label>
                        <input type="number" name="cost_price" class="form-control" value="0" required>
                    </div>
                    <div class="form-group mb-3">
                        <label>Harga Jual</label>
                        <input type="number" name="price" class="form-control" required>
                    </div>
                    <div class="form-group mb-3" id="add_stock" style="display: none;">
                        <label>Stok</label>
                        <input type="number" name="stock" class="form-control" value="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function toggleStock(select, targetId) {
        const target = document.getElementById(targetId);
        if (select.value === 'physical') {
            target.style.display = 'block';
        } else {
            target.style.display = 'none';
        }
    }
</script>
@endpush
