@extends('layouts.app')

@section('title', 'Dashboard NOC')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h4 class="mb-0">Dashboard NOC</h4>
            <div class="text-muted small">Pusat Jaringan</div>
        </div>
        <div class="text-muted small" id="nocLastUpdated">-</div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-lg-4">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <span>ONU Monitoring</span>
                    <span class="badge bg-secondary" id="onuFresh">GenieACS</span>
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-6"><div class="p-2 border rounded">Online: <b id="onuOnline">0</b></div></div>
                        <div class="col-6"><div class="p-2 border rounded">Offline: <b id="onuOffline">0</b></div></div>
                        <div class="col-6"><div class="p-2 border rounded">LOS: <b id="onuLos">0</b></div></div>
                        <div class="col-6"><div class="p-2 border rounded">Dying Gasp: <b id="onuDyingGasp">0</b></div></div>
                        <div class="col-12"><div class="p-2 border rounded">Weak Signal: <b id="onuWeakSignal">0</b></div></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <span>PPPoE Monitoring</span>
                    <span class="badge bg-secondary">MikroTik</span>
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-6"><div class="p-2 border rounded">Router Online: <b id="pppoeOnline">0</b></div></div>
                        <div class="col-6"><div class="p-2 border rounded">Router Offline: <b id="pppoeOffline">0</b></div></div>
                        <div class="col-6"><div class="p-2 border rounded">Active Session: <b id="pppoeActive">0</b></div></div>
                        <div class="col-6"><div class="p-2 border rounded">Total User: <b id="pppoeTotal">0</b></div></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card h-100">
                <div class="card-header">Network Health Score</div>
                <div class="card-body d-flex align-items-center justify-content-center flex-column">
                    <div style="font-size:54px;font-weight:700;" id="healthScore">0</div>
                    <div class="text-muted">0 - 100</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card h-100">
                <div class="card-header">Area Outage</div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-6"><div class="p-2 border rounded">Gangguan Aktif: <b id="outageActive">0</b></div></div>
                        <div class="col-6"><div class="p-2 border rounded">Maintenance: <b id="outageMaintenance">0</b></div></div>
                        <div class="col-6"><div class="p-2 border rounded">Fiber Cut: <b id="outageFiberCut">0</b></div></div>
                        <div class="col-6"><div class="p-2 border rounded">OLT Down: <b id="outageOltDown">0</b></div></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card h-100">
                <div class="card-header">Ticket Summary</div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-6"><div class="p-2 border rounded">Open: <b id="ticketOpen">0</b></div></div>
                        <div class="col-6"><div class="p-2 border rounded">In Progress: <b id="ticketInProgress">0</b></div></div>
                        <div class="col-6"><div class="p-2 border rounded">Pending: <b id="ticketPending">0</b></div></div>
                        <div class="col-6"><div class="p-2 border rounded">Closed: <b id="ticketClosed">0</b></div></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card h-100">
                <div class="card-header">Technician Summary</div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-6"><div class="p-2 border rounded">Online: <b id="techOnline">0</b></div></div>
                        <div class="col-6"><div class="p-2 border rounded">Offline: <b id="techOffline">0</b></div></div>
                        <div class="col-6"><div class="p-2 border rounded">Menangani Tiket: <b id="techHandling">0</b></div></div>
                        <div class="col-6"><div class="p-2 border rounded">Available: <b id="techAvailable">0</b></div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const nocElements = {
        lastUpdated: document.getElementById('nocLastUpdated'),
        onuOnline: document.getElementById('onuOnline'),
        onuOffline: document.getElementById('onuOffline'),
        onuLos: document.getElementById('onuLos'),
        onuDyingGasp: document.getElementById('onuDyingGasp'),
        onuWeakSignal: document.getElementById('onuWeakSignal'),
        pppoeOnline: document.getElementById('pppoeOnline'),
        pppoeOffline: document.getElementById('pppoeOffline'),
        pppoeActive: document.getElementById('pppoeActive'),
        pppoeTotal: document.getElementById('pppoeTotal'),
        outageActive: document.getElementById('outageActive'),
        outageMaintenance: document.getElementById('outageMaintenance'),
        outageFiberCut: document.getElementById('outageFiberCut'),
        outageOltDown: document.getElementById('outageOltDown'),
        ticketOpen: document.getElementById('ticketOpen'),
        ticketInProgress: document.getElementById('ticketInProgress'),
        ticketPending: document.getElementById('ticketPending'),
        ticketClosed: document.getElementById('ticketClosed'),
        techOnline: document.getElementById('techOnline'),
        techOffline: document.getElementById('techOffline'),
        techHandling: document.getElementById('techHandling'),
        techAvailable: document.getElementById('techAvailable'),
        healthScore: document.getElementById('healthScore')
    };

    function applySnapshot(snapshot) {
        if (!snapshot) return;
        nocElements.lastUpdated.textContent = snapshot.captured_at ? ('Updated: ' + snapshot.captured_at) : '-';
        nocElements.onuOnline.textContent = snapshot.onu_online ?? 0;
        nocElements.onuOffline.textContent = snapshot.onu_offline ?? 0;
        nocElements.onuLos.textContent = snapshot.onu_los ?? 0;
        nocElements.onuDyingGasp.textContent = snapshot.onu_dying_gasp ?? 0;
        nocElements.onuWeakSignal.textContent = snapshot.onu_weak_signal ?? 0;
        nocElements.pppoeOnline.textContent = snapshot.pppoe_online ?? 0;
        nocElements.pppoeOffline.textContent = snapshot.pppoe_offline ?? 0;
        nocElements.pppoeActive.textContent = snapshot.pppoe_active_sessions ?? 0;
        nocElements.pppoeTotal.textContent = snapshot.pppoe_total_users ?? 0;
        nocElements.outageActive.textContent = snapshot.outage_active ?? 0;
        nocElements.outageMaintenance.textContent = snapshot.outage_maintenance ?? 0;
        nocElements.outageFiberCut.textContent = snapshot.outage_fiber_cut ?? 0;
        nocElements.outageOltDown.textContent = snapshot.outage_olt_down ?? 0;
        nocElements.ticketOpen.textContent = snapshot.ticket_open ?? 0;
        nocElements.ticketInProgress.textContent = snapshot.ticket_in_progress ?? 0;
        nocElements.ticketPending.textContent = snapshot.ticket_pending ?? 0;
        nocElements.ticketClosed.textContent = snapshot.ticket_closed ?? 0;
        nocElements.techOnline.textContent = snapshot.technician_online ?? 0;
        nocElements.techOffline.textContent = snapshot.technician_offline ?? 0;
        nocElements.techHandling.textContent = snapshot.technician_handling_ticket ?? 0;
        nocElements.techAvailable.textContent = snapshot.technician_available ?? 0;
        nocElements.healthScore.textContent = snapshot.network_health_score ?? 0;
    }

    async function refreshNoc() {
        try {
            const res = await fetch('{{ route('noc.dashboard.data') }}', { headers: { 'Accept': 'application/json' }});
            const json = await res.json();
            if (json && json.snapshot) applySnapshot(json.snapshot);
        } catch (e) {}
    }

    applySnapshot(@json($snapshot));
    refreshNoc();
    setInterval(refreshNoc, 30000);
</script>
@endpush

