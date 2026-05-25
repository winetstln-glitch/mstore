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
                        <label for="label" class="form-label fw-bold">{{ __('Role Name (Label)') }}</label>
                        <input type="text" id="label" name="label" class="form-control @error('label') is-invalid @enderror" value="{{ old('label', $role->label) }}" required maxlength="255">
                        @error('label')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">{{ __('System name (slug):') }} <strong>{{ $role->name }}</strong></div>
                    </div>

                    <div class="mb-4">
                        <h5 class="fw-bold mb-3">{{ __('Permissions') }}</h5>

                        @if(empty($filteredPermissions))
                            <div class="alert alert-info">
                                <i class="fa-solid fa-info-circle me-1"></i>
                                {{ __('You do not have any permissions available to assign.') }}
                            </div>
                        @else
                            <ul class="nav nav-pills mb-3" role="tablist">
                                @foreach($filteredPermissions as $tab => $groups)
                                    @php $tid = \Illuminate\Support\Str::slug($tab); @endphp
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link {{ $loop->first ? 'active' : '' }}" id="tab-{{ $tid }}" data-bs-toggle="tab" data-bs-target="#pane-{{ $tid }}" type="button" role="tab" aria-controls="pane-{{ $tid }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}">{{ $tab }}</button>
                                    </li>
                                @endforeach
                            </ul>
                            <div class="tab-content border rounded p-3">
                                @foreach($filteredPermissions as $tab => $groups)
                                    @php $tid = \Illuminate\Support\Str::slug($tab); @endphp
                                    <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="pane-{{ $tid }}" role="tabpanel" aria-labelledby="tab-{{ $tid }}">
                                        @foreach($groups as $group => $perms)
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
                                                                    <input id="perm_{{ $permission->id }}" name="permissions[]" type="checkbox" value="{{ $permission->id }}" class="form-check-input permission-checkbox" @if(in_array($permission->id, old('permissions', $visiblePermissions))) checked @endif>
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
                        @endif

                        @if($role->permissions->count() > count($visiblePermissions))
                            <div class="alert alert-warning mt-3">
                                <i class="fa-solid fa-exclamation-triangle me-1"></i>
                                {{ __('This role has :total permissions, but you can only manage :visible of them. The remaining permissions are kept unchanged.', [
                                    'total' => $role->permissions->count(),
                                    'visible' => count($visiblePermissions),
                                ]) }}
                            </div>
                        @endif
                    </div>

                    <div class="d-flex justify-content-end pt-3 border-top">
                        <a href="{{ route('roles.index') }}" class="btn btn-secondary me-2">
                            <i class="fa-solid fa-times me-1"></i> {{ __('Cancel') }}
                        </a>
                        <button type="submit" class="btn btn-primary" {{ empty($filteredPermissions) ? 'disabled' : '' }}>
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

    document.addEventListener('DOMContentLoaded', function() {
        // Initialize group checkboxes based on current state
        document.querySelectorAll('.permission-group').forEach(group => {
            const checkboxes = group.querySelectorAll('.permission-checkbox');
            const groupCheckbox = group.querySelector('.group-checkbox');
            if (checkboxes.length > 0 && groupCheckbox) {
                const allChecked = Array.from(checkboxes).every(c => c.checked);
                groupCheckbox.checked = allChecked;
            }
        });
    });
</script>
@endsection