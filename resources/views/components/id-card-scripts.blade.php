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
    // Hentikan setelah 10 detik
    setTimeout(() => clearInterval(checkJsBarcode), 10000);
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
    const svgs = Array.from(element.querySelectorAll('svg'));
    
    const imagePromises = images.map((img) => {
        if (img.src && !img.src.startsWith('data:')) {
            img.crossOrigin = "anonymous";
        }
        
        return new Promise((resolve) => {
            if (img.complete && img.naturalHeight !== 0) return resolve();
            img.onload = img.onerror = () => resolve();
        });
    });

    const fontPromise = (document.fonts && document.fonts.ready) ? document.fonts.ready : Promise.resolve();

    const svgPromises = svgs.map(svg => {
        return new Promise(resolve => {
            if (svg.childNodes.length > 0) return resolve();
            let attempts = 0;
            const check = setInterval(() => {
                if (svg.childNodes.length > 0 || attempts > 20) {
                    clearInterval(check);
                    resolve();
                }
                attempts++;
            }, 100);
        });
    });

    await Promise.all([...imagePromises, ...svgPromises, fontPromise]);
    await new Promise((r) => setTimeout(r, 800)); 
}

async function downloadElement(element, fileName) {
    if (!element) return;
    
    // Check if libraries are loaded
    if (typeof html2canvas === 'undefined') {
        alert('Library html2canvas belum dimuat. Tunggu sebentar atau jalankan: npm install && npm run build');
        return;
    }

    await waitForAssets(element);
    
    try {
        const canvas = await html2canvas(element, {
            scale: 3, // Balanced scale for stability and quality
            useCORS: true,
            allowTaint: false,
            logging: false,
            backgroundColor: '#ffffff',
            imageTimeout: 15000,
            scrollX: 0,
            scrollY: 0,
            onclone: (clonedDoc) => {
                const clonedElement = clonedDoc.getElementById(element.id);
                if (clonedElement) {
                    // Force the "is-capturing" mode to lock dimensions in pixels
                    clonedElement.classList.add('is-capturing');
                    
                    // Reset any transforms or absolute positioning that might shift during clone
                    clonedElement.style.position = 'relative';
                    clonedElement.style.left = '0';
                    clonedElement.style.top = '0';
                    clonedElement.style.margin = '0';
                    clonedElement.style.padding = '0';
                    clonedElement.style.display = 'block';
                }
            }
        });
        
        const link = document.createElement('a');
        link.download = fileName;
        link.href = canvas.toDataURL('image/jpeg', 1.0); // Maximum quality (1.0)
        link.click();
    } catch (e) {
        alert('Gagal membuat gambar. Pastikan logo/foto dapat diakses (CORS) dan coba muat ulang halaman.');
        console.error('Download error:', e);
    }
}
</script>
