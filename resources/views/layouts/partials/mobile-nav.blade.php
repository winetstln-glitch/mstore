<div class="mobile-bottom-nav d-lg-none d-print-none">
    {{-- Dashboard / Home --}}
    @if(Auth::user()->hasRole('customer'))
        <a href="{{ route('client.onu-wifi.show') }}" class="mbn-item {{ request()->routeIs('client.onu-wifi.*') ? 'active' : '' }}">
            <div class="mbn-icon"><i class="fa-solid fa-wifi"></i></div>
            <span class="mbn-label">{{ __('Ganti WiFi') }}</span>
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
