@props([
    'route' => null,
    'searchPlaceholder' => __('Cari...'),
    'showRoleFilter' => false,
    'roles' => collect(),
])

<form action="{{ $route ?? url()->current() }}" method="GET" class="mb-0">
    @if(request('sort'))
        <input type="hidden" name="sort" value="{{ request('sort') }}">
        <input type="hidden" name="direction" value="{{ request('direction') }}">
    @endif
    
    <div class="input-group input-group-sm shadow-sm">
        <!-- Pagination Select -->
        <span class="input-group-text bg-white border-end-0 text-muted px-2" title="Jumlah Baris">
            <i class="fa-solid fa-list-ol"></i>
        </span>
        <select name="per_page" class="form-select border-start-0 ps-1" onchange="this.form.submit()" style="max-width: 80px; cursor: pointer; box-shadow: none;">
            <option value="10" @selected(request('per_page') == '10' || !request()->has('per_page'))>10</option>
            <option value="20" @selected(request('per_page') == '20')>20</option>
            <option value="50" @selected(request('per_page') == '50')>50</option>
            <option value="100" @selected(request('per_page') == '100')>100</option>
            <option value="all" @selected(request('per_page') == 'all')>All</option>
        </select>

        <!-- Role Select (Optional) -->
        @if($showRoleFilter)
            <span class="input-group-text bg-white border-end-0 border-start-0 text-muted px-2 border-start">
                <i class="fa-solid fa-user-tag"></i>
            </span>
            <select name="role_id" class="form-select border-start-0 ps-1" onchange="this.form.submit()" style="max-width: 140px; cursor: pointer; box-shadow: none;">
                <option value="">{{ __('Semua Peran') }}</option>
                @foreach($roles as $role)
                    <option value="{{ $role->id }}" @selected((string) request('role_id') === (string) $role->id)>{{ $role->label }}</option>
                @endforeach
            </select>
        @endif

        <!-- Search Input -->
        <input type="text" name="search" class="form-control border-start-0 border-start" placeholder="{{ $searchPlaceholder }}" value="{{ request('search') }}" style="box-shadow: none; min-width: 150px;">
        
        <!-- Search Button -->
        <button class="btn btn-primary px-3" type="submit" style="z-index: 1;">
            <i class="fa-solid fa-search"></i>
        </button>

        <!-- Reset Button -->
        @if(request()->filled('search') || request()->filled('role_id') || request()->filled('per_page'))
            <a href="{{ $route ?? url()->current() }}" class="btn btn-outline-danger px-3" title="Reset Filter" style="z-index: 1;">
                <i class="fa-solid fa-times"></i>
            </a>
        @endif
    </div>
</form>
