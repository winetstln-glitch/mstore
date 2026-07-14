{{-- ============================================ 
     SHIFT LEGEND BAR 
     ============================================ --}}
<div class="shift-legend-bar mb-4">
    @php
        $teknisiShift = $shiftConfig['teknisi'] ?? null;
        $washShift = $shiftConfig['wash'] ?? null;
    @endphp
    <div class="row g-2 align-items-center">
        @if($teknisiShift)
        <div class="col-12 col-lg-auto">
            <small class="text-muted d-block mb-1 fw-semibold">Teknisi</small>
            <div class="legend-item">
                <span class="legend-dot bg-success"></span>
                <span class="legend-label">Shift 1</span>
                <code class="legend-code">{{ $teknisiShift['shift_1_start'] }} - {{ $teknisiShift['shift_1_end'] }}</code>
            </div>
            <div class="legend-item">
                <span class="legend-dot bg-warning"></span>
                <span class="legend-label">Shift 2</span>
                <code class="legend-code">{{ $teknisiShift['shift_2_start'] }} - {{ $teknisiShift['shift_2_end'] }}</code>
            </div>
            <div class="legend-item">
                <span class="legend-dot bg-info"></span>
                <span class="legend-label">Longshift</span>
                <code class="legend-code">{{ $teknisiShift['longshift_start'] ?? '08:00' }} - {{ $teknisiShift['longshift_end'] ?? '20:00' }}</code>
            </div>
        </div>
        @endif
        @if($washShift)
        <div class="col-12 col-lg-auto px-lg-4">
            <small class="text-muted d-block mb-1 fw-semibold">Operator Wash</small>
            <div class="legend-item">
                <span class="legend-dot bg-success"></span>
                <span class="legend-label">Shift 1</span>
                <code class="legend-code">{{ $washShift['shift_1_start'] }} - {{ $washShift['shift_1_end'] }}</code>
            </div>
            <div class="legend-item">
                <span class="legend-dot bg-warning"></span>
                <span class="legend-label">Shift 2</span>
                <code class="legend-code">{{ $washShift['shift_2_start'] }} - {{ $washShift['shift_2_end'] }}</code>
            </div>
            <div class="legend-item">
                <span class="legend-dot bg-info"></span>
                <span class="legend-label">Longshift</span>
                <code class="legend-code">{{ $washShift['longshift_start'] ?? '08:00' }} - {{ $washShift['longshift_end'] ?? '20:00' }}</code>
            </div>
        </div>
        @endif
        <div class="col-auto px-2 px-lg-4">
            <div class="legend-item">
                <span class="legend-dot bg-secondary"></span>
                <span class="legend-label">Off</span>
            </div>
        </div>
    </div>
</div>
