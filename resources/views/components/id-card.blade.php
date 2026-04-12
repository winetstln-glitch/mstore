@props([
    'user',
    'employee' => null,
    'brandName',
    'logoUrl',
    'brandSlogan',
    'brandKey',
    'idCardCode'
])

@php
    $avatar = (string) ($user->avatar ?? '');
    $cardPhoto = (string) ($employee->id_card_photo_path ?? '');
    $storeAddress = (string) \App\Models\Setting::getValue('store_address', '');
    $storePhone = (string) \App\Models\Setting::getValue('store_phone', '');
    $waNumber = (string) \App\Models\Setting::getValue('whatsapp_number', '');
    $recoveryContact = $waNumber !== '' ? $waNumber : ($storePhone !== '' ? $storePhone : '-');
    $brandWebsite = preg_replace('#^https?://#', '', url('/'));
    
    $photoUrl = null;
    if ($cardPhoto !== '') {
        $photoUrl = asset('storage/'.$cardPhoto);
    } elseif ($avatar !== '') {
        if (str_starts_with($avatar, 'http://') || str_starts_with($avatar, 'https://')) {
            $photoUrl = $avatar;
        } elseif (str_starts_with($avatar, 'storage/') || str_starts_with($avatar, 'img/')) {
            $photoUrl = asset($avatar);
        } else {
            $photoUrl = asset('storage/'.$avatar);
        }
    }
    
    $name = $employee->full_name ?? $user->name;
    $position = strtoupper($employee->position ?? $user->role?->label ?? 'STAFF');
@endphp

<!-- FRONT SIDE (Old Design Restore) -->
<div class="id-card-item brand-{{ $brandKey }} id-card-front">
    <div class="wave-accent-top"></div>
    <div class="header-bg"></div>
    <div class="wave-accent-bottom"></div>
    <div class="side-curve"></div>

    <div class="logo-container">
        <div class="logo-diamond">
            <img src="{{ $logoUrl }}" alt="Logo {{ $brandName }}" crossorigin="anonymous" referrerpolicy="no-referrer">
        </div>
        <div class="company-copy">
            <div class="company-name">{{ $brandName }}</div>
            <div class="company-tagline">{{ $brandSlogan }}</div>
        </div>
    </div>

    <div class="profile-container">
        <div class="profile-image">
            @if($photoUrl)
                <img src="{{ $photoUrl }}" alt="Photo {{ $name }}" class="photo-image" crossorigin="anonymous" referrerpolicy="no-referrer">
            @else
                <div class="photo-placeholder"><i class="fa-solid fa-user"></i></div>
            @endif
        </div>
    </div>

    <div class="content">
        <h1 class="name-block">{{ $name }}</h1>
        <p class="job-title">{{ $position }}</p>
    </div>

    <div class="footer-info">
        <div class="id-label">ID NO : {{ $idCardCode }}</div>
        <div class="barcode-container">
            <svg class="barcode-svg" data-code="{{ $idCardCode }}"></svg>
        </div>
        <div class="barcode-text">{{ $idCardCode }}</div>
    </div>
</div>

<!-- BACK SIDE (Old Design Restore) -->
<div class="id-card-item id-card-back brand-{{ $brandKey }} id-card-back">
    <div class="back-header-bg"></div>
    <div class="back-accent-circle"></div>
    <div class="back-accent-line"></div>

    <div class="back-brand-lockup">
        <div class="back-logo-frame">
            <img src="{{ $logoUrl }}" alt="Logo {{ $brandName }}" crossorigin="anonymous" referrerpolicy="no-referrer">
        </div>
        <div class="back-brand-meta">
            <div class="back-brand-name">{{ $brandName }}</div>
            <div class="back-brand-slogan">{{ $brandSlogan }}</div>
        </div>
    </div>

    <div class="back-content">
        <div class="back-chip">OFFICIAL ID CARD</div>
        <div class="back-title">KARTU IDENTITAS RESMI</div>
        <p class="back-description">
            Kartu ini adalah identitas resmi perusahaan. Jika ditemukan, harap hubungi kontak resmi di bawah ini.
        </p>

        <div class="back-contact-card">
            <div class="back-contact-item">
                <div class="back-contact-label">Alamat</div>
                <div class="back-contact-value">{{ $storeAddress !== '' ? $storeAddress : '-' }}</div>
            </div>
            <div class="back-contact-item">
                <div class="back-contact-label">WhatsApp</div>
                <div class="back-contact-value">{{ $recoveryContact }}</div>
            </div>
            <div class="back-contact-item">
                <div class="back-contact-label">Website</div>
                <div class="back-contact-value">{{ $brandWebsite }}</div>
            </div>
        </div>
    </div>

    <div class="back-footer">
        <div class="back-footer-note">ID NO : {{ $idCardCode }}</div>
        <div class="back-footer-warning">Kartu ini wajib dibawa saat bertugas.</div>
    </div>
</div>
