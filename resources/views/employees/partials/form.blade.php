<div class="col-md-6">
    <label class="form-label">Nama Lengkap *</label>
    <input type="text" name="full_name" class="form-control @error('full_name') is-invalid @enderror" value="{{ old('full_name', $employee->full_name ?? '') }}" required>
    @error('full_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="col-md-6">
    <label class="form-label">Link ke User (opsional)</label>
    <select name="user_id" id="user_id" class="form-select @error('user_id') is-invalid @enderror">
        <option value="">-- Tidak di-link --</option>
        @foreach(($users ?? []) as $u)
            <option value="{{ $u->id }}" data-role-label="{{ $u->role?->label ?? $u->role?->name }}" data-role-name="{{ $u->role?->name }}" {{ (string) old('user_id', $employee->user_id ?? '') === (string) $u->id ? 'selected' : '' }}>
                {{ $u->name }} @if($u->username) ({{ $u->username }}) @endif — {{ $u->role?->label ?? $u->role?->name }}
            </option>
        @endforeach
    </select>
    @error('user_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    <div class="form-text text-primary small"><i class="fa-solid fa-circle-info me-1"></i> Jika di-link, data Jabatan dan Departemen akan disinkronkan dari Role User.</div>
</div>
<div class="col-md-3">
    <label class="form-label">Tanggal Lahir *</label>
    <input type="date" name="date_of_birth" class="form-control @error('date_of_birth') is-invalid @enderror" value="{{ old('date_of_birth', isset($employee?->date_of_birth) ? $employee->date_of_birth->format('Y-m-d') : '') }}" required>
    @error('date_of_birth')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="col-md-3">
    <label class="form-label">Jenis Kelamin *</label>
    <select name="gender" class="form-select @error('gender') is-invalid @enderror" required>
        <option value="">Pilih</option>
        @foreach(['Laki-laki','Perempuan'] as $g)
            <option value="{{ $g }}" {{ old('gender', $employee->gender ?? '') === $g ? 'selected' : '' }}>{{ $g }}</option>
        @endforeach
    </select>
    @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="col-md-12">
    <label class="form-label">Alamat *</label>
    <textarea name="address" rows="2" class="form-control @error('address') is-invalid @enderror" required>{{ old('address', $employee->address ?? '') }}</textarea>
    @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="col-md-6">
    <label class="form-label">Link ke Karyawan Wash (opsional)</label>
    <select name="wash_employee_id" class="form-select @error('wash_employee_id') is-invalid @enderror">
        <option value="">-- Tidak di-link --</option>
        @foreach(($washEmployees ?? []) as $we)
            <option value="{{ $we->id }}" {{ (string) old('wash_employee_id', $employee->wash_employee_id ?? '') === (string) $we->id ? 'selected' : '' }}>
                {{ $we->name }} @if($we->phone) ({{ $we->phone }}) @endif
            </option>
        @endforeach
    </select>
    @error('wash_employee_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="col-md-4">
    <label class="form-label">No HP *</label>
    <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $employee->phone ?? '') }}" required>
    @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="col-md-4">
    <label class="form-label">Email *</label>
    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $employee->email ?? '') }}" required>
    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="col-md-4">
    <label class="form-label">NIK *</label>
    <input type="text" name="nik" class="form-control @error('nik') is-invalid @enderror" value="{{ old('nik', $employee->nik ?? '') }}" required>
    @error('nik')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="col-md-4">
    <label class="form-label">Jabatan *</label>
    @php
        $selectedPosition = old('position', $employee->position ?? '');
        // Merge existing role labels with the default ones if not present
        $positionOptions = array_unique(array_merge(
            ['Administrasi', 'Kasir', 'Teknisi', 'Operator Wash', 'NOC', 'Keuangan'],
            $roleLabels ?? []
        ));
        sort($positionOptions);
    @endphp
    <select name="position" id="position" class="form-select @error('position') is-invalid @enderror" required>
        <option value="">Pilih Jabatan</option>
        @foreach($positionOptions as $positionOption)
            <option value="{{ $positionOption }}" {{ $selectedPosition === $positionOption ? 'selected' : '' }}>{{ $positionOption }}</option>
        @endforeach
        @if($selectedPosition && !in_array($selectedPosition, $positionOptions, true))
            <option value="{{ $selectedPosition }}" selected>{{ $selectedPosition }}</option>
        @endif
    </select>
    @error('position')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="col-md-4">
    <label class="form-label">Departemen *</label>
    @php
        $departmentOptions = ['Administrasi', 'Keuangan', 'Teknis', 'Wash', 'ATK', 'Operasional'];
        $selectedDepartment = old('department', $employee->department ?? '');
    @endphp
    <select name="department" id="department" class="form-select @error('department') is-invalid @enderror" required>
        <option value="">Pilih Departemen</option>
        @foreach($departmentOptions as $departmentOption)
            <option value="{{ $departmentOption }}" {{ $selectedDepartment === $departmentOption ? 'selected' : '' }}>{{ $departmentOption }}</option>
        @endforeach
        @if($selectedDepartment && !in_array($selectedDepartment, $departmentOptions, true))
            <option value="{{ $selectedDepartment }}" selected>{{ $selectedDepartment }}</option>
        @endif
    </select>
    @error('department')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const userSelect = document.getElementById('user_id');
    const positionSelect = document.getElementById('position');
    const departmentSelect = document.getElementById('department');

    if (userSelect) {
        userSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.value) {
                const roleLabel = selectedOption.getAttribute('data-role-label');
                const roleName = selectedOption.getAttribute('data-role-name');

                // Auto select position
                if (roleLabel) {
                    // Try to find matching option
                    let found = false;
                    for (let i = 0; i < positionSelect.options.length; i++) {
                        if (positionSelect.options[i].value === roleLabel) {
                            positionSelect.selectedIndex = i;
                            found = true;
                            break;
                        }
                    }
                    if (!found) {
                        const newOption = new Option(roleLabel, roleLabel, true, true);
                        positionSelect.add(newOption);
                    }
                }

                // Auto select department
                let department = 'Operasional';
                const role = roleName.toLowerCase();
                if (['technician', 'noc', 'network-operations-center'].includes(role)) {
                    department = 'Teknis';
                } else if (['admin', 'owner-pendiri'].includes(role)) {
                    department = 'Administrasi';
                } else if (role === 'finance') {
                    department = 'Keuangan';
                } else if (['kasir-wash', 'karyawan-wash'].includes(role)) {
                    department = 'Wash';
                } else if (role === 'kasir-atk') {
                    department = 'ATK';
                } else if (role === 'coordinator') {
                    department = 'Operasional';
                }

                for (let i = 0; i < departmentSelect.options.length; i++) {
                    if (departmentSelect.options[i].value === department) {
                        departmentSelect.selectedIndex = i;
                        break;
                    }
                }
            }
        });
    }
});
</script>
<div class="col-md-2">
    <label class="form-label">Tanggal Masuk *</label>
    <input type="date" name="join_date" class="form-control @error('join_date') is-invalid @enderror" value="{{ old('join_date', isset($employee?->join_date) ? $employee->join_date->format('Y-m-d') : '') }}" required>
    @error('join_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="col-md-2">
    <label class="form-label">Status *</label>
    <select name="employment_status" class="form-select @error('employment_status') is-invalid @enderror" required>
        <option value="">Pilih</option>
        @foreach(['Tetap','Kontrak','Magang'] as $s)
            <option value="{{ $s }}" {{ old('employment_status', $employee->employment_status ?? '') === $s ? 'selected' : '' }}>{{ $s }}</option>
        @endforeach
    </select>
    @error('employment_status')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="col-md-12">
    <label class="form-label">Upload Dokumen (PDF/DOC max 2MB)</label>
    <input type="file" name="document" class="form-control @error('document') is-invalid @enderror" accept=".pdf,.doc,.docx">
    @error('document')<div class="invalid-feedback">{{ $message }}</div>@enderror
    @if(!empty($employee?->document_path))
        <div class="form-text">Dokumen saat ini: <a href="{{ asset('storage/'.$employee->document_path) }}" target="_blank">Lihat File</a></div>
    @endif
</div>
<div class="col-md-6">
    <label class="form-label">Foto ID Card (JPG/PNG max 2MB)</label>
    <input type="file" name="id_card_photo" class="form-control @error('id_card_photo') is-invalid @enderror" accept=".jpg,.jpeg,.png,.webp,image/*">
    @error('id_card_photo')<div class="invalid-feedback">{{ $message }}</div>@enderror
    @if(!empty($employee?->id_card_photo_path))
        <div class="form-text">Foto saat ini: <a href="{{ asset('storage/'.$employee->id_card_photo_path) }}" target="_blank">Lihat Foto</a></div>
    @endif
</div>
<div class="col-md-3">
    <label class="form-label">Expired ID Card (opsional)</label>
    <input type="date" name="id_card_expires_at" class="form-control @error('id_card_expires_at') is-invalid @enderror" value="{{ old('id_card_expires_at', isset($employee?->id_card_expires_at) ? $employee->id_card_expires_at->format('Y-m-d') : '') }}">
    @error('id_card_expires_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="col-12 d-flex justify-content-end">
    <button type="submit" class="btn btn-primary">
        <i class="fa-solid fa-save me-1"></i> Simpan
    </button>
</div>
