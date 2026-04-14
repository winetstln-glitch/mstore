{{-- ============================================ 
     SHIFT LEGEND BAR 
     ============================================ --}}
<div class="shift-legend-bar mb-4">
    <div class="row g-0 align-items-center">
        <div class="col-auto">
            <div class="legend-item">
                <span class="legend-dot bg-success"></span>
                <span class="legend-label">Shift 1</span>
                <code class="legend-code">{{ $shift1Start }} - {{ $shift1End }}</code>
            </div>
        </div>
        <div class="col-auto px-4">
            <div class="legend-item">
                <span class="legend-dot bg-warning"></span>
                <span class="legend-label">Shift 2</span>
                <code class="legend-code">{{ $shift2Start }} - {{ $shift2End }}</code>
            </div>
        </div>
        <div class="col-auto px-4">
            <div class="legend-item">
                <span class="legend-dot bg-secondary"></span>
                <span class="legend-label">Off</span>
            </div>
        </div>
        <div class="col-auto ms-auto">
            <small class="text-muted">
                <i class="fa-solid fa-gear me-1"></i>Ubah jam shift di <a href="#" class="text-decoration-underline">Pengaturan Attendance</a>
            </small>
        </div>
    </div>
</div>