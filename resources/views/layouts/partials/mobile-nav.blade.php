<div class="mobile-bottom-nav d-lg-none">
    {{-- Dashboard / Home --}}
    @if(Auth::user()->hasRole('customer'))
        <a href="{{ route('client.portal') }}" class="mbn-item {{ request()->routeIs('client.portal') ? 'active' : '' }}">
            <div class="mbn-icon"><i class="fa-solid fa-house"></i></div>
            <span class="mbn-label">{{ __('Beranda') }}</span>
        </a>
        <a href="{{ route('client.invoices.index') }}" class="mbn-item {{ request()->routeIs('client.invoices.*') ? 'active' : '' }}">
            <div class="mbn-icon"><i class="fa-solid fa-file-invoice-dollar"></i></div>
            <span class="mbn-label">{{ __('Tagihan') }}</span>
        </a>
        <a href="{{ route('client.connection') }}" class="mbn-item {{ request()->routeIs('client.connection') ? 'active' : '' }}">
            <div class="mbn-icon"><i class="fa-solid fa-wifi"></i></div>
            <span class="mbn-label">{{ __('Koneksi') }}</span>
        </a>
        <a href="{{ route('profile.edit') }}" class="mbn-item {{ request()->routeIs('profile.edit') ? 'active' : '' }}">
            <div class="mbn-icon"><i class="fa-solid fa-user"></i></div>
            <span class="mbn-label">{{ __('Profil') }}</span>
        </a>
    @else
        <a href="{{ route('dashboard') }}" class="mbn-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <div class="mbn-icon"><i class="fa-solid fa-chart-line"></i></div>
            <span class="mbn-label">{{ __('Dash') }}</span>
        </a>
        
        @if(Auth::user()->hasPermission('customer.view'))
        <a href="{{ route('customers.index') }}" class="mbn-item {{ request()->routeIs('customers.*') ? 'active' : '' }}">
            <div class="mbn-icon"><i class="fa-solid fa-users"></i></div>
            <span class="mbn-label">{{ __('Plg') }}</span>
        </a>
        @endif

        @if(Auth::user()->hasPermission('hotspot.view') || Auth::user()->hasPermission('pppoe.view'))
        <a href="{{ route('pppoe.index') }}" class="mbn-item {{ (request()->routeIs('pppoe.*') || request()->routeIs('hotspot.*')) ? 'active' : '' }}">
            <div class="mbn-icon"><i class="fa-solid fa-network-wired"></i></div>
            <span class="mbn-label">{{ __('Layanan') }}</span>
        </a>
        @endif

        {{-- More Menu Toggle (Sidebar) --}}
        <a href="#" class="mbn-item" id="mobileMenuToggle">
            <div class="mbn-icon"><i class="fa-solid fa-bars"></i></div>
            <span class="mbn-label">{{ __('Menu') }}</span>
        </a>
    @endif
</div>

{{-- Floating Action Button (FAB) for Admin --}}
@if(!Auth::user()->hasRole('customer'))
<div class="fab-container d-lg-none">
    <div class="fab-menu" id="fabMenu">
        @if(Auth::user()->hasPermission('customer.create'))
        <a href="{{ route('customers.create') }}" class="fab-action" data-bs-toggle="tooltip" data-bs-placement="left" title="{{ __('Tambah Pelanggan') }}">
            <i class="fa-solid fa-user-plus"></i>
        </a>
        @endif
        @if(Auth::user()->hasPermission('installation.create'))
        <a href="{{ route('installations.create') }}" class="fab-action" data-bs-toggle="tooltip" data-bs-placement="left" title="{{ __('Pasang Baru') }}">
            <i class="fa-solid fa-screwdriver-wrench"></i>
        </a>
        @endif
        @if(Auth::user()->hasPermission('ticket.create'))
        <a href="{{ route('tickets.create') }}" class="fab-action" data-bs-toggle="tooltip" data-bs-placement="left" title="{{ __('Buat Tiket') }}">
            <i class="fa-solid fa-ticket"></i>
        </a>
        @endif
    </div>
    <button class="btn btn-primary btn-fab" id="mainFabBtn">
        <i class="fa-solid fa-plus"></i>
    </button>
</div>

<style>
/* FAB Specific Styles embedded here or moved to app-android.css */
.fab-container {
    position: fixed;
    bottom: calc(var(--nav-height) + var(--bottom-safe-area) + 20px);
    right: 20px;
    z-index: 1050;
    display: flex;
    flex-direction: column-reverse;
    align-items: center;
}

.btn-fab {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    box-shadow: 0 4px 10px rgba(0,0,0,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    transition: transform 0.2s ease;
    border: none;
}

.btn-fab:active {
    transform: scale(0.95);
}

.fab-menu {
    display: flex;
    flex-direction: column-reverse;
    align-items: center;
    gap: 15px;
    margin-bottom: 15px;
    opacity: 0;
    visibility: hidden;
    transform: translateY(20px);
    transition: all 0.3s cubic-bezier(0.4, 0.0, 0.2, 1);
}

.fab-menu.active {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.fab-action {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    background-color: var(--card-bg, #fff);
    color: var(--text-main, #333);
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    font-size: 18px;
    transition: transform 0.2s;
}

[data-bs-theme="dark"] .fab-action {
    background-color: #334155;
    color: #fff;
}

.fab-action:hover {
    transform: scale(1.1);
}
</style>
@endif
