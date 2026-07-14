@extends('layouts.app')

@section('title', __('Akun Float'))

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('Kelola Akun Float') }}</h1>
        <a href="{{ route('atk.float-accounts.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i>
            <span class="d-none d-md-inline ms-2">{{ __('Tambah Akun') }}</span>
        </a>
    </div>

    @include('atk._help-box', [
        'title' => 'Petunjuk Akun Float',
        'content' => '<p><strong>Halaman ini untuk mengelola akun float (bank/e-wallet)!</strong></p><ul class="mb-0"><li><strong>Tambah Akun:</strong> Klik "Tambah Akun" untuk membuat akun float baru.</li><li><strong>Detail Akun:</strong> Klik icon mata untuk melihat detail dan riwayat transaksi akun.</li><li><strong>Edit/Hapus:</strong> Gunakan icon edit atau hapus untuk mengubah atau menghapus akun.</li><li><strong>Status:</strong> Akun dengan status "Nonaktif" tidak akan muncul di POS.</li></ul>'
    ])

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered align-middle" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>{{ __('Kode') }}</th>
                            <th>{{ __('Nama') }}</th>
                            <th>{{ __('Tipe') }}</th>
                            <th>{{ __('Saldo') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Aksi') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($accounts as $account)
                        <tr>
                            <td>{{ $account->code }}</td>
                            <td>{{ $account->name }}</td>
                            <td>{{ $account->account_type }}</td>
                            <td>Rp {{ number_format($account->current_balance, 0, ',', '.') }}</td>
                            <td>
                                <span class="badge {{ $account->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $account->status === 'active' ? __('Aktif') : __('Nonaktif') }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('atk.float-accounts.show', $account) }}" class="btn btn-sm btn-info">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                <a href="{{ route('atk.float-accounts.edit', $account) }}" class="btn btn-sm btn-warning">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                <form action="{{ route('atk.float-accounts.destroy', $account) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus akun ini?')">
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
                            <td colspan="6" class="text-center">{{ __('Belum ada akun float.') }}</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $accounts->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
