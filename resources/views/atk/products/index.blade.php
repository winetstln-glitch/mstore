@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Manajemen Produk ATK</h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createProductModal">
            <i class="fas fa-plus"></i> Tambah Produk
        </button>
    </div>

    @if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
    @endif

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Gambar</th>
                            <th>Nama</th>
                            <th>Stok</th>
                            <th>Satuan</th>
                            <th>Harga Beli</th>
                            <th>Harga Ecer</th>
                            <th>Harga Grosir</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $product)
                        <tr>
                            <td>{{ $product->code }}</td>
                            <td>
                                @if($product->image)
                                    <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" width="50" height="50" class="img-thumbnail">
                                @else
                                    <span class="badge bg-secondary">No Image</span>
                                @endif
                            </td>
                            <td>{{ $product->name }}</td>
                            <td>
                                <span class="badge {{ $product->stock < 10 ? 'bg-warning' : 'bg-success' }}">
                                    {{ $product->stock }}
                                </span>
                            </td>
                            <td>{{ $product->unit }}</td>
                            <td>Rp {{ number_format($product->buy_price, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format($product->sell_price_retail, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format($product->sell_price_wholesale, 0, ',', '.') }}</td>
                            <td>
                                <button class="btn btn-sm btn-success" 
                                    onclick="restockProduct({{ $product }})"
                                    data-bs-toggle="modal" data-bs-target="#restockProductModal"
                                    title="Restock">
                                    <i class="fas fa-plus"></i>
                                </button>
                                <button class="btn btn-sm btn-info" 
                                    onclick="editProduct({{ $product }})"
                                    data-bs-toggle="modal" data-bs-target="#editProductModal"
                                    title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form action="{{ route('atk.products.destroy', $product->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin hapus produk ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center">Belum ada produk.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $products->links() }}
        </div>
    </div>
</div>

<!-- Create Modal -->
<div class="modal fade" id="createProductModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('atk.products.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Produk Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Gambar Produk</label>
                        <input type="file" name="image" class="form-control" accept="image/*">
                    </div>
                    <div class="mb-3">
                        <label>Kode Produk</label>
                        <input type="text" name="code" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Nama Produk</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Stok Awal</label>
                            <input type="number" name="stock" class="form-control" value="0" min="0" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Satuan</label>
                            <input type="text" name="unit" class="form-control" placeholder="pcs, pack..." required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label>Harga Beli</label>
                        <input type="number" name="buy_price" class="form-control" value="0" min="0" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Harga Jual (Ecer)</label>
                            <input type="number" name="sell_price_retail" class="form-control" value="0" min="0" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Harga Jual (Grosir)</label>
                            <input type="number" name="sell_price_wholesale" class="form-control" value="0" min="0" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editProductModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="editForm" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Produk</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3 text-center">
                        <img id="edit_image_preview" src="" alt="Preview" style="max-width: 150px; display: none;" class="img-thumbnail mb-2">
                    </div>
                    <div class="mb-3">
                        <label>Ganti Gambar</label>
                        <input type="file" name="image" class="form-control" accept="image/*">
                    </div>
                    <div class="mb-3">
                        <label>Kode Produk</label>
                        <input type="text" name="code" id="edit_code" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Nama Produk</label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Stok</label>
                            <input type="number" name="stock" id="edit_stock" class="form-control" min="0" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Satuan</label>
                            <input type="text" name="unit" id="edit_unit" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label>Harga Beli</label>
                        <input type="number" name="buy_price" id="edit_buy_price" class="form-control" min="0" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Harga Jual (Ecer)</label>
                            <input type="number" name="sell_price_retail" id="edit_sell_price_retail" class="form-control" min="0" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Harga Jual (Grosir)</label>
                            <input type="number" name="sell_price_wholesale" id="edit_sell_price_wholesale" class="form-control" min="0" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Restock Modal -->
<div class="modal fade" id="restockProductModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="restockForm" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Stok Produk</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Nama Produk</label>
                        <input type="text" id="restock_name" class="form-control" readonly>
                    </div>
                    <div class="mb-3">
                        <label>Jumlah Masuk</label>
                        <input type="number" name="quantity" class="form-control" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label>Catatan (Optional)</label>
                        <textarea name="note" class="form-control" rows="2" placeholder="Restock dari supplier..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Tambah Stok</button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function restockProduct(product) {
        document.getElementById('restockForm').action = "{{ route('atk.products.index') }}/" + product.id + "/restock";
        document.getElementById('restock_name').value = product.name;
    }

    function editProduct(product) {
        document.getElementById('editForm').action = "{{ route('atk.products.index') }}/" + product.id;
        document.getElementById('edit_code').value = product.code;
        document.getElementById('edit_name').value = product.name;
        document.getElementById('edit_stock').value = product.stock;
        document.getElementById('edit_unit').value = product.unit;
        document.getElementById('edit_buy_price').value = product.buy_price;
        document.getElementById('edit_sell_price_retail').value = product.sell_price_retail;
        document.getElementById('edit_sell_price_wholesale').value = product.sell_price_wholesale;

        // Image Preview
        const imgPreview = document.getElementById('edit_image_preview');
        if (product.image) {
            imgPreview.src = "{{ asset('storage') }}/" + product.image;
            imgPreview.style.display = 'block';
        } else {
            imgPreview.style.display = 'none';
            imgPreview.src = '';
        }
    }
</script>
@endpush
@endsection
