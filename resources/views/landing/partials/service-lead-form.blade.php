@php
    $formConfig = $formConfig ?? [];
    $servicePage = $servicePage ?? [];
    $detailFields = $formConfig['details'] ?? [];
    $isWeddingForm = ($servicePage['slug'] ?? null) === 'wedding-event';
    $genericLeadCardClass = $isWeddingForm ? 'wedding-lead-card' : 'service-lead-card';
    $genericLabelClass = $isWeddingForm ? 'wedding-form-label' : 'service-form-label';
    $genericControlClass = $isWeddingForm ? 'wedding-form-control' : 'service-form-control';
@endphp

<div class="lead-card {{ $genericLeadCardClass }}">
    <div class="section-header mb-3 {{ $isWeddingForm ? '' : 'service-lead-heading' }}">
        <h6 class="{{ $isWeddingForm ? 'wedding-lead-kicker' : 'service-lead-kicker' }}">{{ $servicePage['name'] ?? 'Layanan' }}</h6>
        <h2 class="display-6 fw-800 mb-2 {{ $isWeddingForm ? 'wedding-lead-title' : 'service-lead-title' }}">{{ $formConfig['title'] ?? 'Kirim Kebutuhan' }}</h2>
        <p class="text-muted mb-0 {{ $isWeddingForm ? 'wedding-lead-subtitle' : 'service-lead-subtitle' }}">{{ $formConfig['description'] ?? 'Isi form singkat, tim kami akan follow up via WhatsApp.' }}</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            <div class="fw-bold">{{ session('success') }}</div>
            @if(session('lead_whatsapp_url'))
                <div class="mt-2">
                    <a class="btn btn-primary" href="{{ session('lead_whatsapp_url') }}">
                        <i class="fab fa-whatsapp me-2"></i> Lanjutkan via WhatsApp
                    </a>
                </div>
            @endif
        </div>
    @endif

    <form method="POST" action="{{ route('landing.leads.store') }}" class="lead-form {{ $isWeddingForm ? 'wedding-lead-form' : '' }}" data-track-service-form="{{ $servicePage['slug'] ?? 'service' }}" data-track-label="{{ $servicePage['name'] ?? 'service lead form' }}">
        @csrf
        <input type="hidden" name="service_interest" value="{{ $formConfig['interest'] ?? old('service_interest') }}">
        <input type="hidden" name="landing_page" value="{{ $servicePage['slug'] ?? '' }}">
        <input type="hidden" name="utm_source" value="{{ request('utm_source') }}">
        <input type="hidden" name="utm_medium" value="{{ request('utm_medium') }}">
        <input type="hidden" name="utm_campaign" value="{{ request('utm_campaign') }}">
        <input type="hidden" name="utm_term" value="{{ request('utm_term') }}">
        <input type="hidden" name="utm_content" value="{{ request('utm_content', $servicePage['slug'] ?? '') }}">

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label {{ $genericLabelClass }}">Nama</label>
                <input name="name" value="{{ old('name') }}" class="form-control {{ $genericControlClass }} @error('name') is-invalid @enderror" placeholder="Nama Anda" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label {{ $genericLabelClass }}">WhatsApp</label>
                <input name="phone" value="{{ old('phone') }}" class="form-control {{ $genericControlClass }} @error('phone') is-invalid @enderror" placeholder="Contoh: 08xxxxxxxxxx" required>
                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label {{ $genericLabelClass }}">Email (opsional)</label>
                <input type="email" name="email" value="{{ old('email') }}" class="form-control {{ $genericControlClass }} @error('email') is-invalid @enderror" placeholder="nama@email.com">
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label {{ $genericLabelClass }}">{{ $formConfig['coverage_label'] ?? 'Area / Lokasi' }}</label>
                <input name="coverage_area" value="{{ old('coverage_area') }}" class="form-control {{ $genericControlClass }} @error('coverage_area') is-invalid @enderror" placeholder="{{ $formConfig['coverage_placeholder'] ?? 'Tulis area atau lokasi Anda' }}">
                @error('coverage_area')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            @foreach($detailFields as $index => $detail)
                <div class="col-md-4">
                    <input type="hidden" name="detail_{{ $index + 1 }}_label" value="{{ $detail['label'] }}">
                    <label class="form-label {{ $genericLabelClass }}">{{ $detail['label'] }}</label>
                    <input
                        name="detail_{{ $index + 1 }}"
                        value="{{ old('detail_'.($index + 1)) }}"
                        class="form-control {{ $genericControlClass }} @error('detail_'.($index + 1)) is-invalid @enderror"
                        placeholder="{{ $detail['placeholder'] ?? '' }}">
                    @error('detail_'.($index + 1))<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            @endforeach

            <div class="col-12">
                <label class="form-label {{ $genericLabelClass }}">{{ $formConfig['message_label'] ?? 'Kebutuhan' }}</label>
                <textarea name="message" rows="4" class="form-control {{ $genericControlClass }} {{ $isWeddingForm ? 'wedding-form-textarea' : 'service-form-textarea' }} @error('message') is-invalid @enderror" placeholder="{{ $formConfig['message_placeholder'] ?? 'Jelaskan kebutuhan Anda' }}">{{ old('message') }}</textarea>
                @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12 d-flex flex-wrap gap-2">
                <button class="btn {{ $isWeddingForm ? 'wedding-btn-dark' : 'btn-primary' }} track-service-action" type="submit" data-track-service="{{ $servicePage['slug'] ?? 'service' }}" data-track-action="submit_button">
                    Kirim
                </button>
                <a class="btn {{ $isWeddingForm ? 'wedding-btn-light' : 'btn-outline-primary' }}" href="{{ route('customers.public.register.create') }}">
                    Daftar Customer
                </a>
                <a class="btn btn-green track-service-action" data-track-service="{{ $servicePage['slug'] ?? 'service' }}" data-track-action="whatsapp_cta" href="{{ 'https://wa.me/'.($waNumber ?? '6281234567890').'?text='.urlencode('Halo, saya ingin konsultasi '.($servicePage['name'] ?? 'layanan').'.') }}">
                    WhatsApp
                </a>
            </div>
        </div>
    </form>
</div>
