<div class="row g-3">
    <!-- PERSONAL INFO -->
    <div class="col-md-7">
        <div class="card h-100 border-0 shadow-sm overflow-hidden">
            <div class="card-header bg-white py-3 border-0">
                <h6 class="fw-bold mb-0 text-primary"><i class="fa-solid fa-user me-2"></i>Data Personal & Kontak</h6>
            </div>
            <div class="card-body pt-0">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label x-small fw-bold text-uppercase text-muted">Nama Lengkap *</label>
                        <input type="text" name="full_name" id="full_name" class="form-control @error('full_name') is-invalid @enderror" value="{{ old('full_name', $employee->full_name ?? '') }}" placeholder="Contoh: Budi Santoso" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label x-small fw-bold text-uppercase text-muted">Email *</label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $employee->email ?? '') }}" placeholder="email@perusahaan.com" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label x-small fw-bold text-uppercase text-muted">No HP *</label>
                        <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $employee->phone ?? '') }}" placeholder="0812..." required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label x-small fw-bold text-uppercase text-muted">NIK *</label>
                        <input type="text" name="nik" class="form-control @error('nik') is-invalid @enderror" value="{{ old('nik', $employee->nik ?? '') }}" placeholder="Nomor Induk Karyawan" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label x-small fw-bold text-uppercase text-muted">Tgl Lahir *</label>
                        <input type="date" name="date_of_birth" class="form-control" value="{{ old('date_of_birth', isset($employee?->date_of_birth) ? $employee?->date_of_birth->format('Y-m-d') : '') }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label x-small fw-bold text-uppercase text-muted">Gender *</label>
                        <select name="gender" class="form-select" required>
                            <option value="Laki-laki" {{ old('gender', $employee->gender ?? '') === 'Laki-laki' ? 'selected' : '' }}>Laki-laki</option>
                            <option value="Perempuan" {{ old('gender', $employee->gender ?? '') === 'Perempuan' ? 'selected' : '' }}>Perempuan</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label x-small fw-bold text-uppercase text-muted">Alamat *</label>
                        <textarea name="address" rows="3" class="form-control" placeholder="Alamat lengkap domisili saat ini" required>{{ old('address', $employee->address ?? '') }}</textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ACCOUNT MANAGEMENT -->
    <div class="col-md-5">
        <div class="card h-100 border-0 shadow-sm overflow-hidden border-start border-primary border-4">
            <div class="card-header bg-white py-3 border-0">
                <h6 class="fw-bold mb-0 text-primary"><i class="fa-solid fa-key me-2"></i>Akses Login</h6>
            </div>
            <div class="card-body pt-0">
                <div class="p-3 bg-light rounded-3 mb-3 border">
                    <div class="form-check form-switch m-0">
                        <input class="form-check-input" type="checkbox" name="create_user_account" id="create_user_account" value="1" {{ old('create_user_account', $employee?->user_id ? '1' : '') ? 'checked' : '' }}>
                        <label class="form-check-label fw-bold small text-dark" for="create_user_account">
                            {{ $employee?->user_id ? 'Update / Aktifkan Akun' : 'Aktifkan Akses Login' }}
                        </label>
                    </div>
                </div>

                <div id="user_account_fields" style="display: {{ old('create_user_account', $employee?->user_id ? '1' : '') ? 'block' : 'none' }};">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label x-small fw-bold text-uppercase text-muted">Username *</label>
                            <input type="text" name="username" id="username" class="form-control @error('username') is-invalid @enderror" value="{{ old('username', $employee->user?->username ?? '') }}" placeholder="username_karyawan">
                            @error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label x-small fw-bold text-uppercase text-muted">Password {{ $employee?->user_id ? '(Kosongkan jika tetap)' : '*' }}</label>
                            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="Min 8 karakter">
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label x-small fw-bold text-uppercase text-muted">Role / Hak Akses *</label>
                            <select name="role_id" id="role_id" class="form-select">
                                <option value="">-- Pilih Hak Akses --</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}" data-label="{{ $role->label }}" data-name="{{ $role->name }}" {{ (string) old('role_id', $employee->user?->role_id ?? '') === (string) $role->id ? 'selected' : '' }}>
                                        {{ $role->label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="col-12 mt-4 pt-3 border-top">
                            <label class="form-label x-small fw-bold text-muted text-uppercase d-block mb-2 text-center">-- Atau Hubungkan User --</label>
                            <select name="user_id" id="user_id" class="form-select form-select-sm bg-light">
                                <option value="">Cari User Eksisting...</option>
                                @foreach(($users ?? []) as $u)
                                    <option value="{{ $u->id }}" data-role-label="{{ $u->role?->label ?? $u->role?->name }}" data-role-name="{{ $u->role?->name }}" {{ (string) old('user_id', $employee?->user_id ?? '') === (string) $u->id ? 'selected' : '' }}>
                                        {{ $u->name }} ({{ $u->username }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div id="no_account_msg" class="text-center py-4 text-muted small italic" style="display: {{ old('create_user_account', $employee?->user_id ? '1' : '') ? 'none' : 'block' }};">
                    <i class="fa-solid fa-user-lock fa-2x mb-2 d-block opacity-25"></i>
                    Akses login dinonaktifkan.
                </div>
            </div>
        </div>
    </div>

    <!-- JOB INFO -->
    <div class="col-12">
        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="card-header bg-white py-3 border-0">
                <h6 class="fw-bold mb-0 text-primary"><i class="fa-solid fa-briefcase me-2"></i>Informasi Pekerjaan</h6>
            </div>
            <div class="card-body pt-0">
                @php
                    $positionOptions = $positions ?? \App\Services\EmployeeSyncService::allPositions();
                    $deptOptions = $departments ?? \App\Services\EmployeeSyncService::allDepartments();
                @endphp
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label x-small fw-bold text-uppercase text-muted">Jabatan *</label>
                        <select name="position" id="position" class="form-select" required>
                            @php $pos = old('position', $employee->position ?? ''); @endphp
                            @foreach($positionOptions as $p)
                                <option value="{{ $p }}" {{ $pos === $p ? 'selected' : '' }}>{{ $p }}</option>
                            @endforeach
                            @if($pos && !in_array($pos, $positionOptions))
                                <option value="{{ $pos }}" selected>{{ $pos }}</option>
                            @endif
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label x-small fw-bold text-uppercase text-muted">Departemen *</label>
                        <select name="department" id="department" class="form-select" required>
                            @php $dep = old('department', $employee->department ?? ''); @endphp
                            @foreach($deptOptions as $d)
                                <option value="{{ $d }}" {{ $dep === $d ? 'selected' : '' }}>{{ $d }}</option>
                            @endforeach
                            @if($dep && !in_array($dep, $deptOptions))
                                <option value="{{ $dep }}" selected>{{ $dep }}</option>
                            @endif
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label x-small fw-bold text-uppercase text-muted">Tgl Masuk *</label>
                        <input type="date" name="join_date" class="form-control" value="{{ old('join_date', isset($employee?->join_date) ? $employee?->join_date->format('Y-m-d') : '') }}" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label x-small fw-bold text-uppercase text-muted">Status Kerja *</label>
                        <select name="employment_status" class="form-select" required>
                            @foreach(['Tetap','Training'] as $s)
                                <option value="{{ $s }}" {{ old('employment_status', $employee->employment_status ?? '') === $s ? 'selected' : '' }}>{{ $s }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label x-small fw-bold text-uppercase text-muted">Link Data Wash</label>
                        <select name="wash_employee_id" class="form-select">
                            <option value="">-- Tidak Ada --</option>
                            @foreach(($washEmployees ?? []) as $we)
                                <option value="{{ $we->id }}" {{ (string) old('wash_employee_id', $employee->wash_employee_id ?? '') === (string) $we->id ? 'selected' : '' }}>
                                    {{ $we->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="monthly_salary" class="form-label x-small fw-bold text-uppercase text-muted">{{ __('Gaji Pokok Bulanan (IDR)') }}</label>
                        <input type="number" name="monthly_salary" id="monthly_salary" value="{{ old('monthly_salary', $employee->monthly_salary ?? 0) }}" class="form-control @error('monthly_salary') is-invalid @enderror">
                        <div class="form-text x-small">{{ __('Gaji pokok satu bulan.') }}</div>
                    </div>
                    <div class="col-md-4">
                        <label for="daily_salary" class="form-label x-small fw-bold text-uppercase text-muted">{{ __('Gaji Harian Manual (IDR)') }}</label>
                        <input type="number" name="daily_salary" id="daily_salary" value="{{ old('daily_salary', $employee->daily_salary ?? 0) }}" class="form-control @error('daily_salary') is-invalid @enderror">
                        <div class="form-text x-small">{{ __('Isi jika ingin menggunakan nilai tetap per hari.') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- FILES -->
    <div class="col-12">
        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="card-header bg-white py-3 border-0">
                <h6 class="fw-bold mb-0 text-primary"><i class="fa-solid fa-file-invoice me-2"></i>Berkas & Media</h6>
            </div>
            <div class="card-body pt-0">
                <div class="row g-4">
                    <div class="col-md-4">
                        <label class="form-label x-small fw-bold text-uppercase text-muted d-block mb-3">Foto ID Card (Pas Foto)</label>
                        <div class="d-flex align-items-center gap-3 p-3 border rounded-3 bg-light-subtle">
                            <div id="photoPreviewContainer" class="{{ !empty($employee?->id_card_photo_path) ? '' : 'd-none' }}">
                                <img id="employeePhotoPreview" src="{{ !empty($employee?->id_card_photo_path) ? asset('storage/'.$employee?->id_card_photo_path) : '' }}" class="rounded shadow-sm border" style="width: 80px; height: 80px; object-fit: cover;">
                            </div>
                            <div class="flex-grow-1">
                                <input type="file" name="id_card_photo" id="employee_id_card_photo" class="form-control form-control-sm" accept="image/*">
                                <input type="hidden" name="id_card_photo_base64" id="employee_id_card_photo_base64">
                                <div class="x-small text-muted mt-1">Rekomendasi: Rasio 1:1 (Persegi)</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label x-small fw-bold text-uppercase text-muted">Masa Berlaku ID Card</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="fa-solid fa-calendar-day x-small text-muted"></i></span>
                            <input type="date" name="id_card_expires_at" class="form-control" value="{{ old('id_card_expires_at', isset($employee?->id_card_expires_at) ? $employee?->id_card_expires_at->format('Y-m-d') : '') }}">
                        </div>
                        <div class="form-text x-small">Kosongkan jika tidak ada masa berlaku.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label x-small fw-bold text-uppercase text-muted">Upload Dokumen Kontrak</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="fa-solid fa-file-pdf x-small text-muted"></i></span>
                            <input type="file" name="document" class="form-control" accept=".pdf,.doc,.docx">
                        </div>
                        <div class="form-text x-small">Format: PDF, DOC, DOCX. Max: 5MB.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 mt-4">
        <div class="d-flex justify-content-between align-items-center p-3 bg-white shadow-sm rounded-3 border">
            <span class="text-muted small italic">* Menunjukkan kolom yang wajib diisi.</span>
            <div class="d-flex gap-2">
                <a href="{{ route('employees.index') }}" class="btn btn-light px-4 border fw-semibold">Batal</a>
                <button type="submit" class="btn btn-primary px-5 shadow fw-bold">
                    <i class="fa-solid fa-save me-2"></i> Simpan Data Karyawan
                </button>
            </div>
        </div>
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

@php
    $rolePosMap = collect(\App\Services\EmployeeSyncService::positionRoleDepartmentMap())
        ->mapWithKeys(fn ($item) => [strtolower($item['role']) => ['position' => $item['position'], 'department' => $item['department']]])
        ->all();
    $posDeptMap = $positionDeptMap ?? collect(\App\Services\EmployeeSyncService::positionRoleDepartmentMap())
        ->mapWithKeys(fn ($item) => [$item['position'] => $item['department']])
        ->unique()
        ->all();
@endphp
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
    const monthlySalaryInput = document.getElementById('monthly_salary');
    const dailySalaryInput = document.getElementById('daily_salary');
    const workingDays = {{ \App\Models\Setting::getValue('attendance_working_days', 28) }};

    const ROLE_TO_POS_DEPT = @json($rolePosMap);
    const POS_TO_DEPT = @json($posDeptMap);

    function setSelectValue(selectEl, value) {
        if (!selectEl || !value) return;
        for (let i = 0; i < selectEl.options.length; i++) {
            if (selectEl.options[i].value === value) {
                selectEl.selectedIndex = i;
                return true;
            }
        }
        return false;
    }

    function setDepartmentByPosition(position) {
        if (!depSelect || !position) return;
        const dept = POS_TO_DEPT[position] || 'Operasional';
        if (!setSelectValue(depSelect, dept)) {
            const opt = new Option(dept, dept, true, true);
            depSelect.add(opt);
        }
    }

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

    // Handle Position Change -> Auto Department (new 2-way sync helper)
    if (posSelect) {
        posSelect.addEventListener('change', function() {
            setDepartmentByPosition(this.value);
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
        let targetPosition = label;
        let targetDept = 'Operasional';

        // Use centralized mapping if available
        if (role && ROLE_TO_POS_DEPT[role]) {
            targetPosition = ROLE_TO_POS_DEPT[role].position;
            targetDept = ROLE_TO_POS_DEPT[role].department;
        }

        if (targetPosition) {
            if (!setSelectValue(posSelect, targetPosition)) {
                posSelect.add(new Option(targetPosition, targetPosition, true, true));
            }
        }

        setDepartmentByPosition(targetPosition);
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
                    const container = document.getElementById('photoPreviewContainer');
                    if (container) container.classList.remove('d-none');
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
                        const container = document.getElementById('photoPreviewContainer');
                        if (container) container.classList.remove('d-none');
                    }
                } catch (e) {
                    // Fallback: server will use base64Hidden
                }

                hideCropModal();
            }, 'image/jpeg', 0.95);}
        });
    }

    if (monthlySalaryInput && dailySalaryInput) {
        monthlySalaryInput.addEventListener('input', function() {
            const monthly = parseFloat(this.value) || 0;
            const daily = Math.round(monthly / workingDays);
            dailySalaryInput.value = daily;
        });
    }
});
</script>
<style>
.x-small { font-size: 0.75rem; }
.bg-primary-subtle { background-color: #ebf5ff !important; }
.italic { font-style: italic; }
</style>
