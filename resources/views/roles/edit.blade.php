@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-lg-12">
        <div class="card shadow-sm border-0 border-top border-4 border-primary">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-primary"><i class="fa-solid fa-user-shield me-2"></i>{{ __('Edit Role') }}: {{ $role->label }}</h5>
                <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fa-solid fa-arrow-left me-1"></i> {{ __('Back to List') }}
                </a>
            </div>

            <div class="card-body  bg-opacity-50">
                <form action="{{ route('roles.update', $role) }}" method="POST" id="roleForm">
                    @csrf
                    @method('PUT')
                    
                    <!-- Role Name Section -->
                    <div class="card mb-4 border shadow-sm">
                        <div class="card-body">
                            <label for="label_select" class="form-label fw-bold text-secondary">{{ __('Role Template / Name') }}</label>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <select id="label_select" class="form-select select2" {{ $role->name === 'admin' ? 'disabled' : '' }}>
                                        <option value="">{{ __('Select Role Template') }}</option>
                                        @foreach($standardPermissions as $roleName => $perms)
                                            <option value="{{ $roleName }}" {{ $role->label == $roleName ? 'selected' : '' }}>
                                                {{ $roleName }} ({{ __('Template') }})
                                            </option>
                                        @endforeach
                                        <option value="Custom" {{ !array_key_exists($role->label, $standardPermissions) ? 'selected' : '' }}>
                                            {{ __('Custom / Other') }}
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <input type="text" id="label" name="label" 
                                        class="form-control {{ array_key_exists($role->label, $standardPermissions) ? 'd-none' : '' }}" 
                                        value="{{ $role->label }}"
                                        placeholder="{{ __('Enter custom role name') }}"
                                        {{ $role->name === 'admin' ? 'readonly' : '' }}>
                                    
                                    @if($role->name === 'admin')
                                        <div class="form-text text-warning small">
                                            <i class="fa-solid fa-lock me-1"></i> {{ __('System Admin name cannot be changed.') }}
                                        </div>
                                    @else
                                        <div class="form-text text-muted small">
                                            {{ __('Select a template to auto-fill permissions, or choose Custom to set manually.') }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Permissions Section -->
                    <div class="mb-4">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h5 class="fw-bold mb-0">{{ __('Manage Permissions') }}</h5>
                            <span class="badge bg-secondary-subtle text-body border">{{ count($permissions) }} {{ __('Groups') }}</span>
                        </div>
                        @php
                            $tabOf = function($group) {
                                if (in_array($group, ['Customer Management','Ticket Management','Installation Management','Service Management'])) return 'Pelanggan & Layanan';
                                if (in_array($group, ['ODC Management','ODP Management','HTB Management','OLT Management','Router Management','Map','Network Monitor'])) return 'Jaringan';
                                if (in_array($group, ['Finance','Investor Management'])) return 'Keuangan';
                                if (in_array($group, ['Technician Management','Attendance','Leave Management','Schedule Management','Inventory (Alat & Material)'])) return 'Operasional';
                                if ($group === 'ATK Store') return 'Toko ATK';
                                if ($group === 'Car Wash') return 'Cuci Kendaraan';
                                if (in_array($group, ['User Management','Role Management','Settings','Coordinator Management','Region Management','Package Management','WhatsApp','Telegram','Notification'])) return 'Sistem';
                                if (in_array($group, ['Dashboard','Utilities','Profile'])) return 'Umum';
                                return 'Lainnya';
                            };
                            $grouped = [];
                            foreach ($permissions as $g => $perms) {
                                $tab = $tabOf($g);
                                $grouped[$tab][$g] = $perms;
                            }
                            $tabsOrder = ['Pelanggan & Layanan','Jaringan','Keuangan','Operasional','Toko ATK','Cuci Kendaraan','Sistem','Umum','Lainnya'];
                            $tabs = array_values(array_intersect($tabsOrder, array_keys($grouped)));
                            foreach (array_keys($grouped) as $t) {
                                if (!in_array($t, $tabs)) $tabs[] = $t;
                            }
                            $activeTab = $tabs[0] ?? null;
                        @endphp
                        <ul class="nav nav-pills mb-3" role="tablist">
                            @foreach($tabs as $i => $tab)
                                @php $tid = \Illuminate\Support\Str::slug($tab); @endphp
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link {{ $i===0?'active':'' }}" id="tab-{{ $tid }}" data-bs-toggle="tab" data-bs-target="#pane-{{ $tid }}" type="button" role="tab" aria-controls="pane-{{ $tid }}" aria-selected="{{ $i===0?'true':'false' }}">{{ $tab }}</button>
                                </li>
                            @endforeach
                        </ul>
                        <div class="tab-content border rounded p-3 ">
                            @foreach($tabs as $i => $tab)
                                @php $tid = \Illuminate\Support\Str::slug($tab); @endphp
                                <div class="tab-pane fade {{ $i===0?'show active':'' }}" id="pane-{{ $tid }}" role="tabpanel" aria-labelledby="tab-{{ $tid }}">
                                    @foreach($grouped[$tab] as $group => $perms)
                                        <div class="card mb-3 border shadow-sm permission-group">
                                            <div class="card-header bg-body py-2 d-flex justify-content-between align-items-center">
                                                <h6 class="mb-0 fw-bold text-primary">{{ $group }}</h6>
                                                <div class="form-check form-switch mb-0">
                                                    <input class="form-check-input group-checkbox" type="checkbox" role="switch" id="group_{{ \Illuminate\Support\Str::slug($group) }}">
                                                    <label class="form-check-label small fw-semibold ms-1" for="group_{{ \Illuminate\Support\Str::slug($group) }}">{{ __('Select All') }}</label>
                                                </div>
                                            </div>
                                            <div class="card-body py-3">
                                                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-2">
                                                    @foreach($perms as $permission)
                                                        <div class="col">
                                                            <div class="form-check">
                                                                <input class="form-check-input permission-checkbox" id="perm_{{ $permission->id }}" name="permissions[]" type="checkbox" value="{{ $permission->id }}" {{ in_array($permission->id, $rolePermissions) ? 'checked' : '' }}>
                                                                <label class="form-check-label small" for="perm_{{ $permission->id }}">{{ $permission->label }}</label>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="d-flex justify-content-between align-items-center pt-3 border-top mt-3">
                        <a href="{{ route('roles.index') }}" class="btn btn-link text-muted text-decoration-none">
                            {{ __('Cancel') }}
                        </a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fa-solid fa-save me-2"></i> {{ __('Update Role') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const standardPermissions = @json($standardPermissions);
        const labelSelect = document.getElementById('label_select');
        const labelInput = document.getElementById('label');

        // --- Helper Functions ---

        // Update "Select All" switch state based on children checkboxes
        function updateGroupStatus(groupElement) {
            const checkboxes = groupElement.querySelectorAll('.permission-checkbox');
            const groupCheckbox = groupElement.querySelector('.group-checkbox');
            
            if (checkboxes.length === 0) return;

            const allChecked = Array.from(checkboxes).every(c => c.checked);
            const someChecked = Array.from(checkboxes).some(c => c.checked);
            
            groupCheckbox.checked = allChecked;
            // Optional: Indeterminate state (visual only)
            // groupCheckbox.indeterminate = someChecked && !allChecked;
        }

        // Initialize all groups on page load
        document.querySelectorAll('.permission-group').forEach(group => {
            updateGroupStatus(group);
        });

        // --- Event Listeners ---

        // 1. Toggle Group (Select All / Deselect All)
        document.querySelectorAll('.group-checkbox').forEach(groupCb => {
            groupCb.addEventListener('change', function() {
                const group = this.closest('.permission-group');
                const checkboxes = group.querySelectorAll('.permission-checkbox');
                
                checkboxes.forEach(cb => {
                    cb.checked = this.checked;
                    // Add visual highlight for checked items if desired
                    cb.closest('.form-check').classList.toggle('-subtle', this.checked);
                });
            });
        });

        // 2. Individual Checkbox Change (Update Group Switch)
        document.querySelectorAll('.permission-checkbox').forEach(cb => {
            cb.addEventListener('change', function() {
                const group = this.closest('.permission-group');
                updateGroupStatus(group);
                
                // Visual feedback
                this.closest('.form-check').classList.toggle('-subtle', this.checked);
            });
        });

        // 3. Role Template Selection Logic
        if (labelSelect) {
            labelSelect.addEventListener('change', function() {
                const selectedRole = this.value;

                if (selectedRole === 'Custom') {
                    // Show text input
                    labelInput.classList.remove('d-none');
                    
                    // Clear value if it was previously a standard role (optional, keeps current value if already custom)
                    if (standardPermissions[labelInput.value]) {
                        labelInput.value = '';
                    }
                    labelInput.focus();
                } else {
                    // Hide text input and apply template
                    labelInput.classList.add('d-none');
                    labelInput.value = selectedRole;

                    if (selectedRole && standardPermissions[selectedRole]) {
                        // Use SweetAlert2 for confirmation (Assuming Swal is loaded globally)
                        Swal.fire({
                            title: "{{ __('Apply Template?') }}",
                            text: "{{ __('This will reset current permissions to match the selected template.') }}",
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#0d6efd',
                            cancelButtonColor: '#6c757d',
                            confirmButtonText: "{{ __('Yes, apply it!') }}",
                            cancelButtonText: "{{ __('Cancel') }}"
                        }).then((result) => {
                            if (result.isConfirmed) {
                                applyPermissionsTemplate(standardPermissions[selectedRole]);
                            } else {
                                // Revert to previous selection if cancelled (Optional logic)
                                // For simplicity, we just leave the value as is but don't reset checkboxes
                                this.value = "Custom"; 
                                labelInput.classList.remove('d-none');
                                labelInput.value = selectedRole;
                            }
                        });
                    }
                }
            });
        }

        function applyPermissionsTemplate(ids) {
            // Uncheck everything first
            document.querySelectorAll('.permission-checkbox').forEach(cb => {
                cb.checked = false;
                cb.closest('.form-check').classList.remove('-subtle');
            });
            
            // Check specified IDs
            ids.forEach(id => {
                const cb = document.getElementById('perm_' + id);
                if (cb) {
                    cb.checked = true;
                    cb.closest('.form-check').classList.add('-subtle');
                }
            });
            
            // Refresh group switches
            document.querySelectorAll('.permission-group').forEach(group => {
                updateGroupStatus(group);
            });

            // Show success toast
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
            Toast.fire({
                icon: 'success',
                title: "{{ __('Permissions updated based on template') }}"
            });
        }
    });
</script>
@endsection
