@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    {{-- Feedback Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <div class="d-flex align-items-center">
                <i class="fa-solid fa-circle-check fs-5 me-2"></i>
                <div>{{ session('success') }}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <div class="d-flex align-items-center">
                <i class="fa-solid fa-circle-exclamation fs-5 me-2"></i>
                <div>{{ session('error') }}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
    @endif

    {{-- Loading Overlay --}}
    <div id="loadingOverlay" class="loading-overlay d-none">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <div class="mt-2 fw-bold text-primary">Memproses Jadwal...</div>
    </div>

    @include('schedules.partials.header')

    @include('schedules.partials.legend')

    {{-- Main Content Card --}}
    <div class="card border-0 shadow-sm schedule-main-card">
        <div class="card-body p-0">
            <div class="table-responsive schedule-table-wrapper">
                @if($mode === 'daily')
                    @include('schedules.partials.table-daily')
                @else
                    @include('schedules.partials.table-weekly')
                @endif
            </div>
        </div>
    </div>
</div>

@include('schedules.partials.modals')

@endsection

@push('styles')
<style>
    /* Loading Overlay */
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.8);
        z-index: 9999;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    /* Today Highlight */
    .today-col {
        background-color: rgba(13, 110, 253, 0.05) !important;
        position: relative;
    }
    .today-badge {
        font-size: 8px;
        font-weight: 800;
        color: #0d6efd;
        margin-top: 2px;
        letter-spacing: 0.5px;
    }

    /* Existing Styles */
    .schedule-main-card { border-radius: 12px; overflow: hidden; }
    .schedule-table-wrapper { max-height: 75vh; overflow-y: auto; overflow-x: auto; -webkit-overflow-scrolling: touch; }
    .schedule-daily-scroll { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    .schedule-daily-table { min-width: max-content; width: auto; }
    
    .table thead th { 
        position: sticky; top: 0; z-index: 10; 
        background: #f8f9fa; border-bottom: 2px solid #dee2e6;
        padding: 12px 8px; vertical-align: middle;
    }
    
    .schedule-name-col { 
        position: sticky; left: 0; z-index: 11; 
        background: #fff; width: 220px; min-width: 220px;
        border-right: 1px solid #dee2e6;
    }
    
    .table thead th.schedule-name-col { z-index: 12; background: #f8f9fa; }
    
    .employee-name { font-weight: 700; color: #333; font-size: 13px; line-height: 1.2; }
    .employee-info { font-size: 10px; color: #6c757d; margin-top: 2px; }
    .employee-divider { margin: 0 4px; color: #dee2e6; }
    
    .schedule-day-col { width: 45px; min-width: 45px; }
    .day-number { font-size: 16px; font-weight: 800; line-height: 1; margin-bottom: 2px; }
    .day-name { font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
    
    .sunday-col { background-color: rgba(220, 53, 69, 0.03) !important; }
    
    .group-row td { background: #f1f3f5; font-weight: 700; font-size: 11px; text-transform: uppercase; color: #495057; padding: 8px 15px; }
    .group-badge { background: #adb5bd; color: #fff; border-radius: 10px; padding: 2px 8px; font-size: 10px; }
    
    .btn-xs { padding: 2px 8px; font-size: 10px; border-radius: 4px; }
    .schedule-group-header { background: #f8f9fa; padding: 10px 15px; border-bottom: 1px solid #dee2e6; border-top-left-radius: 12px; border-top-right-radius: 12px; }

    .shift-cell { border: 0; border-radius: 6px; font-weight: 700; font-size: 11px; height: 28px; transition: all 0.2s; cursor: pointer; text-align: center; padding: 0; }
    .shift-cell.piket { background-color: #d1e7dd; color: #0f5132; }
    .shift-cell.backup { background-color: #fff3cd; color: #664d03; }
    .shift-cell.longshift { background-color: #cfe2ff; color: #084298; }
    .shift-cell.off { background-color: #f8f9fa; color: #6c757d; border: 1px dashed #dee2e6; }
    
    .shift-cell:hover { transform: scale(1.05); filter: brightness(0.95); }
    .shift-select { -webkit-appearance: none; -moz-appearance: none; appearance: none; text-align-last: center; }
    
    .shift-badge { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 24px; border-radius: 4px; font-size: 10px; font-weight: 700; }
    .shift-badge.piket { background: #d1e7dd; color: #0f5132; }
    .shift-badge.backup { background: #fff3cd; color: #664d03; }
    .shift-badge.longshift { background: #cfe2ff; color: #084298; }
    .shift-badge.off { background: #f8f9fa; color: #6c757d; border: 1px solid #dee2e6; }

    .date-range-filter { background: #fff; padding: 12px 20px; border-bottom: 1px solid #dee2e6; }
    .slot-config { display: flex; align-items: center; gap: 15px; padding: 15px; background: #f8f9fa; border-radius: 10px; }
    .slot-icon { width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 800; flex-shrink: 0; }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Show loading overlay on form submit
        const shiftForms = document.querySelectorAll('.shift-form');
        shiftForms.forEach(form => {
            form.addEventListener('submit', function() {
                document.getElementById('loadingOverlay').classList.remove('d-none');
            });
        });

        const filterForms = document.querySelectorAll('.schedule-filter-form, .date-range-filter form');
        filterForms.forEach(form => {
            form.addEventListener('submit', function() {
                document.getElementById('loadingOverlay').classList.remove('d-none');
            });
        });

        // Modals loading
        const modals = document.querySelectorAll('.modal form');
        modals.forEach(form => {
            form.addEventListener('submit', function() {
                document.getElementById('loadingOverlay').classList.remove('d-none');
            });
        });
    });

    function editPeriod(year, week, start, end) {
        document.getElementById('periodYear').value = year;
        document.getElementById('periodWeek').value = week;
        document.getElementById('periodStart').value = start;
        document.getElementById('periodEnd').value = end;
        
        var modal = new bootstrap.Modal(document.getElementById('editPeriodModal'));
        modal.show();
    }
</script>
@endpush
