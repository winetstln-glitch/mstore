@extends('layouts.app')

@section('title', __('Peta'))

@section('content')
<style>
    /* Responsive Map Height */
    #map {
        width: 100%;
        /* Default height: Full viewport height minus approx header/footer space */
        height: calc(100vh - 160px); 
        min-height: 500px;
        border-radius: 8px;
        z-index: 1; /* Ensure map stays below other fixed elements if any */
    }

    /* Mobile Adjustment for Map Height */
    @media (max-width: 768px) {
        #map {
            height: calc(100vh - 180px); /* More space for stacked toolbar on mobile */
            min-height: 60vh;
        }
        
        /* Adjust popup font size for mobile */
        .leaflet-popup-content {
            font-size: 12px !important;
            min-width: 200px !important;
        }
        
        /* Make popup content scrollable if too tall */
        .leaflet-popup-content-wrapper {
            max-height: 70vh;
            overflow-y: auto;
        }

        /* Stack toolbar items on mobile */
        .toolbar-container {
            flex-direction: column;
            align-items: stretch !important;
        }
        .toolbar-title {
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .toolbar-actions {
            justify-content: space-between;
            width: 100%;
        }
        .btn-group-mobile {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            width: 100%;
        }
        .btn-group-mobile .btn {
            flex: 1 1 auto; /* Buttons grow to fill space */
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }
    }

    /* Laser Glow Animation */
    .laser-glow {
        width: 10px;
        height: 10px;
        background-color: #00f2ff;
        border-radius: 50%;
        box-shadow: 0 0 5px #ff0000ff, 0 0 10px #ff1500ff, 0 0 15px #ff0055ff;
        animation: pulse-glow 0.5s infinite alternate;
    }
    @keyframes pulse-glow {
        from {
            transform: scale(0.8);
            box-shadow: 0 0 5px #ff0000ff, 0 0 10px #ff0000ff;
            opacity: 0.8;
        }
        to {
            transform: scale(1.2);
            box-shadow: 0 0 10px #ff0000ff, 0 0 20px #ff0026ff, 0 0 30px #ff0033ff;
            opacity: 1;
        }
    }
</style>

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
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
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
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
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
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
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

@endsection

@push('styles')
<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<script src="https://unpkg.com/leaflet-polylinedecorator/dist/leaflet.polylineDecorator.min.js"></script>

<style>
    .leaflet-popup-content-wrapper {
        border-radius: 16px;
        border: 1px solid rgba(148, 163, 184, 0.28);
        background: linear-gradient(145deg, #ffffff 0%, #f8fbff 100%);
        box-shadow: 0 18px 46px rgba(15, 23, 42, 0.16);
    }
    .leaflet-popup-tip {
        background: #ffffff;
        border: 1px solid rgba(148, 163, 184, 0.35);
    }
    .leaflet-popup-content {
        margin: 0;
        padding: 14px;
        color: #0f172a;
    }
    .leaflet-container a.leaflet-popup-close-button {
        color: #334155;
        width: 26px;
        height: 26px;
        line-height: 26px;
        border-radius: 999px;
        transition: all 0.2s ease;
    }
    .leaflet-container a.leaflet-popup-close-button:hover {
        background: rgba(15, 23, 42, 0.08);
        color: #0f172a;
    }
    .map-popup {
        min-width: 230px;
        color: #0f172a;
    }
    .map-popup-title {
        margin-bottom: 0.5rem;
        font-size: 0.96rem;
        font-weight: 700;
        color: #0f172a;
        letter-spacing: 0.01em;
    }
    .map-popup-table {
        margin-bottom: 0.55rem;
        font-size: 0.81rem;
        color: #0f172a;
    }
    .map-popup-table td {
        padding: 0.16rem 0;
        border: none;
        vertical-align: top;
    }
    .map-popup-label {
        color: #64748b;
        width: 40%;
    }
    .map-popup-value {
        text-align: left;
        color: #0f172a;
        font-weight: 600;
        padding-left: 0.5rem;
        word-break: break-word;
    }
    .map-popup-desc {
        margin-bottom: 0.5rem;
        color: #64748b;
        font-size: 0.79rem;
        font-style: italic;
    }
    .map-popup-actions {
        display: flex;
        gap: 0.4rem;
        margin-top: 0.35rem;
        flex-wrap: wrap;
    }
    .map-popup-btn {
        font-size: 0.74rem;
        padding: 0.28rem 0.62rem;
        border-radius: 10px;
        font-weight: 600;
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.12);
        border: none;
        transition: transform 0.14s ease, box-shadow 0.14s ease;
    }
    .map-popup-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 10px 22px rgba(15, 23, 42, 0.16);
    }
    [data-bs-theme="dark"] .leaflet-popup-content-wrapper {
        background: linear-gradient(150deg, #0f172a 0%, #111f35 100%);
        border-color: rgba(125, 249, 255, 0.3);
        box-shadow: 0 20px 52px rgba(2, 6, 23, 0.72);
    }
    [data-bs-theme="dark"] .leaflet-popup-tip {
        background: #0f172a;
        border-color: rgba(125, 249, 255, 0.35);
    }
    [data-bs-theme="dark"] .leaflet-popup-content,
    [data-bs-theme="dark"] .map-popup,
    [data-bs-theme="dark"] .map-popup-title,
    [data-bs-theme="dark"] .map-popup-table,
    [data-bs-theme="dark"] .map-popup-value {
        color: #e6f1ff;
    }
    [data-bs-theme="dark"] .map-popup-label,
    [data-bs-theme="dark"] .map-popup-desc {
        color: #8aa5c9;
    }
    [data-bs-theme="dark"] .leaflet-container a.leaflet-popup-close-button {
        color: #c7def7;
    }
    [data-bs-theme="dark"] .leaflet-container a.leaflet-popup-close-button:hover {
        background: rgba(125, 249, 255, 0.12);
        color: #e6f1ff;
    }
    [data-bs-theme="dark"] .map-popup-btn {
        box-shadow: 0 10px 24px rgba(2, 6, 23, 0.45);
    }
    .custom-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 20%;
        background: #16202280;
        border: 1px solid white;
        box-shadow: 0 2px 5px rgba(0,0,0,0.3);
    }
    [data-bs-theme="dark"] .custom-icon {
        background: #222730db;
        border-color: #f2f6a765;
    }
    .icon-olt { color: #f513e6ff; border-color: #f913f9ff; }
    .icon-odc { color: #f1fd14ff; border-color: #fdf114ff; }
    .icon-odp { color: #34f994; border-color: #34f994; }
    .icon-htb { color: #0707fcff; border-color: #0c14f8ff; }
    .icon-closure { color: #361347ff; border-color: #343a40; }
    .icon-customer-online { color: #10fdddff; border-color: #0ce9daff; }
    .icon-customer-offline { color: #dc3545; border-color: #dc3545; }
    .icon-asset { color: #d63384; border-color: #d63384; }

    /* Online connection line */
     

    /* Neon animation for online customer icon */
    .neon-online {
        box-shadow: 0 0 6px rgba(0, 240, 255, 0.6), 0 0 12px rgba(0, 240, 255, 0.4);
        animation: neon-pulse 1.2s ease-in-out infinite alternate;
    }
    .neon-online i {
        color: #00f2ff !important;
        text-shadow: 0 0 4px #00f2ff, 0 0 8px #00f2ff, 0 0 12px rgba(0, 242, 255, 0.8);
    }
    @keyframes neon-pulse {
        0% { box-shadow: 0 0 4px rgba(0, 240, 255, 0.5), 0 0 8px rgba(0, 240, 255, 0.3); }
        100% { box-shadow: 0 0 10px rgba(0, 240, 255, 0.8), 0 0 18px rgba(0, 240, 255, 0.6); }
    }

    /* Shining Arrow Icon */
    .arrow-glow {
        color: #00f2ff;
        font-size: 16px;
        text-shadow: 0 0 5px #00f2ff, 0 0 10px #00f2ff;
        animation: pulse-shine 1.5s infinite alternate;
    }

    @keyframes pulse-shine {
        0% { opacity: 0.7; text-shadow: 0 0 5px #00f2ff; }
        100% { opacity: 1; text-shadow: 0 0 10px #00f2ff, 0 0 20px #fff; }
    }
</style>
@endpush
@push('scripts')
<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script src="https://cdn.jsdelivr.net/npm/leaflet-ant-path@1.3.0/dist/leaflet-ant-path.min.js"></script>

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
        var coordinatorRegionId = @json($coordinatorRegionId ?? null);

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
        // Auto-center logic commented out to prioritize server location
        /*
        var picked = firstWithCoord(customers) || firstWithCoord(olts) || firstWithCoord(odcs);
        if (picked) {
            defaultLat = picked.latitude;
            defaultLng = picked.longitude;
        }
        */

        var map = L.map('map').setView([defaultLat, defaultLng], initialZoom);

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
            if (['olt', 'odc', 'asset', 'closure'].includes(type)) return true;

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
                    draggable: true
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
                if (odc.olt_id) {
                    var olt = olts.find(o => o.id == odc.olt_id);
                    if (olt) oltName = olt.name;
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
                    draggable: true
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
                if (odp.odc_id) {
                    var odc = odcs.find(o => o.id == odp.odc_id);
                    if (odc) odcName = odc.name;
                }

                var popupContent = document.createElement('div');
                popupContent.innerHTML = `
                    <div class="map-popup">
                        <h6 class="map-popup-title">ODP: ${odp.name}</h6>
                        <table class="table table-sm table-borderless map-popup-table">
                            <tr><td class="map-popup-label">Kapasitas:</td><td class="map-popup-value">${odp.filled || 0}/${odp.capacity}</td></tr>
                            <tr><td class="map-popup-label">ODC:</td><td class="map-popup-value">${odcName}</td></tr>
                            <tr><td class="map-popup-label">Area:</td><td class="map-popup-value">${odp.kampung || '-'}</td></tr>
                            <tr><td class="map-popup-label">Warna:</td><td class="map-popup-value">${odp.color || '-'}</td></tr>
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
                    draggable: true
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
                    draggable: true
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
                    draggable: true
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
                    draggable: true
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

            // Find ODP name
            var odpName = 'N/A';
            if (customer.odp_id) {
                var odp = odps.find(o => o.id == customer.odp_id);
                if (odp) odpName = odp.name;
            } else if (customer.odp) {
                odpName = customer.odp.name || customer.odp; 
            }
            
            var marker = L.marker([customer.latitude, customer.longitude], {
                icon: createIcon(iconType),
                draggable: true
            })
            .addTo(markers)
            .bindPopup(
                `<div class="map-popup">` +
                `<h6 class="map-popup-title">${customer.name}</h6>` +
                `<div class="mb-2">` +
                `<span class="badge ${isOnline ? 'bg-success' : 'bg-danger'} me-1">${isOnline ? 'Online' : 'Offline'}</span>` +
                `<span class="badge bg-secondary">${customer.status}</span>` +
                `</div>` +
                `<table class="table table-sm table-borderless map-popup-table">` +
                `<tr><td class="map-popup-label">ID:</td><td class="map-popup-value">${customer.id}</td></tr>` +
                `<tr><td class="map-popup-label">Alamat:</td><td class="map-popup-value text-truncate" style="max-width: 150px;">${customer.address || '-'}</td></tr>` +
                `<tr><td class="map-popup-label">Telepon:</td><td class="map-popup-value">${customer.phone || '-'}</td></tr>` +
                `<tr><td class="map-popup-label">Paket:</td><td class="map-popup-value">${customer.package || '-'}</td></tr>` +
                `<tr><td class="map-popup-label">ODP:</td><td class="map-popup-value">${odpName}</td></tr>` +
                `<tr><td class="map-popup-label">SN:</td><td class="map-popup-value font-monospace">${customer.onu_serial || '-'}</td></tr>` +
                `<tr><td class="map-popup-label">IP TR069:</td><td class="map-popup-value font-monospace">${tr069Ip}</td></tr>` +
                `</table>` +
                `<div class="map-popup-actions">` +
                `<a href="/customers/${customer.id}" class="btn btn-sm btn-info text-white map-popup-btn">Detail</a>` +
                `<a href="/customers/${customer.id}/edit" class="btn btn-sm btn-outline-primary map-popup-btn">Edit</a>` +
                `</div></div>`
            );

            allMarkerObjs.push({ marker: marker, type: 'customer', data: customer });

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
            if (!addMode) return;

            var lat = e.latlng.lat;
            var lng = e.latlng.lng;

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
    });
</script>
@endpush
