@extends('layouts.app')

@section('title', 'WhatsApp Analytics')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h4 class="mb-0">WhatsApp Analytics</h4>
            <div class="text-muted small">WhatsApp Center</div>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <input type="date" class="form-control form-control-sm" id="waFrom">
            <input type="date" class="form-control form-control-sm" id="waTo">
            <button class="btn btn-sm btn-primary" id="waRefresh">Refresh</button>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-lg-3"><div class="card"><div class="card-body"><div class="text-muted small">Incoming</div><div class="h4 mb-0" id="waIncoming">0</div></div></div></div>
        <div class="col-12 col-lg-3"><div class="card"><div class="card-body"><div class="text-muted small">Outgoing</div><div class="h4 mb-0" id="waOutgoing">0</div></div></div></div>
        <div class="col-12 col-lg-3"><div class="card"><div class="card-body"><div class="text-muted small">Session</div><div class="h4 mb-0" id="waSessions">0</div></div></div></div>
        <div class="col-12 col-lg-3"><div class="card"><div class="card-body"><div class="text-muted small">AI Usage</div><div class="h4 mb-0" id="waAiUsage">0</div></div></div></div>

        <div class="col-12 col-lg-3"><div class="card"><div class="card-body"><div class="text-muted small">Ticket Created</div><div class="h4 mb-0" id="waTicketCreated">0</div></div></div></div>
        <div class="col-12 col-lg-3"><div class="card"><div class="card-body"><div class="text-muted small">QRIS Payment</div><div class="h4 mb-0" id="waQris">0</div></div></div></div>
        <div class="col-12 col-lg-3"><div class="card"><div class="card-body"><div class="text-muted small">Voucher Sold</div><div class="h4 mb-0" id="waVoucher">0</div></div></div></div>
        <div class="col-12 col-lg-3"><div class="card"><div class="card-body"><div class="text-muted small">Range</div><div class="small mb-0" id="waRange">-</div></div></div></div>

        <div class="col-12 col-lg-6">
            <div class="card h-100">
                <div class="card-header">Intent Analytics (Top 5)</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Intent</th><th class="text-end">Total</th></tr></thead>
                            <tbody id="waIntentRows"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card h-100">
                <div class="card-header">AI Analytics</div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-12"><div class="p-2 border rounded">AI Resolution Rate: <b id="waAiResolution">0%</b></div></div>
                        <div class="col-12"><div class="p-2 border rounded">AI Escalation Rate: <b id="waAiEscalation">0%</b></div></div>
                        <div class="col-12"><div class="p-2 border rounded">Fallback Rate: <b id="waAiFallback">0%</b></div></div>
                    </div>
                    <div class="text-muted small mt-2">AI Usage dihitung dari event WhatsApp yang ditandai used_ai pada ingestion.</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const waEl = {
        from: document.getElementById('waFrom'),
        to: document.getElementById('waTo'),
        refresh: document.getElementById('waRefresh'),
        incoming: document.getElementById('waIncoming'),
        outgoing: document.getElementById('waOutgoing'),
        sessions: document.getElementById('waSessions'),
        aiUsage: document.getElementById('waAiUsage'),
        ticketCreated: document.getElementById('waTicketCreated'),
        qris: document.getElementById('waQris'),
        voucher: document.getElementById('waVoucher'),
        range: document.getElementById('waRange'),
        intents: document.getElementById('waIntentRows'),
        aiResolution: document.getElementById('waAiResolution'),
        aiEscalation: document.getElementById('waAiEscalation'),
        aiFallback: document.getElementById('waAiFallback'),
    };

    function toYmd(d) {
        const pad = (n) => String(n).padStart(2, '0');
        return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;
    }

    waEl.from.value = toYmd(new Date(new Date().getFullYear(), new Date().getMonth(), 1));
    waEl.to.value = toYmd(new Date());

    function render(data) {
        waEl.incoming.textContent = data.summary.incoming ?? 0;
        waEl.outgoing.textContent = data.summary.outgoing ?? 0;
        waEl.sessions.textContent = data.summary.sessions ?? 0;
        waEl.aiUsage.textContent = data.summary.ai_usage ?? 0;
        waEl.ticketCreated.textContent = data.summary.ticket_created ?? 0;
        waEl.qris.textContent = data.summary.qris_payment ?? 0;
        waEl.voucher.textContent = data.summary.voucher_sold ?? 0;
        waEl.range.textContent = (data.range?.from ?? '-') + ' → ' + (data.range?.to ?? '-');

        waEl.aiResolution.textContent = (data.ai_analytics?.resolution_rate ?? 0) + '%';
        waEl.aiEscalation.textContent = (data.ai_analytics?.escalation_rate ?? 0) + '%';
        waEl.aiFallback.textContent = (data.ai_analytics?.fallback_rate ?? 0) + '%';

        const rows = (data.intent_analytics ?? []).map(r => `<tr><td>${r.intent}</td><td class="text-end">${r.total}</td></tr>`).join('');
        waEl.intents.innerHTML = rows || '<tr><td colspan="2" class="text-muted">Belum ada data</td></tr>';
    }

    async function refresh() {
        const params = new URLSearchParams();
        if (waEl.from.value) params.set('from', waEl.from.value + ' 00:00:00');
        if (waEl.to.value) params.set('to', waEl.to.value + ' 23:59:59');
        const res = await fetch(`{{ route('whatsapp.analytics.data') }}?${params.toString()}`, { headers: { 'Accept': 'application/json' }});
        const json = await res.json();
        if (json && json.ok) render(json);
    }

    waEl.refresh.addEventListener('click', refresh);
    refresh();
</script>
@endpush

