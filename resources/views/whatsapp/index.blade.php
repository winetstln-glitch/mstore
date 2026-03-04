@extends('layouts.app')

@section('title', 'WhatsApp Admin Panel')

@section('content')
<div class="card shadow-sm border-0">

    <div class="card-header bg-dark text-white">
        <h5 class="mb-0">
            <i class="fa-brands fa-whatsapp me-2"></i>
            WhatsApp System Configuration (Admin)
        </h5>
    </div>

    <div class="card-body">

        {{-- NAV TABS --}}
        <ul class="nav nav-tabs mb-4" id="waTab" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#api">
                    API & Connection
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#template">
                    Template Engine
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#isp">
                    ISP Automation
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#test">
                    Testing
                </button>
            </li>
        </ul>

        <div class="tab-content">

            <div class="tab-pane fade show active" id="api">
                <form method="POST" action="{{ route('whatsapp.update') }}">
                    @csrf
                    <div class="alert alert-secondary">
                        Endpoint harus base URL. Sistem otomatis append <code>/send</code>.
                    </div>
                    <div class="mb-3">
                        <label>API URL</label>
                        <input type="text" class="form-control" name="whatsapp_api_url" value="{{ \App\Models\Setting::getValue('whatsapp_api_url', env('WHATSAPP_API_URL')) }}">
                    </div>
                    <div class="mb-3">
                        <label>API Key</label>
                        <input type="password" class="form-control" name="whatsapp_api_key" placeholder="••••••">
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="fa-solid fa-save"></i> Save
                        </button>
                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="tab" data-bs-target="#test">
                            <i class="fa-solid fa-plug"></i> Test Connection
                        </button>
                    </div>
                </form>
            </div>


            <div class="tab-pane fade" id="template">
                <form method="POST" action="{{ route('whatsapp.update') }}">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <label>ATK Receipt Template</label>
                            <textarea class="form-control" rows="12" name="whatsapp_atk_receipt_template" id="atkTpl">{{ \App\Models\Setting::getValue('whatsapp_atk_receipt_template', '') }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label>Live Preview</label>
                            <div class="border p-3 bg-light" style="min-height:300px;" id="atkPreview"></div>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <label>Wash Receipt Template</label>
                            <textarea class="form-control" rows="12" name="whatsapp_wash_receipt_template" id="washTpl">{{ \App\Models\Setting::getValue('whatsapp_wash_receipt_template', '') }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label>Live Preview</label>
                            <div class="border p-3 bg-light" style="min-height:300px;" id="washPreview"></div>
                        </div>
                    </div>
                    <div class="mt-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            Update Template
                        </button>
                    </div>
                </form>

            </div>

            <div class="tab-pane fade" id="isp">
                <form method="POST" action="{{ route('whatsapp.update') }}">
                    @csrf
                    <label>Monthly Bill Template</label>
                    <textarea class="form-control mb-3" rows="6" name="whatsapp_isp_bill_template">{{ \App\Models\Setting::getValue('whatsapp_isp_bill_template', '') }}</textarea>
                    <label>Reminder Template</label>
                    <textarea class="form-control mb-3" rows="6" name="whatsapp_isp_reminder_template">{{ \App\Models\Setting::getValue('whatsapp_isp_reminder_template', '') }}</textarea>
                    <button type="submit" class="btn btn-primary">
                        Save ISP Templates
                    </button>
                </form>
            </div>

            <div class="tab-pane fade" id="test">
                <form method="POST" action="{{ route('whatsapp.test') }}">
                    @csrf
                    <label>Phone Number</label>
                    <input type="text" class="form-control mb-3" name="test_phone" placeholder="628xxxxxxxxxx">
                    <label>Select Template</label>
                    <select class="form-select mb-3" name="test_mode">
                        <option value="plain">Plain</option>
                        <option value="atk_receipt">ATK Receipt</option>
                        <option value="wash_receipt">Wash Receipt</option>
                        <option value="isp_bill">ISP Bill</option>
                    </select>
                    <button type="submit" class="btn btn-dark">
                        <i class="fa-solid fa-paper-plane"></i>
                        Send Test
                    </button>
                </form>
            </div>

@push('scripts')
<script>
    function renderLoop(tpl, items) {
        const re = /\{\{\#each\s+items\}\}([\s\S]*?)\{\{\/each\}\}/;
        const m = tpl.match(re);
        if (!m) return tpl;
        let out = '';
        items.forEach(it => {
            let seg = m[1];
            Object.keys(it).forEach(k => {
                seg = seg.replaceAll('{{' + k + '}}', String(it[k]));
            });
            out += seg;
        });
        return tpl.replace(m[0], out);
    }
    function renderSimple(tpl, vars) {
        let t = tpl;
        if (Array.isArray(vars.items)) {
            t = renderLoop(tpl, vars.items);
        }
        Object.keys(vars).forEach(k => {
            if (k !== 'items') {
                t = t.replaceAll('{{' + k + '}}', String(vars[k]));
            }
        });
        return t;
    }
    const atkVars = {
        nama_toko: @json(config('app.name')),
        alamat_toko: @json(\App\Models\Setting::getValue('store_address','Jl. Contoh No. 1')),
        no_toko: @json(\App\Models\Setting::getValue('store_phone','081234567890')),
        invoice: 'ATK-TEST-001',
        tanggal: @json(now()->format('d-m-Y H:i')),
        nama_customer: 'Pelanggan Demo',
        subtotal: '15.000',
        diskon: '0',
        pajak: '0',
        grand_total: '15.000',
        metode_bayar: 'CASH',
        status: 'LUNAS',
        items: [
            { nama_produk: 'Pulpen', qty: '1', harga: '5.000', total: '5.000' },
            { nama_produk: 'Buku Tulis', qty: '1', harga: '10.000', total: '10.000' }
        ]
    };
    const washVars = {
        nama_usaha: @json(config('app.name')),
        alamat: @json(\App\Models\Setting::getValue('store_address','Jl. Contoh No. 1')),
        no_hp: @json(\App\Models\Setting::getValue('store_phone','081234567890')),
        invoice: 'WASH-TEST-001',
        tanggal: @json(now()->format('d-m-Y H:i')),
        nama_customer: 'Pelanggan Demo',
        jenis_kendaraan: 'Toyota',
        plat_nomor: 'B 1234 CD',
        subtotal: '25.000',
        diskon: '0',
        total: '25.000',
        metode_bayar: 'CASH',
        status: 'LUNAS',
        items: [
            { nama_layanan: 'Cuci Eksterior', harga: '15.000' },
            { nama_layanan: 'Cuci Interior', harga: '10.000' }
        ]
    };
    function updatePreview(idTpl, idPrev, vars) {
        const elTpl = document.getElementById(idTpl);
        const elPrev = document.getElementById(idPrev);
        if (!elTpl || !elPrev) return;
        const result = renderSimple(elTpl.value, vars);
        elPrev.textContent = result;
    }
    document.addEventListener('DOMContentLoaded', function () {
        updatePreview('atkTpl', 'atkPreview', atkVars);
        updatePreview('washTpl', 'washPreview', washVars);
        const atkEl = document.getElementById('atkTpl');
        const washEl = document.getElementById('washTpl');
        if (atkEl) atkEl.addEventListener('input', () => updatePreview('atkTpl', 'atkPreview', atkVars));
        if (washEl) washEl.addEventListener('input', () => updatePreview('washTpl', 'washPreview', washVars));
    });
</script>
@endpush
        </div>
    </div>
</div>
@endsection
