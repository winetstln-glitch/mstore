@extends('layouts.app')

@section('title', __('Profile Settings'))

@section('content')
@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
<style>
    .profile-card {
        border-radius: 15px;
        overflow: hidden;
    }
    .nav-pills-custom .nav-link {
        color: #64748b;
        font-weight: 600;
        padding: 12px 20px;
        border-radius: 10px;
        transition: all 0.2s;
    }
    .nav-pills-custom .nav-link.active {
        background-color: #3b82f6;
        color: #fff;
        box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.2);
    }
    .nav-pills-custom .nav-link:hover:not(.active) {
        background-color: #f1f5f9;
        color: #334155;
    }
    .info-label {
        font-size: 0.85rem;
        color: #64748b;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.025em;
        margin-bottom: 4px;
    }
    .info-value {
        font-weight: 700;
        color: #1e293b;
        font-size: 1rem;
    }
    .avatar-wrapper {
        position: relative;
        display: inline-block;
    }
    .avatar-edit-badge {
        position: absolute;
        bottom: 0;
        right: 0;
        background: #3b82f6;
        color: #fff;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 3px solid #fff;
        cursor: pointer;
    }
    .img-container {
        max-height: 400px;
    }
    .preview {
        overflow: hidden;
        width: 160px; 
        height: 160px;
        margin: 10px;
        border-radius: 50%;
    }
    .id-card-preview-container {
        background: #0f172a;
        border-radius: 12px;
        padding: 15px;
    }
</style>
@endpush

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <!-- Header Section -->
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h3 class="fw-bold mb-1">{{ __('Profile Settings') }}</h3>
                    <p class="text-muted mb-0">{{ __('Manage your account settings and preferences.') }}</p>
                </div>
                <div class="badge bg-primary px-3 py-2 rounded-pill h5 mb-0">
                    {{ $statusText }}
                </div>
            </div>

            @if (session('status') === 'profile-updated' || session('success'))
                <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                    <i class="fa-solid fa-check-circle me-2"></i> {{ session('status') === 'profile-updated' ? __('Profile updated successfully.') : session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="row">
                <!-- Navigation Tabs -->
                <div class="col-md-3 mb-4">
                    <div class="nav flex-column nav-pills nav-pills-custom shadow-sm bg-white p-2 rounded-4" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                        <button class="nav-link active mb-2 text-start" id="v-pills-summary-tab" data-bs-toggle="pill" data-bs-target="#v-pills-summary" type="button" role="tab">
                            <i class="fa-solid fa-address-card me-2"></i> {{ __('Ringkasan Akun') }}
                        </button>
                        <button class="nav-link mb-2 text-start" id="v-pills-profile-tab" data-bs-toggle="pill" data-bs-target="#v-pills-profile" type="button" role="tab">
                            <i class="fa-solid fa-user-gear me-2"></i> {{ __('Informasi Profil') }}
                        </button>
                        <button class="nav-link mb-2 text-start" id="v-pills-security-tab" data-bs-toggle="pill" data-bs-target="#v-pills-security" type="button" role="tab">
                            <i class="fa-solid fa-shield-halved me-2"></i> {{ __('Keamanan') }}
                        </button>
                        <button class="nav-link mb-2 text-start" id="v-pills-idcard-tab" data-bs-toggle="pill" data-bs-target="#v-pills-idcard" type="button" role="tab">
                            <i class="fa-solid fa-id-badge me-2"></i> {{ __('Digital ID Card') }}
                        </button>
                        <hr class="my-2 opacity-25">
                        <button class="nav-link text-danger text-start" id="v-pills-danger-tab" data-bs-toggle="pill" data-bs-target="#v-pills-danger" type="button" role="tab">
                            <i class="fa-solid fa-trash-can me-2"></i> {{ __('Hapus Akun') }}
                        </button>
                    </div>
                </div>

                <!-- Tab Content -->
                <div class="col-md-9">
                    <div class="tab-content shadow-sm bg-white rounded-4 p-4" id="v-pills-tabContent">
                        
                        <!-- Ringkasan Akun Tab -->
                        <div class="tab-pane fade show active" id="v-pills-summary" role="tabpanel">
                            <h5 class="fw-bold mb-4">{{ __('Ringkasan Data Pelanggan') }}</h5>
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="p-3 bg-light rounded-3 h-100">
                                        <div class="mb-3">
                                            <div class="info-label">ID PELANGGAN</div>
                                            <div class="info-value text-primary">#{{ $idPelanggan }}</div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="info-label">NAMA LENGKAP</div>
                                            <div class="info-value">{{ $user->name }}</div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="info-label">EMAIL</div>
                                            <div class="info-value">{{ $user->email }}</div>
                                        </div>
                                        <div class="mb-0">
                                            <div class="info-label">ALAMAT</div>
                                            <div class="info-value text-wrap">{{ $customer->address ?? '-' }}</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="p-3 bg-light rounded-3 h-100">
                                        <div class="mb-3">
                                            <div class="info-label">SERVICE</div>
                                            <div class="info-value">{{ $serviceText }}</div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="info-label">TIPE PEMBAYARAN</div>
                                            <div class="info-value">{{ $paymentType }}</div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="info-label">TERDAFTAR SEJAK</div>
                                            <div class="info-value">{{ $registeredAt ? $registeredAt->format('d M Y') : '-' }}</div>
                                        </div>
                                        <div class="mb-0">
                                            <div class="info-label">JATUH TEMPO</div>
                                            <div class="info-value text-danger">{{ $dueDate ? $dueDate->format('d M Y') : '-' }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Informasi Profil Tab -->
                        <div class="tab-pane fade" id="v-pills-profile" role="tabpanel">
                            <h5 class="fw-bold mb-4">{{ __('Update Profil') }}</h5>
                            <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                                @csrf
                                @method('patch')

                                <div class="text-center mb-4">
                                    <div class="avatar-wrapper">
                                        <img id="avatar-preview" src="{{ $user->avatar ? asset('storage/' . $user->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&background=3b82f6&color=fff' }}" alt="Avatar" class="rounded-circle shadow-sm" style="width: 120px; height: 120px; object-fit: cover; border: 4px solid #fff;">
                                        <label for="avatar" class="avatar-edit-badge shadow-sm">
                                            <i class="fa-solid fa-camera fa-sm"></i>
                                        </label>
                                    </div>
                                    <input type="hidden" name="avatar_base64" id="avatar_base64">
                                    <input id="avatar" name="avatar" type="file" class="d-none" accept="image/*">
                                    <div class="mt-2 text-muted small">{{ __('Klik ikon kamera untuk ubah foto') }}</div>
                                    @error('avatar')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="name" class="form-label fw-semibold">{{ __('Nama Lengkap') }}</label>
                                        <input id="name" name="name" type="text" class="form-control rounded-3 @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required autocomplete="name">
                                        @error('name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label fw-semibold">{{ __('Email') }}</label>
                                        <input id="email" name="email" type="email" class="form-control rounded-3 @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required autocomplete="username">
                                        @error('email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="d-grid gap-2 mt-2">
                                    <button type="submit" class="btn btn-primary rounded-3 py-2 fw-bold">
                                        <i class="fa-solid fa-save me-1"></i> {{ __('Simpan Perubahan') }}
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Keamanan Tab -->
                        <div class="tab-pane fade" id="v-pills-security" role="tabpanel">
                            <h5 class="fw-bold mb-4">{{ __('Ganti Kata Sandi') }}</h5>
                            <form method="post" action="{{ route('password.update') }}">
                                @csrf
                                @method('put')

                                <div class="mb-3">
                                    <label for="current_password" class="form-label fw-semibold">{{ __('Kata Sandi Saat Ini') }}</label>
                                    <input id="current_password" name="current_password" type="password" class="form-control rounded-3 @error('current_password') is-invalid @enderror" autocomplete="current-password">
                                    @error('current_password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="password" class="form-label fw-semibold">{{ __('Kata Sandi Baru') }}</label>
                                    <input id="password" name="password" type="password" class="form-control rounded-3 @error('password') is-invalid @enderror" autocomplete="new-password">
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="password_confirmation" class="form-label fw-semibold">{{ __('Konfirmasi Kata Sandi Baru') }}</label>
                                    <input id="password_confirmation" name="password_confirmation" type="password" class="form-control rounded-3 @error('password_confirmation') is-invalid @enderror" autocomplete="new-password">
                                </div>

                                <div class="d-grid gap-2 mt-4">
                                    <button type="submit" class="btn btn-warning text-white rounded-3 py-2 fw-bold">
                                        <i class="fa-solid fa-key me-1"></i> {{ __('Update Kata Sandi') }}
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- ID Card Tab -->
                        <div class="tab-pane fade" id="v-pills-idcard" role="tabpanel">
                            <div class="d-flex align-items-center justify-content-between mb-4">
                                <h5 class="fw-bold mb-0">{{ __('Digital ID Card') }}</h5>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('profile.id_card') }}" target="_blank" class="btn btn-outline-info btn-sm rounded-pill px-3">
                                        <i class="fa-solid fa-eye me-1"></i> {{ __('Preview') }}
                                    </a>
                                    <a href="{{ route('profile.id_card.download') }}" class="btn btn-info btn-sm text-white rounded-pill px-3">
                                        <i class="fa-solid fa-download me-1"></i> {{ __('Download PDF') }}
                                    </a>
                                </div>
                            </div>
                            <div class="id-card-preview-container text-center">
                                <p class="text-white-50 small mb-3">{{ __('Tampilan Kartu Identitas Digital Anda') }}</p>
                                <iframe src="{{ route('profile.id_card') }}" style="width:100%; height:420px; border:none; border-radius:12px;"></iframe>
                            </div>
                        </div>

                        <!-- Danger Zone Tab -->
                        <div class="tab-pane fade" id="v-pills-danger" role="tabpanel">
                            <div class="alert alert-danger border-0 rounded-4">
                                <h5 class="fw-bold"><i class="fa-solid fa-triangle-exclamation me-2"></i>Hapus Akun</h5>
                                <p class="mb-0 small">Setelah akun dihapus, semua data akan hilang permanen. Tindakan ini tidak dapat dibatalkan.</p>
                            </div>
                            
                            <form method="post" action="{{ route('profile.destroy') }}" class="mt-4">
                                @csrf
                                @method('delete')
                                <div class="mb-3">
                                    <label for="password_delete" class="form-label fw-semibold">Konfirmasi Password</label>
                                    <input id="password_delete" name="password" type="password" class="form-control rounded-3 @error('password', 'userDeletion') is-invalid @enderror" placeholder="Masukkan password untuk konfirmasi">
                                    @error('password', 'userDeletion')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <button type="submit" class="btn btn-danger w-100 rounded-3 py-2 fw-bold" onclick="return confirm('Apakah Anda yakin ingin menghapus akun?')">
                                    <i class="fa-solid fa-trash-can me-1"></i> Hapus Akun Secara Permanen
                                </button>
                            </form>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Crop Modal -->
<div class="modal fade" id="cropModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="cropModalLabel">{{ __('Potong Foto Profil') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-4 align-items-center">
                    <div class="col-md-8">
                        <div class="img-container rounded-3 overflow-hidden bg-light">
                            <img id="image-to-crop" src="" alt="Picture">
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <p class="fw-bold mb-2 small text-muted">PREVIEW BUNDAR</p>
                        <div class="preview mx-auto shadow-sm"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">{{ __('Batal') }}</button>
                <button type="button" class="btn btn-primary rounded-pill px-4" id="cropButton">{{ __('Potong & Simpan') }}</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var avatarInput = document.getElementById('avatar');
        var avatarBase64Input = document.getElementById('avatar_base64');
        var avatarPreview = document.getElementById('avatar-preview');
        var image = document.getElementById('image-to-crop');
        var cropModalElement = document.getElementById('cropModal');
        var cropModal = new bootstrap.Modal(cropModalElement);
        var cropper;
        var cropSuccess = false;

        avatarInput.addEventListener('change', function (e) {
            var files = e.target.files;
            if (files && files.length > 0) {
                var file = files[0];
                var url = URL.createObjectURL(file);
                avatarInput.setAttribute('name', 'avatar');
                image.src = url;
                cropSuccess = false; 
                cropModal.show();
            }
        });

        cropModalElement.addEventListener('shown.bs.modal', function () {
            if (cropper) { cropper.destroy(); }
            cropper = new Cropper(image, {
                aspectRatio: 1,
                viewMode: 1,
                preview: '.preview',
                autoCropArea: 1,
            });
        });

        cropModalElement.addEventListener('hidden.bs.modal', function () {
            if (cropper) { cropper.destroy(); cropper = null; }
            if (!cropSuccess) {
                avatarInput.value = '';
                avatarBase64Input.value = '';
                avatarPreview.src = "{{ $user->avatar ? asset('storage/' . $user->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&background=3b82f6&color=fff' }}";
            }
        });

        document.getElementById('cropButton').addEventListener('click', function () {
            var btn = this;
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Memproses...';

            setTimeout(function() {
                try {
                    if (cropper) {
                        var canvas = cropper.getCroppedCanvas({ width: 500, height: 500 });
                        var base64data = canvas.toDataURL('image/jpeg', 0.85);
                        avatarBase64Input.value = base64data;
                        avatarPreview.src = base64data;
                        avatarInput.removeAttribute('name');
                        cropSuccess = true;
                        cropModal.hide();
                    }
                } catch (e) {
                    alert('Error: ' + e.message);
                } finally {
                    btn.disabled = false;
                    btn.innerHTML = '{{ __("Potong & Simpan") }}';
                }
            }, 50);
        });
    });
</script>
@endpush
@endsection
