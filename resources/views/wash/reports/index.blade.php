@extends('layouts.app')
@section('title', 'Laporan Wash')

@section('content')
<style>
/* ==========================================================
   WASH REPORTS - FORCE OVERRIDE (in-content inline CSS 100% loaded
   ========================================================== */
html body #page-content-wrapper .wash-reports-page,
body #page-content-wrapper > div > .wash-reports-page {
    padding-top: 0 !important;
}

/* ===== CARD: hapus double-margin, set overflow hidden, border-radius seragam ===== */
body #page-content-wrapper .wash-reports-page > div.card,
#page-content-wrapper .wash-reports-page .card,
.wash-reports-page .card {
    margin-bottom: 1.25rem !important;
    overflow: hidden !important;
    border-radius: 14px !important;
    border: 1px solid rgba(148,163,184,0.22) !important;
    box-shadow: 0 1px 3px 0 rgba(0,0,0,0.04), 0 1px 2px -1px rgba(0,0,0,0.03) !important;
    background-color: var(--card-bg, #fff) !important;
}

#page-content-wrapper .wash-reports-page .card > .card-header {
    padding: 0.9rem 1.25rem !important;
    border-bottom: 1px solid rgba(148,163,184,0.22) !important;
    background-color: rgba(148,163,184,0.06) !important;
    color: var(--text-main, #0f172a) !important;
    font-size: 0.98rem;
    font-weight: 650;
    border-radius: 0 !important;
}

#page-content-wrapper .wash-reports-page .card > .card-body {
    padding: 1.1rem 1.25rem !important;
    color: var(--text-main, #0f172a) !important;
}

@media (max-width: 767.98px) {
    #page-content-wrapper .wash-reports-page .card > .card-header {
        padding: 0.8rem 0.95rem !important;
    }
    #page-content-wrapper .wash-reports-page .card > .card-header.d-flex {
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 0.5rem !important;
    }
    #page-content-wrapper .wash-reports-page .card > .card-body {
        padding: 0.85rem 0.95rem !important;
    }
}

/* ===== NAV TABS ===== */
#page-content-wrapper .wash-reports-page .wash-report-tabs {
    border-bottom: 1px solid rgba(148,163,184,0.22) !important;
    padding: 1rem 1.25rem 0 1.25rem !important;
    background: linear-gradient(180deg, rgba(59,130,246,0.05) 0%, transparent 100%);
    margin: 0 !important;
}

#page-content-wrapper .wash-reports-page .wash-report-tabs .nav-link {
    border: 1px solid transparent !important;
    border-bottom: 0 !important;
    border-top-left-radius: 12px !important;
    border-top-right-radius: 12px !important;
    padding: 0.65rem 1.25rem !important;
    margin-right: 0.3rem !important;
    margin-bottom: -1px !important;
    transition: all 0.2s ease !important;
    background: transparent !important;
    font-weight: 600 !important;
    color: var(--text-muted, #64748b) !important;
    font-size: 0.92rem;
}

#page-content-wrapper .wash-reports-page .wash-report-tabs .nav-link:hover {
    color: #2563eb !important;
    background: rgba(59,130,246,0.06) !important;
}

#page-content-wrapper .wash-reports-page .wash-report-tabs .nav-link.active {
    color: #2563eb !important;
    border-color: rgba(148,163,184,0.22) !important;
    background-color: var(--card-bg, #fff) !important;
    border-bottom: 2px solid var(--card-bg, #fff) !important;
}

/* ===== TAB CONTENT ===== */
#page-content-wrapper .wash-reports-page #reportTabContent.tab-content {
    padding: 1.25rem !important;
    background-color: var(--card-bg, #fff) !important;
    border-radius: 0 0 14px 14px !important;
}

@media (max-width: 767.98px) {
    #page-content-wrapper .wash-reports-page #reportTabContent.tab-content {
        padding: 0.9rem 0.75rem !important;
    }
}

/* ===== TABLE RESPONSIVE: HAPUS DOUBLE BORDER! ===== */
#page-content-wrapper .wash-reports-page .table-responsive,
#page-content-wrapper .wash-reports-page .table-responsive-mobile {
    border: 1px solid rgba(148,163,184,0.22) !important;
    border-radius: 12px !important;
    background-color: var(--card-bg, #fff) !important;
    overflow: hidden !important;
    margin: 0 !important;
    box-shadow: none !important;
}

/* PENTING: kalau .table-responsive DI DALAM card (langsung child dari .card tanpa .card-body), HAPUS BORDER! */
#page-content-wrapper .wash-reports-page .card > .table-responsive,
#page-content-wrapper .wash-reports-page .card > .table-responsive-mobile,
#page-content-wrapper .wash-reports-page .card > .card-body > .table-responsive,
#page-content-wrapper .wash-reports-page .card > .card-body > .table-responsive-mobile {
    border: none !important;
    border-radius: 0 !important;
    background-color: transparent !important;
}

#page-content-wrapper .wash-reports-page .card > .card-header + .table-responsive,
#page-content-wrapper .wash-reports-page .card > .card-header + .table-responsive-mobile {
    border-top: 1px solid rgba(148,163,184,0.22) !important;
}

/* ===== TABLE STYLE ===== */
#page-content-wrapper .wash-reports-page .table {
    color: var(--text-main, #0f172a) !important;
    margin-bottom: 0 !important;
    width: 100% !important;
    border-collapse: separate !important;
    border-spacing: 0 !important;
}

#page-content-wrapper .wash-reports-page .table thead th {
    background: rgba(148,163,184,0.10) !important;
    color: var(--text-muted, #64748b) !important;
    font-weight: 700 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.03em !important;
    font-size: 0.8rem !important;
    padding: 0.7rem 0.9rem !important;
    border-bottom: 1px solid rgba(148,163,184,0.22) !important;
    vertical-align: middle !important;
    white-space: nowrap;
}

#page-content-wrapper .wash-reports-page .table tbody td {
    padding: 0.65rem 0.9rem !important;
    border-top: 1px solid rgba(148,163,184,0.14) !important;
    vertical-align: middle !important;
    font-size: 0.88rem !important;
    color: var(--text-main, #0f172a) !important;
    background-color: transparent !important;
}

#page-content-wrapper .wash-reports-page .table-striped > tbody > tr:nth-of-type(odd) > td {
    background-color: rgba(148,163,184,0.035) !important;
}
#page-content-wrapper .wash-reports-page .table-striped > tbody > tr:nth-of-type(odd) > * {
    --bs-table-bg-type: transparent !important;
}

#page-content-wrapper .wash-reports-page .table-hover > tbody > tr:hover > td {
    background-color: rgba(59,130,246,0.06) !important;
}

#page-content-wrapper .wash-reports-page .table tfoot td,
#page-content-wrapper .wash-reports-page .table tfoot th {
    padding: 0.75rem 0.9rem !important;
    border-top: 2px solid rgba(148,163,184,0.22) !important;
    background: rgba(148,163,184,0.06) !important;
    font-size: 0.92rem !important;
}

/* ===== STAT CARD SUMMARY (4 card atas) ===== */
#page-content-wrapper .wash-reports-page .stat-card-summary.card {
    border: 1px solid rgba(148,163,184,0.18) !important;
    min-height: 100% !important;
}
#page-content-wrapper .wash-reports-page .stat-card-summary .card-body {
    padding: 1.1rem 1.2rem !important;
    display: flex !important;
    flex-direction: column !important;
    justify-content: space-between !important;
    height: 100% !important;
    gap: 0.75rem !important;
}
#page-content-wrapper .wash-reports-page .stat-card-summary .card-body > .d-flex {
    gap: 0.85rem !important;
    align-items: center !important;
}
#page-content-wrapper .wash-reports-page .stat-card-summary .card-body > .d-flex > div:first-child {
    flex: 1 1 auto !important;
    min-width: 0 !important;
}
#page-content-wrapper .wash-reports-page .stat-card-summary .display-6 {
    font-size: clamp(1.3rem, 1.95vw, 2rem) !important;
    font-weight: 800 !important;
    line-height: 1.25 !important;
    letter-spacing: -0.005em;
    word-break: keep-all !important;
    white-space: nowrap !important;
    overflow-wrap: normal !important;
    margin-top: 0.3rem !important;
    display: block !important;
}
#page-content-wrapper .wash-reports-page .stat-card-summary .small.text-uppercase {
    font-size: 0.76rem !important;
    letter-spacing: 0.055em !important;
    font-weight: 700 !important;
    display: block !important;
    margin-bottom: 0.1rem !important;
    line-height: 1.3 !important;
}
#page-content-wrapper .wash-reports-page .stat-card-summary .small.text-muted {
    font-size: 0.82rem !important;
    line-height: 1.4 !important;
    margin-top: auto !important;
    padding-top: 0.1rem !important;
}
#page-content-wrapper .wash-reports-page .stat-card-summary .bg-success.rounded-pill,
#page-content-wrapper .wash-reports-page .stat-card-summary .bg-danger.rounded-pill,
#page-content-wrapper .wash-reports-page .stat-card-summary .bg-warning.rounded-pill,
#page-content-wrapper .wash-reports-page .stat-card-summary .bg-primary.rounded-pill {
    width: 44px !important;
    height: 44px !important;
    min-width: 44px !important;
    padding: 0 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    flex-shrink: 0 !important;
    border-radius: 999px !important;
}
#page-content-wrapper .wash-reports-page .stat-card-summary .bg-success.rounded-pill i,
#page-content-wrapper .wash-reports-page .stat-card-summary .bg-danger.rounded-pill i,
#page-content-wrapper .wash-reports-page .stat-card-summary .bg-warning.rounded-pill i,
#page-content-wrapper .wash-reports-page .stat-card-summary .bg-primary.rounded-pill i {
    font-size: 1.15rem !important;
    line-height: 1 !important;
}

@media (max-width: 991.98px) {
    #page-content-wrapper .wash-reports-page .stat-card-summary .display-6 {
        font-size: clamp(1.15rem, 2.6vw, 1.7rem) !important;
    }
    #page-content-wrapper .wash-reports-page .stat-card-summary .card-body {
        padding: 0.95rem 1rem !important;
        gap: 0.65rem !important;
    }
    #page-content-wrapper .wash-reports-page .stat-card-summary .bg-success.rounded-pill,
    #page-content-wrapper .wash-reports-page .stat-card-summary .bg-danger.rounded-pill,
    #page-content-wrapper .wash-reports-page .stat-card-summary .bg-warning.rounded-pill,
    #page-content-wrapper .wash-reports-page .stat-card-summary .bg-primary.rounded-pill {
        width: 40px !important;
        height: 40px !important;
        min-width: 40px !important;
    }
    #page-content-wrapper .wash-reports-page .stat-card-summary .bg-success.rounded-pill i,
    #page-content-wrapper .wash-reports-page .stat-card-summary .bg-danger.rounded-pill i,
    #page-content-wrapper .wash-reports-page .stat-card-summary .bg-warning.rounded-pill i,
    #page-content-wrapper .wash-reports-page .stat-card-summary .bg-primary.rounded-pill i {
        font-size: 1rem !important;
    }
}

@media (max-width: 575.98px) {
    #page-content-wrapper .wash-reports-page .stat-card-summary .display-6 {
        font-size: clamp(1.35rem, 5.8vw, 1.85rem) !important;
        white-space: normal !important;
        line-height: 1.2 !important;
    }
    #page-content-wrapper .wash-reports-page .stat-card-summary .card-body {
        padding: 1rem 1.05rem !important;
        gap: 0.75rem !important;
    }
    #page-content-wrapper .wash-reports-page .stat-card-summary .card-body > .d-flex {
        gap: 0.9rem !important;
    }
    #page-content-wrapper .wash-reports-page .stat-card-summary .bg-success.rounded-pill,
    #page-content-wrapper .wash-reports-page .stat-card-summary .bg-danger.rounded-pill,
    #page-content-wrapper .wash-reports-page .stat-card-summary .bg-warning.rounded-pill,
    #page-content-wrapper .wash-reports-page .stat-card-summary .bg-primary.rounded-pill {
        width: 46px !important;
        height: 46px !important;
        min-width: 46px !important;
    }
    #page-content-wrapper .wash-reports-page .stat-card-summary .bg-success.rounded-pill i,
    #page-content-wrapper .wash-reports-page .stat-card-summary .bg-danger.rounded-pill i,
    #page-content-wrapper .wash-reports-page .stat-card-summary .bg-warning.rounded-pill i,
    #page-content-wrapper .wash-reports-page .stat-card-summary .bg-primary.rounded-pill i {
        font-size: 1.2rem !important;
    }
    #page-content-wrapper .wash-reports-page .stat-card-summary .small.text-muted {
        font-size: 0.85rem !important;
    }
}

/* ===== SEGMEN CARD (Cuci+Caffe) ===== */
#page-content-wrapper .wash-reports-page .segment-card.card .card-header {
    padding: 0.75rem 1.05rem !important;
}
#page-content-wrapper .wash-reports-page .segment-card .card-body {
    padding: 0.95rem 1.1rem !important;
}
#page-content-wrapper .wash-reports-page .segment-card .d-flex.py-1 {
    padding: 0.45rem 0 !important;
    font-size: 0.92rem;
}
#page-content-wrapper .wash-reports-page .segment-card hr {
    margin: 0.5rem 0 !important;
    opacity: 0.22;
}

/* ===== FILTER FORM ===== */
#page-content-wrapper .wash-reports-page .daily-filter-form,
#page-content-wrapper .wash-reports-page .monthly-filter-form {
    border: 1px solid rgba(148,163,184,0.22) !important;
    background: linear-gradient(180deg, rgba(148,163,184,0.05) 0%, rgba(148,163,184,0.02) 100%) !important;
    border-radius: 12px !important;
    padding: 0.85rem 1rem !important;
    margin-bottom: 1.25rem !important;
    font-size: 0.9rem !important;
}
#page-content-wrapper .wash-reports-page .daily-filter-form .form-label,
#page-content-wrapper .wash-reports-page .monthly-filter-form .form-label {
    font-size: 0.85rem !important;
    font-weight: 600 !important;
    color: var(--text-muted, #64748b) !important;
}
#page-content-wrapper .wash-reports-page .daily-filter-form .fw-bold.text-muted,
#page-content-wrapper .wash-reports-page .monthly-filter-form .fw-bold.text-muted {
    font-size: 0.88rem !important;
}
#page-content-wrapper .wash-reports-page .daily-filter-form .form-control,
#page-content-wrapper .wash-reports-page .monthly-filter-form .form-control,
#page-content-wrapper .wash-reports-page .daily-filter-form .form-select,
#page-content-wrapper .wash-reports-page .monthly-filter-form .form-select,
#page-content-wrapper .wash-reports-page .daily-filter-form .btn,
#page-content-wrapper .wash-reports-page .monthly-filter-form .btn {
    font-size: 0.88rem !important;
}

@media (max-width: 767.98px) {
    #page-content-wrapper .wash-reports-page .wash-report-tabs .nav-link {
        font-size: 0.82rem !important;
        padding: 0.55rem 0.9rem !important;
        margin-right: 0.15rem !important;
    }
    #page-content-wrapper .wash-reports-page .daily-filter-form,
    #page-content-wrapper .wash-reports-page .monthly-filter-form {
        padding: 0.85rem 0.9rem !important;
    }
    #page-content-wrapper .wash-reports-page .daily-filter-form .col-auto,
    #page-content-wrapper .wash-reports-page .monthly-filter-form .col-auto {
        width: 100% !important;
    }
    #page-content-wrapper .wash-reports-page .daily-filter-form .form-control,
    #page-content-wrapper .wash-reports-page .monthly-filter-form .form-control,
    #page-content-wrapper .wash-reports-page .daily-filter-form .form-select,
    #page-content-wrapper .wash-reports-page .monthly-filter-form .form-select,
    #page-content-wrapper .wash-reports-page .daily-filter-form .btn,
    #page-content-wrapper .wash-reports-page .monthly-filter-form .btn {
        width: 100% !important;
        min-height: 42px !important;
        font-size: 0.9rem !important;
    }
    #page-content-wrapper .wash-reports-page .daily-filter-form label {
        display: flex !important;
        flex-direction: column !important;
        align-items: stretch !important;
    }
    #page-content-wrapper .wash-reports-page .card > .card-header {
        font-size: 0.9rem !important;
    }
}

/* ===== Section Title (h6 judul) ===== */
#page-content-wrapper .wash-reports-page h6.fw-bold.text-muted.text-uppercase.small {
    letter-spacing: 0.08em !important;
    font-size: 0.82rem !important;
    padding-bottom: 0.4rem !important;
    border-bottom: 2px solid rgba(148,163,184,0.15) !important;
    margin-bottom: 1rem !important;
    margin-top: 0.2rem;
    color: var(--text-muted, #64748b) !important;
}

/* ===== PROGRESS BAR ===== */
#page-content-wrapper .wash-reports-page .progress {
    border-radius: 999px !important;
    background: rgba(148, 163, 184, 0.18) !important;
    height: 10px !important;
    overflow: hidden !important;
}
#page-content-wrapper .wash-reports-page .progress-bar {
    border-radius: 999px !important;
    background: linear-gradient(90deg, #2563eb 0%, #3b82f6 100%) !important;
}

/* ===== BADGE ===== */
#page-content-wrapper .wash-reports-page .badge {
    font-size: 0.78rem !important;
    padding: 0.35em 0.65em !important;
    font-weight: 600 !important;
}
#page-content-wrapper .wash-reports-page .badge.bg-light.text-dark {
    background: rgba(148,163,184,0.15) !important;
    color: var(--text-main, #0f172a) !important;
    border: 1px solid rgba(148,163,184,0.28) !important;
    font-weight: 600 !important;
}

/* ===== EXPORT BUTTON ===== */
#page-content-wrapper .wash-reports-page .wash-report-export .btn {
    min-height: 38px !important;
    padding: 0.4rem 0.9rem !important;
    border-radius: 10px !important;
    font-weight: 600 !important;
    font-size: 0.85rem !important;
}

/* ===== MEMBER STAT CARD ===== */
#page-content-wrapper .wash-reports-page .member-stat-card .card-body {
    padding: 1.25rem 0.9rem !important;
    text-align: center !important;
}
#page-content-wrapper .wash-reports-page .member-stat-card .display-6 {
    font-size: clamp(1.6rem, 2vw, 2rem) !important;
    font-weight: 800 !important;
}
#page-content-wrapper .wash-reports-page .member-stat-card i.bi {
    font-size: 2.1rem !important;
}
#page-content-wrapper .wash-reports-page .member-stat-card .small.text-uppercase.fw-bold {
    font-size: 0.78rem !important;
    letter-spacing: 0.05em !important;
}
#page-content-wrapper .wash-reports-page .member-stat-card .small.text-muted:not(.text-uppercase) {
    font-size: 0.82rem !important;
    margin-top: 0.2rem !important;
    display: block;
}
@media (max-width: 767.98px) {
    #page-content-wrapper .wash-reports-page .member-stat-card .display-6 {
        font-size: 1.5rem !important;
    }
}

/* ===== JUDUL ATAS ===== */
#page-content-wrapper .wash-reports-page > div > h4.mb-0 {
    font-size: 1.35rem !important;
    line-height: 1.3 !important;
}
#page-content-wrapper .wash-reports-page > div > div > small.text-muted {
    font-size: 0.85rem !important;
}

/* ===== BADGE (bg-success table-warning table-primary di table tbody ===== */
#page-content-wrapper .wash-reports-page .table .table-success td {
    background-color: rgba(16,185,129,0.10) !important;
}
#page-content-wrapper .wash-reports-page .table .table-warning td {
    background-color: rgba(245,158,11,0.12) !important;
}
#page-content-wrapper .wash-reports-page .table .table-primary td {
    background-color: rgba(59,130,246,0.10) !important;
}
#page-content-wrapper .wash-reports-page .table .table-danger td,
#page-content-wrapper .wash-reports-page .table .table-danger th {
    background-color: rgba(239,68,68,0.10) !important;
    color: var(--text-main) !important;
}

/* ===== ROW GAP UNIFORM ===== */
#page-content-wrapper .wash-reports-page .row.g-3 {
    --bs-gutter-y: 1rem !important;
    row-gap: 1rem !important;
    margin-bottom: 0.25rem !important;
}

/* ===== DARK MODE OVERRIDE ===== */
[data-bs-theme="dark"] #page-content-wrapper .wash-reports-page .card,
[data-bs-theme="dark"] body #page-content-wrapper .wash-reports-page .card {
    border-color: rgba(96,165,250,0.22) !important;
    background-color: #0f172a !important;
}
[data-bs-theme="dark"] #page-content-wrapper .wash-reports-page .card > .card-header {
    background-color: rgba(96,165,250,0.08) !important;
    border-bottom-color: rgba(96,165,250,0.22) !important;
    color: #e2e8f0 !important;
}
[data-bs-theme="dark"] #page-content-wrapper .wash-reports-page .table thead th {
    background: rgba(51,65,85,0.55) !important;
    color: #cbd5e1 !important;
    border-bottom-color: rgba(96,165,250,0.22) !important;
}
[data-bs-theme="dark"] #page-content-wrapper .wash-reports-page .table tbody td {
    border-top-color: rgba(96,165,250,0.15) !important;
    color: #e2e8f0 !important;
}
[data-bs-theme="dark"] #page-content-wrapper .wash-reports-page .wash-report-tabs .nav-link.active {
    background-color: #0f172a !important;
    border-color: rgba(96,165,250,0.22) !important;
    border-bottom-color: #0f172a !important;
}
[data-bs-theme="dark"] #page-content-wrapper .wash-reports-page #reportTabContent.tab-content {
    background-color: #0f172a !important;
}
[data-bs-theme="dark"] #page-content-wrapper .wash-reports-page .stat-card-summary.card {
    background: linear-gradient(180deg, rgba(255,255,255,0.02) 0%, transparent 100%) !important;
}
[data-bs-theme="dark"] #page-content-wrapper .wash-reports-page .daily-filter-form,
[data-bs-theme="dark"] #page-content-wrapper .wash-reports-page .monthly-filter-form {
    background: linear-gradient(180deg, rgba(96,165,250,0.08) 0%, rgba(96,165,250,0.02) 100%) !important;
    border-color: rgba(96,165,250,0.22) !important;
}
</style>
<div class="container-fluid py-4 wash-reports-page">
    
    <!-- JUDUL & AKSI -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h4 class="mb-0 fw-bold text-dark">
                <i class="bi bi-graph-up-arrow text-primary me-2"></i>Laporan Keuangan Wash
            </h4>
            <small class="text-muted">Data transaksi wash + komisi operator</small>
        </div>
        <div class="btn-group wash-report-export">
            <a class="btn btn-sm btn-outline-danger shadow-sm" id="btnExportPdf">
                <i class="bi bi-file-earmark-pdf-fill me-1"></i> PDF
            </a>
            <a class="btn btn-sm btn-outline-success shadow-sm" id="btnExportExcel">
                <i class="bi bi-file-earmark-excel-fill me-1"></i> Excel
            </a>
        </div>
    </div>

    <!-- KONTEN UTAMA -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <!-- NAV TAB -->
            <ul class="nav nav-tabs wash-report-tabs px-4 pt-3" id="reportTab" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active fw-semibold px-4" id="daily-tab" data-bs-toggle="tab" data-bs-target="#daily-content" type="button">
                        <i class="bi bi-calendar-day me-1"></i> Harian
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-semibold px-4" id="monthly-tab" data-bs-toggle="tab" data-bs-target="#monthly-content" type="button">
                        <i class="bi bi-calendar3 me-1"></i> Bulanan
                    </button>
                </li>
            </ul>

            <!-- ISI TAB -->
            <div class="tab-content p-4" id="reportTabContent">

                <!-- ======================================
                TAB HARIAN
                ====================================== -->
                <div class="tab-pane fade show active" id="daily-content">

                    <!-- FILTER -->
                    <form method="get" class="row g-2 align-items-center mb-4 justify-content-end daily-filter-form bg-light rounded p-3">
                        <input type="hidden" name="view" value="daily">
                        <div class="col-auto fw-bold text-muted">Filter :</div>
                        <div class="col-auto"><label class="form-label mb-0">Dari</label></div>
                        <div class="col-auto"><input type="date" name="start_date" value="{{ $startDate }}" class="form-control form-control-sm"></div>
                        <div class="col-auto"><label class="form-label mb-0">Sampai</label></div>
                        <div class="col-auto"><input type="date" name="end_date" value="{{ $endDate }}" class="form-control form-control-sm"></div>
                        <div class="col-auto">
                            <select name="vehicle_plate" class="form-select form-select-sm">
                                <option value="">Semua Plat</option>
                                @foreach(($knownVehiclePlates ?? []) as $plateOption)
                                    <option value="{{ $plateOption['plate'] }}" {{ ($vehiclePlate ?? '') === $plateOption['plate'] ? 'selected' : '' }}>
                                        {{ $plateOption['plate'] }}
                                        @if(!empty($plateOption['brand'])) - {{ $plateOption['brand'] }}@endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="bi bi-search me-1"></i>Tampilkan
                            </button>
                        </div>
                    </form>

                    <!-- =============== RINGKASAN UTAMA (CARD) =============== -->
                    <h6 class="fw-bold text-muted mb-3 text-uppercase small"><i class="bi bi-lightning-charge-fill text-warning me-1"></i> Ringkasan Cepat</h6>
                    <div class="row g-3 mb-4">
                        <!-- Pemasukan -->
                        <div class="col-md-3">
                            <div class="card stat-card-summary h-100 border-0 shadow-sm bg-success bg-opacity-10">
                                <div class="card-body">
                                    <div class="d-flex">
                                        <div>
                                            <div class="small text-uppercase fw-bold text-success opacity-75">Pemasukan</div>
                                            <div class="display-6 fw-bold text-success">Rp {{ number_format($dailyIncome,0,',','.') }}</div>
                                        </div>
                                    </div>
                                    <div class="small text-muted">
                                        <i class="bi bi-receipt me-1"></i>{{ $dailyTrxCount }} transaksi
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Pengeluaran -->
                        <div class="col-md-3">
                            <div class="card stat-card-summary h-100 border-0 shadow-sm bg-danger bg-opacity-10">
                                <div class="card-body">
                                    <div class="d-flex">
                                        <div>
                                            <div class="small text-uppercase fw-bold text-danger opacity-75">Pengeluaran</div>
                                            <div class="display-6 fw-bold text-danger">Rp {{ number_format($dailyExpense,0,',','.') }}</div>
                                        </div>
                                    </div>
                                    <div class="small text-muted">
                                        <i class="bi bi-receipt-cutoff me-1"></i>{{ $dailyExpCount }} item
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Potongan Komisi -->
                        <div class="col-md-3">
                            <div class="card stat-card-summary h-100 border-0 shadow-sm bg-warning bg-opacity-10">
                                <div class="card-body">
                                    <div class="d-flex">
                                        <div>
                                            <div class="small text-uppercase fw-bold text-warning opacity-75">Potongan Komisi</div>
                                            <div class="display-6 fw-bold text-warning">- Rp {{ number_format($dailyCommission,0,',','.') }}</div>
                                        </div>
                                    </div>
                                    <div class="small text-muted">
                                        <i class="bi bi-person-check me-1"></i>{{ $dailyCommissionEmpCount }} karyawan &bull; {{ $dailyCommissionItemCount }} item
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Laba Bersih -->
                        <div class="col-md-3">
                            <div class="card stat-card-summary h-100 border-0 shadow-sm {{ $dailyTotalNetProfit >= 0 ? 'bg-primary bg-opacity-10' : 'bg-danger bg-opacity-10' }}">
                                <div class="card-body">
                                    <div class="d-flex">
                                        <div>
                                            <div class="small text-uppercase fw-bold opacity-75 {{ $dailyTotalNetProfit >= 0 ? 'text-primary' : 'text-danger' }}">Laba Bersih</div>
                                            <div class="display-6 fw-bold {{ $dailyTotalNetProfit >= 0 ? 'text-primary' : 'text-danger' }}">Rp {{ number_format($dailyTotalNetProfit,0,',','.') }}</div>
                                        </div>
                                    </div>
                                    <div class="small text-muted">
                                        <strong>Laba Kotor:</strong> Rp {{ number_format($dailyLabaKotor,0,',','.') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- =============== RINCIAN PER SEGMEN (CARD) =============== -->
                    <div class="row g-3 mb-4">
                        <!-- Wash Only -->
                        <div class="col-md-6">
                            <div class="card segment-card h-100 border-0 shadow-sm">
                                <div class="card-header bg-primary text-white fw-semibold py-2">
                                    <i class="bi bi-droplet-half me-1"></i> Segmen Cuci Kendaraan
                                </div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between py-1">
                                        <span class="text-muted">Pemasukan Cuci</span>
                                        <span class="fw-bold text-success">Rp {{ number_format($dailyWashIncome,0,',','.') }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between py-1">
                                        <span class="text-muted">Pengeluaran Wash</span>
                                        <span class="fw-bold text-danger">Rp {{ number_format($dailyWashExpense,0,',','.') }}</span>
                                    </div>
                                    <hr class="my-2">
                                    <div class="d-flex justify-content-between py-1">
                                        <span class="fw-semibold">Laba Bersih Cuci</span>
                                        <span class="fw-bold {{ $dailyWashNetProfit >= 0 ? 'text-primary' : 'text-danger' }}">Rp {{ number_format($dailyWashNetProfit,0,',','.') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Caffe / Warkop -->
                        <div class="col-md-6">
                            <div class="card segment-card h-100 border-0 shadow-sm">
                                <div class="card-header bg-warning text-dark fw-semibold py-2">
                                    <i class="bi bi-cup-hot me-1"></i> Segmen Caffe / Warkop
                                </div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between py-1">
                                        <span class="text-muted">Modal Awal</span>
                                        <span class="fw-bold text-danger">Rp {{ number_format($dailyCaffeInitialCapital,0,',','.') }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between py-1">
                                        <span class="text-muted">Pendapatan</span>
                                        <span class="fw-bold text-success">Rp {{ number_format($dailyCaffeRevenue,0,',','.') }}</span>
                                    </div>
                                    <hr class="my-2">
                                    <div class="d-flex justify-content-between py-1">
                                        <span class="fw-semibold">Selisih</span>
                                        <span class="fw-bold {{ ($dailyCaffeRevenue - $dailyCaffeInitialCapital) >= 0 ? 'text-primary' : 'text-danger' }}">Rp {{ number_format($dailyCaffeRevenue - $dailyCaffeInitialCapital,0,',','.') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- =============== RINCIAN KOMISI PER KARYAWAN =============== -->
                    @if($dailyCommissionDetail->count() > 0)
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-warning bg-opacity-25 fw-semibold py-2 d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-person-gear me-1"></i> Rincian Komisi per Karyawan</span>
                            <span class="badge bg-warning text-dark">Total: -Rp {{ number_format($dailyCommission,0,',','.') }}</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 8%;" class="text-center">No</th>
                                        <th style="width: 52%;">Nama Karyawan</th>
                                        <th class="text-center" style="width: 20%;">Item Dikerjakan</th>
                                        <th class="text-end" style="width: 20%;">Komisi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($dailyCommissionDetail as $idx => $d)
                                    <tr>
                                        <td class="text-center">{{ $idx + 1 }}</td>
                                        <td><i class="bi bi-person-circle me-2 text-muted"></i>{{ $d->name }}</td>
                                        <td class="text-center"><span class="badge bg-light text-dark">{{ number_format((int)$d->item_count,0,',','.') }} item</span></td>
                                        <td class="text-end fw-semibold text-danger">Rp {{ number_format((int)$d->total_commission,0,',','.') }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="table-warning fw-bold">
                                        <td colspan="3" class="text-end">TOTAL POTONGAN KOMISI &rarr;</td>
                                        <td class="text-end text-danger">- Rp {{ number_format($dailyCommission,0,',','.') }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    @endif

                    <!-- =============== STATISTIK (2 KOLOM) =============== -->
                    <div class="row g-3 mb-4">
                        <!-- Breakdown Layanan -->
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-header bg-info bg-opacity-25 fw-semibold py-2">
                                    <i class="bi bi-list-check me-1"></i> Data per Layanan
                                    <span class="small text-muted fw-normal float-end mt-1">(berdasarkan item transaksi)</span>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm align-middle mb-0">
                                        <thead class="table-light"><tr><th>Layanan</th><th class="text-end">Qty</th><th class="text-end">Total</th></tr></thead>
                                        <tbody>
                                            @forelse($dailyByService as $r)
                                            <tr>
                                                <td>{{ $r->service_name }}</td>
                                                <td class="text-end">{{ number_format($r->total_qty,0,',','.') }}</td>
                                                <td class="text-end">Rp {{ number_format($r->amount,0,',','.') }}</td>
                                            </tr>
                                            @empty
                                            <tr><td colspan="3" class="text-center text-muted py-3">-</td></tr>
                                            @endforelse
                                        </tbody>
                                        <tfoot>
                                            <tr class="table-secondary fw-bold">
                                                <td>Total Sesuai Item Layanan</td>
                                                <td class="text-end">{{ number_format($dailyByService->sum('total_qty'),0,',','.') }}</td>
                                                <td class="text-end">Rp {{ number_format($dailySvcTotal,0,',','.') }}</td>
                                            </tr>
                                            @if($dailySvcDiff != 0 || $dailyDiscountTotal > 0)
                                            <tr class="table-danger fw-semibold">
                                                <td>
                                                    <i class="bi bi-dash-circle me-1"></i>Diskon / Penyesuaian
                                                    @if($dailyDiscountTotal > 0)
                                                    <span class="small text-muted fw-normal d-block">(Total Diskon: Rp {{ number_format($dailyDiscountTotal,0,',','.') }})</span>
                                                    @endif
                                                </td>
                                                <td class="text-end"></td>
                                                <td class="text-end">Rp {{ $dailySvcDiff >= 0 ? '+' : '' }}{{ number_format($dailySvcDiff,0,',','.') }}</td>
                                            </tr>
                                            <tr class="table-success fw-bold">
                                                <td><i class="bi bi-check-circle me-1"></i> Total Pemasukan (cocok dengan card atas)</td>
                                                <td class="text-end"></td>
                                                <td class="text-end text-success">Rp {{ number_format($dailyIncome,0,',','.') }}</td>
                                            </tr>
                                            @else
                                            <tr class="table-success fw-bold">
                                                <td><i class="bi bi-check-circle me-1"></i> Total Pemasukan (cocok dengan card atas)</td>
                                                <td class="text-end"></td>
                                                <td class="text-end text-success">Rp {{ number_format($dailyIncome,0,',','.') }}</td>
                                            </tr>
                                            @endif
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <!-- Breakdown Metode Bayar + Setoran -->
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-header bg-info bg-opacity-25 fw-semibold py-2">
                                    <i class="bi bi-credit-card me-1"></i> Metode Pembayaran & Setoran
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm align-middle mb-0">
                                        <thead class="table-light"><tr><th>Metode</th><th class="text-end">Total</th></tr></thead>
                                        <tbody>
                                            @forelse($dailyByPayment as $r)
                                            <tr>
                                                <td>
                                                    @if($r->payment_method == 'cash')<i class="bi bi-cash-coin text-success me-1"></i>
                                                    @elseif($r->payment_method == 'qris')<i class="bi bi-qr-code-scan text-primary me-1"></i>
                                                    @else<i class="bi bi-building text-info me-1"></i>@endif
                                                    {{ strtoupper($r->payment_method) }}
                                                </td>
                                                <td class="text-end">Rp {{ number_format($r->amount,0,',','.') }}</td>
                                            </tr>
                                            @empty
                                            <tr><td colspan="2" class="text-center text-muted py-3">-</td></tr>
                                            @endforelse
                                            <tr class="table-secondary fw-bold">
                                                <td><i class="bi bi-check-all me-1"></i>Total (cocok dengan Pemasukan)</td>
                                                <td class="text-end">Rp {{ number_format($dailyByPayment->sum('amount'),0,',','.') }}</td>
                                            </tr>
                                            <tr class="table-warning">
                                                <td class="fw-semibold"><i class="bi bi-wallet2 me-1"></i>Setoran Cash (Cash - Pengeluaran)</td>
                                                <td class="text-end fw-bold {{ $dailySetoranCash < 0 ? 'text-danger' : '' }}">Rp {{ number_format($dailySetoranCash,0,',','.') }}</td>
                                            </tr>
                                            <tr class="table-primary">
                                                <td class="fw-semibold"><i class="bi bi-wallet-fill me-1"></i>Setoran Cash BERSIH (- Komisi)</td>
                                                <td class="text-end fw-bold {{ $dailySetoranCashBersih < 0 ? 'text-danger' : 'text-primary' }}">Rp {{ number_format($dailySetoranCashBersih,0,',','.') }}</td>
                                            </tr>
                                            @if($loyaltyBonusCount > 0)
                                            <tr class="table-success">
                                                <td class="fw-semibold"><i class="bi bi-gift me-1"></i>Transaksi Bonus Gratis</td>
                                                <td class="text-end fw-bold">{{ $loyaltyBonusCount }}x</td>
                                            </tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- =============== TABEL RINCIAN TRANSAKSI =============== -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-success bg-opacity-25 fw-semibold py-2 d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-journal-text me-1"></i> Rincian Semua Transaksi Pemasukan</span>
                            <span class="small text-muted">{{ $dailyTrxCount }} transaksi</span>
                        </div>
                        <div class="table-responsive table-responsive-mobile">
                            <table class="table table-sm table-striped table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 5%;">No</th>
                                        <th style="width: 10%;">Tanggal</th>
                                        <th style="width: 8%;">Waktu</th>
                                        <th style="width: 8%;">Antri</th>
                                        <th style="width: 15%;">No. Transaksi</th>
                                        <th style="width: 14%;">Kasir</th>
                                        <th style="width: 15%;">Metode</th>
                                        <th class="text-end" style="width: 25%;">Nominal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($dailyIncomeRows as $index => $r)
                                    @php
                                        $rNotes = strtolower(trim((string) ($r->notes ?? '')));
                                        $isBonusRow = str_starts_with($rNotes, 'bonus_cuci');
                                    @endphp
                                    <tr @if($isBonusRow) class="table-success" @endif>
                                        <td class="text-center" data-label="No">{{ $index + 1 }}</td>
                                        <td data-label="Tanggal">{{ $r->created_at->format('Y-m-d') }}</td>
                                        <td data-label="Waktu">{{ $r->created_at->format('H:i') }}</td>
                                        <td data-label="Antri">
                                            {{ $r->queue_number ?? '-' }}
                                            @if($isBonusRow) <span class="badge bg-success ms-1"><i class="bi bi-gift"></i></span>@endif
                                        </td>
                                        <td class="font-monospace small" data-label="No. Transaksi">{{ $r->transaction_number }}</td>
                                        <td data-label="Kasir">{{ $r->user->name ?? '-' }}</td>
                                        <td data-label="Metode">
                                            @if($isBonusRow)<span class="badge bg-success mb-1"><i class="bi bi-gift me-1"></i>BONUS</span><br>@endif
                                            <span class="badge bg-secondary">{{ strtoupper($r->payment_method) }}</span>
                                        </td>
                                        <td class="text-end fw-semibold" data-label="Nominal">
                                            Rp {{ number_format($r->total_amount,0,',','.') }}
                                            @if($isBonusRow && ($r->discount_amount ?? 0) > 0)
                                            <br><small class="text-success fw-bold"><i class="bi bi-percent me-1"></i>Diskon Bonus: Rp {{ number_format($r->discount_amount,0,',','.') }}</small>
                                            @endif
                                        </td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="8" class="text-center py-4 text-muted">Tidak ada data pemasukan</td></tr>
                                    @endforelse
                                </tbody>
                                <tfoot>
                                    <tr class="table-success fw-bold">
                                        <td colspan="7" class="text-end">TOTAL PEMASUKAN &rarr;</td>
                                        <td class="text-end text-success">Rp {{ number_format($dailyIncome,0,',','.') }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- =============== RINCIAN PENGELUARAN =============== -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-danger bg-opacity-25 fw-semibold py-2 d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-journal-x me-1"></i> Rincian Pengeluaran</span>
                            <span class="small text-muted">{{ $dailyExpCount }} item</span>
                        </div>
                        <div class="table-responsive table-responsive-mobile">
                            <table class="table table-sm table-striped table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 5%;">No</th>
                                        <th style="width: 15%;">Tanggal</th>
                                        <th style="width: 50%;">Deskripsi / Keterangan</th>
                                        <th class="text-end" style="width: 30%;">Nominal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($dailyExpenseRows as $index => $r)
                                    <tr>
                                        <td class="text-center" data-label="No">{{ $index + 1 }}</td>
                                        <td data-label="Tanggal">{{ \Carbon\Carbon::parse($r->transaction_date)->format('Y-m-d') }}</td>
                                        <td data-label="Deskripsi">{{ $r->description }}</td>
                                        <td class="text-end fw-semibold text-danger" data-label="Nominal">Rp {{ number_format($r->amount,0,',','.') }}</td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="4" class="text-center py-4 text-muted">Tidak ada data pengeluaran</td></tr>
                                    @endforelse
                                </tbody>
                                <tfoot>
                                    <tr class="table-danger fw-bold">
                                        <td colspan="3" class="text-end">TOTAL PENGELUARAN &rarr;</td>
                                        <td class="text-end text-danger">Rp {{ number_format($dailyExpense,0,',','.') }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- =============== LOYALTY & MEMBER (Statistik) =============== -->
                    <h6 class="fw-bold text-muted mb-3 text-uppercase small mt-5"><i class="bi bi-people-fill text-info me-1"></i> Statistik Member & Loyalty</h6>
                    <div class="row g-3 mb-4">
                        <!-- Statistik Card -->
                        <div class="col-md-4">
                            <div class="card member-stat-card border-0 shadow-sm h-100">
                                <div class="card-body text-center">
                                    <i class="bi bi-person-vcard text-info fs-2 mb-2"></i>
                                    <div class="display-6 fw-bold">{{ number_format($memberActiveCount ?? 0, 0, ',', '.') }}</div>
                                    <div class="small text-muted text-uppercase fw-bold">Member Aktif</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card member-stat-card border-0 shadow-sm h-100">
                                <div class="card-body text-center">
                                    <i class="bi bi-person-plus-fill text-success fs-2 mb-2"></i>
                                    <div class="display-6 fw-bold">{{ number_format($memberNewDailyCount ?? 0, 0, ',', '.') }}</div>
                                    <div class="small text-muted text-uppercase fw-bold">Member Baru</div>
                                    <div class="small text-muted">Periode ini</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card member-stat-card border-0 shadow-sm h-100">
                                <div class="card-body text-center">
                                    <i class="bi bi-ticket-perforated-fill text-warning fs-2 mb-2"></i>
                                    <div class="display-6 fw-bold">{{ number_format($dailyRewardRedemptionCount ?? 0, 0, ',', '.') }}</div>
                                    <div class="small text-muted text-uppercase fw-bold">Reward Dipakai</div>
                                    <div class="small text-muted">Periode ini</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Level + Reward + Top Member 2 Kolom -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-header bg-info bg-opacity-25 fw-semibold py-2">
                                    <i class="bi bi-award me-1"></i> Distribusi Level Member
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm align-middle mb-0">
                                        <thead class="table-light"><tr><th>Level</th><th class="text-end">Member</th><th class="text-end">Diskon</th></tr></thead>
                                        <tbody>
                                            @forelse($levelDistribution as $level)
                                            <tr>
                                                <td>
                                                    <div class="fw-semibold">{{ $level->name }}</div>
                                                    <div class="small text-muted">Rank {{ $level->priority_rank }}</div>
                                                </td>
                                                <td class="text-end">{{ number_format((int) $level->members_count, 0, ',', '.') }}</td>
                                                <td class="text-end">{{ rtrim(rtrim(number_format((float) $level->discount_percent, 2, ',', '.'), '0'), ',') }}%</td>
                                            </tr>
                                            @empty
                                            <tr><td colspan="3" class="text-center text-muted py-3">Tidak ada data</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-header bg-info bg-opacity-25 fw-semibold py-2">
                                    <i class="bi bi-ticket-perforated me-1"></i> Reward Redemption
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm align-middle mb-0">
                                        <thead class="table-light"><tr><th>Voucher</th><th>Member</th><th>Plat</th><th>Tanggal</th></tr></thead>
                                        <tbody>
                                            @forelse($dailyRewardRedemptions as $redemption)
                                            <tr>
                                                <td class="small">{{ $redemption->voucher?->code ?? '-' }}</td>
                                                <td>{{ $redemption->voucher?->member?->name ?? $redemption->voucher?->customer?->name ?? '-' }}</td>
                                                <td>{{ $redemption->voucher?->vehicle_plate ?? '-' }}</td>
                                                <td class="small">{{ $redemption->redeemed_at?->format('d-m-Y H:i') ?? '-' }}</td>
                                            </tr>
                                            @empty
                                            <tr><td colspan="4" class="text-center text-muted py-3">Belum ada reward</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Top Member -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-info bg-opacity-25 fw-semibold py-2">
                            <i class="bi bi-trophy-fill text-warning me-1"></i> Top Member
                        </div>
                        <div class="table-responsive table-responsive-mobile">
                            <table class="table table-sm table-striped align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Member</th>
                                        <th>No Member</th>
                                        <th>Level</th>
                                        <th class="text-end">Trx</th>
                                        <th class="text-end">Kunjungan</th>
                                        <th class="text-end">Total Spending</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($topMembers as $member)
                                    <tr>
                                        <td class="fw-semibold" data-label="Member">{{ $member->name }}</td>
                                        <td class="small font-monospace" data-label="No Member">{{ $member->member_number }}</td>
                                        <td data-label="Level">{{ $member->level?->name ?? 'Bronze' }}</td>
                                        <td class="text-end" data-label="Trx">{{ number_format((int) $member->total_transactions, 0, ',', '.') }}</td>
                                        <td class="text-end" data-label="Kunjungan">{{ number_format((int) $member->total_visits, 0, ',', '.') }}</td>
                                        <td class="text-end fw-bold" data-label="Total Spending">Rp {{ number_format((float) $member->total_spending, 0, ',', '.') }}</td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="6" class="text-center py-3 text-muted">Belum ada data</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Loyalty Progress -->
                    <div class="card border-0 shadow-sm mb-2">
                        <div class="card-header bg-info bg-opacity-25 fw-semibold py-2">
                            <i class="bi bi-star-fill text-warning me-1"></i> Loyalty Progress (Bonus Cuci)
                        </div>
                        <div class="table-responsive table-responsive-mobile">
                            <table class="table table-sm table-striped align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Member</th>
                                        <th>No Member</th>
                                        <th>Level</th>
                                        <th>Plat</th>
                                        <th class="text-end">Progress</th>
                                        <th class="text-end">Sisa</th>
                                        <th class="text-end">Lifetime</th>
                                        <th>Terakhir</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($loyaltyProgressRows as $row)
                                    <tr>
                                        <td data-label="Member">{{ $row->member_name }}</td>
                                        <td class="small font-monospace" data-label="No Member">{{ $row->member_number }}</td>
                                        <td data-label="Level">{{ $row->level_name }}</td>
                                        <td data-label="Plat">{{ $row->vehicle_plate }}</td>
                                        <td class="text-end" data-label="Progress">
                                            <div class="progress" style="height: 8px;">
                                                @php $targetVal = (int)($row->target ?? 0); $pct = ($targetVal > 0) ? min(100, (int)$row->progress / $targetVal * 100) : 0; @endphp
                                                <div class="progress-bar bg-primary" style="width: {{ $pct }}%"></div>
                                            </div>
                                            <small>{{ $row->progress }}/{{ $row->target }}</small>
                                        </td>
                                        <td class="text-end fw-semibold {{ (int)$row->remaining <= 1 ? 'text-success' : '' }}" data-label="Sisa">{{ $row->remaining }}</td>
                                        <td class="text-end" data-label="Lifetime">{{ number_format((int) $row->lifetime_paid_count, 0, ',', '.') }}x</td>
                                        <td class="small" data-label="Terakhir">{{ $row->last_paid_at?->format('d-m-Y H:i') ?? '-' }}</td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="8" class="text-center py-3 text-muted">Belum ada data loyalty</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div> <!-- END DAILY TAB -->

                <!-- ====================================== -->
                <!-- TAB BULANAN -->
                <!-- ====================================== -->
                <div class="tab-pane fade" id="monthly-content">

                    <!-- FILTER BULANAN -->
                    <form method="get" class="row g-2 align-items-center mb-4 justify-content-end monthly-filter-form bg-light rounded p-3">
                        <input type="hidden" name="view" value="monthly">
                        <div class="col-auto fw-bold text-muted">Filter :</div>
                        <div class="col-auto"><label class="form-label mb-0">Bulan</label></div>
                        <div class="col-auto"><input type="month" name="month" value="{{ $month }}" class="form-control form-control-sm"></div>
                        <div class="col-auto">
                            <select name="vehicle_plate" class="form-select form-select-sm">
                                <option value="">Semua Plat</option>
                                @foreach(($knownVehiclePlates ?? []) as $plateOption)
                                    <option value="{{ $plateOption['plate'] }}" {{ ($vehiclePlate ?? '') === $plateOption['plate'] ? 'selected' : '' }}>
                                        {{ $plateOption['plate'] }}
                                        @if(!empty($plateOption['brand'])) - {{ $plateOption['brand'] }}@endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="bi bi-search me-1"></i>Tampilkan
                            </button>
                        </div>
                    </form>

                    <!-- =============== RINGKASAN UTAMA (CARD) =============== -->
                    <h6 class="fw-bold text-muted mb-3 text-uppercase small"><i class="bi bi-lightning-charge-fill text-warning me-1"></i> Ringkasan Bulan {{ $month }}</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <div class="card stat-card-summary h-100 border-0 shadow-sm bg-success bg-opacity-10">
                                <div class="card-body">
                                    <div class="d-flex">
                                        <div>
                                            <div class="small text-uppercase fw-bold text-success opacity-75">Pemasukan</div>
                                            <div class="display-6 fw-bold text-success">Rp {{ number_format($monthlyIncome,0,',','.') }}</div>
                                        </div>
                                        <div class="bg-success rounded-pill"><i class="bi bi-cash-stack text-white"></i></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stat-card-summary h-100 border-0 shadow-sm bg-danger bg-opacity-10">
                                <div class="card-body">
                                    <div class="d-flex">
                                        <div>
                                            <div class="small text-uppercase fw-bold text-danger opacity-75">Pengeluaran</div>
                                            <div class="display-6 fw-bold text-danger">Rp {{ number_format($monthlyExpense,0,',','.') }}</div>
                                        </div>
                                        <div class="bg-danger rounded-pill"><i class="bi bi-bag-dash text-white"></i></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stat-card-summary h-100 border-0 shadow-sm bg-warning bg-opacity-10">
                                <div class="card-body">
                                    <div class="d-flex">
                                        <div>
                                            <div class="small text-uppercase fw-bold text-warning opacity-75">Potongan Komisi</div>
                                            <div class="display-6 fw-bold text-warning">- Rp {{ number_format($monthlyCommission,0,',','.') }}</div>
                                        </div>
                                        <div class="bg-warning rounded-pill"><i class="bi bi-people-fill text-white"></i></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stat-card-summary h-100 border-0 shadow-sm {{ $monthlyTotalNetProfit >= 0 ? 'bg-primary bg-opacity-10' : 'bg-danger bg-opacity-10' }}">
                                <div class="card-body">
                                    <div class="d-flex">
                                        <div>
                                            <div class="small text-uppercase fw-bold opacity-75 {{ $monthlyTotalNetProfit >= 0 ? 'text-primary' : 'text-danger' }}">Laba Bersih Akhir</div>
                                            <div class="display-6 fw-bold {{ $monthlyTotalNetProfit >= 0 ? 'text-primary' : 'text-danger' }}">Rp {{ number_format($monthlyTotalNetProfit,0,',','.') }}</div>
                                        </div>
                                        <div class="rounded-pill {{ $monthlyTotalNetProfit >= 0 ? 'bg-primary' : 'bg-danger' }}">
                                            <i class="bi {{ $monthlyTotalNetProfit >= 0 ? 'bi-graph-up-arrow' : 'bi-graph-down-arrow' }} text-white"></i>
                                        </div>
                                    </div>
                                    <div class="small text-muted">
                                        <strong>Laba Kotor:</strong> Rp {{ number_format($monthlyLabaKotor,0,',','.') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Segmen Cuci + Caffe + Total (3 Kolom) -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="card segment-card h-100 border-0 shadow-sm">
                                <div class="card-header bg-primary text-white fw-semibold py-2"><i class="bi bi-droplet-half me-1"></i> Segmen Cuci</div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between py-1"><span class="text-muted">Pemasukan</span><span class="fw-bold text-success">Rp {{ number_format($monthlyWashIncome,0,',','.') }}</span></div>
                                    <div class="d-flex justify-content-between py-1"><span class="text-muted">Pengeluaran</span><span class="fw-bold text-danger">Rp {{ number_format($monthlyWashExpense,0,',','.') }}</span></div>
                                    <hr class="my-2">
                                    <div class="d-flex justify-content-between py-1"><span class="fw-semibold">Laba Bersih Cuci</span><span class="fw-bold {{ $monthlyWashNetProfit >= 0 ? 'text-primary' : 'text-danger' }}">Rp {{ number_format($monthlyWashNetProfit,0,',','.') }}</span></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card segment-card h-100 border-0 shadow-sm">
                                <div class="card-header bg-warning text-dark fw-semibold py-2"><i class="bi bi-cup-hot me-1"></i> Segmen Caffe</div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between py-1"><span class="text-muted">Modal Awal</span><span class="fw-bold text-danger">Rp {{ number_format($monthlyCaffeInitialCapital,0,',','.') }}</span></div>
                                    <div class="d-flex justify-content-between py-1"><span class="text-muted">Pendapatan</span><span class="fw-bold text-success">Rp {{ number_format($monthlyCaffeRevenue,0,',','.') }}</span></div>
                                    <hr class="my-2">
                                    <div class="d-flex justify-content-between py-1"><span class="fw-semibold">Selisih</span><span class="fw-bold {{ ($monthlyCaffeRevenue - $monthlyCaffeInitialCapital) >= 0 ? 'text-primary' : 'text-danger' }}">Rp {{ number_format($monthlyCaffeRevenue - $monthlyCaffeInitialCapital,0,',','.') }}</span></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card segment-card h-100 border-0 shadow-sm">
                                <div class="card-header bg-dark text-white fw-semibold py-2"><i class="bi bi-calculator me-1"></i> Ringkasan Total</div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between py-1"><span class="text-muted">Laba Kotor</span><span class="fw-bold">Rp {{ number_format($monthlyLabaKotor,0,',','.') }}</span></div>
                                    <div class="d-flex justify-content-between py-1"><span class="text-muted">(-) Komisi</span><span class="fw-bold text-danger">- Rp {{ number_format($monthlyCommission,0,',','.') }}</span></div>
                                    <hr class="my-2">
                                    <div class="d-flex justify-content-between py-1"><span class="fw-semibold">Laba Bersih Akhir</span><span class="fw-bold fs-5 {{ $monthlyTotalNetProfit >= 0 ? 'text-primary' : 'text-danger' }}">Rp {{ number_format($monthlyTotalNetProfit,0,',','.') }}</span></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Statistik Member Card -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="card member-stat-card border-0 shadow-sm h-100">
                                <div class="card-body text-center">
                                    <i class="bi bi-person-vcard text-info mb-2"></i>
                                    <div class="display-6 fw-bold">{{ number_format($memberActiveCount ?? 0, 0, ',', '.') }}</div>
                                    <div class="small text-muted text-uppercase fw-bold">Member Aktif</div>
                                    <div class="small text-muted">Terdaftar di sistem</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card member-stat-card border-0 shadow-sm h-100">
                                <div class="card-body text-center">
                                    <i class="bi bi-person-plus-fill text-success mb-2"></i>
                                    <div class="display-6 fw-bold">{{ number_format($memberNewMonthlyCount ?? 0, 0, ',', '.') }}</div>
                                    <div class="small text-muted text-uppercase fw-bold">Member Baru Bulan Ini</div>
                                    <div class="small text-muted">Tambahan member bulan ini</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card member-stat-card border-0 shadow-sm h-100">
                                <div class="card-body text-center">
                                    <i class="bi bi-ticket-perforated-fill text-warning mb-2"></i>
                                    <div class="display-6 fw-bold">{{ number_format($monthlyRewardRedemptionCount ?? 0, 0, ',', '.') }}</div>
                                    <div class="small text-muted text-uppercase fw-bold">Reward Dipakai</div>
                                    <div class="small text-muted">Total penukaran voucher</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- =============== REKAP HARIAN PER TANGGAL =============== -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-primary bg-opacity-25 fw-semibold py-2 d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-calendar-week me-1"></i> Rekap Harian (Per Tanggal)</span>
                            <span class="badge bg-danger text-white">Total Komisi Bulan Ini: -Rp {{ number_format($monthlyCommission,0,',','.') }}</span>
                        </div>
                        <div class="table-responsive table-responsive-mobile">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 18%;">Tanggal</th>
                                        <th class="text-end" style="width: 20%;">Pemasukan</th>
                                        <th class="text-end" style="width: 20%;">Pengeluaran</th>
                                        <th class="text-end" style="width: 20%;">(-) Komisi</th>
                                        <th class="text-end" style="width: 22%;">Laba Bersih</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $incomeMap = collect($monthlyDailyIncome)->keyBy('d');
                                        $expenseMap = collect($monthlyDailyExpense)->keyBy('d');
                                        $days = collect($incomeMap->keys())->merge($expenseMap->keys())->merge($monthlyDailyCommissionMap->keys())->unique()->sort();
                                    @endphp
                                    @forelse($days as $d)
                                    @php
                                        $inc = (float)($incomeMap[$d]->total ?? 0);
                                        $exp = (float)($expenseMap[$d]->total ?? 0);
                                        $com = (float)($monthlyDailyCommissionMap[$d]->total ?? 0);
                                        $net = $inc - $exp - $com;
                                    @endphp
                                    <tr>
                                        <td class="fw-semibold" data-label="Tanggal">{{ $d }}</td>
                                        <td class="text-end text-success" data-label="Pemasukan">Rp {{ number_format($inc,0,',','.') }}</td>
                                        <td class="text-end text-danger" data-label="Pengeluaran">Rp {{ number_format($exp,0,',','.') }}</td>
                                        <td class="text-end text-danger" data-label="(-) Komisi">- Rp {{ number_format($com,0,',','.') }}</td>
                                        <td class="text-end fw-bold {{ $net < 0 ? 'text-danger' : 'text-primary' }}" data-label="Laba Bersih">Rp {{ number_format($net,0,',','.') }}</td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="5" class="text-center py-4 text-muted">Tidak ada data</td></tr>
                                    @endforelse
                                </tbody>
                                <tfoot>
                                    <tr class="table-secondary fw-bold">
                                        <td class="text-end">TOTAL BULAN INI &rarr;</td>
                                        <td class="text-end text-success">Rp {{ number_format($monthlyIncome,0,',','.') }}</td>
                                        <td class="text-end text-danger">Rp {{ number_format($monthlyExpense,0,',','.') }}</td>
                                        <td class="text-end text-danger">- Rp {{ number_format($monthlyCommission,0,',','.') }}</td>
                                        <td class="text-end {{ $monthlyTotalNetProfit < 0 ? 'text-danger' : 'text-primary' }}">Rp {{ number_format($monthlyTotalNetProfit,0,',','.') }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- =============== STATISTIK LAYANAN + METODE =============== -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-header bg-info bg-opacity-25 fw-semibold py-2">
                                    <i class="bi bi-list-check me-1"></i> Statistik per Layanan
                                    <span class="small text-muted fw-normal float-end mt-1">(berdasarkan item transaksi)</span>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm align-middle mb-0">
                                        <thead class="table-light"><tr><th>Layanan</th><th class="text-end">Qty</th><th class="text-end">Total</th></tr></thead>
                                        <tbody>
                                            @forelse($monthlyByService as $r)
                                            <tr><td>{{ $r->service_name }}</td><td class="text-end">{{ number_format($r->total_qty,0,',','.') }}</td><td class="text-end">Rp {{ number_format($r->amount,0,',','.') }}</td></tr>
                                            @empty<tr><td colspan="3" class="text-center text-muted py-3">-</td></tr>
                                            @endforelse
                                        </tbody>
                                        <tfoot>
                                            <tr class="table-secondary fw-bold">
                                                <td>Total Sesuai Item Layanan</td>
                                                <td class="text-end">{{ number_format($monthlyByService->sum('total_qty'),0,',','.') }}</td>
                                                <td class="text-end">Rp {{ number_format($monthlySvcTotal,0,',','.') }}</td>
                                            </tr>
                                            @if($monthlySvcDiff != 0)
                                            <tr class="table-danger fw-semibold">
                                                <td><i class="bi bi-dash-circle me-1"></i>Diskon / Penyesuaian</td>
                                                <td class="text-end"></td>
                                                <td class="text-end">Rp {{ $monthlySvcDiff >= 0 ? '+' : '' }}{{ number_format($monthlySvcDiff,0,',','.') }}</td>
                                            </tr>
                                            <tr class="table-success fw-bold">
                                                <td><i class="bi bi-check-circle me-1"></i> Total Pemasukan (cocok dengan card atas)</td>
                                                <td class="text-end"></td>
                                                <td class="text-end text-success">Rp {{ number_format($monthlyIncome,0,',','.') }}</td>
                                            </tr>
                                            @else
                                            <tr class="table-success fw-bold">
                                                <td><i class="bi bi-check-circle me-1"></i> Total Pemasukan (cocok dengan card atas)</td>
                                                <td class="text-end"></td>
                                                <td class="text-end text-success">Rp {{ number_format($monthlyIncome,0,',','.') }}</td>
                                            </tr>
                                            @endif
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-header bg-info bg-opacity-25 fw-semibold py-2"><i class="bi bi-credit-card me-1"></i> Metode Bayar & Setoran</div>
                                <div class="table-responsive">
                                    <table class="table table-sm align-middle mb-0">
                                        <thead class="table-light"><tr><th>Metode</th><th class="text-end">Total</th></tr></thead>
                                        <tbody>
                                            @forelse($monthlyByPayment as $r)
                                            <tr>
                                                <td>
                                                    @if($r->payment_method == 'cash')<i class="bi bi-cash-coin text-success me-1"></i>
                                                    @elseif($r->payment_method == 'qris')<i class="bi bi-qr-code-scan text-primary me-1"></i>
                                                    @else<i class="bi bi-building text-info me-1"></i>@endif
                                                    {{ strtoupper($r->payment_method) }}
                                                </td>
                                                <td class="text-end">Rp {{ number_format($r->amount,0,',','.') }}</td>
                                            </tr>
                                            @empty<tr><td colspan="2" class="text-center text-muted py-3">-</td></tr>
                                            @endforelse
                                            <tr class="table-secondary fw-bold">
                                                <td><i class="bi bi-check-all me-1"></i>Total (cocok dengan Pemasukan)</td>
                                                <td class="text-end">Rp {{ number_format($monthlyByPayment->sum('amount'),0,',','.') }}</td>
                                            </tr>
                                            <tr class="table-warning"><td class="fw-semibold"><i class="bi bi-wallet2 me-1"></i>Setoran Cash (Cash - Pengeluaran)</td><td class="text-end fw-bold {{ $monthlySetoranCash < 0 ? 'text-danger' : '' }}">Rp {{ number_format($monthlySetoranCash,0,',','.') }}</td></tr>
                                            <tr class="table-primary"><td class="fw-semibold"><i class="bi bi-wallet-fill me-1"></i>Setoran Cash BERSIH (- Komisi)</td><td class="text-end fw-bold {{ $monthlySetoranCashBersih < 0 ? 'text-danger' : 'text-primary' }}">Rp {{ number_format($monthlySetoranCashBersih,0,',','.') }}</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- =============== REWARD + TOP MEMBER =============== -->
                    <div class="row g-3 mb-2">
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-header bg-info bg-opacity-25 fw-semibold py-2"><i class="bi bi-ticket-perforated me-1"></i> Reward Redemption Bulanan</div>
                                <div class="table-responsive">
                                    <table class="table table-sm align-middle mb-0">
                                        <thead class="table-light"><tr><th>Voucher</th><th>Member</th><th>Plat</th><th>Tanggal</th></tr></thead>
                                        <tbody>
                                            @forelse($monthlyRewardRedemptions as $redemption)
                                            <tr>
                                                <td class="small">{{ $redemption->voucher?->code ?? '-' }}</td>
                                                <td>{{ $redemption->voucher?->member?->name ?? $redemption->voucher?->customer?->name ?? '-' }}</td>
                                                <td>{{ $redemption->voucher?->vehicle_plate ?? '-' }}</td>
                                                <td class="small">{{ $redemption->redeemed_at?->format('d-m-Y H:i') ?? '-' }}</td>
                                            </tr>
                                            @empty<tr><td colspan="4" class="text-center text-muted py-3">Belum ada reward</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-header bg-info bg-opacity-25 fw-semibold py-2"><i class="bi bi-trophy-fill text-warning me-1"></i> Top 5 Member Bulan Ini</div>
                                <div class="table-responsive">
                                    <table class="table table-sm align-middle mb-0">
                                        <thead class="table-light"><tr><th>Member</th><th>Level</th><th class="text-end">Spending</th></tr></thead>
                                        <tbody>
                                            @forelse($topMembers->take(5) as $member)
                                            <tr>
                                                <td><div class="fw-semibold">{{ $member->name }}</div><div class="small text-muted font-monospace">{{ $member->member_number }}</div></td>
                                                <td>{{ $member->level?->name ?? 'Bronze' }}</td>
                                                <td class="text-end fw-bold">Rp {{ number_format((float) $member->total_spending, 0, ',', '.') }}</td>
                                            </tr>
                                            @empty<tr><td colspan="3" class="text-center text-muted py-3">Belum ada data</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                </div> <!-- END MONTHLY TAB -->

            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('month') && !urlParams.has('start_date') && !urlParams.has('end_date')) {
            new bootstrap.Tab(document.querySelector('#monthly-tab')).show();
        }
    });
</script>
<script>
    (function() {
        function q(name) { return new URLSearchParams(window.location.search).get(name); }
        function buildExportUrl(base) {
            var p = new URLSearchParams();
            ['start_date','end_date','month','vehicle_plate','view'].forEach(function(n){ var v=q(n); if(v) p.set(n,v); });
            var s = p.toString();
            return base + (s ? (base.indexOf('?') >= 0 ? '&' : '?') + s : '');
        }
        document.getElementById('btnExportPdf').href = buildExportUrl("{{ route('wash.reports.pdf') }}");
        document.getElementById('btnExportExcel').href = buildExportUrl("{{ route('wash.reports.excel') }}");
        function bindDownload(button) {
            if (!button) return;
            button.addEventListener('click', function(e) {
                if (!button.href) return;
                e.preventDefault();
                var iframe = document.getElementById('wash-report-download-frame');
                if (!iframe) {
                    iframe = document.createElement('iframe');
                    iframe.id = 'wash-report-download-frame';
                    iframe.style.display = 'none';
                    document.body.appendChild(iframe);
                }
                var currentLabel = button.innerHTML;
                button.classList.add('disabled');
                button.setAttribute('aria-disabled', 'true');
                button.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Memproses...';
                var downloadUrl = new URL(button.href, window.location.origin);
                downloadUrl.searchParams.set('_ts', Date.now().toString());
                iframe.src = downloadUrl.toString();
                setTimeout(function() {
                    button.classList.remove('disabled');
                    button.removeAttribute('aria-disabled');
                    button.innerHTML = currentLabel;
                }, 1800);
            });
        }
        bindDownload(document.getElementById('btnExportPdf'));
        bindDownload(document.getElementById('btnExportExcel'));
    })();
</script>
@endsection
