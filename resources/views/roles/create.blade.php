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
                        <label for="label" class="form-label fw-bold">{{ __('Role Name (Label)') }}</label>
                        <input type="text" id="label" name="label" class="form-control" placeholder="{{ __('e.g. Sales Manager') }}" required>
                        <div class="form-text">{{ __('System name (slug) will be generated automatically.') }}</div>
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
                                                        <input id="perm_{{ $permission->id }}" name="permissions[]" type="checkbox" value="{{ $permission->id }}" class="form-check-input permission-checkbox">
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
</script>
@endsection
