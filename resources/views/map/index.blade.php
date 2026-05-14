@extends('layouts.app')

@section('title', __('Peta'))

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="main-card mb-3 card shadow-sm border-0 border-top border-4 border-primary">
                <div class="card-body p-2 p-md-3">
                    <!-- Responsive Toolbar -->
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-3 toolbar-container">
                        
                        <!-- Left Group: Title & Add Buttons -->
                        <div class="d-flex flex-column w-100 w-md-auto toolbar-title">
                            <h5 class="card-title fw-bold mb-2 mb-md-0 text-nowrap">
                                {{ __('Peta Distribusi') }}
                            </h5>
                            
                            <!-- Filter & Add Buttons Container -->
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                @if(isset($isAdmin) && $isAdmin)
                                <div>
                                    <select class="form-select form-select-sm" id="areaFilter" style="min-width: 150px;">
                                        <option value="">{{ __('Semua Area') }}</option>
                                        @foreach($coordinators as $coord)
                                            @if($coord->region)
                                                <option value="{{ $coord->region_id }}">{{ $coord->region->name }} ({{ $coord->name }})</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                                @endif

                                <!-- Mobile: Scrollable/Button Group, Desktop: Inline -->
                                <div class="btn-group-mobile">
                                    <button type="button" class="btn btn-primary btn-sm" id="btnAddOltMode">
                                        <i class="fa fa-server me-1 d-none d-sm-inline"></i> {{ __('OLT') }}
                                    </button>
                                    <button type="button" class="btn btn-warning text-dark btn-sm" id="btnAddOdcMode">
                                        <i class="fa fa-plus me-1 d-none d-sm-inline"></i> {{ __('ODC') }}
                                    </button>
                                    <button type="button" class="btn btn-success btn-sm" id="btnAddOdpMode">
                                        <i class="fa fa-plus me-1 d-none d-sm-inline"></i> {{ __('ODP') }}
                                    </button>
                                    <button type="button" class="btn btn-indigo btn-sm" id="btnAddHtbMode">
                                        <i class="fa fa-plus me-1 d-none d-sm-inline"></i> {{ __('HTB') }}
                                    </button>
                                    <button type="button" class="btn btn-dark btn-sm" id="btnAddClosureMode">
                                        <i class="fa fa-plus me-1 d-none d-sm-inline"></i> {{ __('Closure') }}
                                    </button>
                                    <button type="button" class="btn btn-danger btn-sm d-none" id="btnCancelAdd">
                                        <i class="fa fa-times me-1"></i> {{ __('Batal') }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Right Group: Utility Actions -->
                        <div class="d-flex align-items-center gap-2 w-100 w-md-auto justify-content-md-end toolbar-actions">
                            <button type="button" class="btn btn-info btn-sm text-white" onclick="location.reload()" title="{{ __('Segarkan') }}">
                                <i class="fa fa-refresh"></i>
                            </button>
                            <button type="button" class="btn btn-secondary btn-sm" id="btnFullscreen" title="{{ __('Layar Penuh') }}">
                                <i class="fa fa-expand"></i>
                            </button>
                            <button type="button" class="btn btn-warning btn-sm" id="btnEditLines" title="{{ __('Edit Garis') }}">
                                <i class="fa fa-pencil"></i>
                            </button>
                        </div>
                    </div>

                    <div id="map" class="border"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- OLT Modal -->
<div class="modal fade" id="oltModal" tabindex="-1" aria-hidden="true">
    <!-- Added modal-fullscreen-sm-down for better mobile experience -->
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="oltModalLabel">{{ __('Tempatkan OLT') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>{{ __('Pilih OLT untuk ditempatkan di peta:') }}</p>
                <form id="oltForm">
                    <input type="hidden" id="olt_lat" name="latitude">
                    <input type="hidden" id="olt_lng" name="longitude">
                    <div class="mb-3">
                        <label for="olt_select" class="form-label">OLT</label>
                        <select class="form-select" id="olt_select" name="olt_id" required>
                            <option value="">{{ __('Pilih OLT') }}</option>
                            @foreach($olts as $olt)
                                <option value="{{ $olt->id }}" data-has-coord="{{ $olt->latitude ? 'true' : 'false' }}">
                                    {{ $olt->name }} ({{ $olt->host }}) {{ $olt->latitude ? '[Terkunci]' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Tutup') }}</button>
                <button type="button" class="btn btn-primary" id="saveOltBtn">{{ __('Tempatkan OLT') }}</button>
            </div>
        </div>
    </div>
</div>

<!-- ODC Modal -->
<div class="modal fade" id="odcModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="odcModalLabel">{{ __('Tambah ODC') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="odcForm">
                    <input type="hidden" id="odc_id" name="id">
                    <div class="mb-3">
                        <label for="odc_name" class="form-label">{{ __('Nama ODC') }}</label>
                        <input type="text" class="form-control" id="odc_name" name="name" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="odc_pon_port" class="form-label">{{ __('Port PON') }}</label>
                            <input type="text" class="form-control" id="odc_pon_port" name="pon_port" required placeholder="e.g. 01">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="odc_area" class="form-label">{{ __('Area') }}</label>
                            <input type="text" class="form-control" id="odc_area" name="area" required placeholder="e.g. CI">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="odc_color" class="form-label">{{ __('Warna Tube / Fiber') }}</label>
                            <select class="form-select" id="odc_color" name="color" required>
                                <option value="">{{ __('Pilih Warna') }}</option>
                                <option value="BLUE" data-code="B">Blue (Biru)</option>
                                <option value="ORANGE" data-code="O">Orange (Oranye)</option>
                                <option value="GREEN" data-code="G">Green (Hijau)</option>
                                <option value="BROWN" data-code="C">Brown (Coklat)</option>
                                <option value="SLATE" data-code="S">Slate (Abu-abu)</option>
                                <option value="WHITE" data-code="P">White (Putih)</option>
                                <option value="RED" data-code="M">Red (Merah)</option>
                                <option value="BLACK" data-code="H">Black (Hitam)</option>
                                <option value="YELLOW" data-code="K">Yellow (Kuning)</option>
                                <option value="VIOLET" data-code="U">Violet (Ungu)</option>
                                <option value="ROSE" data-code="P">Rose (Pink)</option>
                                <option value="AQUA" data-code="T">Aqua (Tosca)</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="odc_cable_no" class="form-label">{{ __('No Kabel') }}</label>
                            <input type="text" class="form-control" id="odc_cable_no" name="cable_no" required placeholder="e.g. 01">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="odc_lat" class="form-label">{{ __('Lintang') }}</label>
                            <input type="number" step="any" class="form-control" id="odc_lat" name="latitude" required readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="odc_lng" class="form-label">{{ __('Bujur') }}</label>
                            <input type="number" step="any" class="form-control" id="odc_lng" name="longitude" required readonly>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="odc_capacity" class="form-label">{{ __('Kapasitas') }}</label>
                        <input type="number" class="form-control" id="odc_capacity" name="capacity" value="48" required>
                    </div>
                    <div class="mb-3">
                        <label for="odc_olt" class="form-label">{{ __('OLT') }}</label>
                        <select class="form-select" id="odc_olt" name="olt_id" required>
                            <option value="">{{ __('Pilih OLT') }}</option>
                            @foreach($olts as $olt)
                                <option value="{{ $olt->id }}">{{ $olt->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="odc_description" class="form-label">{{ __('Deskripsi') }}</label>
                        <textarea class="form-control" id="odc_description" name="description"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Tutup') }}</button>
                <button type="button" class="btn btn-primary" id="saveOdcBtn">{{ __('Simpan ODC') }}</button>
            </div>
        </div>
    </div>
</div>

<!-- ODP Modal -->
<div class="modal fade" id="odpModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="odpModalLabel">{{ __('Tambah ODP') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="odpForm">
                    <input type="hidden" id="odp_id" name="id">
                    <div class="mb-3">
                        <label for="odp_name" class="form-label">{{ __('Nama ODP') }}</label>
                        <input type="text" class="form-control" id="odp_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="odp_kampung" class="form-label">{{ __('Kampung') }}</label>
                        <input type="text" class="form-control" id="odp_kampung" name="kampung" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="odp_lat" class="form-label">{{ __('Lintang') }}</label>
                            <input type="number" step="any" class="form-control" id="odp_lat" name="latitude" required readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="odp_lng" class="form-label">{{ __('Bujur') }}</label>
                            <input type="number" step="any" class="form-control" id="odp_lng" name="longitude" required readonly>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="odp_capacity" class="form-label">{{ __('Kapasitas') }}</label>
                        <input type="number" class="form-control" id="odp_capacity" name="capacity" value="8" required>
                    </div>
                    <div class="mb-3">
                        <label for="odp_region" class="form-label">{{ __('Wilayah') }}</label>
                        <select class="form-select" id="odp_region" name="region_id">
                            <option value="">{{ __('Pilih Wilayah') }}</option>
                            @foreach($regions as $region)
                                <option value="{{ $region->id }}">{{ $region->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="odp_odc" class="form-label">{{ __('Uplink ODC') }}</label>
                        <select class="form-select" id="odp_odc" name="odc_id">
                            <option value="">{{ __('Pilih ODC') }}</option>
                            @foreach($odcs as $odc)
                                <option value="{{ $odc->id }}">{{ $odc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="odp_color" class="form-label">{{ __('Warna Uplink') }}</label>
                        <select class="form-select" id="odp_color" name="color">
                            <option value="#0000FF" style="color: blue;">Blue (Biru)</option>
                            <option value="#FFA500" style="color: orange;">Orange (Oranye)</option>
                            <option value="#008000" style="color: green;">Green (Hijau)</option>
                            <option value="#A52A2A" style="color: brown;">Brown (Coklat)</option>
                            <option value="#808080" style="color: grey;">Slate (Abu-abu)</option>
                            <option value="#FFFFFF" style="background-color: #ddd;">White (Putih)</option>
                            <option value="#FF0000" style="color: red;">Red (Merah)</option>
                            <option value="#000000">Black (Hitam)</option>
                            <option value="#FFFF00" style="background-color: #333; color: yellow;">Yellow (Kuning)</option>
                            <option value="#EE82EE" style="color: violet;">Violet (Ungu)</option>
                            <option value="#FFC0CB" style="color: pink; background-color: #333;">Rose (Merah Muda)</option>
                            <option value="#40E0D0" style="color: turquoise; background-color: #333;">Aqua (Tosca)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="odp_description" class="form-label">{{ __('Deskripsi') }}</label>
                        <textarea class="form-control" id="odp_description" name="description"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Tutup') }}</button>
                <button type="button" class="btn btn-primary" id="saveOdpBtn">{{ __('Simpan ODP') }}</button>
            </div>
        </div>
    </div>
</div>
<!-- HTB Modal -->
<div class="modal fade" id="htbModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="htbModalLabel">{{ __('Tambah HTB') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="htbForm">
                    <input type="hidden" id="htb_id" name="id">
                    <div class="mb-3">
                        <label for="htb_name" class="form-label">{{ __('Nama HTB') }}</label>
                        <input type="text" class="form-control" id="htb_name" name="name" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="htb_lat" class="form-label">{{ __('Lintang') }}</label>
                            <input type="number" step="any" class="form-control" id="htb_lat" name="latitude" required readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="htb_lng" class="form-label">{{ __('Bujur') }}</label>
                            <input type="number" step="any" class="form-control" id="htb_lng" name="longitude" required readonly>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="htb_uplink_type" class="form-label">{{ __('Tipe Uplink') }}</label>
                        <select class="form-select" id="htb_uplink_type" name="uplink_type" required>
                            <option value="odp">ODP</option>
                            <option value="htb">Parent HTB</option>
                        </select>
                    </div>
                    <div class="mb-3" id="htb_odp_group">
                        <label for="htb_odp" class="form-label">{{ __('Uplink ODP') }}</label>
                        <select class="form-select" id="htb_odp" name="odp_id">
                            <option value="">{{ __('Pilih ODP') }}</option>
                            @foreach($odps as $odp)
                                <option value="{{ $odp->id }}">{{ $odp->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3 d-none" id="htb_parent_group">
                        <label for="htb_parent" class="form-label">{{ __('Induk HTB') }}</label>
                        <select class="form-select" id="htb_parent" name="parent_htb_id">
                            <option value="">{{ __('Pilih Induk HTB') }}</option>
                            @foreach($htbs as $htb)
                                <option value="{{ $htb->id }}">{{ $htb->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="htb_description" class="form-label">{{ __('Deskripsi') }}</label>
                        <textarea class="form-control" id="htb_description" name="description"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Tutup') }}</button>
                <button type="button" class="btn btn-primary" id="saveHtbBtn">{{ __('Simpan HTB') }}</button>
            </div>
        </div>
    </div>
</div>

<!-- Closure Modal -->
<div class="modal fade" id="closureModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="closureModalLabel">{{ __('Tambah Closure') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="closureForm">
                    <input type="hidden" id="closure_id" name="id">
                    <div class="mb-3">
                        <label for="closure_name" class="form-label">{{ __('Nama Closure') }}</label>
                        <input type="text" class="form-control" id="closure_name" name="name" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="closure_lat" class="form-label">{{ __('Lintang') }}</label>
                            <input type="number" step="any" class="form-control" id="closure_lat" name="latitude" required readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="closure_lng" class="form-label">{{ __('Bujur') }}</label>
                            <input type="number" step="any" class="form-control" id="closure_lng" name="longitude" required readonly>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="closure_capacity" class="form-label">{{ __('Kapasitas') }}</label>
                        <input type="number" class="form-control" id="closure_capacity" name="capacity" value="24" required>
                    </div>
                    <div class="mb-3">
                        <label for="closure_region" class="form-label">{{ __('Wilayah') }}</label>
                        <select class="form-select" id="closure_region" name="region_id">
                            <option value="">{{ __('Pilih Wilayah') }}</option>
                            @foreach($regions as $region)
                                <option value="{{ $region->id }}">{{ $region->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="closure_odc" class="form-label">{{ __('Uplink ODC') }}</label>
                        <select class="form-select" id="closure_odc" name="odc_id">
                            <option value="">{{ __('Pilih ODC') }}</option>
                            @foreach($odcs as $odc)
                                <option value="{{ $odc->id }}">{{ $odc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="closure_description" class="form-label">{{ __('Deskripsi') }}</label>
                        <textarea class="form-control" id="closure_description" name="description"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Tutup') }}</button>
                <button type="button" class="btn btn-primary" id="saveClosureBtn">{{ __('Simpan Closure') }}</button>
            </div>
        </div>
    </div>
</div>

<!-- WiFi Modal -->
<div class="modal fade" id="wifiModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-bold"><i data-lucide="wifi" class="w-5 h-5 inline-block mr-2"></i>Ganti WiFi Pelanggan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="wifiForm">
                    <input type="hidden" id="wifi_customer_id">
                    <div class="mb-3">
                        <label class="form-label">Nama SSID</label>
                        <div class="input-group">
                            <span class="input-group-text"><i data-lucide="signal" class="w-4 h-4"></i></span>
                            <input type="text" id="wifi_ssid" class="form-control" placeholder="Masukkan SSID baru" required maxlength="32">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password WiFi</label>
                        <div class="input-group">
                            <span class="input-group-text"><i data-lucide="key" class="w-4 h-4"></i></span>
                            <input type="text" id="wifi_password" class="form-control" placeholder="Minimal 8 karakter" required minlength="8">
                        </div>
                        <div class="form-text mt-2">
                            <i data-lucide="info" class="w-3 h-3 inline-block mr-1"></i> Perubahan akan dikirim ke GenieACS dan diupdate ke router pelanggan.
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                <button type="button" id="saveWifiBtn" class="btn btn-primary btn-sm font-bold px-4">
                    <span id="wifiBtnSpinner" class="spinner-border spinner-border-sm d-none me-2"></span>
                    Simpan Perubahan
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<script src="https://unpkg.com/leaflet-polylinedecorator/dist/leaflet.polylineDecorator.min.js"></script>

<style>
    #map {
        height: 80vh;
        width: 100%;
        border-radius: 8px;
    }
    .modern-map-popup .leaflet-popup-content-wrapper {
        background: #0f172a !important; /* Very dark navy/slate */
        padding: 0 !important;
        box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.5), 0 8px 10px -6px rgb(0 0 0 / 0.5) !important;
        border: 1px solid #1e293b !important;
        border-radius: 12px !important;
    }
    .modern-map-popup .leaflet-popup-content {
        margin: 0 !important;
        width: 320px !important;
    }
    .modern-map-popup .leaflet-popup-tip {
        background: #0f172a !important;
    }
    .modern-map-popup .leaflet-popup-close-button {
        color: #94a3b8 !important;
        padding: 10px 10px 0 0 !important;
        font-size: 18px !important;
        z-index: 100 !important;
    }
    .modern-map-popup .leaflet-popup-close-button:hover {
        color: #ffffff !important;
        background: transparent !important;
    }
    .lucide-icon {
        display: inline-block;
        vertical-align: middle;
    }
    .leaflet-container a.leaflet-popup-close-button {
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .modern-map-popup .customer-card {
        width: 320px;
        color: #cbd5e1;
        font-family: "Inter", "Segoe UI", Tahoma, sans-serif;
        font-size: 12px;
        line-height: 1.4;
    }
    .modern-map-popup .customer-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px;
        border-bottom: 1px solid #1f2937;
    }
    .modern-map-popup .customer-header-left {
        display: flex;
        align-items: center;
        gap: 8px;
        min-width: 0;
    }
    .modern-map-popup .status-dot {
        font-size: 18px;
        font-weight: 700;
        line-height: 1;
    }
    .modern-map-popup .customer-content {
        padding: 14px;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    .modern-map-popup .customer-header-title {
        font-size: 15px;
        font-weight: 700;
        color: #f1f5f9;
        letter-spacing: 0.01em;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 200px;
    }
    .modern-map-popup .top-row {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .modern-map-popup .ip-box {
        flex: 1;
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 10px;
        border-radius: 10px;
        background: rgba(30, 41, 59, 0.55);
        border: 1px solid rgba(71, 85, 105, 0.5);
    }
    .modern-map-popup .ip-text {
        font-family: "Consolas", "Menlo", monospace;
        font-size: 12px;
        font-weight: 700;
        color: #e2e8f0;
    }
    .modern-map-popup .status-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 12px;
    }
    .modern-map-popup .status-label {
        color: #94a3b8;
        font-weight: 600;
    }
    .modern-map-popup .status-value {
        font-size: 13px;
        font-weight: 800;
        letter-spacing: 0.08em;
    }
    .modern-map-popup .status-online { color: #22c55e; }
    .modern-map-popup .status-offline { color: #ef4444; }
    .modern-map-popup .acs-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }
    .modern-map-popup .acs-title {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: #c084fc;
        font-weight: 700;
    }
    .modern-map-popup .acs-up {
        color: #94a3b8;
        font-size: 11px;
        font-weight: 600;
    }
    .modern-map-popup .acs-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
    }
    .modern-map-popup .acs-item {
        text-align: center;
        background: rgba(15, 23, 42, 0.35);
        border-radius: 8px;
        padding: 6px 4px;
    }
    .modern-map-popup .acs-label {
        font-size: 9px;
        color: #64748b;
        font-weight: 700;
        text-transform: uppercase;
    }
    .modern-map-popup .acs-value {
        margin-top: 2px;
        font-size: 13px;
        font-weight: 800;
    }
    .modern-map-popup .acs-value-start { color: #facc15; }
    .modern-map-popup .acs-value-now-online { color: #22c55e; }
    .modern-map-popup .acs-value-now-offline { color: #64748b; }
    .modern-map-popup .btn-grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
    }
    .modern-map-popup .btn-grid-3 {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 8px;
    }
    .modern-map-popup .traffic-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
    }
    .modern-map-popup .traffic-title {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: #e2e8f0;
        font-weight: 700;
        font-size: 12px;
    }
    .modern-map-popup .traffic-legend {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        color: #cbd5e1;
        font-size: 10px;
        font-weight: 700;
    }
    .modern-map-popup .traffic-dot {
        width: 8px;
        height: 8px;
        border-radius: 999px;
        display: inline-block;
        margin-right: 4px;
    }
    .modern-map-popup .traffic-dot-tx { background: #3b82f6; }
    .modern-map-popup .traffic-dot-rx { background: #22c55e; }
    .modern-map-popup .traffic-chart {
        height: 68px;
        border-radius: 8px;
        background: rgba(15, 23, 42, 0.5);
        border: 1px solid rgba(71, 85, 105, 0.4);
        position: relative;
        overflow: hidden;
    }
    .modern-map-popup .traffic-foot {
        margin-top: 6px;
        display: flex;
        justify-content: space-between;
        color: #94a3b8;
        font-size: 10px;
        font-weight: 700;
    }
    .modern-map-popup .distance-row {
        text-align: center;
        border-top: 1px solid #1f2937;
        padding-top: 8px;
        color: #22d3ee;
        font-weight: 800;
        font-size: 14px;
    }
    .modern-map-popup .customer-badge {
        background: #2563ebcc;
        color: #fff;
        font-size: 10px;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 999px;
    }
    .modern-map-popup .card-panel {
        background: rgba(30, 41, 59, 0.45);
        border: 1px solid rgba(71, 85, 105, 0.45);
        border-radius: 10px;
    }
    .modern-map-popup .kv-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
    }
    .modern-map-popup .popup-btn {
        border: 0;
        border-radius: 10px;
        color: #fff;
        font-weight: 700;
        font-size: 10px;
        letter-spacing: 0.01em;
        text-transform: uppercase;
        /* padding: 9px 10px; */
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        transition: all .15s ease;
        width: 100%;
        min-height: 36px;
        box-shadow: inset 0 1px 0 rgba(255,255,255,.12);
        cursor: pointer;
    }
    .modern-map-popup .popup-btn:hover { filter: brightness(1.08); }
    .modern-map-popup .popup-btn:active { transform: scale(0.98); }
    .modern-map-popup .popup-btn.icon-top {
        flex-direction: column;
        gap: 2px;
        min-height: 48px;
        font-size: 10px;
    }
    .modern-map-popup .popup-btn.main-action {
        min-height: 40px;
        font-size: 12px;
    }
    .modern-map-popup .btn-cyan { background: #06b6d4; }
    .modern-map-popup .btn-green { background: #16a34a; }
    .modern-map-popup .btn-emerald { background: #10b981; }
    .modern-map-popup .btn-red { background: #dc2626; }
    .modern-map-popup .btn-blue { background: #2563eb; }
    .modern-map-popup .btn-slate { background: #334155; }
    .custom-icon.icon-modem {
        background: #7c3aed !important;
        border-color: #6d28d9 !important;
        color: #ffffff !important;
        box-shadow: 0 4px 12px rgba(124, 58, 237, 0.45);
    }

    /* Theme-aware popup + marker styles (sync with map dark/light mode) */
    [data-bs-theme="light"] .modern-map-popup {
        --mp-bg: #ffffff;
        --mp-border: #dbe2ea;
        --mp-text: #1f2937;
        --mp-muted: #64748b;
        --mp-title: #0f172a;
        --mp-panel: #f8fafc;
        --mp-panel-border: #dbe2ea;
        --mp-chip: #e2e8f0;
        --mp-distance: #0891b2;
    }
    [data-bs-theme="dark"] .modern-map-popup {
        --mp-bg: #0f172a;
        --mp-border: #1e293b;
        --mp-text: #cbd5e1;
        --mp-muted: #94a3b8;
        --mp-title: #f1f5f9;
        --mp-panel: rgba(30, 41, 59, 0.45);
        --mp-panel-border: rgba(71, 85, 105, 0.45);
        --mp-chip: #2563ebcc;
        --mp-distance: #22d3ee;
    }

    .modern-map-popup .leaflet-popup-content-wrapper {
        background: var(--mp-bg) !important;
        border-color: var(--mp-border) !important;
    }
    .modern-map-popup .leaflet-popup-tip { background: var(--mp-bg) !important; }
    .modern-map-popup .leaflet-popup-close-button { color: var(--mp-muted) !important; }
    .modern-map-popup .customer-card { color: var(--mp-text); }
    .modern-map-popup .customer-header { border-bottom-color: var(--mp-border); }
    .modern-map-popup .customer-header-title { color: var(--mp-title); }
    .modern-map-popup .status-label,
    .modern-map-popup .acs-up,
    .modern-map-popup .traffic-foot,
    .modern-map-popup .acs-label { color: var(--mp-muted); }
    .modern-map-popup .ip-box,
    .modern-map-popup .card-panel,
    .modern-map-popup .acs-item,
    .modern-map-popup .traffic-chart {
        background: var(--mp-panel);
        border-color: var(--mp-panel-border);
    }
    .modern-map-popup .ip-text,
    .modern-map-popup .traffic-title,
    .modern-map-popup .traffic-legend { color: var(--mp-text); }
    .modern-map-popup .distance-row {
        border-top-color: var(--mp-border);
        color: var(--mp-distance);
    }
    .modern-map-popup .customer-badge {
        background: var(--mp-chip);
        color: var(--mp-title);
    }

    /* Basic marker visual consistency for all icon types */
    .custom-icon {
        width: 100%;
        height: 100%;
        border-radius: 10px;
        border: 2px solid transparent;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #ffffff;
        backdrop-filter: blur(1px);
    }
    .custom-icon.icon-olt { background: #2563eb; border-color: #1d4ed8; box-shadow: 0 4px 12px rgba(37, 99, 235, .38); }
    .custom-icon.icon-odc { background: #f59e0b; border-color: #d97706; box-shadow: 0 4px 12px rgba(245, 158, 11, .38); }
    .custom-icon.icon-odp { background: #10b981; border-color: #059669; box-shadow: 0 4px 12px rgba(16, 185, 129, .38); }
    .custom-icon.icon-htb { background: #6366f1; border-color: #4f46e5; box-shadow: 0 4px 12px rgba(99, 102, 241, .38); }
    .custom-icon.icon-closure { background: #0f172a; border-color: #334155; box-shadow: 0 4px 12px rgba(15, 23, 42, .38); }
    .custom-icon.icon-asset { background: #0ea5e9; border-color: #0284c7; box-shadow: 0 4px 12px rgba(14, 165, 233, .38); }
    .custom-icon.icon-customer-online { background: #06b6d4; border-color: #0891b2; box-shadow: 0 4px 12px rgba(6, 182, 212, .42); }
    .custom-icon.icon-customer-offline { background: #facc15; border-color: #ca8a04; color: #334155; box-shadow: 0 4px 12px rgba(250, 204, 21, .42); }

    [data-bs-theme="light"] .custom-icon {
        border-width: 1.5px;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.18);
    }
</style>
@endpush
@push('scripts')
<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script src="https://cdn.jsdelivr.net/npm/leaflet-ant-path@1.3.0/dist/leaflet-ant-path.min.js"></script>
<!-- Lucide Icons -->
<script src="https://unpkg.com/lucide@latest"></script>
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Data from Controller
        var customers = @json($customers) || [];
        var odps = @json($odps) || [];
        var htbs = @json($htbs) || [];
        var closures = @json($closures) || [];
        var odcs = @json($odcs) || [];
        var olts = @json($olts) || [];
        var assets = @json($assets) || [];
        var modemDataRecords = @json($modemDataRecords ?? []) || [];
        var coordinatorRegionId = @json($coordinatorRegionId ?? null);
        var canManageMap = @json($canManageMap ?? false);
        var canEditCustomer = @json($canEditCustomer ?? false);

        // Initialize map
        // Server Location: -6.800278, 105.939159
        var defaultLat = -6.800278;
        var defaultLng = 105.939159;
        var initialZoom = 20; // Adjusted zoom for better initial view of the area

        function hasCoord(o) {
            return o && typeof o.latitude !== 'undefined' && typeof o.longitude !== 'undefined' && o.latitude !== null && o.longitude !== null;
        }
        function firstWithCoord(arr) {
            for (var i = 0; i < arr.length; i++) {
                if (hasCoord(arr[i])) return arr[i];
            }
            return null;
        }
        function toNumber(value) {
            var num = parseFloat(value);
            return Number.isFinite(num) ? num : null;
        }
        function calculateDistanceMeters(lat1, lng1, lat2, lng2) {
            var aLat = toNumber(lat1);
            var aLng = toNumber(lng1);
            var bLat = toNumber(lat2);
            var bLng = toNumber(lng2);
            if (aLat === null || aLng === null || bLat === null || bLng === null) {
                return null;
            }
            var toRad = function(v) { return v * Math.PI / 180; };
            var earthRadius = 6371000;
            var dLat = toRad(bLat - aLat);
            var dLng = toRad(bLng - aLng);
            var sinDLat = Math.sin(dLat / 2);
            var sinDLng = Math.sin(dLng / 2);
            var h = sinDLat * sinDLat + Math.cos(toRad(aLat)) * Math.cos(toRad(bLat)) * sinDLng * sinDLng;
            var c = 2 * Math.atan2(Math.sqrt(h), Math.sqrt(1 - h));
            return earthRadius * c;
        }
        function formatDistance(value) {
            if (value === null || !Number.isFinite(value)) {
                return '-';
            }
            if (value < 1000) {
                return Math.round(value) + ' m';
            }
            return (value / 1000).toFixed(2) + ' km';
        }
        function calculatePolylineDistanceMeters(pathPoints) {
            if (!Array.isArray(pathPoints) || pathPoints.length < 2) {
                return null;
            }
            var total = 0;
            for (var i = 1; i < pathPoints.length; i++) {
                var prev = pathPoints[i - 1];
                var curr = pathPoints[i];
                var segment = calculateDistanceMeters(prev[0], prev[1], curr[0], curr[1]);
                if (segment === null) {
                    return null;
                }
                total += segment;
            }
            return total;
        }
        function getLineDistanceMeters(startLat, startLng, endLat, endLng, key) {
            var pathPoints = [[startLat, startLng]];
            if (key && Array.isArray(waypoints[key])) {
                waypoints[key].forEach(function(p) {
                    pathPoints.push([p.lat, p.lng]);
                });
            }
            pathPoints.push([endLat, endLng]);
            return calculatePolylineDistanceMeters(pathPoints);
        }
        // Auto-center logic commented out to prioritize server location
        /*
        var picked = firstWithCoord(customers) || firstWithCoord(olts) || firstWithCoord(odcs);
        if (picked) {
            defaultLat = picked.latitude;
            defaultLng = picked.longitude;
        }
        */

        var map = L.map('map').setView([defaultLat, defaultLng], initialZoom);
        var isPickerMode = new URLSearchParams(window.location.search).get('picker') === '1';

        var osm = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap'
        });

        var googleHybrid = L.tileLayer('https://{s}.google.com/vt/lyrs=s,h&x={x}&y={y}&z={z}',{
            maxZoom: 22,
            subdomains:['mt0','mt1','mt2','mt3'],
            attribution: '&copy; Google Maps'
        });
        
        var darkLayer = L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            maxZoom: 20,
            attribution: '&copy; OpenStreetMap contributors &copy; CARTO'
        });

        // Determine initial layer based on theme
        var currentTheme = document.documentElement.getAttribute('data-bs-theme') || 'light';
        if (currentTheme === 'dark') {
            darkLayer.addTo(map);
        } else {
            osm.addTo(map);
        }

        var baseMaps = {
            "Dark Mode": darkLayer,
            "Satellite (Google)": googleHybrid,
            "Street (OSM)": osm
        };
        L.control.layers(baseMaps).addTo(map);

        // Custom Map Toolbar Control (Shortcut)
        L.Control.MapToolbar = L.Control.extend({
            onAdd: function(map) {
                var container = L.DomUtil.create('div', 'leaflet-control-map-toolbar');
                container.style.backgroundColor = 'rgba(255,255,255,0.95)';
                container.style.padding = '8px';
                container.style.borderRadius = '8px';
                container.style.boxShadow = '0 2px 8px rgba(0,0,0,0.15)';
                container.style.display = 'flex';
                container.style.flexDirection = 'column';
                container.style.gap = '6px';

                // Refresh Button
                var refreshBtn = L.DomUtil.create('button', 'btn btn-sm', container);
                refreshBtn.innerHTML = '<i class="fa fa-refresh"></i>';
                refreshBtn.title = '{{ __('Segarkan') }}';
                refreshBtn.style.width = '36px';
                refreshBtn.style.height = '36px';
                refreshBtn.style.backgroundColor = '#06b6d4';
                refreshBtn.style.color = 'white';
                refreshBtn.style.border = 'none';
                refreshBtn.onclick = function() { location.reload(); };

                // Fullscreen Button
                var fullscreenBtn = L.DomUtil.create('button', 'btn btn-sm', container);
                fullscreenBtn.innerHTML = '<i class="fa fa-expand"></i>';
                fullscreenBtn.title = '{{ __('Layar Penuh') }}';
                fullscreenBtn.style.width = '36px';
                fullscreenBtn.style.height = '36px';
                fullscreenBtn.style.backgroundColor = '#6b7280';
                fullscreenBtn.style.color = 'white';
                fullscreenBtn.style.border = 'none';
                fullscreenBtn.onclick = function() {
                    if (!document.fullscreenElement) {
                        document.documentElement.requestFullscreen();
                    } else {
                        document.exitFullscreen();
                    }
                };

                // Edit Lines Button
                var editLinesBtn = L.DomUtil.create('button', 'btn btn-sm', container);
                editLinesBtn.innerHTML = '<i class="fa fa-pencil"></i>';
                editLinesBtn.title = '{{ __('Edit Garis') }}';
                editLinesBtn.style.width = '36px';
                editLinesBtn.style.height = '36px';
                editLinesBtn.style.backgroundColor = '#f97316';
                editLinesBtn.style.color = 'white';
                editLinesBtn.style.border = 'none';
                editLinesBtn.id = 'map-btn-edit-lines';
                editLinesBtn.onclick = function() {
                    document.getElementById('btnEditLines').click();
                };

                @if(isset($isAdmin) && $isAdmin)
                // Add Infrastructure Buttons
                var separator1 = L.DomUtil.create('hr', '', container);
                separator1.style.margin = '6px 0';
                separator1.style.borderColor = '#e5e7eb';

                // Add OLT Button
                var addOltBtn = L.DomUtil.create('button', 'btn btn-sm', container);
                addOltBtn.innerHTML = '<i class="fa fa-server"></i>';
                addOltBtn.title = '{{ __('OLT') }}';
                addOltBtn.style.width = '36px';
                addOltBtn.style.height = '36px';
                addOltBtn.style.backgroundColor = '#3b82f6';
                addOltBtn.style.color = 'white';
                addOltBtn.style.border = 'none';
                addOltBtn.onclick = function() {
                    document.getElementById('btnAddOltMode').click();
                };

                // Add ODC Button
                var addOdcBtn = L.DomUtil.create('button', 'btn btn-sm', container);
                addOdcBtn.innerHTML = '<i class="fa fa-plus"></i>';
                addOdcBtn.title = '{{ __('ODC') }}';
                addOdcBtn.style.width = '36px';
                addOdcBtn.style.height = '36px';
                addOdcBtn.style.backgroundColor = '#eab308';
                addOdcBtn.style.color = '#1f2937';
                addOdcBtn.style.border = 'none';
                addOdcBtn.onclick = function() {
                    document.getElementById('btnAddOdcMode').click();
                };

                // Add ODP Button
                var addOdpBtn = L.DomUtil.create('button', 'btn btn-sm', container);
                addOdpBtn.innerHTML = '<i class="fa fa-plus"></i>';
                addOdpBtn.title = '{{ __('ODP') }}';
                addOdpBtn.style.width = '36px';
                addOdpBtn.style.height = '36px';
                addOdpBtn.style.backgroundColor = '#22c55e';
                addOdpBtn.style.color = 'white';
                addOdpBtn.style.border = 'none';
                addOdpBtn.onclick = function() {
                    document.getElementById('btnAddOdpMode').click();
                };

                // Add HTB Button
                var addHtbBtn = L.DomUtil.create('button', 'btn btn-sm', container);
                addHtbBtn.innerHTML = '<i class="fa fa-plus"></i>';
                addHtbBtn.title = '{{ __('HTB') }}';
                addHtbBtn.style.width = '36px';
                addHtbBtn.style.height = '36px';
                addHtbBtn.style.backgroundColor = '#a855f7';
                addHtbBtn.style.color = 'white';
                addHtbBtn.style.border = 'none';
                addHtbBtn.onclick = function() {
                    document.getElementById('btnAddHtbMode').click();
                };

                // Add Closure Button
                var addClosureBtn = L.DomUtil.create('button', 'btn btn-sm', container);
                addClosureBtn.innerHTML = '<i class="fa fa-plus"></i>';
                addClosureBtn.title = '{{ __('Closure') }}';
                addClosureBtn.style.width = '36px';
                addClosureBtn.style.height = '36px';
                addClosureBtn.style.backgroundColor = '#1f2937';
                addClosureBtn.style.color = 'white';
                addClosureBtn.style.border = 'none';
                addClosureBtn.onclick = function() {
                    document.getElementById('btnAddClosureMode').click();
                };
                @endif

                L.DomEvent.disableClickPropagation(container);
                return container;
            },
            onRemove: function(map) {}
        });

        L.control.mapToolbar = function(opts) {
            return new L.Control.MapToolbar(opts);
        };

        L.control.mapToolbar({ position: 'topright' }).addTo(map);

        var onlineColor = currentTheme === 'dark' ? '#00f2ff' : '#00a3ff';
        var onlinePulseColor = currentTheme === 'dark' ? '#00d8ff' : '#66caff';

        // Listen for theme changes to auto-switch map layer
        window.addEventListener('themeChanged', function(e) {
            if (e.detail.theme === 'dark') {
                if (map.hasLayer(osm)) map.removeLayer(osm);
                if (map.hasLayer(googleHybrid)) map.removeLayer(googleHybrid);
                if (!map.hasLayer(darkLayer)) darkLayer.addTo(map);
            } else {
                if (map.hasLayer(darkLayer)) map.removeLayer(darkLayer);
                // Default to OSM for light mode, unless user was on Satellite? 
                // For simplicity, switch to OSM.
                if (!map.hasLayer(osm) && !map.hasLayer(googleHybrid)) osm.addTo(map);
            }
        });

        // Feature Groups for bounds
        var markers = L.featureGroup().addTo(map);
        var lines = L.featureGroup().addTo(map);
        var onlineOverlay = L.featureGroup().addTo(map);
        var editLayer = L.featureGroup().addTo(map);
        var markerMap = {}; // Store markers for easy access
        var allMarkerObjs = []; // Store all marker objects for filtering
        window.arrowIntervals = []; // Track moving arrow intervals
        var editMode = false;
        var waypoints = {};

        // Helper for visibility
        function isVisible(item, type) {
            var areaFilter = document.getElementById('areaFilter');
            var selectedRegionId = areaFilter ? areaFilter.value : "";
            if (selectedRegionId === "" && coordinatorRegionId) {
                selectedRegionId = String(coordinatorRegionId);
            }
            
            if (selectedRegionId === "") return true;
            
            // Infrastructure always visible
            if (['olt', 'odc', 'asset', 'closure', 'modem'].includes(type)) return true;

            if (type === 'odp') return item.region_id == selectedRegionId;
            if (type === 'htb') {
                return item.odp && item.odp.region_id == selectedRegionId;
            }
            if (type === 'customer') {
                if (!item.odp) return false;
                return item.odp.region_id == selectedRegionId;
            }
            
            return true;
        }

        function updateMapVisibility() {
            markers.clearLayers();
            
            allMarkerObjs.forEach(function(obj) {
                if (isVisible(obj.data, obj.type)) {
                    obj.marker.addTo(markers);
                }
            });
            
            drawLines();
        }

        // Color Mapping for Cables
        const colorMap = {
            'BLUE': 'blue', 'BIRU': 'blue',
            'ORANGE': 'orange', 'ORANYE': 'orange',
            'GREEN': 'green', 'HIJAU': 'green',
            'BROWN': 'brown', 'COKLAT': 'brown',
            'SLATE': 'slategray', 'ABU-ABU': 'gray', 'ABU': 'gray',
            'WHITE': 'white', 'PUTIH': 'white',
            'RED': 'red', 'MERAH': 'red',
            'BLACK': 'black', 'HITAM': 'black',
            'YELLOW': 'yellow', 'KUNING': 'yellow',
            'VIOLET': 'violet', 'UNGU': 'purple',
            'ROSE': 'pink', 'MERAH MUDA': 'pink',
            'AQUA': 'aqua', 'TOSCA': 'turquoise'
        };
        const colorLabelMap = {
            'BLUE': 'Blue (Biru)',
            'BIRU': 'Blue (Biru)',
            'ORANGE': 'Orange (Oranye)',
            'ORANYE': 'Orange (Oranye)',
            'GREEN': 'Green (Hijau)',
            'HIJAU': 'Green (Hijau)',
            'BROWN': 'Brown (Coklat)',
            'COKLAT': 'Brown (Coklat)',
            'SLATE': 'Slate',
            'ABU-ABU': 'Abu-abu',
            'ABU': 'Abu-abu',
            'WHITE': 'White (Putih)',
            'PUTIH': 'White (Putih)',
            'RED': 'Red (Merah)',
            'MERAH': 'Red (Merah)',
            'BLACK': 'Black (Hitam)',
            'HITAM': 'Black (Hitam)',
            'YELLOW': 'Yellow (Kuning)',
            'KUNING': 'Yellow (Kuning)',
            'VIOLET': 'Violet (Ungu)',
            'UNGU': 'Violet (Ungu)',
            'ROSE': 'Rose (Merah Muda)',
            'MERAH MUDA': 'Rose (Merah Muda)',
            'AQUA': 'Aqua (Tosca)',
            'TOSCA': 'Aqua (Tosca)'
        };
        const colorHexLabelMap = {
            '#0000FF': 'Blue (Biru)',
            '#FFA500': 'Orange (Oranye)',
            '#00FF00': 'Green (Hijau)',
            '#A52A2A': 'Brown (Coklat)',
            '#708090': 'Slate',
            '#808080': 'Abu-abu',
            '#FFFFFF': 'White (Putih)',
            '#FF0000': 'Red (Merah)',
            '#000000': 'Black (Hitam)',
            '#FFFF00': 'Yellow (Kuning)',
            '#800080': 'Violet (Ungu)',
            '#FFC0CB': 'Rose (Merah Muda)',
            '#00FFFF': 'Aqua (Tosca)',
            '#40E0D0': 'Aqua (Tosca)'
        };
        const colorPalette = [
            { hex: '#0000FF', label: 'Blue (Biru)' },
            { hex: '#FFA500', label: 'Orange (Oranye)' },
            { hex: '#00FF00', label: 'Green (Hijau)' },
            { hex: '#A52A2A', label: 'Brown (Coklat)' },
            { hex: '#708090', label: 'Slate' },
            { hex: '#808080', label: 'Abu-abu' },
            { hex: '#FFFFFF', label: 'White (Putih)' },
            { hex: '#FF0000', label: 'Red (Merah)' },
            { hex: '#000000', label: 'Black (Hitam)' },
            { hex: '#FFFF00', label: 'Yellow (Kuning)' },
            { hex: '#800080', label: 'Violet (Ungu)' },
            { hex: '#FFC0CB', label: 'Rose (Merah Muda)' },
            { hex: '#00FFFF', label: 'Aqua (Tosca)' }
        ];
        function normalizeHexColor(value) {
            var raw = String(value || '').trim().toUpperCase();
            if (!raw.startsWith('#')) {
                return raw;
            }
            if (/^#[0-9A-F]{3}$/.test(raw)) {
                return '#' + raw[1] + raw[1] + raw[2] + raw[2] + raw[3] + raw[3];
            }
            if (/^#[0-9A-F]{6}$/.test(raw)) {
                return raw;
            }
            return raw;
        }
        function hexToRgb(hex) {
            var normalized = normalizeHexColor(hex);
            if (!/^#[0-9A-F]{6}$/.test(normalized)) {
                return null;
            }
            return {
                r: parseInt(normalized.slice(1, 3), 16),
                g: parseInt(normalized.slice(3, 5), 16),
                b: parseInt(normalized.slice(5, 7), 16)
            };
        }
        function nearestColorLabelFromHex(hex) {
            var rgb = hexToRgb(hex);
            if (!rgb) {
                return null;
            }
            var nearestLabel = null;
            var nearestDistance = Infinity;
            colorPalette.forEach(function(color) {
                var sample = hexToRgb(color.hex);
                if (!sample) {
                    return;
                }
                var dr = rgb.r - sample.r;
                var dg = rgb.g - sample.g;
                var db = rgb.b - sample.b;
                var distance = dr * dr + dg * dg + db * db;
                if (distance < nearestDistance) {
                    nearestDistance = distance;
                    nearestLabel = color.label;
                }
            });
            return nearestLabel;
        }
        function formatFiberColorLabel(rawColor) {
            var raw = String(rawColor || '').trim();
            if (raw === '') {
                return '-';
            }
            var key = raw.toUpperCase();
            if (colorLabelMap[key]) {
                return colorLabelMap[key];
            }
            var normalizedHex = normalizeHexColor(raw);
            if (colorHexLabelMap[normalizedHex]) {
                return colorHexLabelMap[normalizedHex];
            }
            var nearest = nearestColorLabelFromHex(raw);
            return nearest || raw;
        }

        function getConnectionKey(fromType, fromId, toType, toId) {
            return `${fromType}_${fromId}__${toType}_${toId}`;
        }

        function parseConnectionKey(key) {
            var parts = key.split('__');
            var fromParts = parts[0].split('_');
            var toParts = parts[1].split('_');
            return {
                fromType: fromParts[0],
                fromId: fromParts[1],
                toType: toParts[0],
                toId: toParts[1]
            };
        }

        function saveConnectionByKey(key) {
            var meta = parseConnectionKey(key);
            // Fix type names if needed
            var toType = meta.toType === 'cust' ? 'customer' : meta.toType;
            
            var points = (waypoints[key] || []).map(function(p) {
                return { lat: p.lat, lng: p.lng };
            });

            fetch('{{ route("map.connections.save") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    from_type: meta.fromType,
                    from_id: meta.fromId,
                    to_type: toType,
                    to_id: meta.toId,
                    waypoints: points
                })
            })
            .then(res => res.json())
            .then(data => {
                if (!data.success) console.error('Failed to save waypoints', data);
            })
            .catch(err => console.error('Error saving waypoints', err));
        }

        function addWaypointMarker(key, latlng) {
            if (!waypoints[key]) waypoints[key] = [];
            
            // Create marker first
            var icon = L.divIcon({ html: "<span style='color:#00f2ff; font-size: 16px; text-shadow: 0 0 3px black;'>●</span>", className: 'bg-transparent border-0', iconSize: [16,16], iconAnchor: [8,8] });
            var m = L.marker(latlng, { draggable: true, icon: icon }).addTo(editLayer);
            
            // Add to data structure
            var pointData = { lat: latlng.lat, lng: latlng.lng, _m: m };
            waypoints[key].push(pointData);

            m.on('drag', function(e) {
                pointData.lat = e.latlng.lat;
                pointData.lng = e.latlng.lng;
                // Redraw lines immediately for smooth drag
                // But full redraw might be heavy. For now, it's fine.
                drawLines(true); // pass true to skip clearing edit markers to avoid flickering? No, clearing is needed.
            });
            
            m.on('dragend', function() { 
                drawLines(); 
                saveConnectionByKey(key);
            });
            
            m.on('click', function(e) {
                L.DomEvent.stopPropagation(e); // Prevent line click
            });

            m.on('dblclick', function(e){
                L.DomEvent.stopPropagation(e);
                waypoints[key] = (waypoints[key] || []).filter(function(p){ return p._m !== m; });
                editLayer.removeLayer(m);
                drawLines();
                saveConnectionByKey(key);
            });
            
            saveConnectionByKey(key);
        }

        function clearEditMarkers() {
            // We only clear markers if we are re-initializing. 
            // If we are just dragging, we want to keep them.
            // But drawLines calls this. 
            // We need to re-add them if they exist in waypoints[key] but not on map?
            // Actually, existing logic re-clears and we lose reference if we are not careful.
            // The existing logic: `addWaypointMarker` adds to `editLayer`. `drawLines` clears `editLayer`.
            // This means every `drawLines` call REMOVES all edit handles.
            // This is bad for "drag" event.
            // We should Separate drawing lines from drawing edit handles.
            editLayer.clearLayers();
        }

        // We need to restore edit markers after clear
        function restoreEditMarkers() {
            if (!editMode) return;
            Object.keys(waypoints).forEach(function(key) {
                (waypoints[key] || []).forEach(function(p) {
                    var icon = L.divIcon({ html: "<span style='color:#00f2ff; font-size: 16px; text-shadow: 0 0 3px black;'>●</span>", className: 'bg-transparent border-0', iconSize: [16,16], iconAnchor: [8,8] });
                    var m = L.marker([p.lat, p.lng], { draggable: true, icon: icon }).addTo(editLayer);
                    p._m = m; // Update reference

                    m.on('drag', function(e) {
                        p.lat = e.latlng.lat;
                        p.lng = e.latlng.lng;
                        drawLines(true); // Pass flag to avoid clearing edit markers?
                    });
                    m.on('dragend', function() { 
                        drawLines(); 
                        saveConnectionByKey(key);
                    });
                    m.on('click', function(e) { L.DomEvent.stopPropagation(e); });
                    m.on('dblclick', function(e){
                        L.DomEvent.stopPropagation(e);
                        waypoints[key] = waypoints[key].filter(function(wp){ return wp !== p; });
                        editLayer.removeLayer(m);
                        drawLines();
                        saveConnectionByKey(key);
                    });
                });
            });
        }

        document.getElementById('btnEditLines').addEventListener('click', function() {
            editMode = !editMode;
            this.classList.toggle('btn-warning', !editMode);
            this.classList.toggle('btn-success', editMode);
            drawLines();
        });

        function loadConnections() {
            fetch('{{ route("map.connections.index") }}')
            .then(res => res.json())
            .then(data => {
                data.forEach(conn => {
                    // Reconstruct key
                    // conn.to_type might be 'customer' but key expects 'cust'? 
                    // Let's standardize on 'cust' for customer in key if that was previous convention, 
                    // OR switch to 'customer'. 
                    // Previous code used 'odp_' + odpId + '__cust_' + customerId
                    // Let's stick to 'cust' for customer to match existing pattern if we want minimal change, 
                    // BUT 'customer' is cleaner.
                    // Let's use 'customer' everywhere.
                    
                    var toType = conn.to_type === 'customer' ? 'cust' : conn.to_type;
                    var key = getConnectionKey(conn.from_type, conn.from_id, toType, conn.to_id);
                    
                    if (conn.waypoints && Array.isArray(conn.waypoints)) {
                        waypoints[key] = conn.waypoints.map(p => ({ lat: p.lat, lng: p.lng }));
                    }
                });
                drawLines();
            })
            .catch(err => console.error('Error loading connections', err));
        }

        // Redraw lines function
        function drawLines(skipClearEdit) {
            lines.clearLayers();
            if (window.arrowIntervals && window.arrowIntervals.length) {
                window.arrowIntervals.forEach(function(id){ try { clearInterval(id); } catch(e){} });
                window.arrowIntervals = [];
            }
            
            // If skipClearEdit is true (during drag), we don't clear editLayer.
            // But actually, we need to re-render lines. 
            // If we don't clear editLayer, we don't need to restore them.
            if (!skipClearEdit) {
                editLayer.clearLayers();
            }

            // OLT -> ODC
            odcs.forEach(function(odc) {
                if (odc.latitude && odc.longitude) {
                    var uplinkOlt = olts.find(o => o.id == odc.olt_id);
                    if (uplinkOlt && uplinkOlt.latitude && uplinkOlt.longitude) {
                        var colorKey = (odc.color || '').toUpperCase();
                        var lineColor = colorMap[colorKey] || odc.color || '#6f42c1';
                        
                        var key = getConnectionKey('olt', uplinkOlt.id, 'odc', odc.id);
                        var pathPoints = [[uplinkOlt.latitude, uplinkOlt.longitude]];
                        (waypoints[key] || []).forEach(function(p){ pathPoints.push([p.lat, p.lng]); });
                        pathPoints.push([odc.latitude, odc.longitude]);

                        var poly = L.polyline(pathPoints, {
                            color: lineColor,
                            weight: 4,
                            opacity: 0.7,
                            dashArray: '10, 5'
                        }).addTo(lines);

                        if (editMode) {
                            poly.on('click', function(e) {
                                addWaypointMarker(key, e.latlng);
                                drawLines(); // This will save implicitly inside addWaypointMarker logic? No, addWaypoint calls save.
                            });
                        }
                    }
                }
            });

            // ODC -> ODP
            odps.forEach(function(odp) {
                if (isVisible(odp, 'odp') && odp.latitude && odp.longitude) {
                    var uplinkOdc = odcs.find(o => o.id == odp.odc_id);
                    if (uplinkOdc && uplinkOdc.latitude && uplinkOdc.longitude) {
                        var colorKey = (odp.color || '').toUpperCase();
                        var lineColor = colorMap[colorKey] || odp.color || '#fd7e14';
                        
                        var key = getConnectionKey('odc', uplinkOdc.id, 'odp', odp.id);
                        var pathPoints = [[uplinkOdc.latitude, uplinkOdc.longitude]];
                        (waypoints[key] || []).forEach(function(p){ pathPoints.push([p.lat, p.lng]); });
                        pathPoints.push([odp.latitude, odp.longitude]);

                        var poly = L.polyline(pathPoints, {
                            color: lineColor,
                            weight: 3,
                            opacity: 0.8
                        }).addTo(lines);

                        if (editMode) {
                            poly.on('click', function(e) {
                                addWaypointMarker(key, e.latlng);
                                drawLines();
                            });
                        }
                    }
                }
            });

            // HTB Connections
            htbs.forEach(function(htb) {
                if (isVisible(htb, 'htb') && htb.latitude && htb.longitude) {
                    // Connect to ODP
                    if (htb.odp_id) {
                        var uplinkOdp = odps.find(o => o.id == htb.odp_id);
                        if (uplinkOdp && uplinkOdp.latitude && uplinkOdp.longitude) {
                            var key = getConnectionKey('odp', uplinkOdp.id, 'htb', htb.id);
                            var pathPoints = [[uplinkOdp.latitude, uplinkOdp.longitude]];
                            (waypoints[key] || []).forEach(function(p){ pathPoints.push([p.lat, p.lng]); });
                            pathPoints.push([htb.latitude, htb.longitude]);

                            var poly = L.polyline(pathPoints, {
                                color: '#6610f2',
                                weight: 3,
                                opacity: 0.8,
                                dashArray: '5, 5'
                            }).addTo(lines);

                            if (editMode) {
                                poly.on('click', function(e) {
                                    addWaypointMarker(key, e.latlng);
                                    drawLines();
                                });
                            }
                        }
                    } 
                    // Connect to Parent HTB
                    else if (htb.parent_htb_id) {
                        var parentHtb = htbs.find(h => h.id == htb.parent_htb_id);
                        if (parentHtb && parentHtb.latitude && parentHtb.longitude) {
                            var key = getConnectionKey('htb', parentHtb.id, 'htb', htb.id);
                            var pathPoints = [[parentHtb.latitude, parentHtb.longitude]];
                            (waypoints[key] || []).forEach(function(p){ pathPoints.push([p.lat, p.lng]); });
                            pathPoints.push([htb.latitude, htb.longitude]);

                            var poly = L.polyline(pathPoints, {
                                color: '#6610f2',
                                weight: 3,
                                opacity: 0.8,
                                dashArray: '5, 5'
                            }).addTo(lines);

                            if (editMode) {
                                poly.on('click', function(e) {
                                    addWaypointMarker(key, e.latlng);
                                    drawLines();
                                });
                            }
                        }
                    }
                }
            });

            // ODP -> Customer
            customers.forEach(function(customer) {
                if (isVisible(customer, 'customer') && customer.latitude && customer.longitude) {
                    var isOnline = customer.is_online;
                    var uplinkOdp = odps.find(o => o.id == customer.odp_id);
                    if (uplinkOdp && uplinkOdp.latitude && uplinkOdp.longitude) {
                        
                        var lineOptions = {};
                        if (isOnline) {
                            lineOptions = {
                                color: '#00f2fff3', // Cyan Neon
                                weight: 4,
                                opacity: 1.0,
                                className: 'connection-online'
                            };
                        } else {
                            lineOptions = {
                                color: '#dc3545', // Red
                                weight: 3,
                                opacity: 0.6,
                                dashArray: '5, 10' // Red Dashed
                            };
                        }

                        var key = getConnectionKey('odp', uplinkOdp.id, 'cust', customer.id);
                        var pathPoints = [[uplinkOdp.latitude, uplinkOdp.longitude]];
                        (waypoints[key] || []).forEach(function(p){ pathPoints.push([p.lat, p.lng]); });
                        pathPoints.push([customer.latitude, customer.longitude]);
                        
                        var poly;
                        if (isOnline && L.polyline && L.polyline.antPath) {
                            poly = L.polyline.antPath(pathPoints, {
                                color: onlineColor,
                                weight: 4,
                                opacity: 1.0,
                                dashArray: [2, 12],
                                delay: 320,
                                pulseColor: onlinePulseColor,
                                paused: false,
                                reverse: false
                            }).addTo(lines);
                        } else {
                            poly = L.polyline(pathPoints, lineOptions).addTo(lines);
                        }
                        if (editMode) {
                            poly.on('click', function(e) {
                                addWaypointMarker(key, e.latlng);
                                drawLines();
                            });
                        }
                        
                        // Moving Arrow Icon along the online connection
                        if (isOnline) {
                            var points = pathPoints.map(function(pt){ return L.latLng(pt[0], pt[1]); });
                            var arrowIcon = L.divIcon({
                                html: "<div class='laser-glow'></div>",
                                className: 'bg-transparent border-0',
                                iconSize: [12, 12],
                                iconAnchor: [6, 6]
                            });
                            var arrowMarker = L.marker(points[0], { icon: arrowIcon, interactive: false }).addTo(lines);
                            var t = 0;
                            var stepMs = 30;
                            var durationMs = 3000;
                            var delta = stepMs / durationMs;
                            var intervalId = setInterval(function() {
                                t += delta;
                                if (t >= 1) t = 0;
                                var totalSegments = points.length - 1;
                                var segPos = t * totalSegments;
                                var segIndex = Math.min(totalSegments - 1, Math.floor(segPos));
                                var localT = segPos - segIndex;
                                var A = points[segIndex];
                                var B = points[segIndex + 1];
                                var lat = A.lat + (B.lat - A.lat) * localT;
                                var lng = A.lng + (B.lng - A.lng) * localT;
                                arrowMarker.setLatLng([lat, lng]);
                            }, stepMs);
                            window.arrowIntervals.push(intervalId);
                        }
                    }
                }
            });
            
            if (!skipClearEdit) {
                restoreEditMarkers();
            }
        }
        
        // Initial load
        loadConnections();

        fetch('{{ route("api.network.online-paths") }}')
            .then(function(r){ return r.json(); })
            .then(function(data){
                if (!data || !data.paths) return;
                data.paths.forEach(function(p){
                    if (L.polyline && L.polyline.antPath) {
                        L.polyline.antPath(p.path, {
                            color: onlineColor,
                            weight: 4,
                            opacity: 1.0,
                            dashArray: [2, 12],
                            delay: 320,
                            pulseColor: onlinePulseColor,
                            paused: false
                        }).addTo(onlineOverlay);
                    } else {
                        L.polyline(p.path, {
                            color: onlineColor,
                            weight: 4,
                            opacity: 0.95,
                            dashArray: '2, 12',
                            lineCap: 'round',
                            lineJoin: 'round',
                            className: 'connection-online'
                        }).addTo(onlineOverlay);
                    }
                });
            })
            .catch(function(e){ console.error('online-paths', e); });

        function deleteLocation(type, id, marker) {
            if (!confirm('Apakah Anda yakin ingin menghapus titik koordinat ini?')) {
                return;
            }

            var url = `/map/location/${type}/${id}`;
            var data = { latitude: null, longitude: null, _method: 'PUT' };

            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => {
                if (!response.ok) {
                     return response.text().then(text => {
                         let msg = 'Server Error ' + response.status;
                         try {
                             const json = JSON.parse(text);
                             msg = json.message || msg;
                         } catch (e) {
                             msg += ': ' + text.substring(0, 100);
                         }
                         throw new Error(msg);
                     });
                }
                return response.json();
            })
            .then(result => {
                if (result.success || result.id) {
                    if (type === 'olt') {
                        var item = olts.find(i => i.id == id);
                        if (item) { item.latitude = null; item.longitude = null; }
                    } else if (type === 'odc') {
                        var item = odcs.find(i => i.id == id);
                        if (item) { item.latitude = null; item.longitude = null; }
                    } else if (type === 'odp') {
                        var item = odps.find(i => i.id == id);
                        if (item) { item.latitude = null; item.longitude = null; }
                    } else if (type === 'htb') {
                        var item = htbs.find(i => i.id == id);
                        if (item) { item.latitude = null; item.longitude = null; }
                    } else if (type === 'closure') {
                        var item = closures.find(i => i.id == id);
                        if (item) { item.latitude = null; item.longitude = null; }
                    } else if (type === 'asset') {
                        var item = assets.find(i => i.id == id);
                        if (item) { item.latitude = null; item.longitude = null; }
                    }
                    
                    map.removeLayer(marker);
                    drawLines();
                    alert('Lokasi berhasil dihapus!');
                } else {
                    var msg = result.message || JSON.stringify(result);
                    if (result.errors) {
                        msg += '\n' + JSON.stringify(result.errors);
                    }
                    alert('Gagal menghapus lokasi: ' + msg);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error: ' + error.message);
            });
        }

        // Update Location
        function updateLocation(type, id, lat, lng, oldLat, oldLng, marker) {
            if (!confirm('{{ __('Perbarui lokasi ke koordinat baru?') }}')) {
                marker.setLatLng([oldLat, oldLng]);
                drawLines(); // Revert lines if needed
                return;
            }

            var url = `/map/location/${type}/${id}`;
            var data = {
                latitude: lat,
                longitude: lng,
                _method: 'PUT'
            };

            fetch(url, {
                method: 'POST', // POST with _method=PUT
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => {
                        let msg = 'Server Error ' + response.status;
                        try {
                            const json = JSON.parse(text);
                            msg = json.message || msg;
                        } catch (e) {
                            msg += ': ' + text.substring(0, 100);
                        }
                        throw new Error(msg);
                    });
                }
                return response.json();
            })
            .then(result => {
                if (result.success || result.id) {
                    // Update internal array
                    if (type === 'olt') {
                        var item = olts.find(i => i.id == id);
                        if (item) { item.latitude = lat; item.longitude = lng; }
                    } else if (type === 'odc') {
                        var item = odcs.find(i => i.id == id);
                        if (item) { item.latitude = lat; item.longitude = lng; }
                    } else if (type === 'odp') {
                        var item = odps.find(i => i.id == id);
                        if (item) { item.latitude = lat; item.longitude = lng; }
                    } else if (type === 'htb') {
                        var item = htbs.find(i => i.id == id);
                        if (item) { item.latitude = lat; item.longitude = lng; }
                    } else if (type === 'closure') {
                        var item = closures.find(i => i.id == id);
                        if (item) { item.latitude = lat; item.longitude = lng; }
                    } else if (type === 'customer') {
                        var item = customers.find(i => i.id == id);
                        if (item) { item.latitude = lat; item.longitude = lng; }
                    } else if (type === 'asset') {
                        var item = assets.find(i => i.id == id);
                        if (item) { item.latitude = lat; item.longitude = lng; }
                    }
                    drawLines(); // Redraw lines with new position
                    alert('{{ __('Lokasi berhasil diperbarui!') }}');
                } else {
                    var msg = result.message || JSON.stringify(result);
                    if (result.errors) {
                        msg += '\n' + JSON.stringify(result.errors);
                    }
                    alert('{{ __('Gagal memperbarui lokasi:') }} ' + msg);
                    marker.setLatLng([oldLat, oldLng]);
                    drawLines();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('{{ __('Gagal memperbarui lokasi:') }} ' + error.message);
                marker.setLatLng([oldLat, oldLng]);
                drawLines();
            });
        }

        // Icons
        function createIcon(type) {
            let iconClass = 'fa-user';
            let colorClass = 'icon-customer-offline';
            let size = 26;

            if (type === 'olt') { iconClass = 'fa-server'; colorClass = 'icon-olt'; size = 40; }
            else if (type === 'odc') { iconClass = 'fa-hdd'; colorClass = 'icon-odc'; size = 36; }
            else if (type === 'odp') { iconClass = 'fa-box'; colorClass = 'icon-odp'; size = 32; }
            else if (type === 'htb') { iconClass = 'fa-sitemap'; colorClass = 'icon-htb'; size = 30; }
            else if (type === 'closure') { iconClass = 'fa-archive'; colorClass = 'icon-closure'; size = 30; }
            else if (type === 'asset') { iconClass = 'fa-tools'; colorClass = 'icon-asset'; size = 28; }
            else if (type === 'modem') { iconClass = 'fa-wifi'; colorClass = 'icon-modem'; size = 30; }
            else if (type === 'online') { iconClass = 'fa-wifi'; colorClass = 'icon-customer-online'; size = 26; }
            else { iconClass = 'fa-user-slash'; colorClass = 'icon-customer-offline'; size = 26; }

            const onlineClass = type === 'online' ? 'neon-online' : '';
            return L.divIcon({
                html: `<i class="fa ${iconClass}" style="font-size: ${size/1.5}px;"></i>`,
                className: `custom-icon ${colorClass} ${onlineClass}`,
                iconSize: [size, size],
                iconAnchor: [size/2, size/2],
                popupAnchor: [0, -size/2]
            });
        }

        // Draw OLTs
        olts.forEach(function(olt) {
            if (olt.latitude && olt.longitude) {
                var popupContent = document.createElement('div');
                popupContent.innerHTML = `
                    <div class="map-popup">
                        <h6 class="map-popup-title">OLT: ${olt.name}</h6>
                        <table class="table table-sm table-borderless map-popup-table">
                            <tr><td class="map-popup-label">Host:</td><td class="map-popup-value">${olt.host || '-'}</td></tr>
                        </table>
                    </div>`;
                
                var editLink = document.createElement('a');
                editLink.href = `/olt/${olt.id}/edit`;
                editLink.className = 'btn btn-sm btn-outline-primary map-popup-btn';
                editLink.innerText = '{{ __('Edit OLT') }}';

                var deleteBtn = document.createElement('button');
                deleteBtn.className = 'btn btn-sm btn-danger map-popup-btn';
                deleteBtn.innerText = 'Hapus Lokasi';
                deleteBtn.onclick = function() { deleteLocation('olt', olt.id, marker); };

                var actionRow = document.createElement('div');
                actionRow.className = 'map-popup-actions';
                actionRow.appendChild(editLink);
                actionRow.appendChild(deleteBtn);
                popupContent.querySelector('.map-popup').appendChild(actionRow);

                var marker = L.marker([olt.latitude, olt.longitude], {
                    icon: createIcon('olt'),
                    draggable: !!canManageMap
                }).bindPopup(popupContent).addTo(markers);

                allMarkerObjs.push({ marker: marker, type: 'olt', data: olt });

                var oldLat = olt.latitude;
                var oldLng = olt.longitude;

                marker.on('dragstart', function(e) {
                    oldLat = e.target.getLatLng().lat;
                    oldLng = e.target.getLatLng().lng;
                });

                marker.on('dragend', function(e) {
                    var newLat = e.target.getLatLng().lat;
                    var newLng = e.target.getLatLng().lng;
                    updateLocation('olt', olt.id, newLat, newLng, oldLat, oldLng, marker);
                });
            }
        });

        // Draw ODCs
        odcs.forEach(function(odc) {
            if (odc.latitude && odc.longitude) {
                var oltName = 'N/A';
                var odcToServerDistance = '-';
                if (odc.olt_id) {
                    var olt = olts.find(o => o.id == odc.olt_id);
                    if (olt) {
                        oltName = olt.name;
                        var odcLineKey = getConnectionKey('olt', olt.id, 'odc', odc.id);
                        odcToServerDistance = formatDistance(getLineDistanceMeters(
                            olt.latitude,
                            olt.longitude,
                            odc.latitude,
                            odc.longitude,
                            odcLineKey
                        ));
                    }
                }

                var popupContent = document.createElement('div');
                popupContent.innerHTML = `
                    <div class="map-popup">
                        <h6 class="map-popup-title">ODC: ${odc.name}</h6>
                        <table class="table table-sm table-borderless map-popup-table">
                            <tr><td class="map-popup-label">Kapasitas:</td><td class="map-popup-value">${odc.capacity}</td></tr>
                            <tr><td class="map-popup-label">OLT:</td><td class="map-popup-value">${oltName}</td></tr>
                            <tr><td class="map-popup-label">Port PON:</td><td class="map-popup-value">${odc.pon_port || '-'}</td></tr>
                            <tr><td class="map-popup-label">Area:</td><td class="map-popup-value">${odc.area || '-'}</td></tr>
                            <tr><td class="map-popup-label">Warna:</td><td class="map-popup-value">${odc.color || '-'}</td></tr>
                            <tr><td class="map-popup-label">No Kabel:</td><td class="map-popup-value">${odc.cable_no || '-'}</td></tr>
                            <tr><td class="map-popup-label">Jarak ke Server:</td><td class="map-popup-value">${odcToServerDistance}</td></tr>
                        </table>
                        <div class="map-popup-desc">${odc.description || ''}</div>
                    </div>`;
                
                var editBtn = document.createElement('button');
                editBtn.className = 'btn btn-sm btn-outline-primary map-popup-btn';
                editBtn.innerText = '{{ __('Edit ODC') }}';
                editBtn.onclick = function() { editOdc(odc.id); };

                var deleteBtn = document.createElement('button');
                deleteBtn.className = 'btn btn-sm btn-danger map-popup-btn';
                deleteBtn.innerText = 'Hapus Lokasi';
                deleteBtn.onclick = function() { deleteLocation('odc', odc.id, marker); };

                var actionRow = document.createElement('div');
                actionRow.className = 'map-popup-actions';
                actionRow.appendChild(editBtn);
                actionRow.appendChild(deleteBtn);
                popupContent.querySelector('.map-popup').appendChild(actionRow);

                var marker = L.marker([odc.latitude, odc.longitude], {
                    icon: createIcon('odc'),
                    draggable: !!canManageMap
                }).bindPopup(popupContent).addTo(markers);

                allMarkerObjs.push({ marker: marker, type: 'odc', data: odc });

                var oldLat = odc.latitude;
                var oldLng = odc.longitude;

                marker.on('dragstart', function(e) {
                    oldLat = e.target.getLatLng().lat;
                    oldLng = e.target.getLatLng().lng;
                });

                marker.on('dragend', function(e) {
                    var newLat = e.target.getLatLng().lat;
                    var newLng = e.target.getLatLng().lng;
                    updateLocation('odc', odc.id, newLat, newLng, oldLat, oldLng, marker);
                });
            }
        });

        // Draw ODPs
        odps.forEach(function(odp) {
            if (odp.latitude && odp.longitude) {
                var odcName = 'N/A';
                var odpColorLabel = formatFiberColorLabel(odp.color);
                var odpToOdcDistance = '-';
                if (odp.odc_id) {
                    var odc = odcs.find(o => o.id == odp.odc_id);
                    if (odc) {
                        odcName = odc.name;
                        var odpLineKey = getConnectionKey('odc', odc.id, 'odp', odp.id);
                        odpToOdcDistance = formatDistance(getLineDistanceMeters(
                            odp.latitude,
                            odp.longitude,
                            odc.latitude,
                            odc.longitude,
                            odpLineKey
                        ));
                    }
                }

                var popupContent = document.createElement('div');
                popupContent.innerHTML = `
                    <div class="map-popup">
                        <h6 class="map-popup-title">ODP: ${odp.name}</h6>
                        <table class="table table-sm table-borderless map-popup-table">
                            <tr><td class="map-popup-label">Kapasitas:</td><td class="map-popup-value">${odp.filled || 0}/${odp.capacity}</td></tr>
                            <tr><td class="map-popup-label">ODC:</td><td class="map-popup-value">${odcName}</td></tr>
                            <tr><td class="map-popup-label">Area:</td><td class="map-popup-value">${odp.kampung || '-'}</td></tr>
                            <tr><td class="map-popup-label">Warna:</td><td class="map-popup-value">${odpColorLabel}</td></tr>
                            <tr><td class="map-popup-label">Jarak ODP-ODC:</td><td class="map-popup-value">${odpToOdcDistance}</td></tr>
                        </table>
                        <div class="map-popup-desc">${odp.description || ''}</div>
                    </div>`;
                
                var editBtn = document.createElement('button');
                editBtn.className = 'btn btn-sm btn-outline-primary map-popup-btn';
                editBtn.innerText = '{{ __('Edit ODP') }}';
                editBtn.onclick = function() { editOdp(odp.id); };

                var deleteBtn = document.createElement('button');
                deleteBtn.className = 'btn btn-sm btn-danger map-popup-btn';
                deleteBtn.innerText = 'Hapus Lokasi';
                deleteBtn.onclick = function() { deleteLocation('odp', odp.id, marker); };

                var actionRow = document.createElement('div');
                actionRow.className = 'map-popup-actions';
                actionRow.appendChild(editBtn);
                actionRow.appendChild(deleteBtn);
                popupContent.querySelector('.map-popup').appendChild(actionRow);

                var marker = L.marker([odp.latitude, odp.longitude], {
                    icon: createIcon('odp'),
                    draggable: !!canManageMap
                }).bindPopup(popupContent).addTo(markers);

                allMarkerObjs.push({ marker: marker, type: 'odp', data: odp });

                var oldLat = odp.latitude;
                var oldLng = odp.longitude;

                marker.on('dragstart', function(e) {
                    oldLat = e.target.getLatLng().lat;
                    oldLng = e.target.getLatLng().lng;
                });

                marker.on('dragend', function(e) {
                    var newLat = e.target.getLatLng().lat;
                    var newLng = e.target.getLatLng().lng;
                    updateLocation('odp', odp.id, newLat, newLng, oldLat, oldLng, marker);
                });
            }
        });

        // Draw HTBs
        htbs.forEach(function(htb) {
            if (htb.latitude && htb.longitude) {
                var uplinkName = 'N/A';
                if (htb.odp_id) {
                    var odp = odps.find(o => o.id == htb.odp_id);
                    if (odp) uplinkName = 'ODP: ' + odp.name;
                } else if (htb.parent_htb_id) {
                    var parent = htbs.find(h => h.id == htb.parent_htb_id);
                    if (parent) uplinkName = 'HTB: ' + parent.name;
                }

                var popupContent = document.createElement('div');
                popupContent.innerHTML = `
                    <div class="map-popup">
                        <h6 class="map-popup-title">HTB: ${htb.name}</h6>
                        <table class="table table-sm table-borderless map-popup-table">
                            <tr><td class="map-popup-label">Uplink:</td><td class="map-popup-value">${uplinkName}</td></tr>
                            <tr><td class="map-popup-label">Area:</td><td class="map-popup-value">${htb.odp && htb.odp.kampung ? htb.odp.kampung : '-'}</td></tr>
                        </table>
                        <div class="map-popup-desc">${htb.description || ''}</div>
                    </div>`;
                
                var editBtn = document.createElement('button');
                editBtn.className = 'btn btn-sm btn-outline-primary map-popup-btn';
                editBtn.innerText = '{{ __('Edit HTB') }}';
                editBtn.onclick = function() { editHtb(htb.id); };

                var deleteBtn = document.createElement('button');
                deleteBtn.className = 'btn btn-sm btn-danger map-popup-btn';
                deleteBtn.innerText = 'Hapus Lokasi';
                deleteBtn.onclick = function() { deleteLocation('htb', htb.id, marker); };

                var actionRow = document.createElement('div');
                actionRow.className = 'map-popup-actions';
                actionRow.appendChild(editBtn);
                actionRow.appendChild(deleteBtn);
                popupContent.querySelector('.map-popup').appendChild(actionRow);

                var marker = L.marker([htb.latitude, htb.longitude], {
                    icon: createIcon('htb'),
                    draggable: !!canManageMap
                }).bindPopup(popupContent).addTo(markers);

                allMarkerObjs.push({ marker: marker, type: 'htb', data: htb });

                var oldLat = htb.latitude;
                var oldLng = htb.longitude;

                marker.on('dragstart', function(e) {
                    oldLat = e.target.getLatLng().lat;
                    oldLng = e.target.getLatLng().lng;
                });

                marker.on('dragend', function(e) {
                    var newLat = e.target.getLatLng().lat;
                    var newLng = e.target.getLatLng().lng;
                    updateLocation('htb', htb.id, newLat, newLng, oldLat, oldLng, marker);
                });
            }
        });

        // Draw Closures
        closures.forEach(function(closure) {
            if (closure.latitude && closure.longitude) {
                var odcName = 'N/A';
                if (closure.odc_id) {
                    var odc = odcs.find(o => o.id == closure.odc_id);
                    if (odc) odcName = odc.name;
                }

                var popupContent = document.createElement('div');
                popupContent.innerHTML = `
                    <div class="map-popup">
                        <h6 class="map-popup-title">Closure: ${closure.name}</h6>
                        <table class="table table-sm table-borderless map-popup-table">
                            <tr><td class="map-popup-label">Kapasitas:</td><td class="map-popup-value">${closure.filled || 0}/${closure.capacity}</td></tr>
                            <tr><td class="map-popup-label">Uplink ODC:</td><td class="map-popup-value">${odcName}</td></tr>
                            <tr><td class="map-popup-label">Wilayah:</td><td class="map-popup-value">${closure.region ? closure.region.name : '-'}</td></tr>
                        </table>
                        <div class="map-popup-desc">${closure.description || ''}</div>
                    </div>`;
                
                var editBtn = document.createElement('button');
                editBtn.className = 'btn btn-sm btn-outline-primary map-popup-btn';
                editBtn.innerText = '{{ __('Edit Closure') }}';
                editBtn.onclick = function() { editClosure(closure.id); };

                var deleteBtn = document.createElement('button');
                deleteBtn.className = 'btn btn-sm btn-danger map-popup-btn';
                deleteBtn.innerText = 'Hapus Lokasi';
                deleteBtn.onclick = function() { deleteLocation('closure', closure.id, marker); };

                var actionRow = document.createElement('div');
                actionRow.className = 'map-popup-actions';
                actionRow.appendChild(editBtn);
                actionRow.appendChild(deleteBtn);
                popupContent.querySelector('.map-popup').appendChild(actionRow);

                var marker = L.marker([closure.latitude, closure.longitude], {
                    icon: createIcon('closure'),
                    draggable: !!canManageMap
                }).bindPopup(popupContent).addTo(markers);

                allMarkerObjs.push({ marker: marker, type: 'closure', data: closure });

                var oldLat = closure.latitude;
                var oldLng = closure.longitude;

                marker.on('dragstart', function(e) {
                    oldLat = e.target.getLatLng().lat;
                    oldLng = e.target.getLatLng().lng;
                });

                marker.on('dragend', function(e) {
                    var newLat = e.target.getLatLng().lat;
                    var newLng = e.target.getLatLng().lng;
                    updateLocation('closure', closure.id, newLat, newLng, oldLat, oldLng, marker);
                });
            }
        });

        // Draw Assets
        assets.forEach(function(asset) {
            if (asset.latitude && asset.longitude) {
                var itemName = asset.item ? asset.item.name : 'Unknown Item';
                var holderName = asset.holder ? asset.holder.name : 'Unknown Holder';
                var status = asset.status || 'N/A';
                
                var popupContent = document.createElement('div');
                popupContent.innerHTML = `
                    <div class="map-popup">
                        <h6 class="map-popup-title">Aset: ${itemName}</h6>
                        <table class="table table-sm table-borderless map-popup-table">
                            <tr><td class="map-popup-label">Pemegang:</td><td class="map-popup-value">${holderName}</td></tr>
                            <tr><td class="map-popup-label">Status:</td><td class="map-popup-value">${status}</td></tr>
                            <tr><td class="map-popup-label">Kondisi:</td><td class="map-popup-value">${asset.condition || '-'}</td></tr>
                            <tr><td class="map-popup-label">Serial:</td><td class="map-popup-value font-monospace">${asset.serial_number || '-'}</td></tr>
                        </table>
                        <div class="map-popup-desc">${asset.description || ''}</div>
                    </div>`;
                
                var editLink = document.createElement('a');
                editLink.href = `/inventory/assets/${asset.id}/edit`; // Assumed route
                editLink.className = 'btn btn-sm btn-outline-primary map-popup-btn';
                editLink.innerText = '{{ __('Edit Aset') }}';

                var deleteBtn = document.createElement('button');
                deleteBtn.className = 'btn btn-sm btn-danger map-popup-btn';
                deleteBtn.innerText = 'Hapus Lokasi';
                deleteBtn.onclick = function() { deleteLocation('asset', asset.id, marker); };

                var actionRow = document.createElement('div');
                actionRow.className = 'map-popup-actions';
                actionRow.appendChild(editLink);
                actionRow.appendChild(deleteBtn);
                popupContent.querySelector('.map-popup').appendChild(actionRow);

                var marker = L.marker([asset.latitude, asset.longitude], {
                    icon: createIcon('asset'),
                    draggable: !!canManageMap
                }).bindPopup(popupContent).addTo(markers);

                allMarkerObjs.push({ marker: marker, type: 'asset', data: asset });

                var oldLat = asset.latitude;
                var oldLng = asset.longitude;

                marker.on('dragstart', function(e) {
                    oldLat = e.target.getLatLng().lat;
                    oldLng = e.target.getLatLng().lng;
                });

                marker.on('dragend', function(e) {
                    var newLat = e.target.getLatLng().lat;
                    var newLng = e.target.getLatLng().lng;
                    updateLocation('asset', asset.id, newLat, newLng, oldLat, oldLng, marker);
                });
            }
        });

        // Draw Customers
        customers.forEach(function(customer) {
            var isOnline = customer.is_online; // Assumed passed from controller
            var iconType = isOnline ? 'online' : 'offline';
            var tr069Ip = customer.tr069_ip || '-';
            var ssidName = customer.ssid_name || 'N/A';
            var lastInform = customer.last_inform || null;
            var lastReason = customer.last_reason || '-';
            var hasGenieStatus = !!customer.has_genie_status;
            var customerToOdpDistance = '-';

            function formatLastInform(value) {
                if (!value) return 'N/A';
                const date = new Date(value);
                if (Number.isNaN(date.getTime())) return 'N/A';
                const diffMs = Date.now() - date.getTime();
                if (diffMs < 0) return date.toLocaleString('id-ID');
                const totalMinutes = Math.floor(diffMs / 60000);
                if (totalMinutes < 1) return 'baru saja';
                if (totalMinutes < 60) return `${totalMinutes} menit lalu`;
                const hours = Math.floor(totalMinutes / 60);
                if (hours < 24) return `${hours} jam lalu`;
                const days = Math.floor(hours / 24);
                return `${days} hari lalu`;
            }

            // Find ODP name
            var odpName = 'N/A';
            var matchedOdp = null;
            if (customer.odp_id) {
                matchedOdp = odps.find(o => o.id == customer.odp_id) || null;
            } else if (customer.odp && typeof customer.odp === 'object' && customer.odp.id) {
                matchedOdp = odps.find(o => o.id == customer.odp.id) || null;
            }
            if (matchedOdp) {
                odpName = matchedOdp.name;
                var customerLineKey = getConnectionKey('odp', matchedOdp.id, 'cust', customer.id);
                customerToOdpDistance = formatDistance(getLineDistanceMeters(
                    matchedOdp.latitude,
                    matchedOdp.longitude,
                    customer.latitude,
                    customer.longitude,
                    customerLineKey
                ));
            } else if (customer.odp) {
                odpName = customer.odp.name || customer.odp;
            }
            
            var marker = L.marker([customer.latitude, customer.longitude], {
                icon: createIcon(iconType),
                draggable: !!canManageMap || !!canEditCustomer
            })
            .addTo(markers)
            .bindPopup(function() {
                const popupDiv = document.createElement('div');
                popupDiv.className = 'custom-customer-popup';
                popupDiv.innerHTML = `
                    <div class="customer-card flex flex-col">
                        <div class="customer-header">
                            <div class="customer-header-left">
                                <span class="status-dot ${isOnline ? 'status-online' : 'status-offline'}" id="popup-status-dot-${customer.id}">✕</span>
                                <span class="customer-header-title">${customer.id}-${customer.name}</span>
                            </div>
                            <span class="customer-badge">ONU</span>
                        </div>

                        <div class="customer-content">
                            <div class="top-row">
                                <div class="ip-box">
                                    <i data-lucide="server" class="w-4 h-4 text-slate-400"></i>
                                    <span class="ip-text" id="popup-ip-text-${customer.id}">${tr069Ip}</span>
                                    <i data-lucide="globe" class="w-4 h-4 text-blue-400"></i>
                                </div>
                                <button onclick="window.pingCustomer(document.getElementById('popup-ip-text-${customer.id}').innerText, ${customer.id})" class="popup-btn btn-cyan" style="width:auto;min-width:78px">
                                    <i data-lucide="terminal" class="w-4 h-4" id="ping-icon-${customer.id}"></i><span id="ping-text-${customer.id}">Ping</span>
                                </button>
                            </div>

                            <div id="ping-result-${customer.id}" class="card-panel p-2 text-xs font-mono hidden bg-black/50 overflow-auto max-h-32 mb-2"></div>

                            <div class="status-row">
                                <span class="status-label">Status</span>
                                <span class="status-value ${isOnline ? 'status-online' : 'status-offline'}" id="popup-status-text-${customer.id}">${isOnline ? 'ONLINE' : 'OFFLINE'}</span>
                            </div>

                            <div class="card-panel p-2">
                                <div class="acs-head">
                                    <div class="acs-title">
                                        <i data-lucide="zap" class="w-4 h-4"></i><span class="font-bold">GenieACS</span>
                                    </div>
                                    <span class="acs-up" id="popup-last-inform-${customer.id}">${hasGenieStatus ? formatLastInform(lastInform) : 'N/A'}</span>
                                </div>
                                <div class="grid grid-cols-1 gap-2">
                                    <div class="acs-item">
                                        <div class="acs-label">TR069 IP</div>
                                        <div class="acs-value acs-value-start" id="popup-acs-ip-${customer.id}">${tr069Ip}</div>
                                    </div>
                                    <div class="acs-item">
                                        <div class="acs-label">Last Reason</div>
                                        <div class="acs-value ${isOnline ? 'acs-value-now-online' : 'acs-value-now-offline'}" id="popup-last-reason-${customer.id}">${lastReason}</div>
                                    </div>
                                    ${(customer.rx_power || customer.rdm_power) ? `
                                        <div class="flex gap-2 mt-1">
                                            ${customer.rx_power ? `
                                                <div class="flex-1 bg-slate-800/60 rounded-lg p-2 border border-slate-700/50">
                                                    <div class="acs-label text-xs mb-1">RX Power</div>
                                                    <div class="acs-value font-bold text-emerald-400 text-sm">${customer.rx_power}</div>
                                                </div>
                                            ` : ''}
                                            ${customer.rdm_power ? `
                                                <div class="flex-1 bg-slate-800/60 rounded-lg p-2 border border-slate-700/50">
                                                    <div class="acs-label text-xs mb-1">RDM Power</div>
                                                    <div class="acs-value font-bold text-yellow-400 text-sm">${customer.rdm_power}</div>
                                                </div>
                                            ` : ''}
                                        </div>
                                    ` : ''}
                                </div>
                            </div>

                            <div class="card-panel p-2 space-y-1.5">
                                <div class="kv-row"><span class="status-label">SSID</span><span class="text-yellow-400 font-bold" id="popup-ssid-${customer.id}">${ssidName}</span></div>
                                <div class="kv-row">
                                    <span class="status-label">Password</span>
                                    <div class="flex items-center justify-between bg-slate-800/60 px-2.5 py-1.5 rounded-lg border border-slate-700/50">
                                        <span class="text-yellow-400 font-bold font-mono text-sm" id="pass-text-${customer.id}" data-password="${customer.ssid_password || ''}">••••••••</span>
                                        <button onclick="window.togglePassword(${customer.id}, this)" data-visible="0" title="Lihat Password" class="p-1.5 text-slate-400 hover:text-white hover:bg-slate-700 rounded-md transition-all flex items-center justify-center">
                                            <span id="pass-icon-${customer.id}">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                            </span>
                                        </button>
                                    </div>
                                </div>
                                <div class="kv-row"><span class="status-label">Client WLAN Aktif</span><span class="text-green-400 font-bold" id="wlan-clients-${customer.id}">...</span></div>
                            </div>

                            <div class="btn-grid-2">
                                <button onclick="window.openWifiModal(${customer.id})" class="popup-btn btn-emerald">
                                    <i data-lucide="wifi" class="w-4 h-4"></i><span>Ganti WiFi</span>
                                </button>
                                <button onclick="window.rebootCustomer(${customer.id})" class="popup-btn btn-red"><i data-lucide="power" class="w-4 h-4"></i><span>Reboot</span></button>
                            </div>

                            <div class="card-panel p-2">
                                <div class="traffic-head">
                                    <div class="traffic-title"><i data-lucide="activity" class="w-4 h-4 text-slate-300"></i><span>Live Traffic</span></div>
                                    <div class="traffic-legend">
                                        <span><span class="traffic-dot traffic-dot-tx"></span>TX</span>
                                        <span><span class="traffic-dot traffic-dot-rx"></span>RX</span>
                                    </div>
                                </div>
                                <div class="traffic-chart">
                                    <svg class="absolute inset-0 w-full h-full opacity-60">
                                        <line x1="0" y1="32" x2="300" y2="32" stroke="#1e293b" stroke-width="1" />
                                        <path d="M 0 36 Q 45 30, 90 40 T 180 28 T 300 36" fill="none" stroke="#3b82f6" stroke-width="1.6" />
                                        <path d="M 0 44 Q 60 36, 120 48 T 240 34 T 300 42" fill="none" stroke="#22c55e" stroke-width="1.6" />
                                    </svg>
                                </div>
                                <div class="traffic-foot"><span>TX: 0 bps</span><span>RX: 0 bps</span></div>
                            </div>

                            <div class="distance-row">${customerToOdpDistance}</div>

                            <div class="btn-grid-3">
                                <button onclick="window.copyToClipboard('${customer.name}\\n${tr069Ip}')" class="popup-btn btn-blue icon-top"><i data-lucide="copy" class="w-4 h-4"></i><span>Salin</span></button>
                                <button onclick="window.openInMaps(${customer.latitude}, ${customer.longitude})" class="popup-btn btn-emerald icon-top"><i data-lucide="map-pin" class="w-4 h-4"></i><span>Maps</span></button>
                                <button onclick="window.openWA('${customer.phone}')" class="popup-btn btn-green icon-top"><i data-lucide="message-circle" class="w-4 h-4"></i><span>WA</span></button>
                            </div>

                            <div class="btn-grid-2">
                                <button onclick="location.href='/customers/${customer.id}/edit'" class="popup-btn btn-blue main-action">EDIT</button>
                                <button onclick="window.duplicateCustomer(${customer.id})" class="popup-btn btn-blue main-action"><i data-lucide="layers" class="w-4 h-4"></i><span>DUPLIKAT</span></button>
                            </div>
                        </div>
                    </div> 
                `;
                
                // Initialize Lucide icons after the popup is opened
                setTimeout(() => {
                    if (window.lucide) {
                        window.lucide.createIcons({
                            attrs: {
                                class: 'lucide-icon'
                            },
                            nameAttr: 'data-lucide'
                        });
                    }
                }, 10);
                
                return popupDiv;
            }, {
                maxWidth: 300,
                className: 'modern-map-popup'
            });


            allMarkerObjs.push({ marker: marker, type: 'customer', data: customer });

            marker.on('popupopen', function() {
                var clientSpan = document.getElementById(`wlan-clients-${customer.id}`);
                if (clientSpan && (clientSpan.innerText === '...' || clientSpan.innerText === 'N/A')) {
                    fetch(`/map/wlan-status/${customer.id}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                clientSpan.innerText = data.total_clients + ' Device';
                                
                                // Update SSID/password from live ONU data
                                if (data.ssid_name) {
                                    const ssidElement = document.getElementById(`popup-ssid-${customer.id}`);
                                    if (ssidElement) ssidElement.innerText = data.ssid_name;
                                }
                                if (typeof data.ssid_password !== 'undefined') {
                                    const passText = document.getElementById(`pass-text-${customer.id}`);
                                    if (passText) {
                                        passText.setAttribute('data-password', data.ssid_password || '');
                                        passText.innerText = '********';
                                    }
                                }

                                // Update Live Status (Bypass Stale DB)
                                const statusDot = document.getElementById(`popup-status-dot-${customer.id}`);
                                const statusText = document.getElementById(`popup-status-text-${customer.id}`);
                                const ipText = document.getElementById(`popup-ip-text-${customer.id}`);
                                const acsIp = document.getElementById(`popup-acs-ip-${customer.id}`);
                                const lastInform = document.getElementById(`popup-last-inform-${customer.id}`);
                                const lastReason = document.getElementById(`popup-last-reason-${customer.id}`);

                                if (data.is_online) {
                                    statusDot.className = 'status-dot status-online';
                                    statusText.className = 'status-value status-online';
                                    statusText.innerText = 'ONLINE';
                                    if (lastReason) {
                                        lastReason.className = 'acs-value acs-value-now-online';
                                    }
                                } else {
                                    statusDot.className = 'status-dot status-offline';
                                    statusText.className = 'status-value status-offline';
                                    statusText.innerText = 'OFFLINE';
                                    if (lastReason) {
                                        lastReason.className = 'acs-value acs-value-now-offline';
                                    }
                                }

                                if (data.tr069_ip) {
                                    ipText.innerText = data.tr069_ip;
                                    if (acsIp) acsIp.innerText = data.tr069_ip;
                                }

                                if (data.last_inform && lastInform) {
                                    lastInform.innerText = `Last Inform: ${formatLastInform(data.last_inform)}`;
                                }
                            } else {
                                clientSpan.innerText = 'Offline';
                            }
                        })
                        .catch(err => {
                            console.error('Error fetching WLAN status:', err);
                            clientSpan.innerText = 'Error';
                        });
                }
            });

            var oldLat = customer.latitude;
            var oldLng = customer.longitude;

            marker.on('dragstart', function(e) {
                oldLat = e.target.getLatLng().lat;
                oldLng = e.target.getLatLng().lng;
            });

            marker.on('dragend', function(e) {
                var newLat = e.target.getLatLng().lat;
                var newLng = e.target.getLatLng().lng;
                updateLocation('customer', customer.id, newLat, newLng, oldLat, oldLng, marker);
            });
        });

        // Draw Modem Data Records (hasil input form pendataan modem)
        modemDataRecords.forEach(function(record) {
            if (!record.latitude || !record.longitude) return;
            var linkedCustomer = record.customer_id ? customers.find(c => c.id == record.customer_id) : null;
            // If modem record is already linked to an existing customer marker,
            // rely on customer marker so icon + popup model stay consistent.
            if (linkedCustomer) return;

            var marker = L.marker([record.latitude, record.longitude], {
                icon: createIcon('offline'),
                zIndexOffset: 1200,
                draggable: false
            }).addTo(markers);

            marker.bindPopup(`
                <div class="customer-card">
                    <div class="customer-header">
                        <div class="customer-header-left">
                            <span class="status-dot status-offline">✕</span>
                            <span class="customer-header-title">Unlinked - ${record.customer_name || 'Data Modem'}</span>
                        </div>
                        <span class="customer-badge">ONU</span>
                    </div>
                    <div class="customer-content">
                        <div class="card-panel p-2.5 space-y-1.5">
                            <div class="kv-row"><span class="status-label">Type</span><span class="text-yellow-400 font-bold">${record.modem_type || '-'}</span></div>
                            <div class="kv-row"><span class="status-label">MAC</span><span class="text-yellow-400 font-bold">${record.mac_address || '-'}</span></div>
                            <div class="kv-row"><span class="status-label">SN</span><span class="text-yellow-400 font-bold">${record.serial_number || '-'}</span></div>
                        </div>
                    </div>
                </div>
            `, { className: 'modern-map-popup' });

            allMarkerObjs.push({ marker: marker, type: 'modem', data: record });
        });

        // Filter Listener
        var areaFilter = document.getElementById('areaFilter');
        if (areaFilter) {
            areaFilter.addEventListener('change', function() {
                updateMapVisibility();
            });
        }

        // Initial Map Update
        updateMapVisibility();

        // Fit bounds - Disabled to respect default center/zoom
        /*
        if (markers.getLayers().length > 0) {
            map.fitBounds(markers.getBounds().pad(0.1));
        }
        */

        // --- Add Mode Logic ---
        var addMode = null; // 'olt', 'odc', 'odp', or null
        var btnAddOlt = document.getElementById('btnAddOltMode');
        var btnAddOdc = document.getElementById('btnAddOdcMode');
        var btnAddOdp = document.getElementById('btnAddOdpMode');
        var btnAddHtb = document.getElementById('btnAddHtbMode');
        var btnAddClosure = document.getElementById('btnAddClosureMode');
        var btnCancel = document.getElementById('btnCancelAdd');
        var mapContainer = document.getElementById('map');

        function setMode(mode) {
            addMode = mode;
            if (mode) {
                mapContainer.style.cursor = 'crosshair';
                btnCancel.classList.remove('d-none');
                btnAddOlt.disabled = true;
                btnAddOdc.disabled = true;
                btnAddOdp.disabled = true;
                btnAddHtb.disabled = true;
                btnAddClosure.disabled = true;
            } else {
                mapContainer.style.cursor = 'default';
                btnCancel.classList.add('d-none');
                btnAddOlt.disabled = false;
                btnAddOdc.disabled = false;
                btnAddOdp.disabled = false;
                btnAddHtb.disabled = false;
                btnAddClosure.disabled = false;
            }
        }

        btnAddOlt.addEventListener('click', function() { setMode('olt'); });
        btnAddOdc.addEventListener('click', function() { setMode('odc'); });
        btnAddOdp.addEventListener('click', function() { setMode('odp'); });
        btnAddHtb.addEventListener('click', function() { setMode('htb'); });
        btnAddClosure.addEventListener('click', function() { setMode('closure'); });
        btnCancel.addEventListener('click', function() { setMode(null); });

        map.on('click', function(e) {
            var lat = e.latlng.lat;
            var lng = e.latlng.lng;

            if (isPickerMode) {
                if (window.opener && !window.opener.closed) {
                    window.opener.postMessage({
                        type: 'mstore-map-picked',
                        lat: lat,
                        lng: lng,
                    }, window.location.origin);
                }
                window.close();
                return;
            }

            if (!addMode) return;

            if (addMode === 'olt') {
                document.getElementById('oltForm').reset();
                document.getElementById('olt_lat').value = lat;
                document.getElementById('olt_lng').value = lng;
                // Filter dropdown if needed, or just show modal
                var oltModal = new bootstrap.Modal(document.getElementById('oltModal'));
                oltModal.show();
            } else if (addMode === 'odc') {
                document.getElementById('odcForm').reset(); // Reset form
                document.getElementById('odc_id').value = ''; // Clear ID for new
                document.getElementById('odc_lat').value = lat;
                document.getElementById('odc_lng').value = lng;
                document.getElementById('odcModalLabel').innerText = '{{ __('Tambah ODC') }}'; // Set title
                var odcModal = new bootstrap.Modal(document.getElementById('odcModal'));
                odcModal.show();
            } else if (addMode === 'odp') {
                document.getElementById('odpForm').reset(); // Reset form
                document.getElementById('odp_id').value = ''; // Clear ID for new
                document.getElementById('odp_lat').value = lat;
                document.getElementById('odp_lng').value = lng;
                document.getElementById('odpModalLabel').innerText = '{{ __('Tambah ODP') }}'; // Set title
                var odpModal = new bootstrap.Modal(document.getElementById('odpModal'));
                odpModal.show();
            } else if (addMode === 'htb') {
                document.getElementById('htbForm').reset();
                document.getElementById('htb_id').value = '';
                document.getElementById('htb_lat').value = lat;
                document.getElementById('htb_lng').value = lng;
                document.getElementById('htbModalLabel').innerText = '{{ __('Tambah HTB') }}';
                
                // Reset uplink type logic
                document.getElementById('htb_uplink_type').value = 'odp';
                document.getElementById('htb_odp_group').classList.remove('d-none');
                document.getElementById('htb_parent_group').classList.add('d-none');
                
                var htbModal = new bootstrap.Modal(document.getElementById('htbModal'));
                htbModal.show();
            } else if (addMode === 'closure') {
                document.getElementById('closureForm').reset();
                document.getElementById('closure_id').value = '';
                document.getElementById('closure_lat').value = lat;
                document.getElementById('closure_lng').value = lng;
                document.getElementById('closureModalLabel').innerText = '{{ __('Tambah Closure') }}';
                
                var closureModal = new bootstrap.Modal(document.getElementById('closureModal'));
                closureModal.show();
            }
            
            setMode(null); // Reset mode after click
        });

        // --- Edit Functions ---
        window.editOdc = function(id) {
            var odc = odcs.find(o => o.id == id);
            if (odc) {
                document.getElementById('odc_id').value = odc.id;
                document.getElementById('odc_name').value = odc.name;
                document.getElementById('odc_lat').value = odc.latitude;
                document.getElementById('odc_lng').value = odc.longitude;
                document.getElementById('odc_capacity').value = odc.capacity;
                document.getElementById('odc_olt').value = odc.olt_id;
                document.getElementById('odc_description').value = odc.description || '';
                
                // New Fields
                document.getElementById('odc_pon_port').value = odc.pon_port || '';
                document.getElementById('odc_area').value = odc.area || '';
                document.getElementById('odc_color').value = odc.color || '';
                document.getElementById('odc_cable_no').value = odc.cable_no || '';

                document.getElementById('odcModalLabel').innerText = '{{ __('Edit ODC') }}';
                var odcModal = new bootstrap.Modal(document.getElementById('odcModal'));
                odcModal.show();
            }
        };

        window.editOdp = function(id) {
            var odp = odps.find(o => o.id == id);
            if (odp) {
                document.getElementById('odp_id').value = odp.id;
                document.getElementById('odp_name').value = odp.name;
                document.getElementById('odp_lat').value = odp.latitude;
                document.getElementById('odp_lng').value = odp.longitude;
                document.getElementById('odp_capacity').value = odp.capacity;
                document.getElementById('odp_region').value = odp.region_id;
                document.getElementById('odp_odc').value = odp.odc_id;
                document.getElementById('odp_color').value = odp.color || '#fd7e14';
                document.getElementById('odp_description').value = odp.description || '';
                
                // New Fields
                document.getElementById('odp_kampung').value = odp.kampung || '';

                document.getElementById('odpModalLabel').innerText = '{{ __('Edit ODP') }}';
                var odpModal = new bootstrap.Modal(document.getElementById('odpModal'));
                odpModal.show();
            }
        };

        window.editHtb = function(id) {
            var htb = htbs.find(h => h.id == id);
            if (htb) {
                document.getElementById('htb_id').value = htb.id;
                document.getElementById('htb_name').value = htb.name;
                document.getElementById('htb_lat').value = htb.latitude;
                document.getElementById('htb_lng').value = htb.longitude;
                document.getElementById('htb_description').value = htb.description || '';

                // Handle uplink
                if (htb.parent_htb_id) {
                    document.getElementById('htb_uplink_type').value = 'htb';
                    document.getElementById('htb_odp_group').classList.add('d-none');
                    document.getElementById('htb_parent_group').classList.remove('d-none');
                    document.getElementById('htb_parent').value = htb.parent_htb_id;
                    document.getElementById('htb_odp').value = '';
                } else {
                    document.getElementById('htb_uplink_type').value = 'odp';
                    document.getElementById('htb_odp_group').classList.remove('d-none');
                    document.getElementById('htb_parent_group').classList.add('d-none');
                    document.getElementById('htb_odp').value = htb.odp_id;
                    document.getElementById('htb_parent').value = '';
                }

                document.getElementById('htbModalLabel').innerText = '{{ __('Edit HTB') }}';
                var htbModal = new bootstrap.Modal(document.getElementById('htbModal'));
                htbModal.show();
            }
        };

        window.editClosure = function(id) {
            var closure = closures.find(c => c.id == id);
            if (closure) {
                document.getElementById('closure_id').value = closure.id;
                document.getElementById('closure_name').value = closure.name;
                document.getElementById('closure_lat').value = closure.latitude;
                document.getElementById('closure_lng').value = closure.longitude;
                document.getElementById('closure_capacity').value = closure.capacity;
                document.getElementById('closure_region').value = closure.region_id;
                document.getElementById('closure_odc').value = closure.odc_id;
                document.getElementById('closure_description').value = closure.description || '';

                document.getElementById('closureModalLabel').innerText = '{{ __('Edit Closure') }}';
                var closureModal = new bootstrap.Modal(document.getElementById('closureModal'));
                closureModal.show();
            }
        };

        // --- Save Functions (AJAX) ---
        var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        document.getElementById('saveOltBtn').addEventListener('click', function() {
            var id = document.getElementById('olt_select').value;
            if (!id) {
                alert('{{ __('Please select an OLT') }}');
                return;
            }

            var lat = document.getElementById('olt_lat').value;
            var lng = document.getElementById('olt_lng').value;
            var item = olts.find(i => i.id == id);
            
            if (!item) {
                 alert('{{ __('OLT not found') }}');
                 return;
            }

            // We are just updating the location, so we use the update endpoint
            var url = `/olt/${id}`;
            var data = {
                name: item.name,
                host: item.host,
                port: item.port,
                username: item.username,
                type: item.type,
                brand: item.brand,
                latitude: lat,
                longitude: lng,
                _method: 'PUT'
            };

            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success || data.id) {
                    location.reload();
                } else {
                    alert('{{ __('Error placing OLT:') }} ' + (data.message || JSON.stringify(data)));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('{{ __('An error occurred while saving.') }}');
            });
        });

        document.getElementById('saveClosureBtn').addEventListener('click', function() {
            var id = document.getElementById('closure_id').value;
            var url = id ? `/closures/${id}` : '/closures';
            var method = id ? 'PUT' : 'POST';
            var formData = new FormData(document.getElementById('closureForm'));
            var data = Object.fromEntries(formData.entries());

            fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => { throw new Error(text) });
                }
                return response.json();
            })
            .then(data => {
                if (data.success || data.id) {
                    location.reload();
                } else {
                    alert('{{ __('Error saving Closure:') }} ' + (data.message || JSON.stringify(data)));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                let errorMsg = error.message;
                try {
                    const errorObj = JSON.parse(error.message);
                    if (errorObj.message) errorMsg = errorObj.message;
                } catch(e) {}
                alert('{{ __('An error occurred while saving:') }} ' + errorMsg);
            });
        });

        document.getElementById('saveOdcBtn').addEventListener('click', function() {
            var id = document.getElementById('odc_id').value;
            var url = id ? `/odcs/${id}` : '/odcs';
            var method = id ? 'PUT' : 'POST';
            var formData = new FormData(document.getElementById('odcForm'));
            var data = Object.fromEntries(formData.entries());

            fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success || data.id) { // Some controllers return full object
                    location.reload();
                } else {
                    alert('{{ __('Error saving ODC:') }} ' + (data.message || JSON.stringify(data)));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('{{ __('An error occurred while saving.') }}');
            });
        });

        document.getElementById('saveOdpBtn').addEventListener('click', function() {
            var id = document.getElementById('odp_id').value;
            var url = id ? `/odps/${id}` : '/odps';
            var method = id ? 'PUT' : 'POST';
            var formData = new FormData(document.getElementById('odpForm'));
            var data = Object.fromEntries(formData.entries());

            fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success || data.id) {
                    location.reload();
                } else {
                    alert('{{ __('Error saving ODP:') }} ' + (data.message || JSON.stringify(data)));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('{{ __('An error occurred while saving.') }}');
            });
        });

        document.getElementById('htb_uplink_type').addEventListener('change', function() {
            if (this.value === 'odp') {
                document.getElementById('htb_odp_group').classList.remove('d-none');
                document.getElementById('htb_parent_group').classList.add('d-none');
            } else {
                document.getElementById('htb_odp_group').classList.add('d-none');
                document.getElementById('htb_parent_group').classList.remove('d-none');
            }
        });

        document.getElementById('saveHtbBtn').addEventListener('click', function() {
            var id = document.getElementById('htb_id').value;
            var url = id ? `/htbs/${id}` : '/htbs';
            var method = id ? 'PUT' : 'POST';
            var formData = new FormData(document.getElementById('htbForm'));
            var data = Object.fromEntries(formData.entries());

            // Handle uplink logic for submission
            if (data.uplink_type === 'odp') {
                data.parent_htb_id = '';
            } else {
                data.odp_id = '';
            }

            fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success || data.id) {
                    location.reload();
                } else {
                    alert('{{ __('Error saving HTB:') }} ' + (data.message || JSON.stringify(data)));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('{{ __('An error occurred while saving.') }}');
            });
        });

        // Fullscreen
        document.getElementById('btnFullscreen').addEventListener('click', function() {
            var mapElement = document.getElementById('map');
            if (!document.fullscreenElement) {
                mapElement.requestFullscreen().catch(err => {
                    alert(`Error attempting to enable full-screen mode: ${err.message} (${err.name})`);
                });
            } else {
                document.exitFullscreen();
            }
        });

        // --- Customer Popup Helper Functions ---
        window.pingCustomer = function(ip, customerId) {
            if (!ip || ip === '-') {
                alert('IP tidak valid');
                return;
            }

            const resultDiv = document.getElementById(`ping-result-${customerId}`);
            const pingBtn = resultDiv.previousElementSibling.querySelector('button');
            const pingText = document.getElementById(`ping-text-${customerId}`);
            const pingIcon = document.getElementById(`ping-icon-${customerId}`);

            resultDiv.classList.remove('hidden');
            resultDiv.innerHTML = `<span class="text-blue-400">Pinging ${ip}...</span>`;
            pingText.innerText = 'Pinging...';
            pingIcon.classList.add('animate-spin');

            fetch('/map/ping', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ target: ip })
            })
            .then(response => response.json())
            .then(data => {
                pingText.innerText = 'Ping';
                pingIcon.classList.remove('animate-spin');
                
                if (data.success) {
                    resultDiv.innerHTML = `<pre class="text-green-400 mb-0">${data.output}</pre>`;
                } else {
                    resultDiv.innerHTML = `<pre class="text-red-400 mb-0">${data.output || data.message}</pre>`;
                }
            })
            .catch(err => {
                pingText.innerText = 'Ping';
                pingIcon.classList.remove('animate-spin');
                resultDiv.innerHTML = `<span class="text-red-500">Error: ${err.message}</span>`;
            });
        };

        window.togglePassword = function(id, btnEl) {
            const textEl = document.getElementById(`pass-text-${id}`);
            const iconEl = document.getElementById(`pass-icon-${id}`);
            if (!textEl) return;
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }

            const actualPass = textEl.getAttribute('data-password') || '';
            const isVisible = btnEl && btnEl.getAttribute('data-visible') === '1';
            
            if (!isVisible) {
                textEl.innerText = actualPass || '-';
                if (btnEl) {
                    btnEl.setAttribute('data-visible', '1');
                    btnEl.setAttribute('title', 'Sembunyikan Password');
                }
                if (iconEl) {
                    iconEl.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/></svg>`;
                }
            } else {
                textEl.innerText = '••••••••';
                if (btnEl) {
                    btnEl.setAttribute('data-visible', '0');
                    btnEl.setAttribute('title', 'Tampilkan Password');
                }
                if (iconEl) {
                    iconEl.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>`;
                }
            }
        };

        window.openWifiModal = function(id) {
            const ssidEl = document.getElementById(`popup-ssid-${id}`);
            const passEl = document.getElementById(`pass-text-${id}`);
            const customer = customers.find(c => c.id == id) || {};
            const ssid = ssidEl ? ssidEl.innerText : (customer.ssid_name || '');
            const password = passEl ? (passEl.getAttribute('data-password') || '') : (customer.ssid_password || '');

            document.getElementById('wifi_customer_id').value = id;
            document.getElementById('wifi_ssid').value = ssid;
            document.getElementById('wifi_password').value = password;
            
            const wifiModal = new bootstrap.Modal(document.getElementById('wifiModal'));
            wifiModal.show();
        };

        document.getElementById('saveWifiBtn').addEventListener('click', function() {
            const id = document.getElementById('wifi_customer_id').value;
            const ssid = document.getElementById('wifi_ssid').value;
            const password = document.getElementById('wifi_password').value;
            const btn = this;
            const spinner = document.getElementById('wifiBtnSpinner');

            if (!ssid || password.length < 8) {
                alert('SSID harus diisi dan password minimal 8 karakter');
                return;
            }

            btn.disabled = true;
            spinner.classList.remove('d-none');

            fetch(`/map/wlan-update/${id}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ ssid, password })
            })
            .then(response => response.json())
            .then(data => {
                btn.disabled = false;
                spinner.classList.add('d-none');
                
                if (data.success) {
                    alert('Pengaturan WiFi berhasil diperbarui');
                    bootstrap.Modal.getInstance(document.getElementById('wifiModal')).hide();
                    // Update data in map without reload if possible
                    const customer = customers.find(c => c.id == id);
                    if (customer) {
                        customer.ssid_name = ssid;
                        customer.ssid_password = password;
                    }
                    // Refresh marker popup if it's open
                    location.reload(); 
                } else {
                    alert('Gagal: ' + data.message);
                }
            })
            .catch(err => {
                btn.disabled = false;
                spinner.classList.add('d-none');
                alert('Terjadi kesalahan: ' + err.message);
            });
        });

        window.rebootCustomer = function(id) {
            if (confirm('Apakah Anda yakin ingin me-reboot ONU ini?')) {
                alert('Reboot perintah dikirim untuk ID: ' + id);
            }
        };

        window.copyToClipboard = function(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert('Informasi disalin ke clipboard');
            }).catch(err => {
                console.error('Gagal menyalin: ', err);
            });
        };

        window.openInMaps = function(lat, lng) {
            if (!lat || !lng) return;
            window.open(`https://www.google.com/maps?q=${lat},${lng}`, '_blank');
        };

        window.openWA = function(phone) {
            if (!phone || phone === '-') {
                alert('Nomor telepon tidak tersedia');
                return;
            }
            var cleanPhone = phone.replace(/\D/g, '');
            if (cleanPhone.startsWith('0')) cleanPhone = '62' + cleanPhone.substring(1);
            window.open(`https://wa.me/${cleanPhone}`, '_blank');
        };

        window.duplicateCustomer = function(id) {
            if (confirm('Duplikat data pelanggan ini?')) {
                // Implement duplicate logic or redirect
                alert('Fitur duplikat untuk ID: ' + id);
            }
        };
    });
</script>
@endpush
