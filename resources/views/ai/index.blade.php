@extends('layouts.app')

@section('title', __('Pusat AI'))

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-body"><i class="fa-solid fa-robot text-primary me-2"></i>{{ __('Pusat AI') }}</h1>
            <p class="text-body-secondary small mb-0">{{ __('Wawasan cerdas dan prediksi yang didukung oleh Analitik Tingkat Lanjut.') }}</p>
        </div>
        <div>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-2">
                <i class="fa-solid fa-circle-check me-1"></i> {{ __('Sistem Aktif') }}
            </span>
        </div>
    </div>

    {{-- Business Insights Row (New) --}}
    <div class="row g-4 mb-4">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm h-100 overflow-hidden position-relative">
                <div class="card-body position-relative z-1">
                    <div class="d-flex align-items-center mb-2">
                        <i class="fa-solid fa-lightbulb text-warning me-2"></i>
                        <h5 class="card-title mb-0">{{ __('Wawasan Bisnis') }}</h5>
                    </div>
                    <div class="row align-items-center">
                        <div class="col-md-10">
                            <p class="mb-1 opacity-75">{{ $businessInsights['insight_text'] }}</p>
                            <div class="d-flex gap-4 mt-3">
                                <div>
                                    <div class="small opacity-75">{{ __('Pertumbuhan Pendapatan') }}</div>
                                    <div class="h4 fw-bold mb-0">
                                        @if($businessInsights['revenue_growth'] > 0)
                                            <i class="fa-solid fa-arrow-trend-up text-white"></i> 
                                        @elseif($businessInsights['revenue_growth'] < 0)
                                            <i class="fa-solid fa-arrow-trend-down text-warning"></i>
                                        @endif
                                        {{ $businessInsights['revenue_growth'] }}%
                                    </div>
                                </div>
                                <div>
                                    <div class="small opacity-75">{{ __('Produk Terlaris') }}</div>
                                    <div class="h4 fw-bold mb-0">{{ $businessInsights['top_product'] }}</div>
                                </div>
                                <div>
                                    <div class="small opacity-75">{{ __('Pelanggan Setia') }}</div>
                                    <div class="h4 fw-bold mb-0">{{ $businessInsights['repeat_customers'] }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                {{-- Decorative background --}}
                <div class="position-absolute top-0 end-0 h-100 w-50 bg-primary opacity-10" style="transform: skewX(-20deg);"></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title mb-3"><i class="fa-solid fa-magnifying-glass-chart text-primary me-2"></i>{{ __('Analisis Cepat') }}</h5>
                    <ul class="list-group list-group-flush small">
                        @foreach($systemOverview['items'] as $item)
                            <li class="list-group-item bg-transparent px-0 py-2 d-flex justify-content-between">
                                {!! $item !!}
                            </li>
                        @endforeach
                    </ul>
                    <div class="mt-3 small text-body-secondary">
                        {!! $systemOverview['footer'] !!}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Metrics Grid --}}
    <div class="row g-4 mb-4">
        {{-- Network Health Card --}}
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="d-flex align-items-center">
                            <div class="icon-shape bg-info-subtle text-info rounded-3 p-3 me-3">
                                <i class="fa-solid fa-network-wired fa-lg"></i>
                            </div>
                            <h5 class="card-title mb-0">{{ __('Kesehatan Jaringan') }}</h5>
                        </div>
                        <span class="badge {{ $networkInsights['status'] == 'Sehat' ? 'bg-success' : ($networkInsights['status'] == 'Kritis' ? 'bg-danger' : 'bg-warning') }} rounded-pill">
                            {{ $networkInsights['status'] }}
                        </span>
                    </div>
                    
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <div class="p-2 rounded text-center">
                                <small class="text-body-secondary d-block">{{ __('Online') }}</small>
                                <span class="fw-bold text-body">{{ $networkInsights['devices_online'] ?? 0 }}/{{ $networkInsights['devices_total'] ?? 0 }}</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 rounded text-center">
                                <small class="text-body-secondary d-block">{{ __('Router CPU') }}</small>
                                <span class="fw-bold text-body">{{ $networkInsights['router_cpu'] !== null ? $networkInsights['router_cpu'] . '%' : 'N/A' }}</span>
                            </div>
                        </div>
                    </div>

                    <p class="text-body-secondary small mb-3">
                        {{ $networkInsights['message'] }}
                    </p>
                    
                    <div class="alert  border-start border-4 border-info small mb-0">
                        <i class="fa-solid fa-lightbulb text-warning me-1"></i> <strong>Saran AI:</strong> {{ $networkInsights['ai_tip'] }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Sales Forecast Card --}}
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="d-flex align-items-center">
                            <div class="icon-shape bg-success-subtle text-success rounded-3 p-3 me-3">
                                <i class="fa-solid fa-chart-line fa-lg"></i>
                            </div>
                            <h5 class="card-title mb-0">{{ __('Prediksi Penjualan') }}</h5>
                        </div>
                        <div class="text-end">
                            <span class="badge  text-body border">{{ __('Konf.') }} {{ $salesForecast['confidence'] }}%</span>
                        </div>
                    </div>

                    <div class="d-flex align-items-end mb-2">
                        <h2 class="mb-0 me-2">Rp {{ number_format($salesForecast['forecast_next_day'], 0, ',', '.') }}</h2>
                        <span class="badge {{ str_contains($salesForecast['trend'], 'Up') ? 'bg-success' : 'bg-secondary' }} mb-1">
                            {{ $salesForecast['trend'] }}
                        </span>
                    </div>
                    <p class="text-body-secondary small mb-3">
                        {{ __('Estimasi pendapatan besok menggunakan Regresi Linear.') }}
                    </p>

                    {{-- ApexChart Container --}}
                    <div id="salesForecastChart" style="min-height: 120px;"></div>
                </div>
            </div>
        </div>

        {{-- Smart Restock Card --}}
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="icon-shape bg-warning-subtle text-warning rounded-3 p-3 me-3">
                            <i class="fa-solid fa-boxes-stacked fa-lg"></i>
                        </div>
                        <h5 class="card-title mb-0">{{ __('Restock Pintar') }}</h5>
                    </div>

                    <div class="d-flex align-items-end mb-2">
                        <h2 class="mb-0 me-2">{{ count($restockSuggestions) }}</h2>
                        <span class="text-body-secondary small mb-1">{{ __('Item perlu perhatian') }}</span>
                    </div>
                    <p class="text-body-secondary small mb-3">
                        {{ __('Produk dengan penjualan tinggi dan stok menipis.') }}
                    </p>

                    <a href="#restockTable" class="btn btn-sm btn-outline-warning w-100">
                        {{ __('Lihat Rekomendasi') }} <i class="fa-solid fa-arrow-down ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Detailed Sections --}}
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm mb-4" id="restockTable">
                <div class="card-header bg-transparent border-0 py-3">
                    <h5 class="mb-0"><i class="fa-solid fa-list-check text-primary me-2"></i>{{ __('Rekomendasi Restock') }}</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="">
                            <tr>
                                <th class="ps-4">{{ __('Nama Produk') }}</th>
                                <th class="text-center">{{ __('Stok Saat Ini') }}</th>
                                <th class="text-center">{{ __('Est. Habis') }}</th>
                                <th class="text-center">{{ __('Saran Tambah') }}</th>
                                <th>{{ __('Analisis AI') }}</th>
                                <th class="text-end pe-4">{{ __('Aksi') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($restockSuggestions as $item)
                            <tr>
                                <td class="ps-4 fw-medium">
                                    {{ $item['product_name'] }}
                                    @if($item['days_until_stockout'] < 3)
                                        <span class="badge bg-danger ms-1">{{ __('Penting') }}</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-danger-subtle text-danger">{{ $item['current_stock'] }}</span>
                                </td>
                                <td class="text-center text-danger fw-bold">
                                    {{ $item['days_until_stockout'] }} {{ __('hari') }}
                                </td>
                                <td class="text-center fw-bold text-success">+{{ $item['recommended_restock'] }}</td>
                                <td class="text-body-secondary small">
                                    <i class="fa-solid fa-robot text-body-secondary me-1"></i> {{ $item['reason'] }}
                                    <i class="fa-regular fa-circle-question text-primary ms-1" data-bs-toggle="tooltip" title="{{ __('Berdasarkan rata-rata tertimbang kecepatan penjualan.') }}"></i>
                                </td>
                                <td class="text-end pe-4">
                                    <a href="{{ route('atk.products.edit', $item['product_id']) }}" class="btn btn-sm btn-light border">
                                        {{ __('Kelola') }}
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-body-secondary">
                                    <i class="fa-solid fa-check-circle fa-2x mb-3 text-success"></i>
                                    <p class="mb-0">{{ __('Tidak ada restock mendesak berdasarkan kecepatan penjualan saat ini.') }}</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- AI Chat Widget --}}
    <div id="ai-chat-widget" class="position-fixed bottom-0 end-0 p-4" style="z-index: 1050;">
        {{-- Chat Button --}}
        <button id="chat-toggle-btn" class="btn btn-primary rounded-circle shadow-lg p-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;" onclick="toggleChat()">
            <i class="fa-solid fa-robot fa-xl"></i>
        </button>

        {{-- Chat Box --}}
        <div id="chat-box" class="card shadow-lg border-0 d-none" style="width: 350px; height: 500px; position: absolute; bottom: 90px; right: 24px; border-radius: 15px;">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center py-3" style="border-top-left-radius: 15px; border-top-right-radius: 15px;">
                <h6 class="mb-0"><i class="fa-solid fa-robot me-2"></i>Asisten AI</h6>
                <button type="button" class="btn-close btn-close-white btn-sm" onclick="toggleChat()"></button>
            </div>
            <div class="card-body  overflow-auto p-3" id="chat-messages" style="height: 380px;">
                {{-- Welcome Message --}}
                <div class="d-flex flex-column align-items-start mb-3">
                    <div class="bg-body p-3 rounded shadow-sm text-body" style="max-width: 85%; border-bottom-left-radius: 0;">
                        Halo! 👋 Saya Asisten AI Anda. Tanyakan tentang:
                        <ul class="mb-0 ps-3 small mt-1">
                            <li>"Ringkasan sistem"</li>
                            <li>"Bagaimana penjualan?"</li>
                            <li>"Diagnosa jaringan"</li>
                            <li>"Tiket gangguan"</li>
                            <li>"Pemasangan baru"</li>
                            <li>"Perlu restock?"</li>
                            <li>"Status jaringan"</li>
                        </ul>
                    </div>
                    <small class="text-body-secondary mt-1 ms-1" style="font-size: 0.7rem;">Asisten AI</small>
                </div>
            </div>
            <div class="card-footer bg-body border-top p-2">
                <div class="input-group">
                    <input type="text" id="chat-input" class="form-control border-0" placeholder="Ketik pesan..." onkeypress="handleEnter(event)">
                    <button class="btn btn-primary rounded-circle ms-2" style="width: 40px; height: 40px;" onclick="sendMessage()">
                        <i class="fa-solid fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function toggleChat() {
        const chatBox = document.getElementById('chat-box');
        const chatBtn = document.getElementById('chat-toggle-btn');
        
        if (chatBox.classList.contains('d-none')) {
            chatBox.classList.remove('d-none');
            // Animate In
            chatBox.animate([
                { opacity: 0, transform: 'translateY(20px)' },
                { opacity: 1, transform: 'translateY(0)' }
            ], { duration: 300, easing: 'ease-out' });
            document.getElementById('chat-input').focus();
        } else {
            chatBox.classList.add('d-none');
        }
    }

    function handleEnter(e) {
        if (e.key === 'Enter') sendMessage();
    }

    async function sendMessage() {
        const input = document.getElementById('chat-input');
        const message = input.value.trim();
        const messagesContainer = document.getElementById('chat-messages');

        if (!message) return;

        // 1. Add User Message
        appendMessage('user', message);
        input.value = '';

        // 2. Show Typing Indicator
        const typingId = 'typing-' + Date.now();
        appendTyping(typingId);
        scrollToBottom();

        try {
            // 3. Call API
            const response = await fetch("{{ route('ai.chat') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': "{{ csrf_token() }}"
                },
                body: JSON.stringify({ message: message })
            });

            const data = await response.json();

            // 4. Remove Typing & Add Bot Response
            removeMessage(typingId);
            appendMessage('bot', data.response);

        } catch (error) {
            console.error('Chat Error:', error);
            removeMessage(typingId);
            appendMessage('bot', "Maaf, saya kesulitan terhubung ke server. 😓");
        }

        scrollToBottom();
    }

    function appendMessage(sender, messageData) {
        const container = document.getElementById('chat-messages');
        const isUser = sender === 'user';
        
        const wrapperClass = isUser ? 'align-items-end' : 'align-items-start';
        const bgClass = isUser ? 'bg-primary text-white' : 'bg-white text-dark';
        const radiusStyle = isUser ? 'border-bottom-right-radius: 0;' : 'border-bottom-left-radius: 0;';
        const name = isUser ? 'Anda' : 'Asisten AI';

        let contentHtml = '';

        if (typeof messageData === 'object' && messageData !== null && messageData.type === 'list') {
            // Render List
            contentHtml += `<h6 class="mb-2 fw-bold border-bottom pb-1">${messageData.title}</h6>`;
            contentHtml += '<ul class="mb-0 ps-3 small">';
            messageData.items.forEach(item => {
                contentHtml += `<li>${item}</li>`;
            });
            contentHtml += '</ul>';
        } else {
            // Render Text
            contentHtml = messageData;
        }

        const html = `
            <div class="d-flex flex-column ${wrapperClass} mb-3">
                <div class="${bgClass} p-3 rounded shadow-sm" style="max-width: 85%; ${radiusStyle}">
                    ${contentHtml}
                </div>
                <small class="text-muted mt-1 mx-1" style="font-size: 0.7rem;">${name}</small>
            </div>
        `;
        
        container.insertAdjacentHTML('beforeend', html);
    }

    function appendTyping(id) {
        const container = document.getElementById('chat-messages');
        const html = `
            <div id="${id}" class="d-flex flex-column align-items-start mb-3">
                <div class="bg-white p-3 rounded shadow-sm text-muted fst-italic" style="max-width: 85%; border-bottom-left-radius: 0;">
                    <i class="fa-solid fa-circle fa-xs fa-bounce me-1"></i>
                    <i class="fa-solid fa-circle fa-xs fa-bounce me-1" style="animation-delay: 0.1s"></i>
                    <i class="fa-solid fa-circle fa-xs fa-bounce" style="animation-delay: 0.2s"></i>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', html);
    }

    function removeMessage(id) {
        const el = document.getElementById(id);
        if (el) el.remove();
    }

    function scrollToBottom() {
        const container = document.getElementById('chat-messages');
        container.scrollTop = container.scrollHeight;
    }

    document.addEventListener('DOMContentLoaded', function () {
        // Initialize Tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })

        // ApexChart for Sales Forecast
        var historyDates = @json($salesForecast['history']->pluck('date'));
        var historyValues = @json($salesForecast['history']->pluck('total'));
        var forecastData = @json($salesForecast['forecast']);
        
        // Prepare data series
        var dates = historyDates.concat(forecastData.map(d => d.date));
        var values = historyValues.concat(forecastData.map(d => d.value));
        
        // Create a separate series for forecast to style it differently (dashed)
        var historySeries = historyValues.map(v => v);
        // Pad history series with nulls for forecast period
        var forecastSeries = Array(historyValues.length).fill(null);
        // Add last history point to connect lines
        if(historyValues.length > 0) {
            forecastSeries[historyValues.length - 1] = historyValues[historyValues.length - 1];
        }
        forecastData.forEach(d => forecastSeries.push(d.value));

        var options = {
            series: [{
                name: "{{ __('Riwayat') }}",
                data: historySeries
            }, {
                name: "{{ __('Prediksi') }}",
                data: forecastSeries
            }],
            chart: {
                height: 150,
                type: 'area',
                toolbar: { show: false },
                sparkline: { enabled: true }
            },
            dataLabels: { enabled: false },
            stroke: {
                curve: 'smooth',
                width: 2,
                dashArray: [0, 5]
            },
            fill: {
                type: 'gradient',
                gradient: {
                    opacityFrom: 0.5,
                    opacityTo: 0.1,
                }
            },
            colors: ['#0d6efd', '#198754'],
            xaxis: {
                categories: dates,
                crosshairs: {
                    width: 1
                },
            },
            tooltip: {
                fixed: { enabled: false },
                x: { show: true },
                y: {
                    formatter: function (val) {
                        return "Rp " + new Intl.NumberFormat('id-ID').format(val);
                    }
                },
                marker: { show: false }
            }
        };

        var chart = new ApexCharts(document.querySelector("#salesForecastChart"), options);
        chart.render();
    });
</script>
@endpush
@endsection
