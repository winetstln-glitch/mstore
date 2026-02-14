@extends('layouts.app')

@section('title', __('Product Management'))

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('Product Management') }}</h1>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#importModal" title="{{ __('Import Excel') }}">
                <i class="fa-solid fa-file-import"></i>
                <span class="d-none d-md-inline ms-2">{{ __('Import Excel') }}</span>
            </button>
            <a href="{{ route('atk.products.export') }}" class="btn btn-success" title="{{ __('Export Excel') }}">
                <i class="fa-solid fa-file-export"></i>
                <span class="d-none d-md-inline ms-2">{{ __('Export Excel') }}</span>
            </a>
            <a href="{{ route('atk.products.create') }}" class="btn btn-primary" title="{{ __('Add Product') }}">
                <i class="fa-solid fa-plus"></i>
                <span class="d-none d-md-inline ms-2">{{ __('Add Product') }}</span>
            </a>
        </div>
    </div>

    <!-- Import Modal -->
    <div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="importModalLabel">{{ __('Import Products') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('atk.products.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <div class="fw-bold mb-1">{{ __('Panduan Import') }}</div>
                            <ul class="mb-2">
                                <li>{{ __('Gunakan file format .xlsx (bukan .xls)') }}</li>
                                <li>{{ __('Minimal kolom Nama Produk; kolom lain opsional') }}</li>
                                <li>{{ __('Jika Code kosong, sistem akan membuat otomatis') }}</li>
                                <li>{{ __('Urutan kolom yang didukung: Code, Name, Category, Price, Cost Price, Stock, Unit, Description') }}</li>
                                <li>{{ __('Price/Cost Price angka; Stock bilangan bulat; Unit default pcs') }}</li>
                            </ul>
                            <a href="{{ route('atk.products.export') }}" class="btn btn-outline-secondary btn-sm">
                                <i class="fa-solid fa-file-export me-1"></i> {{ __('Unduh Template (Export)') }}
                            </a>
                        </div>
                        <div class="mb-3">
                            <label for="file" class="form-label">{{ __('Choose Excel File') }}</label>
                            <input type="file" class="form-control" id="file" name="file" accept=".xlsx" required>
                            <small class="text-muted">{{ __('Hanya menerima .xlsx') }}</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('Import') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('atk.products.index') }}" class="row g-2 mb-3">
                <div class="col-sm-12 col-md-4">
                    <select name="category" class="form-select">
                        <option value="">{{ __('Semua Kategori') }}</option>
                        @php($opts = isset($categories) ? $categories : ['ATK','JASA POTOCOPY','JASA TRANSFER BANK'])
                        @foreach($opts as $opt)
                            <option value="{{ $opt }}" {{ request('category')===$opt ? 'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-12 col-md-2">
                    <button type="submit" class="btn btn-dark w-100" title="{{ __('Filter') }}">
                        <i class="fa-solid fa-filter"></i>
                        <span class="d-none d-md-inline ms-1">{{ __('Filter') }}</span>
                    </button>
                </div>
            </form>
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>{{ __('Image') }}</th>
                            <th>{{ __('Code') }}</th>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Category') }}</th>
                            <th>{{ __('Price') }}</th>
                            <th>{{ __('Stock') }}</th>
                            <th>{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $product)
                        <tr>
                            <td>
                                @if($product->image)
                                    <img src="{{ Storage::url($product->image) }}" alt="{{ $product->name }}" width="50" height="50" class="img-thumbnail object-fit-cover">
                                @else
                                    <div class="bg-light d-flex align-items-center justify-content-center text-muted border rounded" style="width: 50px; height: 50px;">
                                        <i class="fa-solid fa-image"></i>
                                    </div>
                                @endif
                            </td>
                            <td>{{ $product->code }}</td>
                            <td>{{ $product->name }}</td>
                            <td>{{ $product->category ?? '-' }}</td>
                            <td>Rp {{ number_format($product->price, 0, ',', '.') }}</td>
                            <td>
                                <span class="badge {{ $product->stock > 10 ? 'bg-success' : ($product->stock > 0 ? 'bg-warning' : 'bg-danger') }}">
                                    {{ $product->stock }} {{ $product->unit }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('atk.products.edit', $product) }}" class="btn btn-sm btn-info">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                <form action="{{ route('atk.products.destroy', $product) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center">{{ __('No products found.') }}</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $products->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
