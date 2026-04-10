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
                        <input type="file" name="id_card_photo" class="form-control form-control-sm" accept="image/*">
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
});
</script>
<style>
.x-small { font-size: 0.75rem; }
.bg-primary-subtle { background-color: #ebf5ff !important; }
.italic { font-style: italic; }
</style>