@extends('layouts.app')

@section('title', 'WhatsApp Admin Panel')

@section('content')
<div class="card shadow-sm">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fa-brands fa-whatsapp me-2"></i>
            WhatsApp System Configuration (Admin)
        </h5>
    </div>

    <div class="card-body">
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
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#autoreply">
                    Auto Reply & Webhook
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
            <li class="nav-item ms-auto">
                <a href="{{ route('whatsapp.logs') }}" class="nav-link text-secondary">
                    <i class="fa-solid fa-list"></i> Logs
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('whatsapp.builder.index') }}" class="nav-link text-success">
                    <i class="fa-solid fa-robot"></i> Bot Builder
                </a>
            </li>
        </ul>

        <div class="tab-content">
            @include('whatsapp.partials.api')
            @include('whatsapp.partials.templates')
            @include('whatsapp.partials.autoreply')
            @include('whatsapp.partials.isp')
            @include('whatsapp.partials.testing')
        </div>
    </div>
</div>
@endsection

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
    function copyWebhookUrl() {
        const webhookUrl = document.getElementById('webhookUrl');
        if (webhookUrl) {
            webhookUrl.select();
            webhookUrl.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(webhookUrl.value).then(() => {
                alert('Webhook URL berhasil disalin!');
            });
        }
    }
    function updatePreview(idTpl, idPrev, vars, renderer = renderSimple) {
        const elTpl = document.getElementById(idTpl);
        const elPrev = document.getElementById(idPrev);
        if (!elTpl || !elPrev) return;
        const result = renderer(elTpl.value, vars);
        elPrev.textContent = result;
    }
    
    // Show/Hide API Key
    let apiKeyVisible = false;
    function toggleApiKey() {
        const displayInput = document.getElementById('whatsapp_api_key_display');
        const toggleBtn = document.getElementById('toggle_api_key');
        const hiddenInput = document.getElementById('whatsapp_api_key_hidden');
        
        if (!displayInput || !toggleBtn || !hiddenInput) return;
        
        if (apiKeyVisible) {
            displayInput.type = 'password';
            toggleBtn.innerHTML = '<i class="fa-solid fa-eye"></i>';
            hiddenInput.value = '';
        } else {
            displayInput.type = 'text';
            toggleBtn.innerHTML = '<i class="fa-solid fa-eye-slash"></i>';
            hiddenInput.value = displayInput.value;
        }
        apiKeyVisible = !apiKeyVisible;
    }
    
    document.addEventListener('DOMContentLoaded', function () {
        updatePreview('atkTpl', 'atkPreview', atkVars);
        updatePreview('washTpl', 'washPreview', washVars);
        const atkEl = document.getElementById('atkTpl');
        const washEl = document.getElementById('washTpl');
        if (atkEl) atkEl.addEventListener('input', () => updatePreview('atkTpl', 'atkPreview', atkVars));
        if (washEl) washEl.addEventListener('input', () => updatePreview('washTpl', 'washPreview', washVars));
        
        // Attach show/hide API key listener
        const toggleBtn = document.getElementById('toggle_api_key');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', toggleApiKey);
        }
        
        // Update hidden input when display input changes
        const displayInput = document.getElementById('whatsapp_api_key_display');
        const hiddenInput = document.getElementById('whatsapp_api_key_hidden');
        if (displayInput && hiddenInput) {
            displayInput.addEventListener('input', () => {
                if (apiKeyVisible) {
                    hiddenInput.value = displayInput.value;
                }
            });
        }
    });
</script>
@endpush
