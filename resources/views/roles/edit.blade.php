@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0 border-top border-4 border-primary">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">{{ __('Edit Role') }}: {{ $role->label }}</h5>
                <a href="{{ route('roles.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fa-solid fa-arrow-left me-1"></i> {{ __('Back to List') }}
                </a>
            </div>

            <div class="card-body">
                <form action="{{ route('roles.update', $role) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="mb-4">
                        <label for="label_select" class="form-label fw-bold">{{ __('Role Name (Label)') }}</label>
                        <select id="label_select" class="form-select mb-2">
                            <option value="">{{ __('Select Role Name') }}</option>
                            @foreach($standardPermissions as $roleName => $perms)
                                <option value="{{ $roleName }}" {{ $role->label == $roleName ? 'selected' : '' }}>{{ $roleName }}</option>
                            @endforeach
                            <option value="Custom" {{ !array_key_exists($role->label, $standardPermissions) ? 'selected' : '' }}>{{ __('Custom / Other') }}</option>
                        </select>
                        
                        <input type="text" id="label" name="label" class="form-control {{ array_key_exists($role->label, $standardPermissions) ? 'd-none' : '' }}" value="{{ $role->label }}">

                        @if($role->name === 'admin')
                            <div class="form-text text-warning">{{ __('Note: Admin system name cannot be changed.') }}</div>
                        @endif
                    </div>

                    <div class="mb-4">
                        <h5 class="fw-bold mb-3">{{ __('Permissions') }}</h5>
                        
                        <div class="row g-4">
                            @foreach($permissions as $group => $perms)
                                <div class="col-md-6 col-lg-4">
                                    <div class="card h-100 border permission-group">
                                        <div class="card-header bg-body-tertiary d-flex justify-content-between align-items-center py-2">
                                            <h6 class="mb-0 fw-bold">{{ $group }}</h6>
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input group-checkbox" onchange="toggleGroup(this)">
                                                <label class="form-check-label small">{{ __('All') }}</label>
                                            </div>
                                        </div>
                                        <div class="card-body p-3">
                                            <div class="d-flex flex-column gap-2">
                                                @foreach($perms as $permission)
                                                    <div class="form-check">
                                                        <input id="perm_{{ $permission->id }}" name="permissions[]" type="checkbox" value="{{ $permission->id }}" 
                                                            {{ in_array($permission->id, $rolePermissions) ? 'checked' : '' }}
                                                            class="form-check-input permission-checkbox"
                                                            onchange="checkGroup(this)">
                                                        <label for="perm_{{ $permission->id }}" class="form-check-label">{{ $permission->label }}</label>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="d-flex justify-content-end pt-3 border-top">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-save me-1"></i> {{ __('Update Role') }}
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

    function checkGroup(source) {
        const group = source.closest('.permission-group');
        const checkboxes = group.querySelectorAll('.permission-checkbox');
        const groupCheckbox = group.querySelector('.group-checkbox');
        const allChecked = Array.from(checkboxes).every(c => c.checked);
        groupCheckbox.checked = allChecked;
    }

    document.addEventListener('DOMContentLoaded', function() {
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
                        if (confirm("{{ __('Applying this role template will reset current permissions. Continue?') }}")) {
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
                }
            });
        }
    });
</script>
@endsection
