<div class="row g-3">
    <!-- PERSONAL INFO -->
    <div class="col-md-7">
        <div class="card h-100 border-0 shadow-sm bg-light-subtle">
            <div class="card-body">
                <h6 class="fw-bold mb-3 text-primary"><i class="fa-solid fa-user me-2"></i>Data Personal & Kontak</h6>
                <div class="row g-2">
                    <div class="col-12">
                        <label class="form-label small fw-bold">Nama Lengkap *</label>
                        <input type="text" name="full_name" id="full_name" class="form-control form-control-sm @error('full_name') is-invalid @enderror" value="{{ old('full_name', $employee->full_name ?? '') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Email *</label>
                        <input type="email" name="email" class="form-control form-control-sm @error('email') is-invalid @enderror" value="{{ old('email', $employee->email ?? '') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">No HP *</label>
                        <input type="text" name="phone" class="form-control form-control-sm @error('phone') is-invalid @enderror" value="{{ old('phone', $employee->phone ?? '') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">NIK *</label>
                        <input type="text" name="nik" class="form-control form-control-sm @error('nik') is-invalid @enderror" value="{{ old('nik', $employee->nik ?? '') }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Tgl Lahir *</label>
                        <input type="date" name="date_of_birth" class="form-control form-control-sm" value="{{ old('date_of_birth', isset($employee?->date_of_birth) ? $employee->date_of_birth->format('Y-m-d') : '') }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Gender *</label>
                        <select name="gender" class="form-select form-select-sm" required>
                            <option value="Laki-laki" {{ old('gender', $employee->gender ?? '') === 'Laki-laki' ? 'selected' : '' }}>Laki-laki</option>
                            <option value="Perempuan" {{ old('gender', $employee->gender ?? '') === 'Perempuan' ? 'selected' : '' }}>Perempuan</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold">Alamat *</label>
                        <textarea name="address" rows="2" class="form-control form-control-sm" required>{{ old('address', $employee->address ?? '') }}</textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ACCOUNT MANAGEMENT -->
    <div class="col-md-5">
        <div class="card h-100 border-0 shadow-sm bg-primary-subtle border-start border-primary border-3">
            <div class="card-body">
                <h6 class="fw-bold mb-3 text-primary"><i class="fa-solid fa-key me-2"></i>Akun Login (User)</h6>
                
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" name="create_user_account" id="create_user_account" value="1" {{ old('create_user_account', $employee->user_id ? '1' : '') ? 'checked' : '' }}>
                    <label class="form-check-label fw-bold small" for="create_user_account">
                        {{ $employee->user_id ? 'Update / Aktifkan Akun' : 'Buat Akun Login Baru' }}
                    </label>
                </div>

                <div id="user_account_fields" style="display: {{ old('create_user_account', $employee->user_id ? '1' : '') ? 'block' : 'none' }};">
                    <div class="row g-2">
                        <div class="col-12">
                            <label class="form-label small fw-bold">Username *</label>
                            <input type="text" name="username" id="username" class="form-control form-control-sm @error('username') is-invalid @enderror" value="{{ old('username', $employee->user?->username ?? '') }}">
                            @error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Password {{ $employee->user_id ? '(kosongkan jika tidak ganti)' : '*' }}</label>
                            <input type="password" name="password" class="form-control form-control-sm @error('password') is-invalid @enderror">
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Role / Akses *</label>
                            <select name="role_id" id="role_id" class="form-select form-select-sm">
                                <option value="">-- Pilih Role --</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}" data-label="{{ $role->label }}" data-name="{{ $role->name }}" {{ (string) old('role_id', $employee->user?->role_id ?? '') === (string) $role->id ? 'selected' : '' }}>
                                        {{ $role->label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 mt-3 pt-2 border-top">
                            <label class="form-label x-small fw-bold text-muted text-uppercase">Atau Hubungkan Akun Eksisting</label>
                            <select name="user_id" id="user_id" class="form-select form-select-sm">
                                <option value="">-- Cari Akun User --</option>
                                @foreach(($users ?? []) as $u)
                                    <option value="{{ $u->id }}" data-role-label="{{ $u->role?->label ?? $u->role?->name }}" data-role-name="{{ $u->role?->name }}" {{ (string) old('user_id', $employee->user_id ?? '') === (string) $u->id ? 'selected' : '' }}>
                                        {{ $u->name }} ({{ $u->username }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div id="no_account_msg" class="text-muted small italic" style="display: {{ old('create_user_account', $employee->user_id ? '1' : '') ? 'none' : 'block' }};">
                    Karyawan ini tidak memiliki akses login ke sistem.
                </div>
            </div>
        </div>
    </div>

    <!-- JOB INFO -->
    <div class="col-12 mt-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="fw-bold mb-3 text-primary"><i class="fa-solid fa-briefcase me-2"></i>Pekerjaan</h6>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Jabatan *</label>
                        <select name="position" id="position" class="form-select form-select-sm" required>
                            @php $pos = old('position', $employee->position ?? ''); @endphp
                            @foreach(['Administrasi', 'Kasir', 'Teknisi', 'Operator Wash', 'NOC', 'Keuangan'] as $p)
                                <option value="{{ $p }}" {{ $pos === $p ? 'selected' : '' }}>{{ $p }}</option>
                            @endforeach
                            @if($pos && !in_array($pos, ['Administrasi', 'Kasir', 'Teknisi', 'Operator Wash', 'NOC', 'Keuangan']))
                                <option value="{{ $pos }}" selected>{{ $pos }}</option>
                            @endif
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Departemen *</label>
                        <select name="department" id="department" class="form-select form-select-sm" required>
                            @php $dep = old('department', $employee->department ?? ''); @endphp
                            @foreach(['Administrasi', 'Keuangan', 'Teknis', 'Wash', 'ATK', 'Operasional'] as $d)
                                <option value="{{ $d }}" {{ $dep === $d ? 'selected' : '' }}>{{ $d }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold">Tgl Masuk *</label>
                        <input type="date" name="join_date" class="form-control form-control-sm" value="{{ old('join_date', isset($employee?->join_date) ? $employee->join_date->format('Y-m-d') : '') }}" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold">Status *</label>
                        <select name="employment_status" class="form-select form-select-sm" required>
                            @foreach(['Tetap','Kontrak','Magang'] as $s)
                                <option value="{{ $s }}" {{ old('employment_status', $employee->employment_status ?? '') === $s ? 'selected' : '' }}>{{ $s }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold">Link Karyawan Wash</label>
                        <select name="wash_employee_id" class="form-select form-select-sm">
                            <option value="">-- Kosong --</option>
                            @foreach(($washEmployees ?? []) as $we)
                                <option value="{{ $we->id }}" {{ (string) old('wash_employee_id', $employee->wash_employee_id ?? '') === (string) $we->id ? 'selected' : '' }}>
                                    {{ $we->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- FILES -->
    <div class="col-12 mt-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="fw-bold mb-3 text-primary"><i class="fa-solid fa-file-invoice me-2"></i>Berkas & ID Card</h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Foto ID Card (Pas Foto)</label>
                        <input type="file" name="id_card_photo" id="employee_id_card_photo" class="form-control form-control-sm" accept="image/*">
                        <input type="hidden" name="id_card_photo_base64" id="employee_id_card_photo_base64">
                        @if(!empty($employee?->id_card_photo_path))
                            <div class="mt-2">
                                <img id="employeePhotoPreview" src="{{ asset('storage/'.$employee->id_card_photo_path) }}" class="img-thumbnail" style="height: 70px;">
                            </div>
                        @else
                            <div class="mt-2 d-none">
                                <img id="employeePhotoPreview" src="" class="img-thumbnail" style="height: 70px;">
                            </div>
                        @endif
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Expired ID Card</label>
                        <input type="date" name="id_card_expires_at" class="form-control form-control-sm" value="{{ old('id_card_expires_at', isset($employee?->id_card_expires_at) ? $employee->id_card_expires_at->format('Y-m-d') : '') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Dokumen (PDF/DOC)</label>
                        <input type="file" name="document" class="form-control form-control-sm" accept=".pdf,.doc,.docx">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 mt-4 d-flex justify-content-end gap-2">
        <a href="{{ route('employees.index') }}" class="btn btn-sm btn-light border px-3">Batal</a>
        <button type="submit" class="btn btn-sm btn-primary px-4 shadow-sm">
            <i class="fa-solid fa-save me-1"></i> Simpan Karyawan
        </button>
    </div>
</div>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
<div class="modal fade" id="employeePhotoCropModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow">
            <div class="modal-header">
                <h5 class="modal-title">Atur Foto ID Card</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div style="max-height: 70vh;">
                    <img id="employeeCropImage" src="" style="max-width: 100%; display: block;">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="employeeCropApplyBtn">Gunakan Foto</button>
            </div>
        </div>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const createUserCheck = document.getElementById('create_user_account');
    const userFields = document.getElementById('user_account_fields');
    const noAccountMsg = document.getElementById('no_account_msg');
    const fullNameInput = document.getElementById('full_name');
    const usernameInput = document.getElementById('username');
    const roleSelect = document.getElementById('role_id');
    const userSelect = document.getElementById('user_id');
    const posSelect = document.getElementById('position');
    const depSelect = document.getElementById('department');

    // Toggle user account fields
    if (createUserCheck) {
        createUserCheck.addEventListener('change', function() {
            userFields.style.display = this.checked ? 'block' : 'none';
            noAccountMsg.style.display = this.checked ? 'none' : 'block';
            
            // Auto fill username if empty
            if (this.checked && !usernameInput.value && fullNameInput.value) {
                usernameInput.value = fullNameInput.value.toLowerCase().replace(/\s+/g, '').substring(0, 20);
            }
        });
    }

    // Auto fill username from full name
    if (fullNameInput) {
        fullNameInput.addEventListener('blur', function() {
            if (createUserCheck.checked && !usernameInput.value) {
                usernameInput.value = this.value.toLowerCase().replace(/\s+/g, '').substring(0, 20);
            }
        });
    }

    // Handle Role Change -> Auto Job/Dept
    if (roleSelect) {
        roleSelect.addEventListener('change', function() {
            const opt = this.options[this.selectedIndex];
            if (opt.value) {
                const label = opt.getAttribute('data-label');
                const role = (opt.getAttribute('data-name') || '').toLowerCase();
                autoFillJobDept(label, role);
            }
        });
    }

    // Handle Existing User Select
    if (userSelect) {
        userSelect.addEventListener('change', function() {
            const opt = this.options[this.selectedIndex];
            if (opt.value) {
                const label = opt.getAttribute('data-role-label');
                const role = (opt.getAttribute('data-role-name') || '').toLowerCase();
                autoFillJobDept(label, role);
            }
        });
    }

    function autoFillJobDept(label, role) {
        if (label) {
            let found = false;
            for (let i = 0; i < posSelect.options.length; i++) {
                if (posSelect.options[i].value === label) {
                    posSelect.selectedIndex = i;
                    found = true; break;
                }
            }
            if (!found) {
                posSelect.add(new Option(label, label, true, true));
            }
        }

        let d = 'Operasional';
        if (['technician', 'noc', 'network-operations-center'].includes(role)) d = 'Teknis';
        else if (['admin', 'owner-pendiri'].includes(role)) d = 'Administrasi';
        else if (role === 'finance') d = 'Keuangan';
        else if (['kasir-wash', 'karyawan-wash'].includes(role)) d = 'Wash';
        else if (role === 'kasir-atk') d = 'ATK';

        for (let i = 0; i < depSelect.options.length; i++) {
            if (depSelect.options[i].value === d) {
                depSelect.selectedIndex = i; break;
            }
        }
    }

    const idPhotoInput = document.getElementById('employee_id_card_photo');
    const cropModalEl = document.getElementById('employeePhotoCropModal');
    const cropImageEl = document.getElementById('employeeCropImage');
    const cropApplyBtn = document.getElementById('employeeCropApplyBtn');
    const base64Hidden = document.getElementById('employee_id_card_photo_base64');
    const previewEl = document.getElementById('employeePhotoPreview');
    let cropper = null;
    let modalInstance = null;
    let backdropEl = null;
    let objectUrl = null;

    if (cropModalEl && window.bootstrap && bootstrap.Modal) {
        modalInstance = new bootstrap.Modal(cropModalEl);
    }

    function showCropModal() {
        if (!cropModalEl) return;
        if (modalInstance) {
            modalInstance.show();
            return;
        }

        cropModalEl.classList.add('show');
        cropModalEl.style.display = 'block';
        cropModalEl.removeAttribute('aria-hidden');
        document.body.classList.add('modal-open');

        backdropEl = document.createElement('div');
        backdropEl.className = 'modal-backdrop fade show';
        document.body.appendChild(backdropEl);

        setTimeout(() => {
            initCropper();
        }, 10);
    }

    function hideCropModal({ resetFileInput = false } = {}) {
        if (!cropModalEl) return;
        if (modalInstance) {
            modalInstance.hide();
        } else {
            cropModalEl.classList.remove('show');
            cropModalEl.style.display = 'none';
            cropModalEl.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('modal-open');
            if (backdropEl) {
                backdropEl.remove();
                backdropEl = null;
            }
        }

        if (cropper) {
            cropper.destroy();
            cropper = null;
        }
        if (objectUrl) {
            URL.revokeObjectURL(objectUrl);
            objectUrl = null;
        }
        cropImageEl.src = '';
        if (resetFileInput && idPhotoInput) {
            idPhotoInput.value = '';
        }
        if (resetFileInput && base64Hidden) {
            base64Hidden.value = '';
        }
    }

    function initCropper() {
        if (!cropImageEl || !cropImageEl.src) return;
        if (typeof Cropper === 'undefined') {
            return;
        }
        if (cropper) {
            cropper.destroy();
        }
        cropper = new Cropper(cropImageEl, {
            aspectRatio: 1,
            viewMode: 1,
            autoCropArea: 1,
            background: false,
            movable: true,
            zoomable: true,
            rotatable: true,
            scalable: false,
        });
    }

    if (idPhotoInput) {
        idPhotoInput.addEventListener('change', function () {
            const file = this.files && this.files[0] ? this.files[0] : null;
            if (!file) return;

            if (base64Hidden) {
                base64Hidden.value = '';
            }

            if (objectUrl) {
                URL.revokeObjectURL(objectUrl);
            }
            objectUrl = URL.createObjectURL(file);
            cropImageEl.src = objectUrl;
            showCropModal();
        });
    }

    if (cropModalEl) {
        cropModalEl.addEventListener('shown.bs.modal', function () {
            initCropper();
        });

        cropModalEl.addEventListener('hidden.bs.modal', function () {
            hideCropModal();
        });

        cropModalEl.querySelectorAll('[data-bs-dismiss="modal"]').forEach((btn) => {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                hideCropModal({ resetFileInput: true });
            });
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && cropModalEl.classList.contains('show')) {
                hideCropModal({ resetFileInput: true });
            }
        });
    }

    if (cropApplyBtn) {
        cropApplyBtn.addEventListener('click', function () {
            if (!idPhotoInput) return;
            if (!cropper) {
                if (previewEl && idPhotoInput.files && idPhotoInput.files[0]) {
                    previewEl.src = URL.createObjectURL(idPhotoInput.files[0]);
                    if (previewEl.parentElement) {
                        previewEl.parentElement.classList.remove('d-none');
                    }
                }
                hideCropModal();
                return;
            }
            const canvas = cropper.getCroppedCanvas({ width: 600, height: 600 });
            if (base64Hidden) {
                base64Hidden.value = canvas.toDataURL('image/jpeg', 0.95);
            }
            canvas.toBlob(function (blob) {
                if (!blob) return;

                try {
                    const original = idPhotoInput.files && idPhotoInput.files[0] ? idPhotoInput.files[0] : null;
                    const fileName = original ? original.name : 'id-card.jpg';
                    const file = new File([blob], fileName, { type: 'image/jpeg' });
                    const dt = new DataTransfer();
                    dt.items.add(file);
                    idPhotoInput.files = dt.files;

                    if (previewEl) {
                        previewEl.src = URL.createObjectURL(file);
                        if (previewEl.parentElement) {
                            previewEl.parentElement.classList.remove('d-none');
                        }
                    }
                } catch (e) {
                    // Fallback: server will use base64Hidden
                }

                hideCropModal();
            }, 'image/jpeg', 0.95);
        });
    }
});
</script>
<style>
.x-small { font-size: 0.75rem; }
.bg-primary-subtle { background-color: #ebf5ff !important; }
.italic { font-style: italic; }
</style>
