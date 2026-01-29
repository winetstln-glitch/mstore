@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0 border-top border-4 border-primary">
            <div class="card-header py-3 d-flex justify-content-between align-items-center bg-white">
                <h5 class="mb-0 fw-bold text-primary">
                    <i class="fa-solid fa-user-shield me-2"></i>{{ __('Edit Role') }}: {{ $role->label }}
                </h5>
                <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fa-solid fa-arrow-left me-1"></i> {{ __('Back to List') }}
                </a>
            </div>

            <div class="card-body">
                <form action="{{ route('roles.update', $role) }}" method="POST" id="roleForm">
                    @csrf
                    @method('PUT')
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="label_select" class="form-label fw-bold">{{ __('Role Name (Label)') }}</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fa-solid fa-tag"></i></span>
                                <select id="label_select" class="form-select">
                                    <option value="">{{ __('Select Role Template') }}</option>
                                    @foreach($standardPermissions as $roleName => $perms)
                                        <option value="{{ $roleName }}" {{ $role->label == $roleName ? 'selected' : '' }}>{{ $roleName }}</option>
                                    @endforeach
                                    <option value="Custom" {{ !array_key_exists($role->label, $standardPermissions) ? 'selected' : '' }}>{{ __('Custom / Other') }}</option>
                                </select>
                            </div>
                            
                            <input type="text" id="label" name="label" class="form-control mt-2 {{ array_key_exists($role->label, $standardPermissions) ? 'd-none' : '' }}" value="{{ $role->label }}" placeholder="Enter custom role name...">
                            
                            <div class="mt-2">
                                <label class="form-label text-muted small mb-0">{{ __('Internal Name (Slug) - Cannot be changed') }}</label>
                                <input type="text" class="form-control form-control-sm bg-light text-muted" value="{{ $role->name }}" readonly>
                            </div>

                            @if($role->name === 'admin')
                                <div class="form-text text-warning"><i class="fa-solid fa-triangle-exclamation me-1"></i>{{ __('Note: Admin system name cannot be changed.') }}</div>
                            @endif
                        </div>
                        <div class="col-md-6 d-flex align-items-end justify-content-end">
                             <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-success btn-sm" onclick="selectAllGlobal()">
                                    <i class="fa-solid fa-check-double me-1"></i>{{ __('Select All') }}
                                </button>
                                <button type="button" class="btn btn-outline-danger btn-sm" onclick="deselectAllGlobal()">
                                    <i class="fa-regular fa-square me-1"></i>{{ __('Deselect All') }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-search text-muted"></i></span>
                            <input type="text" id="permissionSearch" class="form-control border-start-0" placeholder="{{ __('Search permissions...') }}">
                        </div>
                    </div>

                    @php
                        $categories = [
                            'System' => ['Dashboard', 'Profile', 'Notification', 'Role Management', 'User Management', 'Settings', 'Utilities', 'WhatsApp', 'Telegram', 'Guide'],
                            'Operations' => ['Customer Management', 'Ticket Management', 'Installation Management', 'Technician Management', 'Attendance', 'Leave Management', 'Schedule Management', 'Coordinator Management', 'Region Management'],
                            'Network' => ['Map', 'OLT Management', 'ODC Management', 'ODP Management', 'HTB Management', 'Router Management', 'PPPoE', 'PPPoE Management', 'Hotspot', 'Network Monitor', 'Radius'],
                            'Finance & Store' => ['Finance', 'Investor Management', 'Package Management', 'Inventory (Alat & Material)', 'ATK Cashier', 'Car Wash'],
                        ];

                        $getCategory = function($group) use ($categories) {
                            foreach($categories as $cat => $groups) {
                                if(in_array($group, $groups)) return $cat;
                            }
                            return 'Other';
                        };

                        $groupedPermissions = [];
                        // Sort permissions by group name first
                        $sortedPermissions = $permissions->sortBy(function($perms, $key) {
                            return $key;
                        });

                        foreach($sortedPermissions as $group => $perms) {
                            $cat = $getCategory($group);
                            $groupedPermissions[$cat][$group] = $perms;
                        }
                        
                        // Ensure 'Other' is last
                        if(isset($groupedPermissions['Other'])) {
                            $other = $groupedPermissions['Other'];
                            unset($groupedPermissions['Other']);
                            $groupedPermissions['Other'] = $other;
                        }
                    @endphp

                    <ul class="nav nav-tabs mb-3" id="permissionTabs" role="tablist">
                        @foreach($groupedPermissions as $category => $groups)
                            <li class="nav-item" role="presentation">
                                <button class="nav-link {{ $loop->first ? 'active' : '' }} fw-bold" id="tab-{{ Str::slug($category) }}" data-bs-toggle="tab" data-bs-target="#content-{{ Str::slug($category) }}" type="button" role="tab" aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                                    {{ __($category) }}
                                </button>
                            </li>
                        @endforeach
                    </ul>

                    <div class="tab-content" id="permissionTabsContent">
                        @foreach($groupedPermissions as $category => $groups)
                            <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="content-{{ Str::slug($category) }}" role="tabpanel">
                                <div class="row g-3">
                                    @foreach($groups as $groupName => $perms)
                                        <div class="col-md-6 col-lg-4 permission-card-wrapper">
                                            <div class="card h-100 border permission-group shadow-sm hover-shadow transition-all">
                                                <div class="card-header bg-light d-flex justify-content-between align-items-center py-2 border-bottom-0">
                                                    <h6 class="mb-0 fw-bold text-dark group-title">{{ $groupName }}</h6>
                                                    <div class="form-check form-switch">
                                                        <input type="checkbox" class="form-check-input group-checkbox" onchange="toggleGroup(this)" role="switch">
                                                    </div>
                                                </div>
                                                <div class="card-body p-3 pt-2">
                                                    <div class="d-flex flex-column gap-2">
                                                        @foreach($perms as $permission)
                                                            <div class="form-check permission-item">
                                                                <input id="perm_{{ $permission->id }}" name="permissions[]" type="checkbox" value="{{ $permission->id }}" 
                                                                    {{ in_array($permission->id, $rolePermissions) ? 'checked' : '' }}
                                                                    class="form-check-input permission-checkbox"
                                                                    onchange="checkGroup(this)">
                                                                <label for="perm_{{ $permission->id }}" class="form-check-label permission-label cursor-pointer user-select-none">{{ $permission->label }}</label>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-4 mt-4 border-top">
                        <div class="text-muted small">
                            <i class="fa-solid fa-info-circle me-1"></i> {{ __('Select a template above to quick-fill permissions.') }}
                        </div>
                        <button type="submit" class="btn btn-primary px-4 btn-lg">
                            <i class="fa-solid fa-save me-2"></i> {{ __('Update Role') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    .hover-shadow:hover {
        transform: translateY(-2px);
        box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
    }
    .transition-all {
        transition: all 0.3s ease;
    }
    .cursor-pointer {
        cursor: pointer;
    }
</style>

<script>
    // Search Functionality
    document.getElementById('permissionSearch').addEventListener('keyup', function() {
        const searchText = this.value.toLowerCase();
        const cards = document.querySelectorAll('.permission-card-wrapper');
        
        cards.forEach(card => {
            const groupTitle = card.querySelector('.group-title').textContent.toLowerCase();
            const labels = Array.from(card.querySelectorAll('.permission-label')).map(l => l.textContent.toLowerCase());
            const hasMatch = groupTitle.includes(searchText) || labels.some(l => l.includes(searchText));
            
            if (hasMatch) {
                card.style.display = '';
                // Highlight matching labels? (Optional complexity)
            } else {
                card.style.display = 'none';
            }
        });

        // Switch tabs if needed? No, user searches globally, we might need to show all or indicate where matches are.
        // Simple approach: Search filters visible cards. If user switches tabs, they see filtered results.
    });

    function toggleGroup(source) {
        const group = source.closest('.permission-group');
        const checkboxes = group.querySelectorAll('.permission-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = source.checked;
        });
    }

    function checkGroup(source) {
        const group = source.closest('.permission-group');
        const checkboxes = group.querySelectorAll('.permission-checkbox');
        const groupCheckbox = group.querySelector('.group-checkbox');
        const allChecked = Array.from(checkboxes).every(c => c.checked);
        // Only auto-check group if all are checked.
        // Also auto-uncheck group if not all are checked.
        groupCheckbox.checked = allChecked;
    }

    function selectAllGlobal() {
        document.querySelectorAll('.permission-checkbox').forEach(cb => {
            if(cb.offsetParent !== null) { // Only visible ones if search is active
                 cb.checked = true;
            }
        });
        document.querySelectorAll('.group-checkbox').forEach(cb => {
             if(cb.offsetParent !== null) cb.checked = true;
        });
    }

    function deselectAllGlobal() {
        document.querySelectorAll('.permission-checkbox').forEach(cb => {
             if(cb.offsetParent !== null) cb.checked = false;
        });
        document.querySelectorAll('.group-checkbox').forEach(cb => {
             if(cb.offsetParent !== null) cb.checked = false;
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Initialize group checkboxes state
        document.querySelectorAll('.permission-group').forEach(group => {
            const checkboxes = group.querySelectorAll('.permission-checkbox');
            const groupCheckbox = group.querySelector('.group-checkbox');
            if (checkboxes.length > 0 && groupCheckbox) {
                const allChecked = Array.from(checkboxes).every(c => c.checked);
                groupCheckbox.checked = allChecked;
            }
        });

        // Auto-select permissions based on Role Label
        const standardPermissions = @json($standardPermissions);
        const labelSelect = document.getElementById('label_select');
        const labelInput = document.getElementById('label');

        if (labelSelect) {
            labelSelect.addEventListener('change', function() {
                const selectedRole = this.value;
                
                if (selectedRole === 'Custom') {
                    labelInput.classList.remove('d-none');
                    // Clear value if it was a standard role
                    if (standardPermissions[labelInput.value]) {
                        labelInput.value = '';
                    }
                    labelInput.focus();
                } else {
                    labelInput.classList.add('d-none');
                    labelInput.value = selectedRole;
                    
                    if (selectedRole && standardPermissions[selectedRole]) {
                        Swal.fire({
                            title: "{{ __('Apply Role Template?') }}",
                            text: "{{ __('This will reset current permissions to the default for') }} " + selectedRole,
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonColor: '#3085d6',
                            cancelButtonColor: '#d33',
                            confirmButtonText: "{{ __('Yes, apply it!') }}"
                        }).then((result) => {
                            if (result.isConfirmed) {
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

                                Swal.fire(
                                    "{{ __('Applied!') }}",
                                    "{{ __('Permissions have been updated.') }}",
                                    'success'
                                )
                            } else {
                                // Reset select if cancelled? No, user might want to keep the name but not perms.
                            }
                        });
                    }
                }
            });
        }
    });
</script>
@endsection
