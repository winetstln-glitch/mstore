@extends('layouts.app')

@section('title', __('Pengaturan ATK'))

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0 border-top border-4 border-primary">
            <div class="card-header py-3">
                <h5 class="mb-0 fw-bold">{{ __('Pengaturan ATK') }}</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('settings.atk.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-4 pb-3 border-bottom">
                        <h6 class="fw-bold text-primary text-uppercase mb-3">
                            <i class="fa-solid fa-store me-1"></i> Identitas Toko ATK
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="atk_store_name" class="form-label fw-medium">Nama Toko ATK</label>
                                <input type="text" class="form-control" id="atk_store_name" name="atk_store_name" value="{{ \App\Models\Setting::getValue('atk_store_name', \App\Models\Setting::getValue('store_name', config('app.name', 'MStore'))) }}">
                            </div>
                            <div class="col-md-6">
                                <label for="atk_store_phone" class="form-label fw-medium">Telepon Toko ATK</label>
                                <input type="text" class="form-control" id="atk_store_phone" name="atk_store_phone" value="{{ \App\Models\Setting::getValue('atk_store_phone', \App\Models\Setting::getValue('store_phone', '081234567890')) }}">
                            </div>
                            <div class="col-md-6">
                                <label for="atk_store_logo_file" class="form-label fw-medium">Upload Logo Toko ATK</label>
                                <input type="file" class="form-control" id="atk_store_logo_file" name="atk_store_logo_file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                                @if(\App\Models\Setting::getValue('atk_store_logo'))
                                    <img src="{{ str_starts_with(\App\Models\Setting::getValue('atk_store_logo'), 'http') ? \App\Models\Setting::getValue('atk_store_logo') : asset(\App\Models\Setting::getValue('atk_store_logo')) }}" alt="Logo Toko ATK" class="img-thumbnail mt-2" style="max-height: 56px;">
                                    <div class="form-check mt-2">
                                        <input type="hidden" name="clear_atk_store_logo" value="0">
                                        <input class="form-check-input" type="checkbox" value="1" id="clear_atk_store_logo" name="clear_atk_store_logo">
                                        <label class="form-check-label text-danger" for="clear_atk_store_logo">Hapus logo toko ATK</label>
                                    </div>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <label for="atk_store_address" class="form-label fw-medium">Alamat Toko ATK</label>
                                <textarea class="form-control" id="atk_store_address" name="atk_store_address" rows="3">{{ \App\Models\Setting::getValue('atk_store_address', \App\Models\Setting::getValue('store_address', 'Jl. Contoh No. 1')) }}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h6 class="fw-bold text-primary text-uppercase mb-3">
                            <i class="fa-solid fa-print me-1"></i> POS Android
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="pos_printer_auto_reconnect" class="form-label fw-medium">Auto Reconnect Printer</label>
                                @php $posPrinterAutoReconnect = \App\Models\Setting::getValue('pos_printer_auto_reconnect', '1'); @endphp
                                <select class="form-select" id="pos_printer_auto_reconnect" name="pos_printer_auto_reconnect">
                                    <option value="1" {{ $posPrinterAutoReconnect === '1' ? 'selected' : '' }}>Aktif</option>
                                    <option value="0" {{ $posPrinterAutoReconnect === '0' ? 'selected' : '' }}>Nonaktif</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="pos_print_logo_enabled" class="form-label fw-medium">Cetak Logo ESC/POS</label>
                                @php $posPrintLogoEnabled = \App\Models\Setting::getValue('pos_print_logo_enabled', '1'); @endphp
                                <select class="form-select" id="pos_print_logo_enabled" name="pos_print_logo_enabled">
                                    <option value="1" {{ $posPrintLogoEnabled === '1' ? 'selected' : '' }}>Aktif</option>
                                    <option value="0" {{ $posPrintLogoEnabled === '0' ? 'selected' : '' }}>Nonaktif</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="pos_bluetooth_chunk_size" class="form-label fw-medium">Ukuran Paket Bluetooth</label>
                                <input type="number" class="form-control" id="pos_bluetooth_chunk_size" name="pos_bluetooth_chunk_size" min="90" max="512" value="{{ \App\Models\Setting::getValue('pos_bluetooth_chunk_size', '256') }}">
                            </div>
                            <div class="col-md-6">
                                <label for="pos_bluetooth_chunk_delay_ms" class="form-label fw-medium">Delay Antar Paket (ms)</label>
                                <input type="number" class="form-control" id="pos_bluetooth_chunk_delay_ms" name="pos_bluetooth_chunk_delay_ms" min="0" max="100" value="{{ \App\Models\Setting::getValue('pos_bluetooth_chunk_delay_ms', '0') }}">
                            </div>
                            <div class="col-12">
                                <label for="pos_qris_text" class="form-label fw-medium">Data String QRIS</label>
                                <textarea class="form-control" id="pos_qris_text" name="pos_qris_text" rows="3">{{ \App\Models\Setting::getValue('pos_qris_text', '') }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <label for="pos_preferred_printer_name" class="form-label fw-medium">Nama Printer Default</label>
                                <input type="text" class="form-control" id="pos_preferred_printer_name" name="pos_preferred_printer_name" value="{{ \App\Models\Setting::getValue('pos_preferred_printer_name', '') }}">
                            </div>
                            <div class="col-md-6">
                                <label for="pos_preferred_printer_id" class="form-label fw-medium">ID Printer Default</label>
                                <input type="text" class="form-control" id="pos_preferred_printer_id" name="pos_preferred_printer_id" value="{{ \App\Models\Setting::getValue('pos_preferred_printer_id', '') }}">
                            </div>
                            <div class="col-md-6">
                                <label for="pos_performance_profile" class="form-label fw-medium">Profil Performa Printer</label>
                                @php $posPerformanceProfile = \App\Models\Setting::getValue('pos_performance_profile', 'ultrafast'); @endphp
                                <select class="form-select" id="pos_performance_profile" name="pos_performance_profile">
                                    <option value="ultrafast" {{ $posPerformanceProfile === 'ultrafast' ? 'selected' : '' }}>Ultra Fast (&lt;0.5 detik)</option>
                                    <option value="balanced" {{ $posPerformanceProfile === 'balanced' ? 'selected' : '' }}>Balanced</option>
                                    <option value="stable" {{ $posPerformanceProfile === 'stable' ? 'selected' : '' }}>Stable</option>
                                </select>
                            </div>
                        </div>
                    </div>

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
