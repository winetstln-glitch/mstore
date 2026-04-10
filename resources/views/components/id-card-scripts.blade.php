@props([
    'barcodeColor' => '#111827',
    'barcodeHeight' => 26,
    'barcodeWidth' => 2,
    'scale' => 4
])

<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    initBarcodes();
});

function initBarcodes() {
    if (typeof JsBarcode === 'undefined') return;
    
    document.querySelectorAll('.barcode-svg').forEach((el) => {
        const code = el.dataset.code || '';
        if (!code) return;

        JsBarcode(el, code, {
            format: 'CODE128',
            lineColor: '{{ $barcodeColor }}',
            width: {{ $barcodeWidth }},
            height: {{ $barcodeHeight }},
            displayValue: false,
            margin: 0,
        });
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