@php
    $generalStoreName = \App\Models\Setting::getValue('store_name', config('app.name', 'MStore'));
    $generalStoreAddress = \App\Models\Setting::getValue('store_address', 'Jl. Contoh No. 1');
    $generalStorePhone = \App\Models\Setting::getValue('store_phone', '081234567890');
    $generalStoreLogo = \App\Models\Setting::getValue('store_logo', '');
    $generalStoreLogo = $generalStoreLogo && !str_starts_with($generalStoreLogo, 'http') && !str_starts_with($generalStoreLogo, 'data:') && !str_starts_with($generalStoreLogo, '/')
        ? asset($generalStoreLogo)
        : $generalStoreLogo;

    $receiptStoreName = \App\Models\Setting::getValue('atk_store_name', $generalStoreName ?: 'ATK PREMIUM');
    $receiptStoreAddress = \App\Models\Setting::getValue('atk_store_address', $generalStoreAddress ?: 'Pusat Perbelanjaan ATK No. 101');
    $receiptStorePhone = \App\Models\Setting::getValue('atk_store_phone', $generalStorePhone ?: '0812-3456-7890');
    $receiptStoreLogo = \App\Models\Setting::getValue('atk_store_logo', $generalStoreLogo);
    $receiptStoreLogo = $receiptStoreLogo && !str_starts_with($receiptStoreLogo, 'http') && !str_starts_with($receiptStoreLogo, 'data:') && !str_starts_with($receiptStoreLogo, '/')
        ? asset($receiptStoreLogo)
        : $receiptStoreLogo;
    $receiptStorePhoneLabel = str_starts_with(strtolower($receiptStorePhone), 'telp') ? $receiptStorePhone : 'Telp: '.$receiptStorePhone;
    $receiptTitle = \App\Models\Setting::getValue('atk_receipt_title', 'NOTA PENJUALAN');
    $receiptFooterTitle = \App\Models\Setting::getValue('atk_receipt_footer_title', '*** TERIMA KASIH ***');
    $receiptFooterMessage = \App\Models\Setting::getValue('atk_receipt_footer_message', 'Barang yang sudah dibeli tidak dapat ditukar.');
    $receiptFooterNote = \App\Models\Setting::getValue('atk_receipt_footer_note', '');
    $receiptPoweredBy = \App\Models\Setting::getValue('atk_receipt_powered_by', 'POWERED BY MSTORE');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk #{{ $transaction->transaction_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-main: #f8fafc;
            --receipt-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-main);
            color: #1e293b;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 1.5rem;
        }

        /* Utility classes for thermal font */
        .thermal-font {
            font-family: 'JetBrains Mono', 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.2;
        }

        /* Glassmorphism Panel */
        .control-panel {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: var(--receipt-shadow);
        }

        /* Receipt Card */
        #receipt-wrapper {
            background: white;
            box-shadow: var(--receipt-shadow);
            border-radius: 2px;
            padding: 1.25rem;
            position: relative;
            overflow: hidden;
            transition: width 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        #receipt-wrapper::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 4px;
            background: linear-gradient(90deg, #3b82f6, #2dd4bf);
        }

        #receipt-wrapper::after {
            content: '';
            position: absolute;
            inset: 0;
            background-image: var(--receipt-watermark);
            background-repeat: no-repeat;
            background-position: center;
            background-size: 65%;
            opacity: 0.06;
            pointer-events: none;
            z-index: 0;
        }

        #receipt-wrapper > * {
            position: relative;
            z-index: 1;
        }

        .size-58 { width: 58mm; }
        .size-80 { width: 80mm; }

        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }

        @media print {
            .no-print { display: none !important; }
            body { background: white; padding: 0; }
            #receipt-wrapper { box-shadow: none; border: none; width: 100% !important; padding: 0; }
            #receipt-wrapper::before { display: none; }
        }

        /* Checkbox styling */
        .radio-tile-group { display: flex; gap: 0.75rem; width: 100%; }
        .radio-tile {
            position: relative;
            flex: 1;
            cursor: pointer;
            border: 2px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 0.75rem;
            text-align: center;
            transition: all 0.2s;
        }
        .radio-tile:hover { background: #f1f5f9; }
        input[type="radio"]:checked + .radio-tile {
            border-color: #3b82f6;
            background: #eff6ff;
            color: #1d4ed8;
        }
    </style>
</head>
<body>

    <!-- CONTROL PANEL -->
    <div class="no-print control-panel max-w-md w-full rounded-2xl p-6 mb-8">
        <div class="flex items-center gap-3 mb-6">
            <div class="bg-blue-600 p-2 rounded-lg text-white">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v7" />
                </svg>
            </div>
            <div>
                <h2 class="font-bold text-lg">Cetak Struk</h2>
                <p class="text-xs text-slate-500">Konfigurasi printer thermal Anda</p>
            </div>
        </div>

        <div class="mb-6">
            <span class="block text-sm font-semibold mb-3 text-slate-700">Jenis Printer</span>
            <div class="radio-tile-group">
                <div class="relative flex-1">
                    <input type="radio" name="printer_type" id="typeEscpos" value="escpos" class="hidden" checked onchange="updatePrinterType()">
                    <label for="typeEscpos" class="radio-tile block">
                        <span class="block text-sm font-bold">Receipt (ESC/POS)</span>
                        <span class="text-[10px] opacity-60">Printer Struk</span>
                    </label>
                </div>
                <div class="relative flex-1">
                    <input type="radio" name="printer_type" id="typeTspl" value="tspl" class="hidden" onchange="updatePrinterType()">
                    <label for="typeTspl" class="radio-tile block">
                        <span class="block text-sm font-bold">Label (TSPL)</span>
                        <span class="text-[10px] opacity-60">Printer Label</span>
                    </label>
                </div>
            </div>
        </div>

        <div class="mb-6">
            <span class="block text-sm font-semibold mb-3 text-slate-700">Ukuran Kertas</span>
            <div class="radio-tile-group">
                <div class="relative flex-1">
                    <input type="radio" name="paper_size" id="size58" value="32" class="hidden" checked onchange="updateView('58')">
                    <label for="size58" class="radio-tile block">
                        <span class="block text-sm font-bold">58mm</span>
                        <span class="text-[10px] opacity-60">32 Karakter</span>
                    </label>
                </div>
                <div class="relative flex-1">
                    <input type="radio" name="paper_size" id="size80" value="48" class="hidden" onchange="updateView('80')">
                    <label for="size80" class="radio-tile block">
                        <span class="block text-sm font-bold">80mm</span>
                        <span class="text-[10px] opacity-60">48 Karakter</span>
                    </label>
                </div>
            </div>
        </div>

        <div class="flex flex-col gap-3">
            <button onclick="printBluetooth()" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl transition shadow-lg shadow-blue-200 flex items-center justify-center gap-2 group">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 group-hover:scale-110 transition" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M17.71 7.71L12 2h-1v7.59L6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 11 14.41V22h1l5.71-5.71-4.3-4.29 4.3-4.29zM13 5.83l1.88 1.88L13 9.59V5.83zm1.88 10.29L13 18.17v-3.76l1.88 1.88z"/>
                </svg>
                Bluetooth Print
            </button>
            <button onclick="window.print()" class="w-full bg-slate-800 hover:bg-slate-900 text-white font-bold py-3 rounded-xl transition shadow-lg shadow-slate-200">
                Print ke PDF / Sistem
            </button>
            <button onclick="shareAtkReceiptNow(this)" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 rounded-xl transition shadow-lg shadow-emerald-200">
                Bagikan Struk (PNG)
            </button>
        </div>

        <div id="status-bar" class="mt-4 py-2 px-3 bg-slate-100 rounded-lg flex items-center justify-between">
            <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Status</span>
            <span id="print-status" class="text-[11px] font-semibold text-slate-600 italic">Siap Mencetak</span>
        </div>
    </div>

    <!-- RECEIPT AREA -->
    <div id="receipt-wrapper" class="size-58 thermal-font" style="--receipt-watermark: {{ !empty($receiptStoreLogo) ? 'url(\''.$receiptStoreLogo.'\')' : 'none' }};">
        <div class="text-center mb-4">
            @if(!empty($receiptStoreLogo))
                <img id="logo-img" src="{{ $receiptStoreLogo }}" class="mx-auto h-12 mb-2 grayscale object-contain" crossorigin="anonymous">
            @endif
            <h1 class="text-base font-bold tracking-tight uppercase leading-tight">{{ $receiptStoreName }}</h1>
            <p class="text-[10px] mt-1">{{ $receiptStoreAddress }}</p>
            <p class="text-[10px]">{{ $receiptStorePhoneLabel }}</p>
            <p class="text-[10px] mt-1 font-semibold">{{ $receiptTitle }}</p>
        </div>

        <div class="border-t border-dashed border-slate-900 my-3"></div>

        <div class="text-[11px] space-y-0.5">
            <div class="flex justify-between"><span>NO NOTA</span><span>#{{ $transaction->transaction_number }}</span></div>
            <div class="flex justify-between"><span>TANGGAL</span><span>{{ $transaction->created_at->format('d/m/Y H:i') }}</span></div>
            <div class="flex justify-between"><span>KASIR</span><span>{{ strtoupper($transaction->user->name ?? 'Admin') }}</span></div>
        </div>

        @if(!empty($transaction->queue_number))
            <div class="text-center border-2 border-slate-900 p-3 my-3 text-xl font-bold">ANTRIAN: #{{ $transaction->queue_number }}</div>
        @endif

        <div class="border-t border-dashed border-slate-900 my-3"></div>

        <div class="space-y-3">
            @foreach($transaction->items as $item)
                <div>
                    <span class="block font-bold uppercase">{{ $item->product_name }}</span>
                    <div class="flex justify-between text-[11px]">
                        <span>{{ (float)$item->quantity }} x {{ number_format($item->price, 0, ',', '.') }}</span>
                        <span>{{ number_format($item->subtotal, 0, ',', '.') }}</span>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="border-t border-dashed border-slate-900 my-3"></div>

        <div class="space-y-1">
            <div class="flex justify-between">
                <span>SUBTOTAL</span>
                <span>{{ number_format($transaction->total_amount, 0, ',', '.') }}</span>
            </div>
            <div class="flex justify-between font-bold text-sm pt-1 border-t border-slate-300 mt-1">
                <span>TOTAL AKHIR</span>
                <span>Rp {{ number_format($transaction->total_amount, 0, ',', '.') }}</span>
            </div>
            <div class="flex justify-between pt-2">
                <span class="opacity-70">METODE BAYAR</span>
                <span class="font-bold uppercase">{{ $transaction->payment_method }}</span>
            </div>
            @if($transaction->cash_amount > 0)
                <div class="flex justify-between">
                    <span>TUNAI</span>
                    <span>{{ number_format($transaction->cash_amount, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between italic">
                    <span>KEMBALI</span>
                    <span>{{ number_format($transaction->change_amount, 0, ',', '.') }}</span>
                </div>
            @endif
        </div>

        <div class="mt-8 text-center space-y-1">
            <p class="uppercase font-bold text-[11px]">{{ $receiptFooterTitle }}</p>
            <p class="text-[9px]">{!! nl2br(e($receiptFooterMessage)) !!}</p>
            @if(trim((string) $receiptFooterNote) !== '')
                <p class="text-[9px]">{!! nl2br(e($receiptFooterNote)) !!}</p>
            @endif
            @if(trim((string) $receiptPoweredBy) !== '')
                <div class="pt-4 opacity-30 text-[8px]">
                    {{ $receiptPoweredBy }}
                </div>
            @endif
        </div>
    </div>

    <canvas id="canvas-logo" style="display:none;"></canvas>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
/** * DATA & CONFIGURATION
 */
const atkReceiptPngName = @json('struk-atk-' . $transaction->transaction_number . '.png');
const txnData = {{ Js::from([
    'store' => $receiptStoreName,
    'addr' => $receiptStoreAddress,
    'phone' => $receiptStorePhoneLabel,
    'nota' => $transaction->transaction_number,
    'time' => $transaction->created_at->format('d/m/Y H:i'),
    'cashier' => $transaction->user->name ?? 'Admin',
    'queue' => $transaction->queue_number ?? null,
    'receipt_title' => $receiptTitle,
    'receipt_footer_title' => $receiptFooterTitle,
    'receipt_footer_message' => $receiptFooterMessage,
    'receipt_footer_note' => $receiptFooterNote,
    'receipt_powered_by' => $receiptPoweredBy,
    'items' => $transaction->items->map(fn($i) => [
        'name' => strtoupper($i->product_name),
        'qty' => (float)$i->quantity,
        'prc' => (float)$i->price,
        'sub' => (float)$i->subtotal
    ]),
    'total' => (float)$transaction->total_amount,
    'method' => strtoupper($transaction->payment_method),
    'cash' => (float)$transaction->cash_amount,
    'change' => (float)$transaction->change_amount
]) }};

// === UNIVERSAL BLUETOOTH BRIDGE (same as wash receipt)
function invokeBridgePrinter(invoker,payload){try{invoker(payload);return true;}catch(_){try{invoker(JSON.stringify(payload));return true;}catch(_){return false;}}}
function resolveBluetoothBridge(){
    const methodNames=['printBluetoothAction','printBluetooth','printReceipt','printStruk','printBluetoothReceipt','cetakBluetooth','handleBluetoothPrint','printViaBluetooth','sendPrintJob','bluetoothPrint'];
    for(const method of methodNames){if(typeof window[method]==='function'){return data=>invokeBridgePrinter(window[method],data);}}
    if(window.AndroidPrinter && typeof window.AndroidPrinter.printText==='function'){return data=>{try{window.AndroidPrinter.printText(buildEscPosText(data));return true;}catch(e){return false;}};}
    const bridgeCandidates=[window.Android,window.android,window.MstoreAndroid,window.NativeAndroid,window.JsBridge].filter(Boolean);
    for(const bridge of bridgeCandidates){
        for(const method of methodNames){if(typeof bridge[method]==='function'){return data=>invokeBridgePrinter(d=>bridge[method](d),data);}}
        if(typeof bridge.postMessage==='function'){return data=>invokeBridgePrinter(d=>bridge.postMessage({action:'printBluetooth',payload:d}),data);}
    }
    // Fix for iPhone/iOS webkit message handlers - try multiple handler names
    if(window.webkit && window.webkit.messageHandlers){
        const iosHandlerNames=['printBluetooth','bluetoothPrint','printReceipt','printStruk','cetakBluetooth','handlePrint','print'];
        for(const handlerName of iosHandlerNames){
            if(window.webkit.messageHandlers[handlerName] && typeof window.webkit.messageHandlers[handlerName].postMessage==='function'){
                return data=>{
                    try{
                        // iOS webkit handlers often prefer stringified JSON
                        window.webkit.messageHandlers[handlerName].postMessage(typeof data === 'string' ? data : JSON.stringify(data));
                        return true;
                    }catch(e){
                        console.error('iOS bridge error:', e);
                        return false;
                    }
                };
            }
        }
    }
    if(window.ReactNativeWebView && typeof window.ReactNativeWebView.postMessage==='function'){return data=>invokeBridgePrinter(d=>window.ReactNativeWebView.postMessage(JSON.stringify({action:'printBluetooth',payload:d})),data);}
    return null;
}

// === BUILD ESC/POS TEXT (simple version for ATK)
function buildEscPosText(data){
    let txt="[C]<b>"+data.store+"</b>\n";
    txt+="[C]"+data.addr+"\n";
    txt+="[C]"+data.phone+"\n";
    txt+="[L]--------------------------------\n";
    if(data.receipt_title){txt+="[C]<b>"+data.receipt_title+"</b>\n";}
    txt+="[L]NOTA : #"+data.nota+"\n";
    txt+="[L]TGL  : "+data.time+"\n";
    txt+="[L]KASIR: "+data.cashier+"\n";
    if(data.queue){txt+="\n[C]<b>ANTRIAN: #"+data.queue+"</b>\n\n";}
    txt+="[L]--------------------------------\n";
    data.items.forEach(item=>{txt+="[L]"+item.name+"\n[L]"+item.qty+" x "+item.prc.toLocaleString('id-ID')+" = "+item.sub.toLocaleString('id-ID')+"\n";});
    txt+="[L]--------------------------------\n";
    txt+="[L]TOTAL AKHIR : Rp "+data.total.toLocaleString('id-ID')+"\n";
    txt+="[L]METODE: "+data.method+"\n";
    if(data.cash > 0){txt+="[L]TUNAI : Rp "+data.cash.toLocaleString('id-ID')+"\n[L]KEMBALI: Rp "+data.change.toLocaleString('id-ID')+"\n";}
    txt+="\n[C]"+(data.receipt_footer_title || "*** TERIMA KASIH ***")+"\n";
    if(data.receipt_footer_message){txt+="[C]"+String(data.receipt_footer_message).replace(/\n/g,"\n[C]")+"\n";}
    if(data.receipt_footer_note){txt+="[C]"+String(data.receipt_footer_note).replace(/\n/g,"\n[C]")+"\n";}
    if(data.receipt_powered_by){txt+="[C]"+data.receipt_powered_by+"\n";}
    txt+="\n\n";
    return txt;
}

function updateView(size) {
    const el = document.getElementById('receipt-wrapper');
    el.classList.remove('size-58', 'size-80');
    el.classList.add('size-' + size);
}

function updatePrinterType() {
    const type = document.querySelector('input[name="printer_type"]:checked').value;
    // Optional: add preview changes here
}

// Initialize printer type from settings
document.addEventListener('DOMContentLoaded', function() {
    @if(\App\Models\Setting::getValue('pos_printer_type', 'escpos') === 'tspl')
        document.getElementById('typeTspl').checked = true;
        updatePrinterType();
    @endif
    
    checkAutoprint();
});

// Check for autoprint parameter
function checkAutoprint() {
    const urlParams = new URLSearchParams(window.location.search);
    const autoprint = urlParams.get('autoprint');
    if (autoprint === '1') {
        // Trigger Bluetooth print
        setTimeout(() => {
            printBluetooth();
        }, 500);
    }
}

/** * ESC/POS PRINT ENGINE
 */
class PrinterEngine {
    constructor(cols) {
        this.cols = cols;
        this.buffer = [];
        this.encoder = new TextEncoder();
    }

    raw(data) {
        if (typeof data === 'string') this.buffer.push(...this.encoder.encode(data));
        else this.buffer.push(...data);
    }

    init() { this.raw([0x1B, 0x40]); }
    center() { this.raw([0x1B, 0x61, 1]); }
    left() { this.raw([0x1B, 0x61, 0]); }
    bold(v) { this.raw([0x1B, 0x45, v ? 1 : 0]); }
    divider() { this.raw("-".repeat(this.cols) + "\n"); }
    feed(n=3) { for(let i=0; i<n; i++) this.raw([0x0A]); }
    
    justify(left, right) {
        const gap = this.cols - left.toString().length - right.toString().length;
        this.raw(left + " ".repeat(Math.max(1, gap)) + right + "\n");
    }

    async processLogo(img) {
        if (!img) return;
        const canvas = document.getElementById('canvas-logo');
        const ctx = canvas.getContext('2d');
        const w = this.cols === 32 ? 184 : 360; 
        const h = Math.round(img.naturalHeight * (w / img.naturalWidth));
        canvas.width = w; canvas.height = h;
        ctx.fillStyle = "white"; ctx.fillRect(0,0,w,h);
        ctx.drawImage(img, 0, 0, w, h);
        const pixels = ctx.getImageData(0,0,w,h).data;
        const widthBytes = Math.ceil(w/8);
        const rast = new Uint8Array(widthBytes * h);
        for(let y=0; y<h; y++) {
            for(let x=0; x<w; x++) {
                const idx = (y*w+x)*4;
                if (((pixels[idx]+pixels[idx+1]+pixels[idx+2])/3) < 140) {
                    rast[y*widthBytes + Math.floor(x/8)] |= (0x80 >> (x%8));
                }
            }
        }
        this.raw([0x1D, 0x76, 0x30, 0, widthBytes%256, Math.floor(widthBytes/256), h%256, Math.floor(h/256)]);
        this.raw(rast); this.raw("\n");
    }
}

/** * TSPL PRINT ENGINE (for Label Printers)
 */
class TsplEngine {
    constructor(width=null, height=null, gap=null) {
        this.width = width || {{ \App\Models\Setting::getValue('pos_label_width_mm', 80) }};
        this.height = height || {{ \App\Models\Setting::getValue('pos_label_height_mm', 150) }};
        this.gap = gap || {{ \App\Models\Setting::getValue('pos_label_gap_mm', 3) }};
        this.buffer = [];
        this.encoder = new TextEncoder();
    }

    raw(data) {
        if (typeof data === 'string') this.buffer.push(...this.encoder.encode(data));
        else this.buffer.push(...data);
    }

    init() {
        const wDots = this.width * 8;
        const hDots = this.height * 8;
        this.raw(`SIZE ${wDots} dot,${hDots} dot\n`);
        this.raw(`GAP ${this.gap} mm,0 mm\n`);
        this.raw("CLS\n");
    }

    text(x, y, font, rotation, xMul, yMul, content) {
        this.raw(`TEXT ${x},${y},"${font}",${rotation},${xMul},${yMul},"${content}"\n`);
    }

    line(x1, y1, x2, y2, thickness) {
        this.raw(`LINE ${x1},${y1},${x2},${y2},${thickness}\n`);
    }

    print(copies=1) {
        this.raw(`PRINT ${copies},1\n`);
    }

    generate() {
        return new Uint8Array(this.buffer);
    }
}

async function shareAtkReceiptNow(button) {
    const defaultLabel = button ? button.innerHTML : '';
    if (button) {
        button.disabled = true;
        button.innerHTML = 'Mempersiapkan PNG...';
    }
    try {
        const receiptFile = await buildAtkReceiptFile();
        if (navigator.share && navigator.canShare && navigator.canShare({ files: [receiptFile] })) {
            await navigator.share({ files: [receiptFile] });
            return;
        }
        downloadAtkReceiptFile(receiptFile);
    } finally {
        if (button) {
            button.disabled = false;
            button.innerHTML = defaultLabel;
        }
    }
}

async function buildAtkReceiptFile() {
    const captureTarget = document.getElementById('receipt-wrapper');
    if (!captureTarget || typeof html2canvas === 'undefined') {
        throw new Error('capture unavailable');
    }
    const canvas = await html2canvas(captureTarget, {
        useCORS: true,
        scale: 2,
        backgroundColor: '#ffffff'
    });
    const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/png'));
    if (!blob) {
        throw new Error('blob failed');
    }
    return new File([blob], atkReceiptPngName, { type: 'image/png' });
}

function downloadAtkReceiptFile(file) {
    const downloadUrl = URL.createObjectURL(file);
    const link = document.createElement('a');
    link.href = downloadUrl;
    link.download = file.name;
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.setTimeout(() => URL.revokeObjectURL(downloadUrl), 1000);
}

async function printBluetooth() {
    const status = document.getElementById('print-status');
    // Get paper size with default
    const colsInput = document.querySelector('input[name="paper_size"]:checked');
    const cols = colsInput ? parseInt(colsInput.value) : 32; // Default 58mm (32 columns)
    // Get printer type with default
    const printerTypeInput = document.querySelector('input[name="printer_type"]:checked');
    const printerType = printerTypeInput ? printerTypeInput.value : 'escpos'; // Default ESC/POS
    
    // Create bridge payload
    const bridgePayload = Object.assign({}, txnData, {
        paper_size: cols,
        printer_type: printerType,
        escpos_text: buildEscPosText(txnData)
    });
    const bridgePrinter = resolveBluetoothBridge();
    
    // PRIORITIZE BRIDGE FIRST!
    if (bridgePrinter) {
        status.innerText = "Mengirim ke printer via App...";
        if (bridgePrinter(bridgePayload)) {
            status.innerText = "Selesai! Mengalihkan ke POS...";
            setTimeout(() => {
                window.location.href = "{{ route('atk.pos') }}";
            }, 1500);
            return;
        }
    }
    
    try {
        status.innerText = "Mencari Perangkat...";
        const device = await navigator.bluetooth.requestDevice({
            filters: [
                { services: ['000018f0-0000-1000-8000-00805f9b34fb'] },
                { services: ['0000ffe0-0000-1000-8000-00805f9b34fb'] },
                { namePrefix: 'Printer' },
                { namePrefix: 'XP' },
                { namePrefix: 'Mprint' }
            ],
            optionalServices: ['000018f0-0000-1000-8000-00805f9b34fb', '0000ffe0-0000-1000-8000-00805f9b34fb']
        });

        status.innerText = "Menghubungkan...";
        const server = await device.gatt.connect();
        
        // Try multiple services/characteristics
        let service = null;
        let char = null;
        try {
            service = await server.getPrimaryService('000018f0-0000-1000-8000-00805f9b34fb');
            char = await service.getCharacteristic('00002af1-0000-1000-8000-00805f9b34fb');
        } catch (e) {
            try {
                service = await server.getPrimaryService('0000ffe0-0000-1000-8000-00805f9b34fb');
                char = await service.getCharacteristic('0000ffe1-0000-1000-8000-00805f9b34fb');
            } catch (e2) {
                throw new Error('Printer tidak mendukung layanan standar');
            }
        }

        let payload;
            if(printerType === 'tspl') {
                // Build TSPL label
                const tsplWidth = cols === 32 ? 58 : 80;
                const tspl = new TsplEngine(tsplWidth, 150);
                tspl.init();
                
                let yPos = 20;
                tspl.text(20, yPos, "TSS24.BF2", 0, 1, 1, txnData.store);
                yPos += 40;
                tspl.text(20, yPos, "TSS24.BF2", 0, 1, 1, txnData.addr);
                yPos += 30;
                tspl.text(20, yPos, "TSS24.BF2", 0, 1, 1, txnData.phone);
                yPos += 30;
                tspl.line(20, yPos, tsplWidth*8 - 20, yPos, 2);
                yPos += 20;
                
                if(txnData.receipt_title) {
                    tspl.text(20, yPos, "TSS24.BF2", 0, 1, 1, txnData.receipt_title);
                    yPos += 30;
                }
                
                tspl.text(20, yPos, "TSS24.BF2", 0, 1, 1, "NOTA: #" + txnData.nota);
                yPos += 30;
                tspl.text(20, yPos, "TSS24.BF2", 0, 1, 1, "TGL: " + txnData.time);
                yPos += 30;
                tspl.text(20, yPos, "TSS24.BF2", 0, 1, 1, "KASIR: " + txnData.cashier);
                yPos += 30;
                
                if(txnData.queue) {
                    tspl.line(20, yPos, tsplWidth*8 - 20, yPos, 2);
                    yPos += 20;
                    tspl.text(20, yPos, "TSS24.BF2", 0, 1, 2, "ANTRIAN: #" + txnData.queue);
                    yPos += 50;
                }
                
                tspl.line(20, yPos, tsplWidth*8 - 20, yPos, 2);
                yPos += 20;
            
            txnData.items.forEach(i => {
                tspl.text(20, yPos, "TSS24.BF2", 0, 1, 1, i.name);
                yPos += 30;
                tspl.text(20, yPos, "TSS24.BF2", 0, 1, 1, i.qty + " x " + i.prc.toLocaleString('id-ID') + " = " + i.sub.toLocaleString('id-ID'));
                yPos += 30;
            });
            
            tspl.line(20, yPos, tsplWidth*8 - 20, yPos, 2);
            yPos += 20;
            tspl.text(20, yPos, "TSS24.BF2", 0, 1, 2, "TOTAL AKHIR: Rp " + txnData.total.toLocaleString('id-ID'));
            yPos += 50;
            tspl.text(20, yPos, "TSS24.BF2", 0, 1, 1, "METODE: " + txnData.method);
            yPos += 30;
            
            if(txnData.cash > 0) {
                tspl.text(20, yPos, "TSS24.BF2", 0, 1, 1, "TUNAI: " + txnData.cash.toLocaleString('id-ID'));
                yPos += 30;
                tspl.text(20, yPos, "TSS24.BF2", 0, 1, 1, "KEMBALI: " + txnData.change.toLocaleString('id-ID'));
                yPos += 30;
            }
            
            tspl.text(20, yPos, "TSS24.BF2", 0, 1, 1, txnData.receipt_footer_title || "*** TERIMA KASIH ***");
            tspl.print(1);
            payload = tspl.generate();
        } else {
            // Build ESC/POS receipt
            const engine = new PrinterEngine(cols);
            engine.init();

            const logo = document.getElementById('logo-img');
            if (logo && logo.complete) {
                engine.center();
                await engine.processLogo(logo);
            }

            engine.center();
            engine.bold(true); engine.raw(txnData.store + "\n"); engine.bold(false);
            engine.raw(txnData.addr + "\n" + txnData.phone + "\n");
            engine.divider();

            engine.left();
            if (txnData.receipt_title) {
                engine.bold(true); engine.raw(txnData.receipt_title + "\n"); engine.bold(false);
            }
            engine.raw(`NOTA : #${txnData.nota}\n`);
            engine.raw(`TGL  : ${txnData.time}\n`);
            engine.raw(`KASIR: ${txnData.cashier}\n`);
            
            if(txnData.queue) {
                engine.center();
                engine.bold(true);
                engine.raw("\nANTRIAN: #" + txnData.queue + "\n\n");
                engine.bold(false);
                engine.left();
            }
            
            engine.divider();

            txnData.items.forEach(i => {
                engine.bold(true); engine.raw(i.name + "\n"); engine.bold(false);
                engine.justify(`${i.qty} x ${i.prc.toLocaleString('id-ID')}`, i.sub.toLocaleString('id-ID'));
            });
            engine.divider();

            engine.justify("SUBTOTAL", txnData.total.toLocaleString('id-ID'));
            engine.bold(true);
            engine.justify("TOTAL AKHIR", "Rp " + txnData.total.toLocaleString('id-ID'));
            engine.bold(false);
            engine.raw(`METODE: ${txnData.method}\n`);

            if (txnData.cash > 0) {
                engine.justify("TUNAI", txnData.cash.toLocaleString('id-ID'));
                engine.justify("KEMBALI", txnData.change.toLocaleString('id-ID'));
            }

            engine.feed(1);
            engine.center();
            engine.raw((txnData.receipt_footer_title || "*** TERIMA KASIH ***") + "\n");
            if (txnData.receipt_footer_message) {
                String(txnData.receipt_footer_message).split('\n').forEach(line => engine.raw(line + "\n"));
            }
            if (txnData.receipt_footer_note) {
                String(txnData.receipt_footer_note).split('\n').forEach(line => engine.raw(line + "\n"));
            }
            if (txnData.receipt_powered_by) {
                engine.raw(String(txnData.receipt_powered_by) + "\n");
            }
            engine.feed(4);
            payload = new Uint8Array(engine.buffer);
        }

        status.innerText = "Mengirim Data...";
        const chunkSize = 20;
        for (let i = 0; i < payload.length; i += chunkSize) {
            await char.writeValue(payload.slice(i, i + chunkSize));
        }

        status.innerText = "Selesai! Mengalihkan ke POS...";
        setTimeout(() => {
            window.location.href = "{{ route('atk.pos') }}";
        }, 1500);

    } catch (e) {
        console.error('Print error:', e);
        if (bridgePrinter) {
            status.innerText = "Mencoba via App...";
            if (bridgePrinter(bridgePayload)) {
                status.innerText = "Selesai via App! Mengalihkan ke POS...";
                setTimeout(() => {
                    window.location.href = "{{ route('atk.pos') }}";
                }, 1500);
                return;
            }
        }
        status.innerText = "Error: " + e.message;
        console.error(e);
    }
}
</script>
</body>
</html>
