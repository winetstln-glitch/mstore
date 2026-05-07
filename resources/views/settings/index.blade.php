@extends('layouts.app')

@section('title', __('Pengaturan'))

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0 border-top border-4 border-primary">
            <div class="card-header py-3">
                <h5 class="mb-0 fw-bold">{{ __('Pengaturan') }}</h5>
            </div>

            <div class="card-body">
                <div class="mb-4 pb-3 border-bottom d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold text-primary text-uppercase mb-1">
                            <i class="fa-solid fa-database me-1"></i> Pemeliharaan Sistem
                        </h6>
                        <p class="text-muted small mb-0">Cadangkan database Anda secara berkala untuk keamanan data.</p>
                    </div>
                    <a href="{{ route('settings.backup') }}" class="btn btn-outline-primary">
                        <i class="fa-solid fa-download me-1"></i> Backup Database
                    </a>
                </div>

                <form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @php
                        $receiptIdentityKeys = [
                            'store_name',
                            'store_address',
                            'store_phone',
                            'whatsapp_number',
                            'store_logo',
                            'brand_gtwash_name',
                            'brand_gtwash_slogan',
                            'brand_gtwash_logo',
                            'brand_mstore_name',
                            'brand_mstore_slogan',
                            'brand_mstore_logo',
                            'brand_mstorenet_name',
                            'brand_mstorenet_slogan',
                            'brand_mstorenet_logo',
                            'atk_store_name',
                            'atk_store_address',
                            'atk_store_phone',
                            'atk_store_logo',
                            'wash_store_name',
                            'wash_store_address',
                            'wash_store_phone',
                            'wash_store_logo',
                            'pos_printer_auto_reconnect',
                            'pos_print_logo_enabled',
                            'pos_bluetooth_chunk_size',
                            'pos_bluetooth_chunk_delay_ms',
                            'pos_qris_text',
                            'pos_preferred_printer_name',
                            'pos_preferred_printer_id',
                            'pos_performance_profile',
                            'landing_internet_promo_enabled',
                            'landing_internet_promo_percent',
                            'landing_internet_promo_label',
                            'cctv_section_badge',
                            'cctv_section_title',
                            'cctv_package_1_speed',
                            'cctv_package_1_subtitle',
                            'cctv_package_1_price',
                            'cctv_package_1_features',
                            'cctv_package_2_speed',
                            'cctv_package_2_subtitle',
                            'cctv_package_2_price',
                            'cctv_package_2_features',
                            'cctv_package_3_speed',
                            'cctv_package_3_subtitle',
                            'cctv_package_3_price',
                            'cctv_package_3_features',
                            'cctv_package_4_speed',
                            'cctv_package_4_subtitle',
                            'cctv_package_4_price',
                            'cctv_package_4_features',
                            'wedding_section_badge',
                            'wedding_section_title',
                            'wedding_service_1_badge',
                            'wedding_service_1_name',
                            'wedding_service_1_desc',
                            'wedding_service_1_image',
                            'wedding_service_2_badge',
                            'wedding_service_2_name',
                            'wedding_service_2_desc',
                            'wedding_service_2_image',
                            'wedding_service_3_badge',
                            'wedding_service_3_name',
                            'wedding_service_3_desc',
                            'wedding_service_3_image',
                            'mixradius_base_url',
                            'mixradius_billing_endpoint',
                            'mixradius_invoice_html_url',
                            'mixradius_enforce_customer_login',
                            'mixradius_api_token',
                        ];
                    @endphp
                    <div class="mb-4 pb-3 border-bottom">
                        <h6 class="fw-bold text-primary text-uppercase mb-3">
                            <i class="fa-solid fa-receipt me-1"></i> Identitas Struk
                        </h6>
                        <div class="row g-3">
                            <div class="col-12">
                                <h6 class="mb-2">Umum</h6>
                            </div>
                            <div class="col-md-6">
                                <label for="store_name" class="form-label fw-medium">Nama Toko</label>
                                <input type="text" class="form-control" id="store_name" name="store_name" value="{{ \App\Models\Setting::getValue('store_name', config('app.name', 'MStore')) }}">
                            </div>
                            <div class="col-md-6">
                                <label for="store_phone" class="form-label fw-medium">Telepon Toko</label>
                                <input type="text" class="form-control" id="store_phone" name="store_phone" value="{{ \App\Models\Setting::getValue('store_phone', '081234567890') }}">
                            </div>
                            <div class="col-md-6">
                                <label for="whatsapp_number" class="form-label fw-medium">Nomor WhatsApp Landing</label>
                                <input type="text" class="form-control" id="whatsapp_number" name="whatsapp_number" value="{{ \App\Models\Setting::getValue('whatsapp_number', '6281234567890') }}">
                            </div>
                            <div class="col-md-6">
                                <label for="store_logo_file" class="form-label fw-medium">Upload Logo Toko</label>
                                <input type="file" class="form-control" id="store_logo_file" name="store_logo_file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                                @if(\App\Models\Setting::getValue('store_logo'))
                                    <img src="{{ str_starts_with(\App\Models\Setting::getValue('store_logo'), 'http') ? \App\Models\Setting::getValue('store_logo') : asset(\App\Models\Setting::getValue('store_logo')) }}" alt="Logo Toko" class="img-thumbnail mt-2" style="max-height: 56px;">
                                    <div class="form-check mt-2">
                                        <input type="hidden" name="clear_store_logo" value="0">
                                        <input class="form-check-input" type="checkbox" value="1" id="clear_store_logo" name="clear_store_logo">
                                        <label class="form-check-label text-danger" for="clear_store_logo">Hapus logo toko</label>
                                    </div>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <label for="store_address" class="form-label fw-medium">Alamat Toko</label>
                                <textarea class="form-control" id="store_address" name="store_address" rows="3">{{ \App\Models\Setting::getValue('store_address', 'Jl. Contoh No. 1') }}</textarea>
                            </div>

                            <div class="col-12 mt-2">
                                <h6 class="mb-2">Brand Company ID Card (Terpisah)</h6>
                            </div>
                            <div class="col-md-4">
                                <label for="brand_gtwash_name" class="form-label fw-medium">Nama Brand GTWASH</label>
                                <input type="text" class="form-control" id="brand_gtwash_name" name="brand_gtwash_name" value="{{ \App\Models\Setting::getValue('brand_gtwash_name', 'GTWASH') }}">
                            </div>
                            <div class="col-md-4">
                                <label for="brand_mstore_name" class="form-label fw-medium">Nama Brand MSTORE</label>
                                <input type="text" class="form-control" id="brand_mstore_name" name="brand_mstore_name" value="{{ \App\Models\Setting::getValue('brand_mstore_name', 'MSTORE') }}">
                            </div>
                            <div class="col-md-4">
                                <label for="brand_mstorenet_name" class="form-label fw-medium">Nama Brand MSTORE.NET</label>
                                <input type="text" class="form-control" id="brand_mstorenet_name" name="brand_mstorenet_name" value="{{ \App\Models\Setting::getValue('brand_mstorenet_name', 'MSTORE.NET') }}">
                            </div>
                            <div class="col-md-4">
                                <label for="brand_gtwash_slogan" class="form-label fw-medium">Slogan GTWASH</label>
                                <input type="text" class="form-control" id="brand_gtwash_slogan" name="brand_gtwash_slogan" value="{{ \App\Models\Setting::getValue('brand_gtwash_slogan', 'Solusi Digital Cepat dan Terpercaya') }}">
                            </div>
                            <div class="col-md-4">
                                <label for="brand_mstore_slogan" class="form-label fw-medium">Slogan MSTORE</label>
                                <input type="text" class="form-control" id="brand_mstore_slogan" name="brand_mstore_slogan" value="{{ \App\Models\Setting::getValue('brand_mstore_slogan', 'Solusi Digital Cepat dan Terpercaya') }}">
                            </div>
                            <div class="col-md-4">
                                <label for="brand_mstorenet_slogan" class="form-label fw-medium">Slogan MSTORE.NET</label>
                                <input type="text" class="form-control" id="brand_mstorenet_slogan" name="brand_mstorenet_slogan" value="{{ \App\Models\Setting::getValue('brand_mstorenet_slogan', 'Solusi Digital Cepat dan Terpercaya') }}">
                            </div>

                            <div class="col-md-4">
                                <label for="brand_gtwash_logo_file" class="form-label fw-medium">Logo GTWASH</label>
                                <input type="file" class="form-control" id="brand_gtwash_logo_file" name="brand_gtwash_logo_file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                                @if(\App\Models\Setting::getValue('brand_gtwash_logo'))
                                    <img src="{{ str_starts_with(\App\Models\Setting::getValue('brand_gtwash_logo'), 'http') ? \App\Models\Setting::getValue('brand_gtwash_logo') : asset(\App\Models\Setting::getValue('brand_gtwash_logo')) }}" alt="Logo GTWASH" class="img-thumbnail mt-2" style="max-height: 56px;">
                                    <div class="form-check mt-2">
                                        <input type="hidden" name="clear_brand_gtwash_logo" value="0">
                                        <input class="form-check-input" type="checkbox" value="1" id="clear_brand_gtwash_logo" name="clear_brand_gtwash_logo">
                                        <label class="form-check-label text-danger" for="clear_brand_gtwash_logo">Hapus logo GTWASH</label>
                                    </div>
                                @endif
                            </div>
                            <div class="col-md-4">
                                <label for="brand_mstore_logo_file" class="form-label fw-medium">Logo MSTORE</label>
                                <input type="file" class="form-control" id="brand_mstore_logo_file" name="brand_mstore_logo_file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                                @if(\App\Models\Setting::getValue('brand_mstore_logo'))
                                    <img src="{{ str_starts_with(\App\Models\Setting::getValue('brand_mstore_logo'), 'http') ? \App\Models\Setting::getValue('brand_mstore_logo') : asset(\App\Models\Setting::getValue('brand_mstore_logo')) }}" alt="Logo MSTORE" class="img-thumbnail mt-2" style="max-height: 56px;">
                                    <div class="form-check mt-2">
                                        <input type="hidden" name="clear_brand_mstore_logo" value="0">
                                        <input class="form-check-input" type="checkbox" value="1" id="clear_brand_mstore_logo" name="clear_brand_mstore_logo">
                                        <label class="form-check-label text-danger" for="clear_brand_mstore_logo">Hapus logo MSTORE</label>
                                    </div>
                                @endif
                            </div>
                            <div class="col-md-4">
                                <label for="brand_mstorenet_logo_file" class="form-label fw-medium">Logo MSTORE.NET</label>
                                <input type="file" class="form-control" id="brand_mstorenet_logo_file" name="brand_mstorenet_logo_file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                                @if(\App\Models\Setting::getValue('brand_mstorenet_logo'))
                                    <img src="{{ str_starts_with(\App\Models\Setting::getValue('brand_mstorenet_logo'), 'http') ? \App\Models\Setting::getValue('brand_mstorenet_logo') : asset(\App\Models\Setting::getValue('brand_mstorenet_logo')) }}" alt="Logo MSTORE.NET" class="img-thumbnail mt-2" style="max-height: 56px;">
                                    <div class="form-check mt-2">
                                        <input type="hidden" name="clear_brand_mstorenet_logo" value="0">
                                        <input class="form-check-input" type="checkbox" value="1" id="clear_brand_mstorenet_logo" name="clear_brand_mstorenet_logo">
                                        <label class="form-check-label text-danger" for="clear_brand_mstorenet_logo">Hapus logo MSTORE.NET</label>
                                    </div>
                                @endif
                            </div>

                            <div class="col-12 mt-2">
                                <h6 class="mb-2">Landing Promo Internet</h6>
                            </div>
                            <div class="col-md-4">
                                <label for="landing_internet_promo_enabled" class="form-label fw-medium">Status Promo</label>
                                @php $internetPromoEnabled = \App\Models\Setting::getValue('landing_internet_promo_enabled', '1'); @endphp
                                <select class="form-select" id="landing_internet_promo_enabled" name="landing_internet_promo_enabled">
                                    <option value="1" {{ $internetPromoEnabled === '1' ? 'selected' : '' }}>Aktif</option>
                                    <option value="0" {{ $internetPromoEnabled === '0' ? 'selected' : '' }}>Nonaktif</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="landing_internet_promo_label" class="form-label fw-medium">Teks Promo</label>
                                <input type="text" class="form-control" id="landing_internet_promo_label" name="landing_internet_promo_label" value="{{ \App\Models\Setting::getValue('landing_internet_promo_label', 'Promo Paket Internet') }}">
                            </div>
                            <div class="col-md-4">
                                <label for="landing_internet_promo_percent" class="form-label fw-medium">Diskon Promo (%)</label>
                                <input type="number" class="form-control" id="landing_internet_promo_percent" name="landing_internet_promo_percent" value="{{ \App\Models\Setting::getValue('landing_internet_promo_percent', '10') }}" min="0" max="90">
                                <div class="form-text">Nilai 0 untuk menonaktifkan promo di landing.</div>
                            </div>

                            <div class="col-12 mt-2">
                                <h6 class="mb-2">Landing Paket CCTV</h6>
                            </div>
                            <div class="col-md-6">
                                <label for="cctv_section_badge" class="form-label fw-medium">Badge Section CCTV</label>
                                <input type="text" class="form-control" id="cctv_section_badge" name="cctv_section_badge" value="{{ \App\Models\Setting::getValue('cctv_section_badge', 'Security Solutions') }}">
                            </div>
                            <div class="col-md-6">
                                <label for="cctv_section_title" class="form-label fw-medium">Judul Section CCTV</label>
                                <input type="text" class="form-control" id="cctv_section_title" name="cctv_section_title" value="{{ \App\Models\Setting::getValue('cctv_section_title', 'Paket Instalasi CCTV') }}">
                            </div>

                            <div class="col-12">
                                <h6 class="mb-2">Paket CCTV 1</h6>
                            </div>
                            <div class="col-md-4">
                                <label for="cctv_package_1_speed" class="form-label fw-medium">Label Paket 1</label>
                                <input type="text" class="form-control" id="cctv_package_1_speed" name="cctv_package_1_speed" value="{{ \App\Models\Setting::getValue('cctv_package_1_speed', 'Basic') }}">
                            </div>
                            <div class="col-md-4">
                                <label for="cctv_package_1_subtitle" class="form-label fw-medium">Subjudul Paket 1</label>
                                <input type="text" class="form-control" id="cctv_package_1_subtitle" name="cctv_package_1_subtitle" value="{{ \App\Models\Setting::getValue('cctv_package_1_subtitle', '1 Kamera HD') }}">
                            </div>
                            <div class="col-md-4">
                                <label for="cctv_package_1_price" class="form-label fw-medium">Harga Paket 1</label>
                                <input type="text" class="form-control" id="cctv_package_1_price" name="cctv_package_1_price" value="{{ \App\Models\Setting::getValue('cctv_package_1_price', 'Rp 600Rb') }}">
                            </div>
                            <div class="col-12">
                                <label for="cctv_package_1_features" class="form-label fw-medium">Fitur Paket 1</label>
                                <textarea class="form-control" id="cctv_package_1_features" name="cctv_package_1_features" rows="3">{{ \App\Models\Setting::getValue('cctv_package_1_features', "Camera 1 Channel\nHDD 250GB\nFree Instalasi") }}</textarea>
                            </div>

                            <div class="col-12">
                                <h6 class="mb-2">Paket CCTV 2</h6>
                            </div>
                            <div class="col-md-4">
                                <label for="cctv_package_2_speed" class="form-label fw-medium">Label Paket 2</label>
                                <input type="text" class="form-control" id="cctv_package_2_speed" name="cctv_package_2_speed" value="{{ \App\Models\Setting::getValue('cctv_package_2_speed', 'Basic') }}">
                            </div>
                            <div class="col-md-4">
                                <label for="cctv_package_2_subtitle" class="form-label fw-medium">Subjudul Paket 2</label>
                                <input type="text" class="form-control" id="cctv_package_2_subtitle" name="cctv_package_2_subtitle" value="{{ \App\Models\Setting::getValue('cctv_package_2_subtitle', '2 Kamera HD') }}">
                            </div>
                            <div class="col-md-4">
                                <label for="cctv_package_2_price" class="form-label fw-medium">Harga Paket 2</label>
                                <input type="text" class="form-control" id="cctv_package_2_price" name="cctv_package_2_price" value="{{ \App\Models\Setting::getValue('cctv_package_2_price', 'Rp 1.1jt') }}">
                            </div>
                            <div class="col-12">
                                <label for="cctv_package_2_features" class="form-label fw-medium">Fitur Paket 2</label>
                                <textarea class="form-control" id="cctv_package_2_features" name="cctv_package_2_features" rows="3">{{ \App\Models\Setting::getValue('cctv_package_2_features', "Camera 2 Channel\nHDD 125GB\nFree Instalasi") }}</textarea>
                            </div>

                            <div class="col-12">
                                <h6 class="mb-2">Paket CCTV 3</h6>
                            </div>
                            <div class="col-md-4">
                                <label for="cctv_package_3_speed" class="form-label fw-medium">Label Paket 3</label>
                                <input type="text" class="form-control" id="cctv_package_3_speed" name="cctv_package_3_speed" value="{{ \App\Models\Setting::getValue('cctv_package_3_speed', 'Basic') }}">
                            </div>
                            <div class="col-md-4">
                                <label for="cctv_package_3_subtitle" class="form-label fw-medium">Subjudul Paket 3</label>
                                <input type="text" class="form-control" id="cctv_package_3_subtitle" name="cctv_package_3_subtitle" value="{{ \App\Models\Setting::getValue('cctv_package_3_subtitle', '2 Kamera HD') }}">
                            </div>
                            <div class="col-md-4">
                                <label for="cctv_package_3_price" class="form-label fw-medium">Harga Paket 3</label>
                                <input type="text" class="form-control" id="cctv_package_3_price" name="cctv_package_3_price" value="{{ \App\Models\Setting::getValue('cctv_package_3_price', 'Rp 1.9jt') }}">
                            </div>
                            <div class="col-12">
                                <label for="cctv_package_3_features" class="form-label fw-medium">Fitur Paket 3</label>
                                <textarea class="form-control" id="cctv_package_3_features" name="cctv_package_3_features" rows="3">{{ \App\Models\Setting::getValue('cctv_package_3_features', "DVR 4 Channel\nHDD 500GB\nFree Instalasi") }}</textarea>
                            </div>

                            <div class="col-12">
                                <h6 class="mb-2">Paket CCTV 4</h6>
                            </div>
                            <div class="col-md-4">
                                <label for="cctv_package_4_speed" class="form-label fw-medium">Label Paket 4</label>
                                <input type="text" class="form-control" id="cctv_package_4_speed" name="cctv_package_4_speed" value="{{ \App\Models\Setting::getValue('cctv_package_4_speed', 'Basic') }}">
                            </div>
                            <div class="col-md-4">
                                <label for="cctv_package_4_subtitle" class="form-label fw-medium">Subjudul Paket 4</label>
                                <input type="text" class="form-control" id="cctv_package_4_subtitle" name="cctv_package_4_subtitle" value="{{ \App\Models\Setting::getValue('cctv_package_4_subtitle', '4 Kamera HD') }}">
                            </div>
                            <div class="col-md-4">
                                <label for="cctv_package_4_price" class="form-label fw-medium">Harga Paket 4</label>
                                <input type="text" class="form-control" id="cctv_package_4_price" name="cctv_package_4_price" value="{{ \App\Models\Setting::getValue('cctv_package_4_price', 'Rp 1.9jt') }}">
                            </div>
                            <div class="col-12">
                                <label for="cctv_package_4_features" class="form-label fw-medium">Fitur Paket 4</label>
                                <textarea class="form-control" id="cctv_package_4_features" name="cctv_package_4_features" rows="3">{{ \App\Models\Setting::getValue('cctv_package_4_features', "DVR 4 Channel\nHDD 500GB\nFree Instalasi") }}</textarea>
                            </div>

                            <div class="col-12 mt-2">
                                <h6 class="mb-2">Landing Wedding & Event</h6>
                            </div>
                            <div class="col-md-6">
                                <label for="wedding_section_badge" class="form-label fw-medium">Badge Section</label>
                                <input type="text" class="form-control" id="wedding_section_badge" name="wedding_section_badge" value="{{ \App\Models\Setting::getValue('wedding_section_badge', 'Event Services') }}">
                            </div>
                            <div class="col-md-6">
                                <label for="wedding_section_title" class="form-label fw-medium">Judul Section</label>
                                <input type="text" class="form-control" id="wedding_section_title" name="wedding_section_title" value="{{ \App\Models\Setting::getValue('wedding_section_title', 'Layanan Wedding & Event') }}">
                            </div>

                            <div class="col-12">
                                <h6 class="mb-2">Kartu 1</h6>
                            </div>
                            <div class="col-md-4">
                                <label for="wedding_service_1_badge" class="form-label fw-medium">Badge Kartu 1</label>
                                <input type="text" class="form-control" id="wedding_service_1_badge" name="wedding_service_1_badge" value="{{ \App\Models\Setting::getValue('wedding_service_1_badge', 'Wedding') }}">
                            </div>
                            <div class="col-md-4">
                                <label for="wedding_service_1_name" class="form-label fw-medium">Nama Kartu 1</label>
                                <input type="text" class="form-control" id="wedding_service_1_name" name="wedding_service_1_name" value="{{ \App\Models\Setting::getValue('wedding_service_1_name', 'Hias Pengantin') }}">
                            </div>
                            <div class="col-md-4">
                                <label for="wedding_service_1_image_file" class="form-label fw-medium">Upload Gambar Kartu 1</label>
                                <input type="file" class="form-control" id="wedding_service_1_image_file" name="wedding_service_1_image_file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                                @if(\App\Models\Setting::getValue('wedding_service_1_image'))
                                    <img src="{{ str_starts_with(\App\Models\Setting::getValue('wedding_service_1_image'), 'http') ? \App\Models\Setting::getValue('wedding_service_1_image') : asset(\App\Models\Setting::getValue('wedding_service_1_image')) }}" alt="Wedding Service 1" class="img-thumbnail mt-2" style="max-height: 70px;">
                                    <div class="form-check mt-2">
                                        <input type="hidden" name="clear_wedding_service_1_image" value="0">
                                        <input class="form-check-input" type="checkbox" value="1" id="clear_wedding_service_1_image" name="clear_wedding_service_1_image">
                                        <label class="form-check-label text-danger" for="clear_wedding_service_1_image">Hapus gambar kartu 1</label>
                                    </div>
                                @endif
                            </div>
                            <div class="col-12">
                                <label for="wedding_service_1_desc" class="form-label fw-medium">Deskripsi Kartu 1</label>
                                <textarea class="form-control" id="wedding_service_1_desc" name="wedding_service_1_desc" rows="2">{{ \App\Models\Setting::getValue('wedding_service_1_desc', 'Dekorasi pelaminan elegan untuk akad, resepsi, dan acara keluarga.') }}</textarea>
                            </div>

                            <div class="col-12">
                                <h6 class="mb-2">Kartu 2</h6>
                            </div>
                            <div class="col-md-4">
                                <label for="wedding_service_2_badge" class="form-label fw-medium">Badge Kartu 2</label>
                                <input type="text" class="form-control" id="wedding_service_2_badge" name="wedding_service_2_badge" value="{{ \App\Models\Setting::getValue('wedding_service_2_badge', 'Photography') }}">
                            </div>
                            <div class="col-md-4">
                                <label for="wedding_service_2_name" class="form-label fw-medium">Nama Kartu 2</label>
                                <input type="text" class="form-control" id="wedding_service_2_name" name="wedding_service_2_name" value="{{ \App\Models\Setting::getValue('wedding_service_2_name', 'Poto Moment') }}">
                            </div>
                            <div class="col-md-4">
                                <label for="wedding_service_2_image_file" class="form-label fw-medium">Upload Gambar Kartu 2</label>
                                <input type="file" class="form-control" id="wedding_service_2_image_file" name="wedding_service_2_image_file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                                @if(\App\Models\Setting::getValue('wedding_service_2_image'))
                                    <img src="{{ str_starts_with(\App\Models\Setting::getValue('wedding_service_2_image'), 'http') ? \App\Models\Setting::getValue('wedding_service_2_image') : asset(\App\Models\Setting::getValue('wedding_service_2_image')) }}" alt="Wedding Service 2" class="img-thumbnail mt-2" style="max-height: 70px;">
                                    <div class="form-check mt-2">
                                        <input type="hidden" name="clear_wedding_service_2_image" value="0">
                                        <input class="form-check-input" type="checkbox" value="1" id="clear_wedding_service_2_image" name="clear_wedding_service_2_image">
                                        <label class="form-check-label text-danger" for="clear_wedding_service_2_image">Hapus gambar kartu 2</label>
                                    </div>
                                @endif
                            </div>
                            <div class="col-12">
                                <label for="wedding_service_2_desc" class="form-label fw-medium">Deskripsi Kartu 2</label>
                                <textarea class="form-control" id="wedding_service_2_desc" name="wedding_service_2_desc" rows="2">{{ \App\Models\Setting::getValue('wedding_service_2_desc', 'Dokumentasi foto momen spesial agar setiap detik berharga tetap terabadikan.') }}</textarea>
                            </div>

                            <div class="col-12">
                                <h6 class="mb-2">Kartu 3</h6>
                            </div>
                            <div class="col-md-4">
                                <label for="wedding_service_3_badge" class="form-label fw-medium">Badge Kartu 3</label>
                                <input type="text" class="form-control" id="wedding_service_3_badge" name="wedding_service_3_badge" value="{{ \App\Models\Setting::getValue('wedding_service_3_badge', 'Event Support') }}">
                            </div>
                            <div class="col-md-4">
                                <label for="wedding_service_3_name" class="form-label fw-medium">Nama Kartu 3</label>
                                <input type="text" class="form-control" id="wedding_service_3_name" name="wedding_service_3_name" value="{{ \App\Models\Setting::getValue('wedding_service_3_name', 'Sewa Auning') }}">
                            </div>
                            <div class="col-md-4">
                                <label for="wedding_service_3_image_file" class="form-label fw-medium">Upload Gambar Kartu 3</label>
                                <input type="file" class="form-control" id="wedding_service_3_image_file" name="wedding_service_3_image_file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                                @if(\App\Models\Setting::getValue('wedding_service_3_image'))
                                    <img src="{{ str_starts_with(\App\Models\Setting::getValue('wedding_service_3_image'), 'http') ? \App\Models\Setting::getValue('wedding_service_3_image') : asset(\App\Models\Setting::getValue('wedding_service_3_image')) }}" alt="Wedding Service 3" class="img-thumbnail mt-2" style="max-height: 70px;">
                                    <div class="form-check mt-2">
                                        <input type="hidden" name="clear_wedding_service_3_image" value="0">
                                        <input class="form-check-input" type="checkbox" value="1" id="clear_wedding_service_3_image" name="clear_wedding_service_3_image">
                                        <label class="form-check-label text-danger" for="clear_wedding_service_3_image">Hapus gambar kartu 3</label>
                                    </div>
                                @endif
                            </div>
                            <div class="col-12">
                                <label for="wedding_service_3_desc" class="form-label fw-medium">Deskripsi Kartu 3</label>
                                <textarea class="form-control" id="wedding_service_3_desc" name="wedding_service_3_desc" rows="2">{{ \App\Models\Setting::getValue('wedding_service_3_desc', 'Penyewaan auning untuk area tamu, panggung, dan kebutuhan acara outdoor.') }}</textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4 pb-3 border-bottom">
                        <h6 class="fw-bold text-primary text-uppercase mb-3">
                            <i class="fa-solid fa-wifi me-1"></i> {{ __('Pengaturan Koneksi MixRADIUS') }}
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="mixradius_base_url" class="form-label fw-medium">Base URL</label>
                                <input type="text" class="form-control" id="mixradius_base_url" name="mixradius_base_url" value="{{ \App\Models\Setting::getValue('mixradius_base_url', env('MIXRADIUS_BASE_URL')) }}">
                            </div>
                            <div class="col-md-6">
                                <label for="mixradius_billing_endpoint" class="form-label fw-medium">Billing Endpoint</label>
                                <input type="text" class="form-control" id="mixradius_billing_endpoint" name="mixradius_billing_endpoint" value="{{ \App\Models\Setting::getValue('mixradius_billing_endpoint', env('MIXRADIUS_BILLING_ENDPOINT', '/api/invoices')) }}">
                            </div>
                            <div class="col-md-6">
                                <label for="mixradius_invoice_html_url" class="form-label fw-medium">Invoice HTML URL</label>
                                <input type="text" class="form-control" id="mixradius_invoice_html_url" name="mixradius_invoice_html_url" value="{{ \App\Models\Setting::getValue('mixradius_invoice_html_url', env('MIXRADIUS_INVOICE_HTML_URL')) }}">
                            </div>
                            <div class="col-md-6">
                                <label for="mixradius_enforce_customer_login" class="form-label fw-medium">Wajibkan Login Pelanggan via MixRADIUS</label>
                                @php $enf = \App\Models\Setting::getValue('mixradius_enforce_customer_login', env('MIXRADIUS_ENFORCE_CUSTOMER_LOGIN', false) ? '1':'0'); @endphp
                                <select name="mixradius_enforce_customer_login" id="mixradius_enforce_customer_login" class="form-select">
                                    <option value="1" {{ $enf == '1' ? 'selected' : '' }}>{{ __('Ya') }}</option>
                                    <option value="0" {{ $enf == '0' ? 'selected' : '' }}>{{ __('Tidak') }}</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="mixradius_api_token" class="form-label fw-medium">API Token</label>
                                <input type="password" class="form-control" id="mixradius_api_token" name="mixradius_api_token" placeholder="••••••">
                                <div class="form-text">{{ __('Biarkan kosong jika tidak ingin mengubah token.') }}</div>
                            </div>
                        </div>
                    </div>
                    @foreach($settings as $group => $groupSettings)
                        @php
                            $normalizedGroup = strtolower((string) $group);
                            $isAttendanceGroup = in_array($normalizedGroup, ['attendance', 'schedule'], true);
                        @endphp
                        @if($isAttendanceGroup)
                            @continue
                        @endif
                        <div class="mb-4 pb-3 border-bottom last:border-0">
                            <h6 class="fw-bold text-primary text-uppercase mb-3">
                                <i class="fa-solid fa-layer-group me-1"></i> {{ __(str_replace('_', ' ', $group)) }} {{ __('Pengaturan') }}
                            </h6>
                            
                            <div class="row g-3">
                                @foreach($groupSettings as $setting)
                                    @if(in_array($setting->key, $receiptIdentityKeys, true))
                                        @continue
                                    @endif
                                    <div class="{{ $setting->type == 'schedule_weekly' ? 'col-12' : 'col-md-6' }}">
                                        <label for="{{ $setting->key }}" class="form-label fw-medium">
                                            {{ $setting->label ?? ucwords(str_replace('_', ' ', $setting->key)) }}
                                            @if($setting->type == 'schedule_weekly')
                                                <span class="text-muted small">({{ __('Jadwal Kerja Mingguan') }})</span>
                                            @endif
                                        </label>
                                        
                                        @if($setting->type == 'time')
                                            <input type="time" name="{{ $setting->key }}" id="{{ $setting->key }}" value="{{ $setting->value }}" class="form-control">
                                        @elseif($setting->type == 'number')
                                            <input type="number" name="{{ $setting->key }}" id="{{ $setting->key }}" value="{{ $setting->value }}" class="form-control">
                                        @elseif($setting->type == 'boolean')
                                            <select name="{{ $setting->key }}" id="{{ $setting->key }}" class="form-select">
                                                <option value="1" {{ $setting->value == '1' ? 'selected' : '' }}>{{ __('Ya') }}</option>
                                                <option value="0" {{ $setting->value == '0' ? 'selected' : '' }}>{{ __('Tidak') }}</option>
                                            </select>
                                        @elseif($setting->type == 'schedule_weekly')
                                            <div>
                                                @php
                                                    $schedule = json_decode($setting->value, true) ?? [];
                                                    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                                                @endphp
                                                <div class="table-responsive">
                                                    <table class="table table-bordered table-sm align-middle">
                                                        <thead class="">
                                                            <tr>
                                                                <th>{{ __('Hari') }}</th>
                                                                <th class="text-center" style="width: 100px">{{ __('Hari Kerja') }}</th>
                                                                <th>{{ __('Jam Mulai') }}</th>
                                                                <th>{{ __('Jam Selesai') }}</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($days as $day)
                                                                @php
                                                                    $daySettings = $schedule[$day] ?? ['enabled' => false, 'start' => '08:00', 'end' => '17:00'];
                                                                @endphp
                                                                <tr>
                                                                    <td class="fw-medium">{{ $day }}</td>
                                                                    <td class="text-center">
                                                                        <div class="form-check d-inline-block">
                                                                            <input type="hidden" name="{{ $setting->key }}[{{ $day }}][enabled]" value="0">
                                                                            <input class="form-check-input" type="checkbox" name="{{ $setting->key }}[{{ $day }}][enabled]" value="1" {{ $daySettings['enabled'] ? 'checked' : '' }}>
                                                                        </div>
                                                                    </td>
                                                                    <td>
                                                                        <input type="time" class="form-control form-control-sm" name="{{ $setting->key }}[{{ $day }}][start]" value="{{ $daySettings['start'] }}">
                                                                    </td>
                                                                    <td>
                                                                        <input type="time" class="form-control form-control-sm" name="{{ $setting->key }}[{{ $day }}][end]" value="{{ $daySettings['end'] }}">
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        @elseif($setting->type == 'account')
                                            <select name="{{ $setting->key }}" id="{{ $setting->key }}" class="form-select">
                                                <option value="">-- Pilih Akun --</option>
                                                @foreach(($accountOptions ?? []) as $acc)
                                                    <option value="{{ $acc->id }}" {{ $setting->value == (string)$acc->id ? 'selected' : '' }}>
                                                        {{ $acc->code }} - {{ $acc->name }} ({{ $acc->type }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        @elseif($setting->type == 'textarea')
                                            <textarea name="{{ $setting->key }}" id="{{ $setting->key }}" class="form-control" rows="3">{{ $setting->value }}</textarea>
                                        @elseif($setting->type == 'packages')
                                            @php
                                                $packages = json_decode($setting->value, true) ?? [];
                                                $rows = max(count($packages), 3);
                                            @endphp
                                            <div class="table-responsive">
                                                <table class="table table-bordered table-sm align-middle mb-0">
                                                    <thead class="">
                                                        <tr>
                                                            <th style="width: 60%">{{ __('Nama') }}</th>
                                                            <th style="width: 40%">{{ __('Harga') }}</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @for($i = 0; $i < $rows; $i++)
                                                            @php
                                                                $pkg = $packages[$i] ?? ['name' => '', 'price' => ''];
                                                            @endphp
                                                            <tr>
                                                                <td>
                                                                    <input type="text" name="{{ $setting->key }}[{{ $i }}][name]" value="{{ $pkg['name'] }}" class="form-control form-control-sm">
                                                                </td>
                                                                <td>
                                                                    <input type="number" name="{{ $setting->key }}[{{ $i }}][price]" value="{{ $pkg['price'] }}" class="form-control form-control-sm" min="0" step="1000">
                                                                </td>
                                                            </tr>
                                                        @endfor
                                                    </tbody>
                                                </table>
                                            </div>
                                        @else
                                            <input type="text" name="{{ $setting->key }}" id="{{ $setting->key }}" value="{{ $setting->value }}" class="form-control">
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    <div class="d-flex justify-content-end pt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-save me-1"></i> {{ __('Simpan Pengaturan') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
