@extends('layouts.app')

@section('content')
<div class="wash-pos-page">
    <div class="wash-pos-shell">
        <header class="wash-pos-header">
            <span id="current-time" class="wash-current-time"></span>
        </header>

        <div class="row g-4">
            <div class="col-12 col-lg-8 order-2 order-lg-1">
                <div class="wash-card">
                    <div class="wash-card-header">
                        <h2 class="wash-card-title"><i class="fas fa-th-large"></i> Pilih Layanan</h2>
                        <div class="wash-filter-group">
                            <button class="filter-btn active" data-filter="all" type="button">Semua</button>
                            <button class="filter-btn" data-filter="mobil" type="button">Mobil</button>
                            <button class="filter-btn" data-filter="motor" type="button">Motor</button>
                            <button class="filter-btn" data-filter="addon" type="button">Add On</button>
                            <button class="filter-btn" data-filter="caffe" type="button">Caffe</button>
                        </div>
                        <div class="mt-2">
                            <input type="text" id="serviceSearchInput" class="form-control form-control-sm wash-input" placeholder="Cari layanan cepat...">
                        </div>
                    </div>
                    <div class="wash-card-body">
                        <div class="row g-3" id="services-container">
                            @php
                                $lastFilterType = null;
                                $sectionLabels = [
                                    'mobil' => 'Layanan Mobil',
                                    'motor' => 'Layanan Motor',
                                    'addon' => 'Add On & Skincare',
                                    'caffe' => 'Caffe',
                                    'umum' => 'Layanan Umum',
                                ];
                            @endphp
                            @foreach($services as $service)
                            @php
                                $rawType = strtolower((string) ($service->vehicle_type ?? ''));
                                $categoryRaw = strtolower((string) ($service->service_category ?? 'main'));
                                if ($rawType === 'car') {
                                    $normalizedType = 'mobil';
                                } elseif ($rawType === 'coffee') {
                                    $normalizedType = 'caffe';
                                } else {
                                    $normalizedType = $rawType;
                                }
                                $addonTypeClass = in_array($normalizedType, ['mobil', 'motor', 'caffe'], true) ? $normalizedType : 'umum';
                                $filterType = in_array($categoryRaw, ['addon', 'skincare'], true) ? 'addon' : $normalizedType;
                                $serviceTypeClass = in_array($filterType, ['mobil', 'motor', 'caffe', 'addon']) ? $filterType : 'umum';
                                $fallbackIcon = $normalizedType === 'mobil'
                                    ? 'fa-car-side'
                                    : ($normalizedType === 'motor'
                                        ? 'fa-motorcycle'
                                        : ($normalizedType === 'caffe' ? 'fa-mug-hot' : ($filterType === 'addon' ? 'fa-plus-circle' : 'fa-soap')));
                                $adjustment = is_null($service->holiday_price) ? null : (float) $service->holiday_price;
                                $isHolidayActive = (bool) ($holidaySchedule['active'] ?? false);
                                $effectivePrice = $isHolidayActive && !is_null($adjustment)
                                    ? max(0, ((float) $service->price) + $adjustment)
                                    : (float) $service->price;
                                $rulePayload = $service->priceRules->map(function ($rule) use ($isHolidayActive, $adjustment, $categoryRaw) {
                                    $rulePrice = (float) $rule->price;
                                    if ($isHolidayActive && !is_null($adjustment)) {
                                        $rulePrice = max(0, $rulePrice + (float) $adjustment);
                                    }
                                    $ruleLabel = (string) $rule->label;
                                    if (! in_array($categoryRaw, ['addon', 'skincare'], true)) {
                                        $ruleLabel = preg_replace('/^(Kecil|Sedang|Besar|Extra Besar)\s*-\s*/i', '', $ruleLabel);
                                    }
                                    $ruleLabel = trim((string) $ruleLabel);
                                    if ($ruleLabel === '') {
                                        $ruleLabel = (string) $rule->label;
                                    }

                                    return [
                                        'id' => $rule->id,
                                        'label' => $ruleLabel,
                                        'price' => $rulePrice,
                                    ];
                                })->values();
                            @endphp
                            @if($lastFilterType !== $filterType)
                                <div class="col-12 service-section-heading" data-type="{{ $filterType }}">
                                    <h6 class="mb-1 mt-2">{{ $sectionLabels[$filterType] ?? 'Layanan' }}</h6>
                                </div>
                                @php $lastFilterType = $filterType; @endphp
                            @endif
                            <div class="col-6 col-md-4 service-item" data-type="{{ $filterType }}">
                                <div class="service-card service-card-{{ $serviceTypeClass }}" data-fasttap
                                     data-id="{{ $service->id }}"
                                     data-name="{{ $service->name }}"
                                     data-price="{{ $effectivePrice }}"
                                     data-description="{{ $service->description }}"
                                     data-vehicletype="{{ $normalizedType }}"
                                     data-rules='@json($rulePayload)'>
                                    <div class="service-image-wrap">
                                        @if($service->image)
                                            <img src="{{ Storage::url($service->image) }}" class="img-fluid service-image" alt="{{ $service->name }}">
                                        @else
                                            <i class="fas {{ $fallbackIcon }} service-fallback-icon"></i>
                                        @endif
                                    </div>
                                    <h5 class="service-title">{{ $service->name }}</h5>
                                    @if($rulePayload->count() > 0)
                                        <div class="service-rule-picker mb-2">
                                            <select class="form-select form-select-sm service-rule-select">
                                                @foreach($rulePayload as $rule)
                                                    <option value="{{ $rule['id'] }}" data-label="{{ $rule['label'] }}" data-price="{{ $rule['price'] }}">
                                                        {{ $rule['label'] }} - Rp {{ number_format($rule['price'], 0, ',', '.') }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <div class="service-rule-mobile-options mt-2">
                                                @foreach($rulePayload as $idx => $rule)
                                                    <label class="service-rule-mobile-option">
                                                        <input type="radio" class="service-rule-mobile-input" name="service_rule_mobile_{{ $service->id }}" value="{{ $rule['id'] }}" data-label="{{ $rule['label'] }}" data-price="{{ $rule['price'] }}" {{ $idx === 0 ? 'checked' : '' }}>
                                                        <span class="service-rule-mobile-chip">{{ $rule['label'] }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                    @if(!empty($service->description))
                                        @php
                                            $descriptionItems = array_values(array_filter(
                                                preg_split('/\s*[,;\n]+\s*/', trim((string) $service->description)),
                                                function ($item) {
                                                    $item = trim((string) $item);
                                                    if ($item === '') {
                                                        return false;
                                                    }
                                                    if (preg_match('/^dan\s+sejenis/i', $item)) {
                                                        return false;
                                                    }
                                                    if (preg_match('/^(cocok|perawatan|pembersihan|khusus)\b/i', $item)) {
                                                        return false;
                                                    }
                                                    return str_word_count($item) <= 5;
                                                }
                                            ));
                                        @endphp
                                        @if(!empty($descriptionItems))
                                            <div class="service-description-chips">
                                                @foreach($descriptionItems as $item)
                                                    <span class="service-description-chip">{{ $item }}</span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="service-description-chip service-description-chip-empty">-</span>
                                        @endif
                                    @else
                                        <span class="service-description-chip service-description-chip-empty">-</span>
                                    @endif
                                    <div class="service-meta">
                                        <span class="service-price" data-default-price="{{ $effectivePrice }}">Rp {{ number_format($effectivePrice, 0, ',', '.') }}</span>
                                        @if(!is_null($adjustment))
                                        <span class="service-adjustment bg-warning text-dark">
                                            Adj {{ $adjustment >= 0 ? '+' : '-' }}Rp {{ number_format(abs($adjustment), 0, ',', '.') }}
                                        </span>
                                        @endif
                                        @if($filterType === 'addon')
                                        <span class="service-type service-type-{{ $addonTypeClass }}">
                                            Tipe {{ ucfirst($normalizedType) }}
                                        </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-4 order-1 order-lg-2">
                <div class="wash-card wash-checkout-card">
                    <div class="wash-card-header">
                        <h2 class="wash-card-title"><i class="fas fa-shopping-cart"></i> Rincian Transaksi</h2>
                    </div>
                    <div class="wash-card-body">
                        @if(($holidaySchedule['active'] ?? false) && !empty($holidaySchedule['start_date']) && !empty($holidaySchedule['end_date']))
                            <div class="alert alert-warning py-2 px-3 mb-3">
                                Harga hari raya aktif otomatis ({{ \Carbon\Carbon::parse($holidaySchedule['start_date'])->translatedFormat('d M Y') }} - {{ \Carbon\Carbon::parse($holidaySchedule['end_date'])->translatedFormat('d M Y') }})
                            </div>
                        @endif
                        <form id="checkoutForm" class="checkout-form">
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label for="vehicle_brand" class="wash-field-label">Merek</label>
                                    <select class="form-select wash-input" id="vehicle_brand" name="vehicle_brand">
                                        <option value="">Pilih</option>
                                        <optgroup label="Motor">
                                            @foreach($brands['Motor'] as $brand)
                                                <option value="{{ $brand }}">{{ $brand }}</option>
                                            @endforeach
                                        </optgroup>
                                        <optgroup label="Mobil">
                                            @foreach($brands['Mobil'] as $brand)
                                                <option value="{{ $brand }}">{{ $brand }}</option>
                                            @endforeach
                                        </optgroup>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label for="vehicle_plate" class="wash-field-label">Plat Nomor</label>
                                    <input type="text" class="form-control wash-input text-uppercase" id="vehicle_plate" name="vehicle_plate" list="vehiclePlateOptions" placeholder="B 1234 ABC">
                                    <datalist id="vehiclePlateOptions">
                                        @foreach($knownVehiclePlates ?? [] as $plateOption)
                                            <option value="{{ $plateOption }}"></option>
                                        @endforeach
                                    </datalist>
                                </div>
                                <div class="col-6">
                                    <label for="known_plate" class="wash-field-label">Plat Tersimpan</label>
                                    <select id="known_plate" class="form-select wash-input">
                                        <option value="">Pilih dari riwayat</option>
                                        @foreach($knownVehiclePlates ?? [] as $plateOption)
                                            <option value="{{ $plateOption }}">{{ $plateOption }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="customer_phone" class="wash-field-label">Nomor HP & Nama</label>
                                <div class="d-flex gap-2 mb-2">
                                    <input type="text" class="form-control wash-input" id="customer_phone" name="customer_phone" placeholder="0812...">
                                    <button class="btn wash-secondary-btn" type="button" id="btnCheckCustomer">Cek</button>
                                </div>
                                <input type="text" class="form-control wash-input" id="customer_name" name="customer_name" placeholder="Nama Pelanggan">
                                <small id="customerInfo" class="form-text mt-2 d-block"></small>
                            </div>

                            <div class="form-check mb-3 wash-inline-check">
                                <input class="form-check-input" type="checkbox" id="send_whatsapp">
                                <label class="form-check-label" for="send_whatsapp">Kirim nota via WhatsApp</label>
                            </div>

                            <div class="mb-3" id="voucherSection" style="display:none;">
                                <div class="wash-voucher-box">
                                    <div>
                                        <i class="fas fa-ticket-alt me-1"></i> Bonus cuci tersedia
                                        (<span id="voucherCount">0</span>)
                                    </div>
                                    <input type="checkbox" id="use_voucher" name="use_voucher">
                                </div>
                            </div>

                            <div id="cartItems" class="mb-3 custom-scrollbar">
                                <p class="text-center text-muted py-4 mb-0" id="emptyCartMsg">Keranjang masih kosong</p>
                            </div>

                            <div class="wash-summary-row wash-summary-divider">
                                <span>Subtotal</span>
                                <span id="subtotalAmount">Rp 0</span>
                            </div>
                            <div class="wash-summary-row wash-summary-total">
                                <span>Total Akhir</span>
                                <span id="totalAmount">Rp 0</span>
                            </div>

                            <div class="row g-2 mt-3">
                                <div class="col-6">
                                    <select class="form-select wash-input" id="payment_method">
                                        <option value="cash">💵 Tunai</option>
                                        <option value="qris">📱 QRIS</option>
                                        <option value="transfer">🏦 Transfer</option>
                                        <option value="edc">💳 EDC</option>
                                        <option value="kasbon">📜 Kasbon</option>
                                    </select>
                                </div>
                                <div class="col-6" id="cashSection">
                                    <input type="number" class="form-control wash-input" id="cash_amount" name="cash_amount" placeholder="Bayar Tunai..." oninput="calculateChange()">
                                </div>
                            </div>

                            <div id="changeDisplay" class="wash-change-box mt-2">
                                <span>Kembalian</span>
                                <strong id="changeAmount">Rp 0</strong>
                            </div>

                            <div id="kasbonSection" class="mt-3" style="display: none;">
                                <div class="card card-body bg-light">
                                    <h6 class="fw-bold text-primary mb-3"><i class="fas fa-user-tie me-1"></i> Detail Kasbon</h6>
                                    <div class="mb-3">
                                        <label class="wash-field-label">Tipe Pihak Kasbon</label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="kasbon_type" id="kasbon_employee" value="employee" checked>
                                            <label class="form-check-label" for="kasbon_employee">Karyawan (Daftar Akun)</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="kasbon_type" id="kasbon_outsider" value="outsider">
                                            <label class="form-check-label" for="kasbon_outsider">Orang Luar / Nama Custom</label>
                                        </div>
                                    </div>
                                    <div id="kasbon_employee_section">
                                        <label for="kasbon_user_id" class="wash-field-label">Pilih Karyawan</label>
                                        <select class="form-select wash-input" id="kasbon_user_id" name="kasbon_user_id">
                                            <option value="">-- Pilih Karyawan --</option>
                                            @foreach($allUsers as $user)
                                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div id="kasbon_outsider_section" style="display: none;">
                                        <label for="kasbon_name" class="wash-field-label">Nama Pihak Kasbon</label>
                                        <input type="text" class="form-control wash-input" id="kasbon_name" name="kasbon_name" placeholder="Masukkan nama...">
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn wash-primary-btn w-100 mt-3" id="btnCheckout" disabled>Proses Pembayaran</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="loyaltyBonusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Bonus Loyalty</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                Selamat! anda sudah mencapai bonus cuci dan mendapatkan gratis 1 layanan.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="btnLoyaltyBonusOk">OK</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const RULE_INTERACTION_LOCK_MS = 500;
    const CARD_ADD_LOCK_MS = 320;

    const markCardRuleInteraction = function (card) {
        if (!card) {
            return;
        }
        card.dataset.ruleInteractedAt = String(Date.now());
    };

    const shouldBlockCardAdd = function (card) {
        const now = Date.now();
        const lastRuleInteraction = parseInt(card?.dataset?.ruleInteractedAt || '0', 10) || 0;
        const lastAddAt = parseInt(card?.dataset?.lastAddAt || '0', 10) || 0;
        if (lastRuleInteraction > 0 && (now - lastRuleInteraction) < RULE_INTERACTION_LOCK_MS) {
            return true;
        }
        if (lastAddAt > 0 && (now - lastAddAt) < CARD_ADD_LOCK_MS) {
            return true;
        }
        return false;
    };

    const updateCardDisplayedPrice = function (card, value) {
        const priceEl = card.querySelector('.service-price');
        if (priceEl) {
            priceEl.textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(value || 0);
        }
    };

    const syncCardRuleSelection = function (card, sourceEl) {
        const selectedValue = String(sourceEl?.value || '');
        const selectedLabel = sourceEl?.dataset?.label || '';
        const selectedPrice = parseFloat(sourceEl?.dataset?.price || 0);
        const selectEl = card.querySelector('.service-rule-select');
        if (selectEl) {
            selectEl.value = selectedValue;
        }
        card.querySelectorAll('.service-rule-mobile-input').forEach(function (radioEl) {
            radioEl.checked = String(radioEl.value) === selectedValue;
        });
        updateCardDisplayedPrice(card, selectedPrice);
        return {
            id: parseInt(selectedValue || 0),
            label: selectedLabel,
            price: selectedPrice,
        };
    };

    const getCardSelectedRule = function (card, rules) {
        const selectedRadio = card.querySelector('.service-rule-mobile-input:checked');
        if (selectedRadio) {
            return syncCardRuleSelection(card, selectedRadio);
        }
        const selectEl = card.querySelector('.service-rule-select');
        if (selectEl) {
            const selected = selectEl.options[selectEl.selectedIndex];
            return syncCardRuleSelection(card, selected);
        }
        return rules[0] || null;
    };

    document.querySelectorAll('.service-rule-select').forEach(function (selectEl) {
        ['click', 'mousedown', 'touchstart'].forEach(function (evt) {
            selectEl.addEventListener(evt, function (event) {
                event.stopPropagation();
                markCardRuleInteraction(this.closest('.service-card'));
            });
        });
        selectEl.addEventListener('change', function (event) {
            event.stopPropagation();
            const card = this.closest('.service-card');
            if (!card) {
                return;
            }
            markCardRuleInteraction(card);
            const option = this.options[this.selectedIndex];
            syncCardRuleSelection(card, option);
        });
    });

    document.querySelectorAll('.service-rule-mobile-input').forEach(function (radioEl) {
        ['click', 'mousedown', 'touchstart'].forEach(function (evt) {
            radioEl.addEventListener(evt, function (event) {
                event.stopPropagation();
                markCardRuleInteraction(this.closest('.service-card'));
            });
        });
        radioEl.addEventListener('change', function (event) {
            event.stopPropagation();
            const card = this.closest('.service-card');
            if (!card) {
                return;
            }
            markCardRuleInteraction(card);
            syncCardRuleSelection(card, this);
        });
    });

    document.querySelectorAll('.service-rule-mobile-option, .service-rule-mobile-chip').forEach(function (el) {
        ['click', 'mousedown', 'touchstart', 'pointerdown'].forEach(function (evt) {
            el.addEventListener(evt, function (event) {
                event.stopPropagation();
                markCardRuleInteraction(this.closest('.service-card'));
            });
        });
    });

    document.querySelectorAll('.service-card').forEach(function (el) {
        el.addEventListener('click', function () {
            if (shouldBlockCardAdd(this)) {
                return;
            }
            const id = parseInt(this.dataset.id);
            const name = this.dataset.name;
            const price = parseFloat(this.dataset.price);
            const type = this.dataset.vehicletype;
            const rules = JSON.parse(this.dataset.rules || '[]');
            if (Array.isArray(rules) && rules.length > 0) {
                const chosenRule = getCardSelectedRule(this, rules);
                if (!chosenRule) {
                    return;
                }
                this.dataset.lastAddAt = String(Date.now());
                addToCart(id, name, parseFloat(chosenRule.price || 0), type, chosenRule);
                return;
            }
            this.dataset.lastAddAt = String(Date.now());
            addToCart(id, name, price, type, null);
        });
    });
    const timeEl = document.getElementById('current-time');
    if (timeEl) {
        const updateTime = () => {
            timeEl.textContent = new Date().toLocaleString('id-ID', {
                weekday: 'short',
                day: '2-digit',
                month: 'short',
                hour: '2-digit',
                minute: '2-digit'
            });
        };
        updateTime();
        setInterval(updateTime, 60000);
    }
});
</script>
@endpush

<script>
    document.getElementById('btnCheckCustomer').addEventListener('click', function() {
        const phone = document.getElementById('customer_phone').value;
        const vehiclePlate = document.getElementById('vehicle_plate').value;
        const customerName = document.getElementById('customer_name').value;
        if (!vehiclePlate) {
            alert('Isi plat kendaraan untuk cek bonus cuci');
            return;
        }
        const params = new URLSearchParams({
            phone: phone || '',
            vehicle_plate: vehiclePlate || '',
            customer_name: customerName || ''
        });
        fetch(`{{ route('wash.customer.check') }}?${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                const info = document.getElementById('customerInfo');
                const voucherSection = document.getElementById('voucherSection');
                const nameInput = document.getElementById('customer_name');
                const voucherCount = document.getElementById('voucherCount');
                const basisMap = {
                    plate: 'plat kendaraan'
                };
                const basis = basisMap[data.loyalty_basis] || 'data pelanggan';

                if (data.found) {
                    if (data.name && !nameInput.value) {
                        nameInput.value = data.name;
                    }
                    info.innerHTML = `<span class="text-success">Riwayat ${basis}: ${data.visit_count}x cuci, bonus dalam ${data.next_bonus_in}x lagi.</span>`;
                    
                    if (data.free_wash_eligibility > 0) {
                        voucherSection.style.display = 'block';
                        voucherCount.textContent = data.free_wash_eligibility;
                    } else {
                        voucherSection.style.display = 'none';
                    }
                } else {
                    info.innerHTML = `<span class="text-warning">Belum ada riwayat, bonus akan didapat pada cuci ke-${data.loyalty_target}.</span>`;
                    voucherSection.style.display = 'none';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Gagal cek riwayat pelanggan');
            });
    });

    const knownPlateSelect = document.getElementById('known_plate');
    if (knownPlateSelect) {
        knownPlateSelect.addEventListener('change', function () {
            if (!this.value) {
                return;
            }
            const plateInput = document.getElementById('vehicle_plate');
            plateInput.value = this.value;
            document.getElementById('btnCheckCustomer').click();
        });
    }

    document.getElementById('use_voucher').addEventListener('change', function() {
        updateCartUI();
    });

    const paymentMethodSelect = document.getElementById('payment_method');
    const cashSection = document.getElementById('cashSection');
    const kasbonSection = document.getElementById('kasbonSection');
    const kasbonTypeRadios = document.querySelectorAll('input[name="kasbon_type"]');
    const kasbonEmployeeSection = document.getElementById('kasbon_employee_section');
    const kasbonOutsiderSection = document.getElementById('kasbon_outsider_section');

    paymentMethodSelect.addEventListener('change', function() {
        const method = this.value;
        if (method === 'cash') {
            cashSection.style.display = 'block';
            kasbonSection.style.display = 'none';
        } else if (method === 'kasbon') {
            cashSection.style.display = 'none';
            kasbonSection.style.display = 'block';
        } else {
            cashSection.style.display = 'none';
            kasbonSection.style.display = 'none';
        }
        calculateChange();
    });

    kasbonTypeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'employee') {
                kasbonEmployeeSection.style.display = 'block';
                kasbonOutsiderSection.style.display = 'none';
            } else {
                kasbonEmployeeSection.style.display = 'none';
                kasbonOutsiderSection.style.display = 'block';
            }
        });
    });

    let cart = [];
    const employees = @json($employees ?? []);

    function isMobileDevice() {
        const ua = navigator.userAgent || navigator.vendor || '';
        return /Android|iPhone|iPad|iPod|IEMobile|Opera Mini/i.test(ua) || window.innerWidth <= 768;
    }

    const sendWhatsappCheckbox = document.getElementById('send_whatsapp');
    if (sendWhatsappCheckbox && isMobileDevice()) {
        sendWhatsappCheckbox.checked = true;
    }

    function withAutoPrint(url) {
        try {
            const absoluteUrl = new URL(url, window.location.origin);
            absoluteUrl.searchParams.set('autoprint', '1');
            absoluteUrl.searchParams.set('source', 'pos-wash-mobile');
            return absoluteUrl.toString();
        } catch (error) {
            const separator = url.includes('?') ? '&' : '?';
            return `${url}${separator}autoprint=1&source=pos-wash-mobile`;
        }
    }

    function formatRupiah(value) {
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(value || 0);
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    async function buildWashReceiptBlob(transactionId, payload) {
        if (typeof html2canvas === 'undefined') {
            return null;
        }

        const capture = document.createElement('div');
        capture.style.position = 'fixed';
        capture.style.left = '-10000px';
        capture.style.top = '0';
        capture.style.width = '360px';
        capture.style.background = '#ffffff';
        capture.style.color = '#111827';
        capture.style.padding = '14px';
        capture.style.fontFamily = 'Arial, sans-serif';
        capture.style.fontSize = '12px';
        capture.style.lineHeight = '1.4';
        capture.style.border = '1px solid #e5e7eb';
        const itemsHtml = payload.items.map((item) => `
            <tr>
                <td style="padding:4px 0;border-bottom:1px dashed #d1d5db;">${escapeHtml(item.name)}</td>
                <td style="padding:4px 0;text-align:center;border-bottom:1px dashed #d1d5db;">${item.quantity}</td>
                <td style="padding:4px 0;text-align:right;border-bottom:1px dashed #d1d5db;">${formatRupiah(item.price)}</td>
                <td style="padding:4px 0;text-align:right;border-bottom:1px dashed #d1d5db;">${formatRupiah(item.subtotal)}</td>
            </tr>
        `).join('');

        capture.innerHTML = `
            <div style="text-align:center;font-weight:700;font-size:14px;">${escapeHtml(@json(config('app.name')))}</div>
            <div style="text-align:center;color:#4b5563;">Detail Transaksi Wash</div>
            <div style="margin-top:8px;">No. Transaksi: #${escapeHtml(transactionId)}</div>
            <div>Tanggal: ${escapeHtml(new Date().toLocaleString('id-ID'))}</div>
            <div>Pelanggan: ${escapeHtml(payload.customerName || '-')}</div>
            <div>Kendaraan: ${escapeHtml(payload.vehicleBrand || '-')} - ${escapeHtml(payload.vehiclePlate || '-')}</div>
            <div style="margin-top:8px;">
                <table style="width:100%;border-collapse:collapse;">
                    <thead>
                        <tr>
                            <th style="text-align:left;padding:4px 0;border-bottom:1px solid #111827;">Item</th>
                            <th style="text-align:center;padding:4px 0;border-bottom:1px solid #111827;">Qty</th>
                            <th style="text-align:right;padding:4px 0;border-bottom:1px solid #111827;">Harga</th>
                            <th style="text-align:right;padding:4px 0;border-bottom:1px solid #111827;">Total</th>
                        </tr>
                    </thead>
                    <tbody>${itemsHtml}</tbody>
                </table>
            </div>
            <div style="margin-top:8px;display:flex;justify-content:space-between;">
                <span>Metode Bayar</span>
                <strong>${escapeHtml((payload.paymentMethod || '').toUpperCase())}</strong>
            </div>
            <div style="margin-top:4px;display:flex;justify-content:space-between;font-size:14px;">
                <span>Total</span>
                <strong>${formatRupiah(payload.total)}</strong>
            </div>
        `;

        document.body.appendChild(capture);
        try {
            const canvas = await html2canvas(capture, { scale: 2, backgroundColor: '#ffffff' });
            return await new Promise((resolve) => canvas.toBlob(resolve, 'image/png'));
        } finally {
            capture.remove();
        }
    }

    async function sendWashWhatsappReceipt(transactionId, phone, payload) {
        const formData = new FormData();
        formData.append('phone', phone);
        try {
            const blob = await buildWashReceiptBlob(transactionId, payload);
            if (blob) {
                formData.append('receipt_image', blob, `struk-wash-${transactionId}.png`);
            }
        } catch (error) {
            console.error(error);
        }

        return fetch(`{{ url('wash/transactions') }}/${transactionId}/whatsapp-receipt`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: formData
        });
    }

    function addToCart(id, name, price, type, selectedRule = null) {
        id = parseInt(id);
        const ruleId = selectedRule ? parseInt(selectedRule.id) : null;
        const cartKey = `${id}::${ruleId || 'base'}`;
        const displayName = selectedRule ? `${name} (${selectedRule.label})` : name;
        const existingItem = cart.find(item => item.key === cartKey);
        if (existingItem) {
            existingItem.quantity++;
        } else {
            cart.push({
                key: cartKey,
                id,
                rule_id: ruleId,
                name: displayName,
                base_name: name,
                rule_label: selectedRule ? selectedRule.label : null,
                price,
                type,
                quantity: 1,
            });
        }
        updateCartUI();
        filterBrands(cart.length > 0 ? 'all' : type);
    }

    function filterBrands(type) {
        const brandSelect = document.getElementById('vehicle_brand');
        const options = brandSelect.options;
        let typeLower = type.toLowerCase();
        
        // Map database types to optgroup labels
        if (typeLower === 'car') {
            typeLower = 'mobil';
        } else if (typeLower === 'motor') {
            typeLower = 'motor';
        }

        if (!['mobil', 'motor'].includes(typeLower)) {
            typeLower = 'all';
        }

        for (let i = 0; i < options.length; i++) {
            const opt = options[i];
            const parent = opt.parentElement;
            if (parent.tagName === 'OPTGROUP') {
                if (typeLower === 'all' || parent.label.toLowerCase() === typeLower) {
                    parent.style.display = '';
                } else {
                    parent.style.display = 'none';
                }
            }
        }
        // Select the first valid option or reset
        brandSelect.value = "";
    }

    function removeFromCart(id) {
        id = parseInt(id);
        const index = cart.findIndex(item => item.id === id);
        if (index > -1) {
            cart.splice(index, 1);
        }
        updateCartUI();
        if (cart.length === 0) {
            resetServiceSelection();
        }
    }

    function removeFromCartByKey(key) {
        const index = cart.findIndex(item => item.key === key);
        if (index > -1) {
            cart.splice(index, 1);
        }
        updateCartUI();
        if (cart.length === 0) {
            resetServiceSelection();
        }
    }

    function resetServiceSelection() {
        // Only reset filters and service visibility, NOT customer data
        
        // Reset filters by clicking All button to trigger all UI logic
        const allBtn = document.querySelector('.filter-btn[data-filter="all"]');
        if (allBtn) {
            allBtn.click();
        }
        const serviceSearchInput = document.getElementById('serviceSearchInput');
        if (serviceSearchInput) {
            serviceSearchInput.value = '';
        }
        
        // Reset brand options visibility
        const brandSelect = document.getElementById('vehicle_brand');
        const options = brandSelect.options;
        for (let i = 0; i < options.length; i++) {
            if (options[i].parentElement.tagName === 'OPTGROUP') {
                options[i].parentElement.style.display = '';
            }
        }
    }

    function resetCart() {
        cart = [];
        document.getElementById('vehicle_brand').value = "";
        document.getElementById('vehicle_plate').value = "";
        document.getElementById('customer_name').value = "";
        document.getElementById('customer_phone').value = "";
        const sendWhatsapp = document.getElementById('send_whatsapp');
        if (sendWhatsapp) {
            sendWhatsapp.checked = false;
        }
        document.getElementById('cash_amount').value = "";
        document.getElementById('changeAmount').textContent = "Rp 0";
        document.getElementById('customerInfo').innerHTML = "";
        document.getElementById('voucherSection').style.display = 'none';
        document.getElementById('use_voucher').checked = false;
        
        resetServiceSelection();

        updateCartUI();
    }

    function updateCartUI() {
        const cartContainer = document.getElementById('cartItems');
        const emptyMsg = document.getElementById('emptyCartMsg');
        const totalEl = document.getElementById('totalAmount');
        const subtotalEl = document.getElementById('subtotalAmount');
        const btnCheckout = document.getElementById('btnCheckout');
        const voucherEl = document.getElementById('use_voucher');
        const useVoucher = voucherEl ? !!voucherEl.checked : false;
        
        cartContainer.innerHTML = '';
        let total = 0;
        let discount = 0;

        if (cart.length === 0) {
            if (emptyMsg) emptyMsg.style.display = 'block';
            if (btnCheckout) btnCheckout.disabled = true;
        } else {
            if (emptyMsg) emptyMsg.style.display = 'none';
            if (btnCheckout) btnCheckout.disabled = false;

            cart.forEach(item => {
                const itemTotal = item.price * item.quantity;
                total += itemTotal;
                
                const div = document.createElement('div');
                div.className = 'cart-item';
                const selectId = 'emp_sel_' + String(item.key || item.id).replace(/[^a-zA-Z0-9_]/g, '_');
                const empOptions = ['<option value=\"\">- Pegawai -</option>']
                    .concat(employees.map(e => `<option value=\"${e.id}\" ${item.employee_id==e.id?'selected':''}>${e.name}</option>`))
                    .join('');
                div.innerHTML = `
                    <div class="cart-item-left">
                        <div class="cart-item-name">${item.name}</div>
                        <small class="cart-item-meta">${item.quantity} x Rp ${item.price.toLocaleString('id-ID')}</small>
                        <div class="mt-2">
                            <select id="${selectId}" class="form-select form-select-sm wash-input">
                                ${empOptions}
                            </select>
                        </div>
                    </div>
                    <div class="cart-item-right">
                        <span class="cart-item-total">Rp ${itemTotal.toLocaleString('id-ID')}</span>
                        <button class="btn btn-sm text-danger p-0" type="button" onclick="removeFromCartByKey('${item.key}')"><i class="fas fa-trash"></i></button>
                    </div>
                `;
                setTimeout(() => {
                    const sel = document.getElementById(selectId);
                    if (sel) {
                        sel.addEventListener('change', function() {
                            item.employee_id = this.value ? parseInt(this.value) : null;
                        });
                    }
                }, 0);
                cartContainer.appendChild(div);
            });

            if (useVoucher && cart.length > 0) {
                discount = cart[0].price;
            }
        }

        const finalTotal = Math.max(0, total - discount);
        if (subtotalEl) subtotalEl.textContent = 'Rp ' + total.toLocaleString('id-ID');
        
        if (discount > 0) {
             totalEl.innerHTML = `<small class="text-decoration-line-through text-muted">Rp ${total.toLocaleString('id-ID')}</small> <span class="text-success fw-bold">Rp ${finalTotal.toLocaleString('id-ID')}</span>`;
        } else {
             totalEl.textContent = 'Rp ' + finalTotal.toLocaleString('id-ID');
        }
        
        if (totalEl) totalEl.dataset.amount = finalTotal; // Store numeric value
        calculateChange();
    }

    function calculateChange() {
        const totalEl = document.getElementById('totalAmount');
        const total = parseInt(totalEl?.dataset?.amount || 0) || 0;
        const method = document.getElementById('payment_method')?.value || 'cash';
        const cashInput = document.getElementById('cash_amount')?.value || 0;
        const cash = parseInt(cashInput) || 0;
        const changeEl = document.getElementById('changeAmount');
        const cashSection = document.getElementById('cashSection');
        const changeBox = document.getElementById('changeDisplay');

        if (cashSection) cashSection.style.display = (method === 'cash') ? '' : 'none';

        if (method === 'cash' && cash >= total) {
            if (changeEl) changeEl.textContent = 'Rp ' + (cash - total).toLocaleString('id-ID');
            if (changeBox) changeBox.style.display = '';
        } else {
            if (changeEl) changeEl.textContent = 'Rp 0';
            if (changeBox) changeBox.style.display = method === 'cash' ? '' : 'none';
        }
    }

    const serviceSearchInput = document.getElementById('serviceSearchInput');
    const applyServiceVisibility = function () {
        const activeBtn = document.querySelector('.filter-btn.active');
        const type = activeBtn ? activeBtn.getAttribute('data-filter') : 'all';
        const keyword = (serviceSearchInput?.value || '').trim().toLowerCase();

        document.querySelectorAll('.service-item').forEach(item => {
            const typeMatch = type === 'all' || item.dataset.type === type;
            const card = item.querySelector('.service-card');
            const rawName = (card?.dataset?.name || '').toLowerCase();
            const desc = (card?.dataset?.description || '').toLowerCase();
            const textMatch = keyword === '' || rawName.includes(keyword) || desc.includes(keyword);
            item.style.display = (typeMatch && textMatch) ? 'block' : 'none';
        });

        document.querySelectorAll('.service-section-heading').forEach(section => {
            const sectionType = section.dataset.type;
            const hasVisibleItems = Array.from(document.querySelectorAll('.service-item[data-type="' + sectionType + '"]'))
                .some(item => item.style.display !== 'none');
            section.style.display = hasVisibleItems ? 'block' : 'none';
        });
    };

    document.querySelectorAll('.filter-btn').forEach(button => {
        button.addEventListener('click', function () {
            document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            const type = this.getAttribute('data-filter');
            if (cart.length === 0) {
                filterBrands(type);
            }
            applyServiceVisibility();
        });
    });

    if (serviceSearchInput) {
        serviceSearchInput.addEventListener('input', applyServiceVisibility);
    }

    // Toggle cash section on payment method change
    const paymentMethodEl = document.getElementById('payment_method');
    if (paymentMethodEl) {
        paymentMethodEl.addEventListener('change', calculateChange);
        // Initialize visibility
        calculateChange();
    }
    function showLoyaltyBonusPopup() {
        return new Promise((resolve) => {
            const modalEl = document.getElementById('loyaltyBonusModal');
            const okBtn = document.getElementById('btnLoyaltyBonusOk');
            if (!modalEl || !okBtn || typeof bootstrap === 'undefined' || !bootstrap.Modal) {
                alert('Selamat! Bonus cuci ke-10 otomatis diterapkan pada transaksi ini.');
                resolve();
                return;
            }
            const bonusModal = bootstrap.Modal.getOrCreateInstance(modalEl);
            const handleResolve = () => {
                okBtn.removeEventListener('click', handleOk);
                modalEl.removeEventListener('hidden.bs.modal', handleResolve);
                resolve();
            };
            const handleOk = () => {
                bonusModal.hide();
            };
            okBtn.addEventListener('click', handleOk);
            modalEl.addEventListener('hidden.bs.modal', handleResolve, { once: true });
            bonusModal.show();
        });
    }
    document.getElementById('checkoutForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (cart.length === 0) return;

        const method = document.getElementById('payment_method')?.value || 'cash';
        const cashInput = document.getElementById('cash_amount')?.value || 0;
        const cash = parseInt(cashInput) || 0;
        const sendWhatsapp = !!document.getElementById('send_whatsapp')?.checked;
        const phone = document.getElementById('customer_phone').value;

        if (sendWhatsapp && !phone) {
            alert('Isi nomor WhatsApp pelanggan terlebih dahulu.');
            return;
        }

        /* if (cash < total) {
             alert('Insufficient cash!');
             return;
        } */ // Allow credit/later payment if needed, or enforce. Let's enforce for POS.
        
        const data = {
            items: cart,
            payment_method: method,
            cash_amount: method === 'cash' ? cash : null,
            customer_name: document.getElementById('customer_name').value,
            customer_phone: document.getElementById('customer_phone').value,
            use_voucher: document.getElementById('use_voucher').checked,
            vehicle_plate: document.getElementById('vehicle_plate').value,
            vehicle_brand: document.getElementById('vehicle_brand').value
        };

        if (method === 'kasbon') {
            const kasbonType = document.querySelector('input[name="kasbon_type"]:checked')?.value;
            data.kasbon_type = kasbonType;
            if (kasbonType === 'employee') {
                data.kasbon_user_id = document.getElementById('kasbon_user_id')?.value;
            } else if (kasbonType === 'outsider') {
                data.kasbon_name = document.getElementById('kasbon_name')?.value;
            }
        }
        
        const btn = document.getElementById('btnCheckout');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Memproses...';

        fetch('{{ route("wash.transactions.store") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(data)
        })
        .then(async response => {
            let responseText = '';
            try {
                responseText = await response.text();
                if (!response.ok) {
                    let errorMsg = 'Respons jaringan tidak valid';
                    try {
                        const errorData = JSON.parse(responseText);
                        errorMsg = errorData.message || errorMsg;
                    } catch (e) {
                        // If not JSON, use first 200 chars of response text
                        errorMsg = responseText.substring(0, 200);
                    }
                    throw new Error(errorMsg);
                }
                return JSON.parse(responseText);
            } catch (e) {
                if (e instanceof SyntaxError) {
                    // JSON parse error
                    throw new Error('Respons server tidak valid: ' + (responseText.substring(0, 200) || 'Tidak ada data'));
                }
                throw e;
            }
        })
        .then(async data => {
            if (data.success) {
                const url = data.receipt_url ? data.receipt_url : ('{{ url("wash/transactions") }}/' + data.transaction_id + '/receipt');
                const openReceipt = () => {
                    if (isMobileDevice()) {
                        window.location.href = withAutoPrint(url);
                    } else {
                        window.open(url, '_blank', 'width=400,height=600');
                    }
                };
                if (data.discount_type === 'loyalty') {
                    await showLoyaltyBonusPopup();
                }
                openReceipt();
                const resolvedPhone = data.customer_phone || phone;
                if (sendWhatsapp && resolvedPhone && !data.wa_sent) {
                    await sendWashWhatsappReceipt(data.transaction_id, resolvedPhone, {
                        items: cart.map((item) => ({
                            name: item.name,
                            quantity: item.quantity,
                            price: item.price,
                            subtotal: item.price * item.quantity
                        })),
                        total: parseInt(document.getElementById('totalAmount')?.dataset?.amount || 0) || 0,
                        customerName: document.getElementById('customer_name')?.value || '',
                        vehicleBrand: document.getElementById('vehicle_brand')?.value || '',
                        vehiclePlate: document.getElementById('vehicle_plate')?.value || '',
                        paymentMethod: method
                    }).catch(() => {});
                }
                resetCart();
            } else {
                alert('Kesalahan: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan: ' + error.message);
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    });
</script>

<style>
    .wash-pos-page {
        color: #1e293b;
    }

    .wash-pos-shell {
        max-width: 1440px;
        margin: 0 auto;
        padding: 1.5rem 1rem;
    }

    .wash-pos-header {
        display: flex;
        align-items: flex-start;
        justify-content: flex-end;
        gap: 1rem;
        margin-bottom: 1.1rem;
    }

    .wash-pos-title {
        margin: 0;
        font-size: 1.6rem;
        font-weight: 700;
        color: #0f172a;
    }

    .wash-pos-subtitle {
        margin: 0.25rem 0 0;
        font-size: 0.9rem;
        color: #64748b;
    }

    .wash-current-time {
        display: inline-flex;
        align-items: center;
        min-height: 42px;
        min-width: 170px;
        justify-content: center;
        padding: 0.45rem 0.9rem;
        border-radius: 0.75rem;
        border: 1px solid #e2e8f0;
        background: #fff;
        font-size: 0.85rem;
        font-weight: 600;
        color: #334155;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.05);
    }

    .wash-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 1rem;
        box-shadow: 0 10px 26px rgba(15, 23, 42, 0.06);
        overflow: hidden;
    }

    .wash-checkout-card {
        position: sticky;
        top: 1.2rem;
    }

    .wash-card-header {
        padding: 1rem 1.1rem;
        background: linear-gradient(180deg, rgb(58 126 232 / 22%) 0%, rgb(231 236 255 / 30%) 100%);
        border-bottom-color: rgb(22 22 23 / 98%);
        display: flex;
        gap: 0.8rem;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
    }

    .wash-card-title {
        margin: 0;
        font-size: 1rem;
        font-weight: 700;
        color: #334155;
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
    }

    .wash-card-title i {
        color: #3b82f6;
    }

    .wash-card-body {
        padding: 1rem;
    }

    .wash-filter-group {
        background: #e2e8f0;
        border-radius: 0.8rem;
        padding: 0.2rem;
        display: inline-flex;
        gap: 0.25rem;
    }

    #serviceSearchInput {
        min-width: 220px;
    }

    .wash-filter-group .filter-btn {
        border: 0;
        background: transparent;
        color: #475569;
        border-radius: 0.6rem;
        font-size: 0.82rem;
        font-weight: 600;
        padding: 0.35rem 0.85rem;
        transition: all 0.2s ease;
    }

    .wash-filter-group .filter-btn.active {
        background: #fff;
        color: #2563eb;
        box-shadow: 0 1px 4px rgba(15, 23, 42, 0.12);
    }

    .wash-filter-group .filter-btn:focus-visible {
        outline: 0;
        box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25);
    }

    .service-card {
        height: 100%;
        border: 1px solid #e2e8f0;
        border-radius: 1rem;
        padding: 0.8rem;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
        cursor: pointer;
        background: #fff;
    }

    .service-card:hover {
        border-color: #60a5fa;
        box-shadow: 0 10px 18px rgba(37, 99, 235, 0.14);
    }

    .service-card:active {
        transform: none;
    }

    .service-card-mobil {
        border-color: #bfdbfe;
    }

    .service-card-motor {
        border-color: #fed7aa;
    }

    .service-card-caffe {
        border-color: #f5d0a7;
    }

    .service-card-addon {
        border-color: #bbf7d0;
    }

    .service-section-heading h6 {
        font-size: 0.82rem;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: #64748b;
        border-bottom: 1px dashed #cbd5e1;
        padding-bottom: 0.32rem;
    }

    .service-image-wrap {
        aspect-ratio: 1 / 1;
        border-radius: 0.8rem;
        background: #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        margin-bottom: 0.75rem;
    }

    .service-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform .25s ease;
    }

    .service-fallback-icon {
        width: 52px;
        height: 52px;
        border-radius: 999px;
        background: #e2e8f0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        color: #64748b;
        transition: transform .25s ease;
    }

    .service-title {
        font-size: 0.94rem;
        font-weight: 700;
        line-height: 1.35;
        color: #1e293b;
        margin: 0;
    }

    .service-description {
        font-size: 0.75rem;
        color: #64748b;
        margin: 0.32rem 0 0;
        min-height: 2.2em;
    }

    .service-description-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 0.32rem;
        margin-top: 0.35rem;
        min-height: 1.8rem;
    }

    .service-description-chip {
        display: inline-flex;
        align-items: center;
        min-height: 26px;
        padding: 0.24rem 0.52rem;
        border-radius: 0.65rem;
        background: #eef2ff;
        border: 1px solid #c7d2fe;
        color: #3730a3;
        font-size: 0.68rem;
        line-height: 1.25;
        font-weight: 600;
    }

    .service-description-chip-empty {
        background: #f8fafc;
        border-color: #e2e8f0;
        color: #64748b;
        font-weight: 500;
    }

    .service-rule-picker .form-select {
        border-radius: 0.6rem;
        font-size: 0.76rem;
        padding-top: 0.3rem;
        padding-bottom: 0.3rem;
    }

    .service-rule-mobile-options {
        display: none;
        gap: 0.28rem;
        flex-wrap: wrap;
    }

    .service-rule-mobile-option {
        display: inline-flex;
        align-items: center;
        margin: 0;
    }

    .service-rule-mobile-input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .service-rule-mobile-chip {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        border: 1px solid #cbd5e1;
        background: #f8fafc;
        color: #334155;
        font-size: 0.64rem;
        font-weight: 700;
        padding: 0.14rem 0.45rem;
        line-height: 1.2;
    }

    .service-rule-mobile-input:checked + .service-rule-mobile-chip {
        background: #11bb4dff;
        border-color: #13b433ff;
        color: #ffffffff;
    }

    .service-meta {
        margin-top: 0.6rem;
        display: flex;
        align-items: flex-start;
        justify-content: flex-start;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .service-price {
        font-size: 0.9rem;
        font-weight: 700;
        color: #2563eb;
        width: 100%;
        line-height: 1.2;
    }

    .service-type {
        font-size: 0.68rem;
        font-weight: 700;
        color: #334155;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        background: #eff6ff;
        border-radius: 999px;
        padding: 0.2rem 0.55rem;
    }

    .service-type-mobil {
        background: #dbeafe;
        color: #1d4ed8;
    }

    .service-type-motor {
        background: #ffedd5;
        color: #c2410c;
    }

    .service-type-caffe {
        background: #fef3c7;
        color: #92400e;
    }

    .service-type-umum {
        background: #e2e8f0;
        color: #334155;
    }

    .service-adjustment {
        border: 1px solid rgba(180, 83, 9, 0.2);
    }

    .wash-field-label {
        display: block;
        margin-bottom: 0.35rem;
        font-size: 0.68rem;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.07em;
    }

    .wash-input {
        min-height: 42px;
        border-color: #e2e8f0;
        background: #f8fafc;
        border-radius: 0.7rem;
    }

    .wash-input::placeholder {
        color: #94a3b8;
    }

    .wash-input:focus {
        border-color: #60a5fa;
        box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.18);
        background: #fff;
    }

    .wash-secondary-btn {
        border-radius: 0.7rem;
        border: 1px solid #cbd5e1;
        background: #e2e8f0;
        color: #334155;
        min-width: 66px;
        font-weight: 600;
    }

    .wash-secondary-btn:hover {
        background: #cbd5e1;
    }

    .wash-secondary-btn:focus-visible {
        outline: 0;
        box-shadow: 0 0 0 0.2rem rgba(148, 163, 184, 0.35);
    }

    .wash-inline-check .form-check-input {
        margin-top: 0.2rem;
        border-color: #94a3b8;
    }

    .wash-inline-check .form-check-label {
        color: #475569;
        font-size: 0.84rem;
    }

    .wash-voucher-box {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.6rem;
        padding: 0.65rem 0.75rem;
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        border-radius: 0.75rem;
        color: #166534;
        font-size: 0.8rem;
        font-weight: 600;
    }

    #cartItems {
        border: 1px solid #e2e8f0;
        border-radius: 0.8rem;
        background: #fff;
        padding: 0.7rem;
        max-height: 230px;
        overflow-y: auto;
    }

    .custom-scrollbar::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 999px;
    }

    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }

    .cart-item {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.65rem;
        padding-bottom: 0.65rem;
        margin-bottom: 0.65rem;
        border-bottom: 1px solid #e2e8f0;
    }

    .cart-item:last-child {
        border-bottom: 0;
        margin-bottom: 0;
        padding-bottom: 0;
    }

    .cart-item-left {
        flex: 1 1 auto;
        min-width: 0;
    }

    .cart-item-name {
        font-weight: 700;
        color: #0f172a;
        font-size: 0.9rem;
    }

    .cart-item-meta {
        display: block;
        margin-top: 0.15rem;
        color: #64748b;
        font-size: 0.75rem;
    }

    .cart-item-right {
        flex: 0 0 auto;
        text-align: right;
    }

    .cart-item-total {
        display: inline-block;
        font-size: 0.82rem;
        font-weight: 700;
        color: #1e293b;
        margin-right: 0.4rem;
    }

    .wash-summary-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        color: #475569;
        font-size: 0.9rem;
    }

    .wash-summary-divider {
        padding-top: 0.65rem;
        border-top: 1px solid #e2e8f0;
    }

    .wash-summary-total {
        margin-top: 0.2rem;
        color: #0f172a;
        font-weight: 700;
        font-size: 1rem;
    }

    .wash-summary-total #totalAmount {
        color: #2563eb;
        font-size: 1.2rem;
        font-weight: 800;
    }

    .wash-change-box {
        border-radius: 0.75rem;
        border: 1px solid #bfdbfe;
        background: #eff6ff;
        padding: 0.55rem 0.75rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        color: #1e3a8a;
        font-size: 0.84rem;
    }

    .wash-primary-btn {
        min-height: 44px;
        border-radius: 0.8rem;
        background: linear-gradient(135deg, #16a34a, #22c55e);
        border: 0;
        color: #fff;
        font-weight: 700;
        letter-spacing: 0.01em;
    }

    .wash-primary-btn:hover {
        color: #fff;
        filter: brightness(1.03);
    }

    .wash-primary-btn:focus-visible {
        outline: 0;
        box-shadow: 0 0 0 0.24rem rgba(59, 130, 246, 0.3);
    }

    @media (max-width: 991.98px) {
        .wash-checkout-card {
            position: static;
        }
    }

    @media (max-width: 767.98px) {
        .wash-pos-shell {
            padding: 0.7rem 0.35rem 0.8rem;
        }

        .wash-pos-header {
            justify-content: flex-end;
            align-items: flex-end;
            margin-bottom: 1rem;
        }

        .wash-current-time {
            min-width: 0;
            width: fit-content;
        }

        .wash-card-header {
            padding: 0.72rem;
            gap: 0.55rem;
        }

        .wash-card-body {
            padding: 0.72rem;
        }

        .wash-filter-group {
            width: 100%;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
        }

        .wash-filter-group .filter-btn {
            width: 100%;
            padding-left: 0.2rem;
            padding-right: 0.2rem;
            min-height: 34px;
        }

        #serviceSearchInput {
            width: 100%;
            min-width: 0;
            font-size: 0.82rem;
            min-height: 34px;
        }

        .service-meta {
            gap: 0.35rem;
        }

        .service-price {
            font-size: 0.82rem;
        }

        .service-type {
            font-size: 0.62rem;
            letter-spacing: 0.02em;
            padding: 0.18rem 0.45rem;
            white-space: nowrap;
        }

        .cart-item {
            flex-direction: column;
            gap: 0.45rem;
        }

        .cart-item-right {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .wash-summary-row {
            font-size: 0.84rem;
        }

        .wash-summary-total {
            font-size: 1rem;
        }
    }

    @media (max-width: 767.98px) {
        .wash-filter-group {
            width: 100%;
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 0.2rem;
        }

        .wash-filter-group .filter-btn {
            width: 100%;
            padding: 0.3rem 0.15rem;
            min-height: 34px;
            font-size: 0.7rem;
        }
    }

    @media (max-width: 575.98px) {
        .wash-card {
            border-radius: 0.85rem;
        }

        .service-item {
            width: 50%;
        }

        .service-card {
            border-radius: 0.85rem;
            padding: 0.62rem;
        }

        .service-image-wrap {
            aspect-ratio: 1 / 1;
            margin-bottom: 0.5rem;
        }

        .service-title {
            font-size: 0.8rem;
            line-height: 1.2;
            margin-bottom: 0.3rem;
        }

        .service-rule-picker .form-select {
            font-size: 0.68rem;
            padding-top: 0.2rem;
            padding-bottom: 0.2rem;
            min-height: 30px;
        }

        .service-rule-picker .form-select {
            display: none;
        }

        .service-rule-mobile-options {
            display: flex;
        }

        .service-description-chips {
            min-height: 0;
            margin-top: 0.2rem;
            gap: 0.22rem;
        }

        .service-description-chip {
            font-size: 0.58rem;
            min-height: 20px;
            padding: 0.16rem 0.34rem;
        }

        .service-price {
            font-size: 0.76rem;
        }

        .service-type {
            font-size: 0.56rem;
            padding: 0.14rem 0.34rem;
        }

        .wash-inline-check {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.5rem;
        }

        #cartItems {
            max-height: 220px;
        }
        
        .wash-filter-group {
            grid-template-columns: repeat(5, 1fr);
        }
        
        .wash-filter-group .filter-btn {
            font-size: 0.65rem;
            padding: 0.25rem 0.1rem;
        }
    }

    [data-bs-theme="dark"] .wash-pos-page {
        color: #e2e8f0;
        background: linear-gradient(180deg, #0f172a 0%, #0b1228 100%);
        border-color: rgba(96, 165, 250, 0.28);
        border-radius: 1rem;
    }

    [data-bs-theme="dark"] .wash-pos-title {
        color: #f8fafc;
    }

    [data-bs-theme="dark"] .wash-pos-subtitle {
        color: #94a3b8;
    }

    [data-bs-theme="dark"] .wash-current-time,
    [data-bs-theme="dark"] .wash-card,
    [data-bs-theme="dark"] .service-card,
    [data-bs-theme="dark"] #cartItems {
        background: #0f172a;
        border-color: #334155;
        color: #e2e8f0;
    }

    [data-bs-theme="dark"] .wash-current-time {
        box-shadow: none;
        color: #cbd5e1;
        border-color: #334155;
    }

    [data-bs-theme="dark"] .wash-card {
        box-shadow: 0 14px 30px rgba(2, 6, 23, 0.45);
    }

    [data-bs-theme="dark"] .wash-card-header {
        background: linear-gradient(180deg, rgba(59, 130, 246, 0.22) 0%, rgba(15, 23, 42, 0.3) 100%);
        border-bottom-color: rgba(96, 165, 250, 0.28);
    }

    [data-bs-theme="dark"] .wash-card-title {
        color: #e2e8f0;
    }

    [data-bs-theme="dark"] .wash-card-title i {
        color: #93c5fd;
    }

    [data-bs-theme="dark"] .service-image-wrap {
        background: #1e293b;
    }

    [data-bs-theme="dark"] .service-card-mobil {
        border-color: rgba(96, 165, 250, 0.45);
    }

    [data-bs-theme="dark"] .service-card-motor {
        border-color: rgba(251, 146, 60, 0.45);
    }

    [data-bs-theme="dark"] .service-card-caffe {
        border-color: rgba(217, 119, 6, 0.45);
    }

    [data-bs-theme="dark"] .service-card-addon {
        border-color: rgba(74, 222, 128, 0.42);
    }

    [data-bs-theme="dark"] .service-rule-mobile-chip {
        background: #1e293b;
        border-color: #334155;
        color: #cbd5e1;
    }

    [data-bs-theme="dark"] .service-rule-mobile-input:checked + .service-rule-mobile-chip {
        background: rgba(14, 182, 45, 0.86);
        border-color: rgba(34, 197, 94, 0.55);
        color: #daeae0ff;
    }

    [data-bs-theme="dark"] .service-section-heading h6 {
        color: #94a3b8;
        border-bottom-color: #334155;
    }

    [data-bs-theme="dark"] .service-fallback-icon {
        background: #334155;
        color: #bfdbfe;
    }

    [data-bs-theme="dark"] .service-title,
    [data-bs-theme="dark"] .cart-item-name,
    [data-bs-theme="dark"] .cart-item-total {
        color: #f8fafc;
    }

    [data-bs-theme="dark"] .service-description,
    [data-bs-theme="dark"] .wash-field-label,
    [data-bs-theme="dark"] .wash-inline-check .form-check-label,
    [data-bs-theme="dark"] #customerInfo {
        color: #94a3b8;
    }

    [data-bs-theme="dark"] .service-description-chip {
        background: rgba(59, 130, 246, 0.2);
        border-color: rgba(96, 165, 250, 0.42);
        color: #bfdbfe;
    }

    [data-bs-theme="dark"] .service-description-chip-empty {
        background: #1e293b;
        border-color: #334155;
        color: #94a3b8;
    }

    [data-bs-theme="dark"] .service-price {
        color: #60a5fa;
    }

    [data-bs-theme="dark"] .wash-input {
        background: #0b1220;
        border-color: #334155;
        color: #e2e8f0;
    }

    [data-bs-theme="dark"] .wash-input::placeholder {
        color: #64748b;
    }

    [data-bs-theme="dark"] .wash-input option,
    [data-bs-theme="dark"] .wash-input optgroup {
        background: #0f172a;
        color: #e2e8f0;
    }

    [data-bs-theme="dark"] .wash-secondary-btn {
        background: #1e293b;
        border-color: #334155;
        color: #e2e8f0;
    }

    [data-bs-theme="dark"] .wash-secondary-btn:hover {
        background: #334155;
        border-color: #475569;
    }

    [data-bs-theme="dark"] .wash-filter-group {
        background: #1e293b;
    }

    [data-bs-theme="dark"] .wash-inline-check .form-check-input {
        background-color: #0b1220;
        border-color: #475569;
    }

    [data-bs-theme="dark"] .wash-inline-check .form-check-input:checked {
        background-color: #2563eb;
        border-color: #2563eb;
    }

    [data-bs-theme="dark"] .wash-inline-check .form-check-input:focus {
        box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.24);
    }

    [data-bs-theme="dark"] .wash-filter-group .filter-btn {
        color: #cbd5e1;
    }

    [data-bs-theme="dark"] .wash-filter-group .filter-btn.active {
        background: #0b1220;
        color: #93c5fd;
        box-shadow: inset 0 0 0 1px rgba(96, 165, 250, 0.32);
    }

    [data-bs-theme="dark"] .wash-voucher-box {
        background: rgba(22, 101, 52, 0.2);
        border-color: rgba(74, 222, 128, 0.35);
        color: #86efac;
    }

    [data-bs-theme="dark"] #cartItems .text-muted,
    [data-bs-theme="dark"] #emptyCartMsg {
        color: #94a3b8 !important;
    }

    [data-bs-theme="dark"] .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #475569;
    }

    [data-bs-theme="dark"] .wash-summary-row,
    [data-bs-theme="dark"] .cart-item-meta {
        color: #94a3b8;
    }

    [data-bs-theme="dark"] .cart-item {
        border-bottom-color: #334155;
    }

    [data-bs-theme="dark"] .wash-summary-divider {
        border-top-color: #334155;
    }

    [data-bs-theme="dark"] .wash-summary-total {
        color: #f8fafc;
    }

    [data-bs-theme="dark"] .wash-summary-total #totalAmount {
        color: #60a5fa;
    }

    [data-bs-theme="dark"] .service-type-mobil {
        background: rgba(30, 64, 175, 0.3);
        color: #93c5fd;
    }

    [data-bs-theme="dark"] .service-type-motor {
        background: rgba(154, 52, 18, 0.35);
        color: #fdba74;
    }

    [data-bs-theme="dark"] .service-type-caffe {
        background: rgba(146, 64, 14, 0.35);
        color: #fcd34d;
    }

    [data-bs-theme="dark"] .service-type-umum {
        background: #334155;
        color: #cbd5e1;
    }

    [data-bs-theme="dark"] .wash-change-box {
        background: rgba(30, 64, 175, 0.22);
        border-color: rgba(96, 165, 250, 0.35);
        color: #bfdbfe;
    }

    [data-bs-theme="dark"] .wash-primary-btn {
        background: linear-gradient(135deg, #15803d, #16a34a);
    }
</style>
@endsection
