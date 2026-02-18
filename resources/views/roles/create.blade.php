@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0 border-top border-4 border-primary">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">{{ __('Create New Role') }}</h5>
                <a href="{{ route('roles.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fa-solid fa-arrow-left me-1"></i> {{ __('Back to List') }}
                </a>
            </div>

            <div class="card-body">
                <form action="{{ route('roles.store') }}" method="POST">
                    @csrf
                    
                    <div class="mb-4">
                        <label for="label_select" class="form-label fw-bold">{{ __('Role Name (Label)') }}</label>
                        <select id="label_select" class="form-select mb-2">
                            <option value="">{{ __('Select Role Template') }}</option>
                            @foreach($standardPermissions as $roleName => $perms)
                                <option value="{{ $roleName }}">{{ $roleName }}</option>
                            @endforeach
                            <option value="Custom">{{ __('Custom / Other') }}</option>
                        </select>
                        
                        <input type="text" id="label" name="label" class="form-control d-none" placeholder="{{ __('Enter Custom Role Name') }}">
                        <div class="form-text">{{ __('System name (slug) will be generated automatically.') }}</div>
                    </div>

                    <div class="mb-4">
                        <h5 class="fw-bold mb-3">{{ __('Permissions') }}</h5>
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
                        @endphp
                        <ul class="nav nav-pills mb-3" role="tablist">
                            @foreach($tabs as $i => $tab)
                                @php $tid = \Illuminate\Support\Str::slug($tab); @endphp
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link {{ $i===0?'active':'' }}" id="tab-{{ $tid }}" data-bs-toggle="tab" data-bs-target="#pane-{{ $tid }}" type="button" role="tab" aria-controls="pane-{{ $tid }}" aria-selected="{{ $i===0?'true':'false' }}">{{ $tab }}</button>
                                </li>
                            @endforeach
                        </ul>
                        <div class="tab-content border rounded p-3 bg-body-tertiary">
                            @foreach($tabs as $i => $tab)
                                @php $tid = \Illuminate\Support\Str::slug($tab); @endphp
                                <div class="tab-pane fade {{ $i===0?'show active':'' }}" id="pane-{{ $tid }}" role="tabpanel" aria-labelledby="tab-{{ $tid }}">
                                    @foreach($grouped[$tab] as $group => $perms)
                                        <div class="card mb-3 border permission-group">
                                            <div class="card-header bg-body d-flex justify-content-between align-items-center py-2">
                                                <h6 class="mb-0 fw-bold">{{ $group }}</h6>
                                                <div class="form-check">
                                                    <input type="checkbox" class="form-check-input group-checkbox" onchange="toggleGroup(this)">
                                                    <label class="form-check-label small">{{ __('All') }}</label>
                                                </div>
                                            </div>
                                            <div class="card-body p-3">
                                                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-2">
                                                    @foreach($perms as $permission)
                                                        <div class="col">
                                                            <div class="form-check">
                                                                <input id="perm_{{ $permission->id }}" name="permissions[]" type="checkbox" value="{{ $permission->id }}" class="form-check-input permission-checkbox">
                                                                <label for="perm_{{ $permission->id }}" class="form-check-label small">{{ $permission->label }}</label>
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

                    <div class="d-flex justify-content-end pt-3 border-top">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-save me-1"></i> {{ __('Create Role') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleGroup(source) {
        const group = source.closest('.permission-group');
        const checkboxes = group.querySelectorAll('.permission-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = source.checked;
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        const standardPermissions = @json($standardPermissions);
        const labelSelect = document.getElementById('label_select');
        const labelInput = document.getElementById('label');

        if (labelSelect) {
            labelSelect.addEventListener('change', function() {
                const selectedRole = this.value;
                
                if (selectedRole === 'Custom') {
                    labelInput.classList.remove('d-none');
                    labelInput.value = '';
                    labelInput.focus();
                    // Optional: Clear permissions? No, maybe they want to start from previous selection.
                } else {
                    labelInput.classList.add('d-none');
                    labelInput.value = selectedRole;
                    
                    if (selectedRole && standardPermissions[selectedRole]) {
                        // Uncheck all first
                        document.querySelectorAll('.permission-checkbox').forEach(cb => cb.checked = false);
                        document.querySelectorAll('.group-checkbox').forEach(cb => cb.checked = false);
                        
                        // Check relevant ones
                        const ids = standardPermissions[selectedRole];
                        ids.forEach(id => {
                            const cb = document.getElementById('perm_' + id);
                            if (cb) cb.checked = true;
                        });
                        
                        // Update group checkboxes
                        document.querySelectorAll('.permission-group').forEach(group => {
                            const checkboxes = group.querySelectorAll('.permission-checkbox');
                            const groupCheckbox = group.querySelector('.group-checkbox');
                            if (checkboxes.length > 0 && groupCheckbox) {
                                const allChecked = Array.from(checkboxes).every(c => c.checked);
                                groupCheckbox.checked = allChecked;
                            }
                        });
                    }
                }
            });
        }
    });
</script>
@endsection
