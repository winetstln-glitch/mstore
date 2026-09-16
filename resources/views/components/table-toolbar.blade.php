@props([
    'route' => null,
    'searchPlaceholder' => __('Cari...'),
    'showRoleFilter' => false,
    'roles' => collect(),
])

<form action="{{ $route ?? url()->current() }}" method="GET" class="m-0">
    @if(request('sort'))
        <input type="hidden" name="sort" value="{{ request('sort') }}">
        <input type="hidden" name="direction" value="{{ request('direction') }}">
    @endif
    
    <div class="row g-2 align-items-center justify-content-xl-end">
        <!-- Pagination Select -->
        <div class="col-auto">
            <select name="per_page" class="form-select form-select-sm" onchange="this.form.submit()" style="cursor: pointer; min-width: 90px;">
                <option value="10" @selected(request('per_page') == '10' || !request()->has('per_page'))>10 Data</option>
                <option value="20" @selected(request('per_page') == '20')>20 Data</option>
                <option value="50" @selected(request('per_page') == '50')>50 Data</option>
                <option value="100" @selected(request('per_page') == '100')>100 Data</option>
                <option value="all" @selected(request('per_page') == 'all')>Semua</option>
            </select>
        </div>

        <!-- Role Select (Optional) -->
        @if($showRoleFilter)
            <div class="col-auto">
                <select name="role_id" class="form-select form-select-sm" onchange="this.form.submit()" style="cursor: pointer; min-width: 140px;">
                    <option value="">{{ __('Semua Peran') }}</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}" @selected((string) request('role_id') === (string) $role->id)>{{ $role->label }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        <!-- Search Input -->
        <div class="col-auto flex-grow-1 flex-md-grow-0">
            <div class="input-group input-group-sm">
                <input type="text" name="search" class="form-control" placeholder="{{ $searchPlaceholder }}" value="{{ request('search') }}" style="min-width: 150px;">
                <button class="btn btn-primary px-3" type="submit">
                    <i class="fa-solid fa-search"></i>
                </button>
                @if(request()->filled('search') || request()->filled('role_id') || request()->filled('per_page'))
                    <a href="{{ $route ?? url()->current() }}" class="btn btn-danger px-3" title="Reset Filter">
                        <i class="fa-solid fa-times"></i>
                    </a>
                @endif
            </div>
        </div>
    </div>
</form>
