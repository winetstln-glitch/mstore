@extends('layouts.app')

@section('title', __('Dana Talangan Pemilik'))

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-2 mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('Dana Talangan Pemilik') }}</h1>
        <a href="{{ route('atk.owner-funds.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i>
            <span class="d-none d-md-inline ms-2">{{ __('Tambah Transaksi') }}</span>
        </a>
    </div>

    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('Saldo Aktif') }}</h6>
                </div>
                <div class="card-body text-center">
                    <h2 class="text-primary">Rp {{ number_format($currentBalance, 0, ',', '.') }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-8 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('Riwayat Transaksi') }}</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>{{ __('Kode Transaksi') }}</th>
                                    <th>{{ __('Tanggal') }}</th>
                                    <th>{{ __('Tipe') }}</th>
                                    <th>{{ __('Jumlah') }}</th>
                                    <th>{{ __('Saldo') }}</th>
                                    <th>{{ __('Aksi') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($funds as $fund)
                                <tr>
                                    <td>{{ $fund->transaction_code }}</td>
                                    <td>{{ optional($fund->transaction_date)->format('d M Y') ?? '-' }}</td>
                                    <td>
                                        <span class="badge {{ $fund->type === 'loan' ? 'bg-success' : 'bg-danger' }}">
                                            {{ $fund->type === 'loan' ? __('Pinjaman') : __('Pengembalian') }}
                                        </span>
                                    </td>
                                    <td>Rp {{ number_format($fund->amount, 0, ',', '.') }}</td>
                                    <td>Rp {{ number_format($fund->balance, 0, ',', '.') }}</td>
                                    <td>
                                        <a href="{{ route('atk.owner-funds.show', $fund) }}" class="btn btn-sm btn-info">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>
                                        <form action="{{ route('atk.owner-funds.destroy', $fund) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus transaksi ini?')">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center">{{ __('Belum ada transaksi dana talangan.') }}</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        {{ $funds->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
