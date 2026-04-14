<style>
/* Base ID Card Structure */
.id-card-item {
    width: 54mm;
    height: 85.6mm;
    border-radius: 4.5mm;
    overflow: hidden;
    background: linear-gradient(180deg, #ffffff 0%, #fffaf5 100%);
    position: relative;
    box-shadow: 0 12px 30px rgba(15, 23, 42, 0.12);
    border: 1px solid #dbe3f0;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
    flex-shrink: 0;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
    text-rendering: optimizeLegibility;
}

/* Capture Helper (Used by html2canvas) */
.id-card-item.is-capturing {
    width: 204.1px !important;
    height: 323.5px !important;
    border-radius: 17px !important;
    box-shadow: none !important;
    border: none !important;
}

/* Page specific layouts */
.employee-id-card-page .employee-print-sheet.is-preview {
    display: flex;
    justify-content: center;
    gap: 1.5rem;
    margin-top: 1rem;
    padding: 2rem;
    background: #f1f5f9;
    border-radius: 1rem;
}

/* Bulk Print Layout */
.id-card-sheet {
    display: flex;
    flex-wrap: wrap;
    gap: 1.5rem;
    justify-content: center;
}

.id-card-pair {
    display: flex;
    gap: 1.5rem;
    margin-bottom: 2rem;
}

@media (max-width: 767.98px) {
    .id-card-pair {
        flex-direction: column;
        align-items: center;
    }
}

/* Brand Themes */
.brand-gtwash .header-bg, .brand-gtwash .back-header-bg { background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); }
.brand-gtwash .wave-accent-top { background: #86efac; }
.brand-gtwash .side-curve { border-color: #86efac; }
.brand-gtwash .job-title, .brand-gtwash .back-contact-label { color: #16a34a; }

.brand-mstorenet .header-bg, .brand-mstorenet .back-header-bg { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); }
.brand-mstorenet .wave-accent-top { background: #93c5fd; }
.brand-mstorenet .side-curve { border-color: #93c5fd; }
.brand-mstorenet .job-title, .brand-mstorenet .back-contact-label { color: #2563eb; }

/* Front Design Elements (Old Design Restore) */
.header-bg {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 29mm;
    background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
    clip-path: polygon(0 0, 100% 0, 100% 70%, 0 100%);
    z-index: 1;
}

.wave-accent-top {
    position: absolute;
    top: 0;
    right: 0;
    width: 100%;
    height: 31mm;
    background: #fdba74;
    clip-path: polygon(30% 0, 100% 0, 100% 85%, 0 100%);
    z-index: 0;
    opacity: 0.55;
}

.wave-accent-bottom {
    position: absolute;
    bottom: -6mm;
    right: -6mm;
    width: 26mm;
    height: 26mm;
    border-radius: 50%;
    background: #fef08a;
    z-index: 0;
    opacity: 0.6;
}

.side-curve {
    position: absolute;
    left: -8mm;
    bottom: 12mm;
    width: 18mm;
    height: 46mm;
    border: 2.4mm solid #fdba74;
    border-radius: 999px;
    opacity: 0.35;
    z-index: 0;
    transform: rotate(-15deg);
}

.logo-container {
    position: absolute;
    top: 4.5mm;
    left: 4mm;
    right: 4mm;
    z-index: 3;
    display: flex;
    align-items: center;
    gap: 2.6mm;
    color: #fff;
}

.logo-diamond {
    width: 11mm;
    height: 11mm;
    position: relative;
    border-radius: 0.6mm;
    background: #ffffff;
    border: 0.7pt solid rgba(255, 255, 255, 0.96);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    transform: rotate(45deg);
    box-shadow: 0 5px 12px rgba(15, 23, 42, 0.16);
}

.logo-diamond::before {
    content: "";
    position: absolute;
    inset: 0.65mm;
    border: 0.55pt solid rgba(212, 175, 55, 0.92);
    border-radius: 0.4mm;
    pointer-events: none;
}

.logo-diamond .logo-img-wrapper {
    width: 100%;
    height: 100%;
    position: relative;
    transform: rotate(-45deg);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.logo-diamond .logo-img {
    width: 85%;
    height: 85%;
    object-fit: contain;
    display: block;
}

.company-copy {
    min-width: 0;
    flex: 1;
}

.company-name {
    font-size: 3mm;
    font-weight: 900;
    letter-spacing: 0.06em;
    line-height: 1.1;
    text-transform: uppercase;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    color: #fff;
}

.company-tagline {
    margin-top: 0.7mm;
    font-size: 1.55mm;
    font-weight: 700;
    letter-spacing: 0.07em;
    line-height: 1.2;
    opacity: 0.95;
    text-transform: none;
    color: #fff;
}

.profile-container {
    position: relative;
    z-index: 2;
    margin-top: 17.8mm;
    display: flex;
    justify-content: center;
}

.profile-image {
    width: 31mm;
    height: 31mm;
    border-radius: 50%;
    background: #ffffff;
    border: 1.5mm solid #ffffff;
    box-shadow: 0 10px 22px rgba(15, 23, 42, 0.16);
    overflow: hidden;
    box-sizing: border-box;
    display: flex;
    align-items: center;
    justify-content: center;
}

.photo-img-wrapper {
    width: 100%;
    height: 100%;
    position: relative;
    overflow: hidden;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.photo-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    border-radius: 50%; /* Double down for better canvas support */
}

.photo-placeholder {
    width: 100%;
    height: 100%;
    background: #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10mm;
    color: #cbd5e1;
    border-radius: 50%;
}

.content {
    position: relative;
    z-index: 2;
    margin-top: 2.8mm;
    margin-left: 3.3mm;
    margin-right: 3.3mm;
    padding: 2.4mm 2.8mm 2mm;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}

.name-block {
    margin: 0;
    font-size: 4.05mm;
    font-weight: 900;
    line-height: 1.05;
    color: #111827;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    max-width: 100%;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.job-title {
    margin: 1mm 0 0;
    font-size: 2.2mm;
    font-weight: 800;
    color: #f97316;
    text-transform: uppercase;
    letter-spacing: 0.1em;
}

.footer-info {
    position: absolute;
    left: 4mm;
    right: 4mm;
    bottom: 3mm;
    z-index: 2;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.9mm;
    padding-top: 2.2mm;
    border-top: 0.8pt solid rgba(148, 163, 184, 0.3);
}

.id-label {
    width: 100%;
    font-size: 1.9mm;
    font-weight: 800;
    color: #111827;
    letter-spacing: 0.12em;
    text-align: center;
    margin-bottom: 0.55mm;
}

.barcode-container {
    width: 100%;
    min-height: 10.6mm;
    background: rgba(255, 255, 255, 0.92);
    border-radius: 2.4mm;
    padding: 1.1mm 0.8mm 0.4mm;
    border: 0.8pt solid rgba(15, 23, 42, 0.12);
    display: flex;
    justify-content: center;
    align-items: center;
}

.barcode-svg {
    max-width: 100%;
    max-height: 8.2mm;
    height: auto;
}

.barcode-text {
    font-size: 1.55mm;
    font-weight: 800;
    color: #334155;
    letter-spacing: 0.18em;
    line-height: 1;
    text-transform: uppercase;
}

/* Back Design Elements (Old Design Restore) */
.id-card-back {
    background: radial-gradient(circle at top right, rgba(253, 186, 116, 0.22), transparent 30%),
                linear-gradient(180deg, #fffaf5 0%, #ffffff 100%);
}

.back-header-bg {
    position: absolute;
    inset: 0 0 auto 0;
    height: 22mm;
    background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
    border-bottom-left-radius: 7mm;
    z-index: 0;
}

.back-accent-circle {
    position: absolute;
    top: 7mm;
    right: -6mm;
    width: 24mm;
    height: 24mm;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.18);
    z-index: 1;
}

.back-accent-line {
    position: absolute;
    left: -7mm;
    bottom: 14mm;
    width: 16mm;
    height: 38mm;
    border: 2mm solid rgba(249, 115, 22, 0.25);
    border-radius: 999px;
    transform: rotate(-18deg);
    z-index: 0;
}

.back-brand-lockup {
    position: relative;
    z-index: 2;
    display: flex;
    align-items: center;
    gap: 2.6mm;
    padding: 4.6mm 4mm 0;
    color: #fff;
}

.back-logo-frame {
    width: 11.5mm;
    height: 11.5mm;
    border-radius: 3mm;
    background: rgba(255, 255, 255, 0.96);
    border: 0.7pt solid rgba(255, 255, 255, 0.98);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 6px 16px rgba(15, 23, 42, 0.15);
}

.back-logo-frame .logo-img-wrapper {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.back-logo-frame .logo-img {
    max-width: 80%;
    max-height: 80%;
    width: auto;
    height: auto;
}

.back-brand-name {
    font-size: 3mm;
    font-weight: 900;
    letter-spacing: 0.05em;
    line-height: 1.1;
    text-transform: uppercase;
    color: #fff;
}

.back-brand-slogan {
    margin-top: 0.7mm;
    font-size: 1.45mm;
    font-weight: 700;
    line-height: 1.2;
    opacity: 0.95;
    color: #fff;
}

.back-content {
    position: relative;
    z-index: 2;
    padding: 7mm 4mm 0;
}

.back-chip {
    display: inline-flex;
    align-items: center;
    min-height: 4.8mm;
    padding: 0.7mm 2mm;
    border-radius: 999px;
    background: #fff7ed;
    border: 0.8pt solid #fdba74;
    color: #c2410c;
    font-size: 1.6mm;
    font-weight: 800;
    letter-spacing: 0.08em;
}

.back-title {
    margin-top: 2.2mm;
    font-size: 3.2mm;
    font-weight: 900;
    line-height: 1.1;
    color: #111827;
    letter-spacing: 0.03em;
}

.back-description {
    margin: 1.8mm 0 0;
    font-size: 1.75mm;
    line-height: 1.45;
    color: #475569;
}

.back-contact-card {
    margin-top: 3.2mm;
    padding: 2.4mm 2.4mm 2.2mm;
    border-radius: 3mm;
    background: rgba(255, 255, 255, 0.9);
    border: 0.8pt solid rgba(148, 163, 184, 0.22);
    box-shadow: 0 8px 18px rgba(15, 23, 42, 0.06);
    display: flex;
    flex-direction: column;
    gap: 1.8mm;
}

.back-contact-item {
    display: flex;
    flex-direction: column;
    gap: 0.45mm;
}

.back-contact-label {
    font-size: 1.45mm;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #f97316;
}

.back-contact-value {
    font-size: 1.75mm;
    font-weight: 700;
    line-height: 1.35;
    color: #0f172a;
    word-break: break-word;
}

.back-footer {
    position: absolute;
    left: 4mm;
    right: 4mm;
    bottom: 4mm;
    z-index: 2;
    padding-top: 2mm;
    border-top: 0.8pt solid rgba(148, 163, 184, 0.28);
    text-align: center;
}

.back-footer-note {
    font-size: 1.55mm;
    font-weight: 800;
    letter-spacing: 0.14em;
    color: #334155;
}

.back-footer-warning {
    margin-top: 0.8mm;
    font-size: 1.55mm;
    line-height: 1.35;
    color: #64748b;
}

/* PRINT MEDIA QUERY */
@media print {
    body { background: none !important; padding: 0 !important; }
    
    .employee-id-toolbar, nav, footer, .btn, .no-print, .dropdown, .dropdown-menu {
        display: none !important;
    }

    body * {
        visibility: hidden !important;
    }

    .employee-print-sheet,
    .employee-print-sheet *,
    .id-card-sheet,
    .id-card-sheet * {
        visibility: visible !important;
    }

    .employee-print-sheet,
    .id-card-sheet {
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
        width: 100% !important;
    }
    
    .employee-print-sheet.is-preview {
        background: none !important;
        border: none !important;
        padding: 0 !important;
        margin: 0 !important;
        display: block !important;
    }
    
    .id-card-sheet {
        display: block !important;
        width: 100% !important;
    }
    
    .id-card-pair {
        display: flex !important;
        flex-direction: row !important;
        justify-content: center !important;
        gap: 10mm !important;
        margin-bottom: 10mm !important;
        page-break-inside: avoid !important;
    }
    
    .id-card-item {
        margin: 0 !important;
        box-shadow: none !important;
        border: 0.1mm solid #ccc !important;
        page-break-inside: avoid !important;
    }
}
</style>
