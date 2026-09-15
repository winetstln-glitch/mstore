@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow-sm border-0 border-top border-4 border-primary mb-4">
                <div class="card-header py-3 bg-white">
                    <h5 class="mb-0 fw-bold"><i class="fa-solid fa-user-edit me-2"></i> {{ __('Ubah Pengguna') }}: {{ $user->name }}</h5>
                </div>

                <div class="card-body bg-light">
                    <form action="{{ route('users.update', $user) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <!-- Section: Informasi Profil -->
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                                <h6 class="fw-bold text-primary mb-0"><i class="fa-solid fa-address-card me-1"></i> Informasi Profil</h6>
                                <hr class="mt-2 mb-0">
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="name" class="form-label fw-medium">{{ __('Nama Lengkap') }}</label>
                                        <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" class="form-control @error('name') is-invalid @enderror" required>
                                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="email" class="form-label fw-medium">{{ __('Email') }}</label>
                                        <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" class="form-control @error('email') is-invalid @enderror">
                                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="phone" class="form-label fw-medium">{{ __('Nomor HP (WhatsApp)') }}</label>
                                        <input type="text" name="phone" id="phone" value="{{ old('phone', $user->phone) }}" class="form-control @error('phone') is-invalid @enderror">
                                        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="job_title" class="form-label fw-medium">{{ __('Jabatan Pekerjaan') }}</label>
                                        <input type="text" name="job_title" id="job_title" value="{{ old('job_title', $user->job_title) }}" class="form-control @error('job_title') is-invalid @enderror" maxlength="255" placeholder="{{ __('contoh: Team Leader Teknisi') }}">
                                        <div class="form-text small">{{ __('Jabatan struktural/fungsional di lapangan.') }}</div>
                                        @error('job_title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section: Akses & Autentikasi -->
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                                <h6 class="fw-bold text-primary mb-0"><i class="fa-solid fa-shield-halved me-1"></i> Akses & Keamanan Sistem</h6>
                                <hr class="mt-2 mb-0">
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="username" class="form-label fw-medium">{{ __('Username Login') }}</label>
                                        <input type="text" name="username" id="username" value="{{ old('username', $user->username) }}" class="form-control @error('username') is-invalid @enderror" required>
                                        @error('username') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label for="radius_username" class="form-label fw-medium">{{ __('Radius Username') }}</label>
                                        <input type="text" name="radius_username" id="radius_username" value="{{ old('radius_username', $user->radius_username) }}" class="form-control @error('radius_username') is-invalid @enderror">
                                        <div class="form-text small">Untuk akun pppoe/hotspot terikat.</div>
                                        @error('radius_username') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label for="role_id" class="form-label fw-medium">{{ __('Peran (Sistem Role)') }}</label>
                                        <select name="role_id" id="role_id" class="form-select @error('role_id') is-invalid @enderror" required>
                                            <option value="">{{ __('Pilih Peran') }}</option>
                                            @foreach($roles as $role)
                                                <option value="{{ $role->id }}" {{ old('role_id', $user->role_id) == $role->id ? 'selected' : '' }}>{{ $role->label }}</option>
                                            @endforeach
                                        </select>
                                        @error('role_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-12 mt-4">
                                        <div class="d-flex align-items-center gap-4 bg-light p-3 rounded border">
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input" type="checkbox" role="switch" name="is_active" id="is_active" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }}>
                                                <label class="form-check-label fw-medium" for="is_active">Akun Aktif (Dapat Login)</label>
                                            </div>
                                            <div class="form-check mb-0 text-danger">
                                                <input type="checkbox" name="reset_default_password" id="reset_default_password" value="1" class="form-check-input">
                                                <label class="form-check-label" for="reset_default_password">
                                                    Reset password ke default (12345678)
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section: Penempatan & Departemen -->
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                                <h6 class="fw-bold text-primary mb-0"><i class="fa-solid fa-sitemap me-1"></i> Penempatan Kerja & Scope</h6>
                                <hr class="mt-2 mb-0">
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="company_id" class="form-label fw-medium">{{ __('Perusahaan Induk') }}</label>
                                        <select name="company_id" id="company_id" class="form-select @error('company_id') is-invalid @enderror" required>
                                            <option value="">{{ __('Pilih Perusahaan') }}</option>
                                            @foreach($companies as $company)
                                                <option value="{{ $company->id }}" {{ old('company_id', $user->company_id) == $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('company_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="company_branch_id" class="form-label fw-medium">{{ __('Cabang Penempatan') }}</label>
                                        <select name="company_branch_id" id="company_branch_id" class="form-select @error('company_branch_id') is-invalid @enderror">
                                            <option value="">{{ __('Pilih Perusahaan terlebih dahulu') }}</option>
                                            @foreach($branches as $branch)
                                                <option value="{{ $branch->id }}" {{ old('company_branch_id', $user->company_branch_id) == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('company_branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>

                                    <div class="col-12 mt-3">
                                        <label class="form-label fw-medium d-block mb-2">{{ __('Akses Unit Bisnis (BU)') }}</label>
                                        <div class="d-flex flex-wrap gap-3 p-3 bg-light rounded border">
                                            @foreach($buOptions as $buCode => $buLabel)
                                                <div class="form-check form-check-inline m-0">
                                                    <input class="form-check-input shadow-sm" type="checkbox" name="bu[]" id="bu_{{ $buCode }}" value="{{ $buCode }}"
                                                        {{ in_array($buCode, $selectedBu) ? 'checked' : '' }}>
                                                    <label class="form-check-label small fw-medium" for="bu_{{ $buCode }}">{{ $buLabel }}</label>
                                                </div>
                                            @endforeach
                                        </div>
                                        @error('bu') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                    </div>

                                    <div class="col-md-4 mt-3">
                                        <label for="department" class="form-label fw-medium">{{ __('Departemen') }}</label>
                                        <select name="department" id="department" class="form-select @error('department') is-invalid @enderror">
                                            <option value="">{{ __('— Pilih (opsional) —') }}</option>
                                            @foreach($departmentOptions as $deptCode => $deptLabel)
                                                <option value="{{ $deptCode }}" {{ $selectedDepartment === $deptCode ? 'selected' : '' }}>{{ $deptLabel }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4 mt-3">
                                        <label for="bu_level" class="form-label fw-medium">{{ __('Level Operasional BU') }}</label>
                                        <select name="bu_level" id="bu_level" class="form-select @error('bu_level') is-invalid @enderror">
                                            <option value="">{{ __('— Pilih (opsional) —') }}</option>
                                            @foreach($buLevelOptions as $lv)
                                                <option value="{{ $lv }}" {{ $selectedBuLevel === $lv ? 'selected' : '' }}>{{ ucwords(str_replace(['-', '_'], ' ', $lv)) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4 mt-3">
                                        <label for="field_level" class="form-label fw-medium">{{ __('Level Lapangan') }}</label>
                                        <select name="field_level" id="field_level" class="form-select @error('field_level') is-invalid @enderror">
                                            <option value="">{{ __('— Pilih (opsional) —') }}</option>
                                            @foreach($fieldLevelOptions as $flCode => $flLabel)
                                                <option value="{{ $flCode }}" {{ $selectedFieldLevel === $flCode ? 'selected' : '' }}>{{ $flLabel }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section: Data Pegawai & Keuangan -->
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                                <h6 class="fw-bold text-primary mb-0"><i class="fa-solid fa-wallet me-1"></i> Payroll & Absensi</h6>
                                <hr class="mt-2 mb-0">
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-12 mb-2">
                                        <label for="attendance_card_code" class="form-label fw-medium">{{ __('Kode ID Card Absensi') }}</label>
                                        <input type="text" name="attendance_card_code" id="attendance_card_code" value="{{ old('attendance_card_code', $user->attendance_card_code) }}" class="form-control @error('attendance_card_code') is-invalid @enderror">
                                        <div class="form-text small">Gunakan kode unik ini untuk pembuatan barcode/QR kartu pegawai.</div>
                                        @error('attendance_card_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="monthly_salary" class="form-label fw-medium">{{ __('Gaji Pokok Bulanan (Rp)') }}</label>
                                        <input type="number" name="monthly_salary" id="monthly_salary" value="{{ old('monthly_salary', $user->monthly_salary) }}" class="form-control @error('monthly_salary') is-invalid @enderror" min="0">
                                        @error('monthly_salary') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="daily_salary" class="form-label fw-medium">{{ __('Gaji Harian (Rp)') }}</label>
                                        <input type="number" name="daily_salary" id="daily_salary" value="{{ old('daily_salary', $user->daily_salary) }}" class="form-control @error('daily_salary') is-invalid @enderror" min="0">
                                        <div class="form-text small">Otomatis terisi jika gaji bulanan diinput (dibagi 26 hari).</div>
                                        @error('daily_salary') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    
                                    <div class="col-md-4 mt-3">
                                        <label for="bank_name" class="form-label fw-medium">{{ __('Nama Bank') }}</label>
                                        <input type="text" name="bank_name" id="bank_name" value="{{ old('bank_name', $user->bank_name) }}" class="form-control @error('bank_name') is-invalid @enderror" placeholder="BCA, Mandiri, BRI...">
                                    </div>
                                    <div class="col-md-4 mt-3">
                                        <label for="bank_account_number" class="form-label fw-medium">{{ __('Nomor Rekening') }}</label>
                                        <input type="text" name="bank_account_number" id="bank_account_number" value="{{ old('bank_account_number', $user->bank_account_number) }}" class="form-control @error('bank_account_number') is-invalid @enderror">
                                    </div>
                                    <div class="col-md-4 mt-3">
                                        <label for="bank_account_name" class="form-label fw-medium">{{ __('Atas Nama') }}</label>
                                        <input type="text" name="bank_account_name" id="bank_account_name" value="{{ old('bank_account_name', $user->bank_account_name) }}" class="form-control @error('bank_account_name') is-invalid @enderror">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4 mb-2">
                            <a href="{{ route('users.index') }}" class="btn btn-light border px-4 shadow-sm">
                                {{ __('Batal') }}
                            </a>
                            <button type="submit" class="btn btn-primary px-4 shadow-sm">
                                <i class="fa-solid fa-save me-1"></i> {{ __('Simpan Perubahan') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const nameInput = document.getElementById('name');
        const usernameInput = document.getElementById('username');
        const companySelect = document.getElementById('company_id');
        const branchSelect = document.getElementById('company_branch_id');
        const monthlySalaryInput = document.getElementById('monthly_salary');
        const dailySalaryInput = document.getElementById('daily_salary');

        if (nameInput && usernameInput) {
            const slugify = (value) => value
                .toLowerCase()
                .normalize('NFKD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/[^a-z0-9]+/g, '_')
                .replace(/^_+|_+$/g, '');
            const syncUsername = () => {
                usernameInput.value = slugify(nameInput.value);
            };
            nameInput.addEventListener('input', function() {
                if (usernameInput.value === slugify(nameInput.defaultValue)) {
                    syncUsername();
                }
            });
        }

        if (companySelect && branchSelect) {
            const branchesByCompany = @json(
                $companies->mapWithKeys(fn ($c) => [
                    $c->id => $c->branches()->where('is_active', true)->orderBy('name')->get(['id', 'name'])
                ])
            );
            const selectedOldBranch = @json(old('company_branch_id', $user->company_branch_id));

            const refreshBranches = () => {
                const companyId = parseInt(companySelect.value, 10) || null;
                branchSelect.innerHTML = '';
                const defaultOpt = document.createElement('option');
                defaultOpt.value = '';
                defaultOpt.textContent = companyId
                    ? '{{ __('Pilih Cabang (opsional)') }}'
                    : '{{ __('Pilih Perusahaan terlebih dahulu') }}';
                branchSelect.appendChild(defaultOpt);

                if (companyId && branchesByCompany[companyId]) {
                    branchesByCompany[companyId].forEach(function (branch) {
                        const opt = document.createElement('option');
                        opt.value = branch.id;
                        opt.textContent = branch.name;
                        if (String(selectedOldBranch) === String(branch.id)) {
                            opt.selected = true;
                        }
                        branchSelect.appendChild(opt);
                    });
                }
            };

            companySelect.addEventListener('change', refreshBranches);
            refreshBranches();
        }

        if (monthlySalaryInput && dailySalaryInput) {
            monthlySalaryInput.addEventListener('input', function() {
                const monthly = parseFloat(this.value) || 0;
                const workingDays = 26; // Default standard working days
                const daily = Math.round(monthly / workingDays);
                dailySalaryInput.value = daily;
            });
        }
    });
</script>
@endpush
