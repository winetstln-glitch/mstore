@extends('layouts.app')

@section('title', __('Manajemen Pengguna'))

@section('content')
<div class="mb-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
        <div>
            <h4 class="fw-bold text-primary mb-1">{{ __('Manajemen Pengguna') }}</h4>
            <p class="text-muted small mb-0">{{ __('Kelola pengguna sistem dan perannya.') }}</p>
        </div>
        <div class="d-flex flex-column flex-xl-row gap-2 w-100 justify-content-xl-end align-items-stretch align-items-xl-center">
            <x-table-toolbar 
                :route="route('users.index')" 
                searchPlaceholder="Cari pengguna..." 
                :showRoleFilter="true" 
                :roles="$roles ?? collect()" 
            />

            <div class="d-flex flex-wrap gap-2 w-100 w-xl-auto">
                <a href="{{ route('users.export', request()->query()) }}" class="btn btn-sm btn-outline-success text-nowrap flex-fill flex-sm-grow-0">
                    <i class="fa-solid fa-file-excel me-1"></i> {{ __('Ekspor Excel') }}
                </a>
                <a href="{{ route('users.create') }}" class="btn btn-sm btn-outline-primary text-nowrap flex-fill flex-sm-grow-0">
                    <i class="fa-solid fa-plus me-1"></i> {{ __('Tambah Pengguna Baru') }}
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                {{-- Alerts handled by SweetAlert in Layout --}}

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 table-responsive-mobile">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-3 text-uppercase small text-muted border-0">{{ __('Nama / Jabatan') }}</th>
                                <th class="text-uppercase small text-muted border-0">{{ __('Email') }}</th>
                                <th class="text-uppercase small text-muted border-0">{{ __('Peran + Scope') }}</th>
                                <th class="text-uppercase small text-muted border-0">{{ __('Status') }}</th>
                                <th class="text-end pe-3 text-uppercase small text-muted border-0">{{ __('Aksi') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $user)
                            <tr>
                                <td class="ps-3 fw-medium">
                                    <div>{{ $user->name }}</div>
                                    @if(trim((string) $user->job_title) !== '')
                                        <div class="small text-primary fw-normal mb-1">
                                            <i class="fa-solid fa-briefcase me-1"></i>{{ $user->job_title }}
                                        </div>
                                    @endif
                                    <div class="small text-muted">
                                        <i class="fa-solid fa-id-badge me-1"></i>{{ $user->attendance_card_code ?: $user->username }}
                                        @if($user->phone)
                                            <span class="mx-1">·</span>
                                            <i class="fa-solid fa-phone me-1"></i>{{ $user->phone }}
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    {{ $user->email ?: '-' }}
                                </td>
                                <td>
                                    @if($user->role)
                                        <div class="mb-1">
                                            <span class="badge bg-info-subtle text-info border border-info-subtle">
                                                <i class="fa-solid fa-key me-1"></i>{{ $user->role->label }}
                                            </span>
                                        </div>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle mb-1 d-inline-block">
                                            {{ __('Tanpa Peran') }}
                                        </span>
                                    @endif
                                    @php
                                        $buList = $user->bu;
                                        $buLevel = $user->bu_level;
                                        $dept = $user->department;
                                        $fieldLevel = $user->field_level;
                                    @endphp
                                    <div class="d-flex flex-wrap gap-1">
                                        @if(!empty($buList))
                                            @foreach($buList as $buCode)
                                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle small">
                                                    <i class="fa-solid fa-store me-1"></i>{{ $buCode }}
                                                </span>
                                            @endforeach
                                        @endif
                                        @if(!empty($buLevel))
                                            <span class="badge bg-orange-subtle text-orange-emphasis border border-orange-subtle small" style="background:#fff3e0;color:#e65100;border-color:#ffe0b2;">
                                                Lv. {{ $buLevel }}
                                            </span>
                                        @endif
                                        @if(!empty($dept))
                                            <span class="badge bg-purple-subtle text-purple-emphasis border border-purple-subtle small" style="background:#f3e5f5;color:#4a148c;border-color:#e1bee7;">
                                                <i class="fa-solid fa-building me-1"></i>{{ $dept }}
                                            </span>
                                        @endif
                                        @if(!empty($fieldLevel))
                                            <span class="badge bg-teal-subtle text-teal-emphasis border border-teal-subtle small" style="background:#e0f2f1;color:#004d40;border-color:#b2dfdb;">
                                                <i class="fa-solid fa-hard-hat me-1"></i>{{ $fieldLevel }}
                                            </span>
                                        @endif
                                        @if(empty($buList) && empty($buLevel) && empty($dept) && empty($fieldLevel))
                                            <span class="small text-muted">—</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    @if($user->is_active)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">{{ __('Aktif') }}</span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">{{ __('Tidak Aktif') }}</span>
                                    @endif
                                </td>
                                <td class="text-end pe-3">
                                    <div class="d-flex justify-content-end gap-1">
                                        <a href="{{ route('users.id-card', $user) }}" class="btn btn-sm btn-outline-dark" title="ID Card">
                                            <i class="fa-solid fa-id-card"></i>
                                        </a>
                                        <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-outline-primary" title="{{ __('Ubah') }}">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </a>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-success"
                                            title="{{ __('WhatsApp Notif') }}"
                                            data-bs-toggle="modal"
                                            data-bs-target="#sendAccountWaModal"
                                            data-send-url="{{ route('users.send-whatsapp-account', $user) }}"
                                            data-user-name="{{ $user->name }}"
                                            data-user-username="{{ $user->username }}"
                                            data-user-phone="{{ $user->phone }}"
                                            {{ empty($user->phone) ? 'disabled' : '' }}
                                        >
                                            <i class="fa-brands fa-whatsapp"></i>
                                        </button>
                                        
                                        @if(auth()->user()?->isSuperAdmin() && $user->id !== auth()->id())
                                            <form action="{{ route('users.destroy', $user) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Yakin ingin menghapus pengguna ini?') }}');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="{{ __('Hapus') }}">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($users instanceof \Illuminate\Pagination\LengthAwarePaginator)
                <div class="mt-4">
                    {{ $users->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="sendAccountWaModal" tabindex="-1" aria-labelledby="sendAccountWaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" id="sendAccountWaForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="sendAccountWaModalLabel">{{ __('Kirim Akun via WhatsApp') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Tutup') }}"></button>
                </div>
                <div class="modal-body">
                    <div class="small text-muted mb-2">{{ __('Data akun akan dikirim lengkap ke WhatsApp pengguna ini:') }}</div>
                    <div class="mb-2"><strong>{{ __('Nama') }}:</strong> <span id="waUserName">-</span></div>
                    <div class="mb-2"><strong>{{ __('Username') }}:</strong> <span id="waUsername">-</span></div>
                    <div class="mb-3"><strong>{{ __('Nomor HP') }}:</strong> <span id="waUserPhone">-</span></div>
                    <label for="send_password" class="form-label">{{ __('Password yang akan dikirim') }}</label>
                    <input type="text" class="form-control" id="send_password" name="send_password" value="12345678" required>
                    <div class="form-text">{{ __('Silakan isi password akun yang benar sebelum mengirim.') }}</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Batal') }}</button>
                    <button type="submit" class="btn btn-success">{{ __('Kirim ke WhatsApp') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('sendAccountWaModal');
    if (!modal) return;

    modal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        if (!button) return;

        const sendUrl = button.getAttribute('data-send-url') || '';
        const userName = button.getAttribute('data-user-name') || '-';
        const username = button.getAttribute('data-user-username') || '-';
        const phone = button.getAttribute('data-user-phone') || '-';

        const form = document.getElementById('sendAccountWaForm');
        if (form) form.action = sendUrl;

        const nameEl = document.getElementById('waUserName');
        const usernameEl = document.getElementById('waUsername');
        const phoneEl = document.getElementById('waUserPhone');
        if (nameEl) nameEl.textContent = userName;
        if (usernameEl) usernameEl.textContent = username;
        if (phoneEl) phoneEl.textContent = phone;
    });
});
</script>
@endpush
