@props([
    'barcodeColor' => '#111827',
    'barcodeHeight' => 26,
    'barcodeWidth' => 2,
    'scale' => 4
])

<script>
document.addEventListener('DOMContentLoaded', function () {
    initBarcodes();
});

// Jalankan juga segera jika JsBarcode sudah tersedia (antisipasi Vite loading)
if (typeof JsBarcode !== 'undefined') {
    initBarcodes();
} else {
    // Tunggu sebentar jika masih loading
    let checkJsBarcode = setInterval(() => {
        if (typeof JsBarcode !== 'undefined') {
            initBarcodes();
            clearInterval(checkJsBarcode);
        }
    }, 500);
    // Hentikan setelah 5 detik
    setTimeout(() => clearInterval(checkJsBarcode), 5000);
}

function initBarcodes() {
    if (typeof JsBarcode === 'undefined') return;
    
    const elements = document.querySelectorAll('.barcode-svg');
    if (elements.length === 0) return;

    elements.forEach((el) => {
        const code = el.dataset.code || '';
        if (!code) return;
        
        // Reset SVG content if it already has something
        el.innerHTML = '';

        try {
            JsBarcode(el, code, {
                format: 'CODE128',
                lineColor: '{{ $barcodeColor }}',
                width: {{ $barcodeWidth }},
                height: {{ $barcodeHeight }},
                displayValue: false,
                margin: 0,
            });
        } catch (e) {
            console.error('Barcode generation failed for code:', code, e);
        }
    });
}

async function waitForAssets(element) {
    const images = Array.from(element.querySelectorAll('img'));
    const promises = images.map((img) => {
        try {
            if (img.decode) {
                return img.decode().catch(() => {});
            }
        } catch (e) {}
        return new Promise((resolve) => {
            if (img.complete) return resolve();
            img.onload = img.onerror = () => resolve();
        });
    });
    await Promise.all(promises);
    await new Promise((r) => setTimeout(r, 200));
}

async function downloadElement(element, fileName) {
    if (!element) return;
    
    await waitForAssets(element);
    
    try {
        if (typeof html2canvas === 'undefined') {
            alert('Fitur download image membutuhkan assets front-end. Jalankan: npm install && npm run build');
            return;
        }
        const canvas = await html2canvas(element, {
            scale: {{ $scale }},
            useCORS: true,
            logging: false,
            backgroundColor: '#ffffff'
        });
        
        const link = document.createElement('a');
        link.download = fileName;
        link.href = canvas.toDataURL('image/jpeg', 0.95);
        link.click();
    } catch (e) {
        alert('Gagal membuat gambar. Pastikan logo/foto dapat diakses (CORS) dan coba muat ulang halaman.');
        console.error(e);
    }
}
</script>
