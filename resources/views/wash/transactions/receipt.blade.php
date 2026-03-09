@php
    $generalStoreName = \App\Models\Setting::getValue('store_name', config('app.name', 'MStore'));
    $generalStoreAddress = \App\Models\Setting::getValue('store_address', 'Jl. Contoh No. 1');
    $generalStorePhone = \App\Models\Setting::getValue('store_phone', '081234567890');
    $generalStoreLogo = \App\Models\Setting::getValue('store_logo', '');
    $generalStoreLogo = $generalStoreLogo && !str_starts_with($generalStoreLogo, 'http') && !str_starts_with($generalStoreLogo, 'data:') && !str_starts_with($generalStoreLogo, '/')
        ? asset($generalStoreLogo)
        : $generalStoreLogo;

    $receiptStoreName = \App\Models\Setting::getValue('wash_store_name', $generalStoreName ?: 'AUTO WASH');
    $receiptStoreAddress = \App\Models\Setting::getValue('wash_store_address', $generalStoreAddress ?: 'Jl. Contoh Bersih No. 123');
    $receiptStorePhone = \App\Models\Setting::getValue('wash_store_phone', $generalStorePhone ?: '0812-0000-0000');
    $receiptStoreLogo = \App\Models\Setting::getValue('wash_store_logo', $generalStoreLogo);
    $receiptStoreLogo = $receiptStoreLogo && !str_starts_with($receiptStoreLogo, 'http') && !str_starts_with($receiptStoreLogo, 'data:') && !str_starts_with($receiptStoreLogo, '/')
        ? asset($receiptStoreLogo)
        : $receiptStoreLogo;

    $receiptStorePhoneLabel = str_starts_with(strtolower($receiptStorePhone), 'telp') ? $receiptStorePhone : 'Telp: '.$receiptStorePhone;

    $customerName = trim((string) ($transaction->customer_name ?? ''));
    $customerName = $customerName !== '' ? $customerName : '-';
    $vehiclePlate = strtoupper(trim((string) ($transaction->vehicle_plate ?? '')));
    $vehiclePlate = $vehiclePlate !== '' ? $vehiclePlate : '-';
    $cashierName = strtoupper(trim((string) ($transaction->user->name ?? '')));
    $cashierName = $cashierName !== '' ? $cashierName : '-';
    $printedAt = date('d/m/Y H:i:s');
@endphp

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #{{ $transaction->transaction_number }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            line-height: 1;
            color: #000;
            margin: 0 auto;
            padding: 10px;
            background: #f0f0f0;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .no-print-area {
            width: 100%;
            max-width: 400px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 10px;
            background: #fff;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .panel-title {
            text-align: center;
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .paper-selector {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-bottom: 5px;
        }

        .paper-selector label {
            font-size: 12px;
            font-weight: bold;
            cursor: pointer;
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            flex: 1;
            text-align: center;
        }

        .paper-selector input:checked + label {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }

        .paper-selector input { display: none; }

        .btn {
            background: #222;
            color: #fff;
            border: none;
            padding: 12px;
            border-radius: 5px;
            font-size: 13px;
            cursor: pointer;
            width: 100%;
            font-weight: bold;
        }
        .btn-blue { background: #007bff; }
        .btn-green { background: #16a34a; }
        
        #receipt-wrapper {
            background: #fff;
            padding: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
            transition: width 0.3s ease;
        }

        #receipt-wrapper::after {
            content: '';
            position: absolute;
            inset: 0;
            background-image: var(--receipt-watermark);
            background-repeat: no-repeat;
            background-position: center;
            background-size: 62%;
            opacity: 0.06;
            pointer-events: none;
            z-index: 0;
        }

        #receipt-content {
            position: relative;
            z-index: 1;
        }

        .size-58mm { width: 58mm; }
        .size-80mm { width: 80mm; }

        .header { text-align: center; margin-bottom: 10px; }
        .header-logo {
            display: block;
            margin: 0 auto;
            max-width: 80px;
            filter: grayscale(1);
        }
        .header h2 { margin: 0; font-size: 16px; text-transform: uppercase; }
        .header p { margin: 2px 0; font-size: 10px; }

        .divider { border-top: 1px dashed #000; margin: 8px 0; }

        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { font-size: 11px; padding: 2px 0; vertical-align: top; }
        .label { width: 70px; }

        .queue-badge {
            text-align: center;
            border: 2px solid #000;
            padding: 10px;
            margin: 10px 0;
            font-size: 20px;
            font-weight: bold;
        }

        .item-row { margin-bottom: 5px; }
        .item-main { display: flex; justify-content: space-between; font-weight: bold; }
        .item-sub { font-size: 10px; }

        .total-row { display: flex; justify-content: space-between; margin-top: 2px; }
        .grand-total { font-weight: bold; font-size: 14px; border-top: 1px solid #000; padding-top: 4px; margin-top: 4px;}

        .footer { text-align: center; margin-top: 20px; }
        .footer h2 { margin: 0; font-size: 12px; text-transform: uppercase; }
        .footer p { margin: 2px 0; font-size: 10px; font-style: italic; }

        @media (max-width: 575.98px) {
            body {
                padding: 6px;
                align-items: stretch;
            }

            .no-print-area {
                max-width: 100%;
                gap: 8px;
                margin-bottom: 8px;
                padding: 8px;
                border-radius: 6px;
            }

            .paper-selector {
                gap: 6px;
            }

            .paper-selector label {
                font-size: 11px;
                padding: 7px 8px;
            }

            .btn {
                padding: 10px;
                font-size: 12px;
            }

            #receipt-wrapper {
                width: 100%;
                max-width: 100%;
                padding: 8px;
                box-shadow: 0 0 6px rgba(0,0,0,0.1);
            }

            .size-58mm,
            .size-80mm {
                width: 100%;
            }

            .header h2 {
                font-size: 15px;
            }

            .header p,
            .item-sub,
            .footer p {
                font-size: 9px;
            }

            .info-table td {
                font-size: 10px;
                line-height: 1.15;
            }

            .label {
                width: 58px;
            }

            .queue-badge {
                font-size: 18px;
                padding: 8px 6px;
            }

            .grand-total {
                font-size: 13px;
            }
        }

        @media print {
            .no-print-area { display: none; }
            body { background: transparent; padding: 0; }
            #receipt-wrapper { box-shadow: none; padding: 0; }
        }
    </style>
</head>
<body>

<div class="no-print-area">
    <div class="panel-title">Pilih Ukuran Kertas:</div>
    <div class="paper-selector">
        <input type="radio" name="paper_size" id="size58" value="32" onchange="updatePreviewSize('58')">
        <label for="size58">58mm (32 Kolom)</label>
        
        <input type="radio" name="paper_size" id="size80" value="48" checked onchange="updatePreviewSize('80')">
        <label for="size80">80mm (48 Kolom)</label>
    </div>
    
    <button class="btn btn-blue" onclick="printBluetoothDirect()">Connect & Print Bluetooth</button>
    <button class="btn" onclick="window.print()">Print via Browser (PDF/System)</button>
    <button class="btn btn-green" onclick="shareWashReceiptNow(this)">Bagikan Struk (PNG)</button>
    <div id="status" style="font-size: 10px; color: #666; text-align: center;">Status: Ready</div>
</div>

<!-- RECEIPT CONTENT -->
<div id="receipt-wrapper" class="size-80mm" style="--receipt-watermark: {{ !empty($receiptStoreLogo) ? 'url(\''.$receiptStoreLogo.'\')' : 'none' }};">
    <div id="receipt-content">
        <div class="header">
            @if(!empty($receiptStoreLogo))
                <img id="logo-img" src="{{ $receiptStoreLogo }}" class="header-logo" crossorigin="anonymous">
            @endif
            <h2>{{ $receiptStoreName }}</h2>
            <p>{{ $receiptStoreAddress }}</p>
            <p>{{ $receiptStorePhoneLabel }}</p>
        </div>

        <div class="divider"></div>

        <table class="info-table">
            <tr><td class="label">Nota</td><td>: {{ $transaction->transaction_number }}</td></tr>
            <tr><td class="label">Waktu</td><td>: {{ $transaction->created_at->format('d/m/y H:i') }}</td></tr>
            <tr><td class="label">Kasir</td><td>: {{ $cashierName }}</td></tr>
            <tr><td class="label">Cust/Plat</td><td>: {{ $customerName }} / {{ $vehiclePlate }}</td></tr>
        </table>

        @if(!empty($transaction->queue_number))
            <div class="queue-badge">ANTRIAN: #{{ $transaction->queue_number }}</div>
        @endif

        <div class="divider"></div>

        @foreach($transaction->items as $item)
            <div class="item-row">
                <div class="item-main">
                    <span>{{ strtoupper($item->service_name) }}</span>
                    <span>{{ number_format($item->subtotal, 0, ',', '.') }}</span>
                </div>
                <div class="item-sub">
                    {{ (float)$item->quantity }} x {{ number_format($item->price, 0, ',', '.') }}
                </div>
            </div>
        @endforeach

        <div class="divider"></div>

        @if(($transaction->discount_amount ?? 0) > 0)
            <div class="total-row">
                <span>Diskon</span>
                <span>-{{ number_format($transaction->discount_amount, 0, ',', '.') }}</span>
            </div>
        @endif

        <div class="total-row grand-total">
            <span>TOTAL</span>
            <span>Rp {{ number_format($transaction->total_amount, 0, ',', '.') }}</span>
        </div>

        <div class="total-row">
            <span>Metode</span>
            <span>{{ strtoupper($transaction->payment_method ?? 'CASH') }}</span>
        </div>

        @if(($transaction->cash_amount ?? 0) > 0)
            <div class="total-row">
                <span>Bayar</span>
                <span>{{ number_format($transaction->cash_amount, 0, ',', '.') }}</span>
            </div>
            <div class="total-row">
                <span>Kembali</span>
                <span>{{ number_format($transaction->change_amount, 0, ',', '.') }}</span>
            </div>
        @endif

        <div class="footer">
            <h2>*** TERIMA KASIH ***</h2>
            <p>Kepuasan Anda Kebanggaan Kami.<br>Periksa kembali barang bawaan Anda sebelum meninggalkan lokasi.</p>
            <p class="timestamp">Dicetak pada: {{ $printedAt }}</p>
        </div>
    </div>
</div>

<canvas id="canvas-logo" style="display:none;"></canvas>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
// === DATA ===
const washReceiptPngName = @json('struk-wash-' . $transaction->transaction_number . '.png');
const data = {{ Js::from([
    'logo' => !empty($receiptStoreLogo) ? $receiptStoreLogo : null,
    'store' => $receiptStoreName,
    'address' => $receiptStoreAddress,
    'phone' => $receiptStorePhoneLabel,
    'number' => $transaction->transaction_number,
    'date' => $transaction->created_at->format('d/m/y H:i'),
    'cashier' => $cashierName,
    'customer' => $customerName . " / " . $vehiclePlate,
    'queue' => $transaction->queue_number ?? null,
    'items' => $transaction->items->map(fn($i)=>[
        'n'=>strtoupper($i->service_name),
        'q'=>(float)$i->quantity,
        'p'=>(float)$i->price,
        's'=>(float)$i->subtotal
    ]),
    'discount'=>(float)($transaction->discount_amount ?? 0),
    'total'=>(float)$transaction->total_amount,
    'method'=>strtoupper($transaction->payment_method ?? 'CASH'),
    'cash'=>(float)($transaction->cash_amount ?? 0),
    'change'=>(float)($transaction->change_amount ?? 0),
    'printed_at'=>$printedAt
]) }};

// === PREVIEW ===
function updatePreviewSize(size){
    document.getElementById('receipt-wrapper').className = 'size-'+size+'mm';
}

function initReceiptPreview(){
    const isMobile = window.matchMedia('(max-width: 575.98px)').matches;
    if (isMobile) {
        const paper58 = document.getElementById('size58');
        if (paper58) {
            paper58.checked = true;
            updatePreviewSize('58');
        }
    }
}

// === FORMAT IDR ===
const formatIdr = n => new Intl.NumberFormat('id-ID').format(n);

// === ESC/POS BUILDER ===
class EscPosBuilder {
    constructor(maxChars=48){
        this.encoder = new TextEncoder();
        this.buffer = [];
        this.maxChars=maxChars;
    }
    add(data){if(typeof data==='string'){this.buffer.push(...this.encoder.encode(data));}else if(data instanceof Uint8Array||Array.isArray(data)){this.buffer.push(...data);}}
    init(){this.add([0x1B,0x40]);}
    center(){this.add([0x1B,0x61,1]);}
    left(){this.add([0x1B,0x61,0]);}
    right(){this.add([0x1B,0x61,2]);}
    bold(on){this.add([0x1B,0x45,on?1:0]);}
    big(on){this.add([0x1B,0x21,on?0x30:0x00]);}
    feed(n=3){this.add(new Array(n).fill(0x0A));}
    line(){this.add("-".repeat(this.maxChars)+"\n");}
    justify(left,right){const spaces=this.maxChars-left.toString().length-right.toString().length;this.add(left+" ".repeat(Math.max(1,spaces))+right+"\n");}
    async addImage(imgElement){
        if(!imgElement) return;
        const canvas=document.getElementById('canvas-logo');const ctx=canvas.getContext('2d');
        const maxWidth=this.maxChars===48?240:180;const scale=maxWidth/imgElement.naturalWidth;
        const width=maxWidth;const height=Math.round(imgElement.naturalHeight*scale);
        canvas.width=width;canvas.height=height;
        ctx.fillStyle="white";ctx.fillRect(0,0,width,height);ctx.drawImage(imgElement,0,0,width,height);
        const pixels=ctx.getImageData(0,0,width,height).data;
        const widthBytes=Math.ceil(width/8);const raster=new Uint8Array(widthBytes*height);
        for(let y=0;y<height;y++){for(let x=0;x<width;x++){const idx=(y*width+x)*4;const intensity=(pixels[idx]+pixels[idx+1]+pixels[idx+2])/3;if(intensity<150){raster[y*widthBytes+Math.floor(x/8)]|=(0x80>>(x%8));}}}
        const xL=widthBytes%256,xH=Math.floor(widthBytes/256),yL=height%256,yH=Math.floor(height/256);
        this.add([0x1D,0x76,0x30,0,xL,xH,yL,yH]);this.add(raster);this.add("\n");
    }
    generate(){return new Uint8Array(this.buffer);}
}

// === UNIVERSAL BLUETOOTH BRIDGE ===
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
    if(window.webkit && window.webkit.messageHandlers && window.webkit.messageHandlers.printBluetooth && typeof window.webkit.messageHandlers.printBluetooth.postMessage==='function'){return data=>invokeBridgePrinter(d=>window.webkit.messageHandlers.printBluetooth.postMessage(d),data);}
    if(window.ReactNativeWebView && typeof window.ReactNativeWebView.postMessage==='function'){return data=>invokeBridgePrinter(d=>window.ReactNativeWebView.postMessage(JSON.stringify({action:'printBluetooth',payload:d})),data);}
    return null;
}

// === BUILD ESC/POS TEXT ===
function buildEscPosText(data){
    let txt="[C]<b>"+data.store+"</b>\n";
    txt+="[C]"+data.address+"\n";
    txt+="[C]"+data.phone+"\n";
    txt+="[L]--------------------------------\n";
    txt+="[L]Nota : "+data.number+"\n";
    txt+="[L]Waktu: "+data.date+"\n";
    txt+="[L]Kasir: "+data.cashier+"\n";
    txt+="[L]Cust : "+data.customer+"\n";
    if(data.queue){txt+="\n[C]<font size='big'>ANTRIAN #"+data.queue+"</font>\n\n";}
    txt+="[L]--------------------------------\n";
    data.items.forEach(item=>{txt+="[L]"+item.n+"\n[R]"+item.s+"\n[L]"+item.q+" x "+item.p+"\n";});
    if(data.discount>0){txt+="[L]Diskon [R]-"+data.discount+"\n";}
    txt+="[L]TOTAL [R]"+data.total+"\n";
    txt+="[L]Metode: "+data.method+"\n";
    if(data.cash>0){txt+="[L]Bayar [R]"+data.cash+"\n[K]Kembali [R]"+data.change+"\n";}
    txt+="\n[C]*** TERIMA KASIH ***\n[C]Periksa kembali barang bawaan Anda\n[C]Dicetak pada: "+data.printed_at+"\n\n\n";
    return txt;
}

async function shareWashReceiptNow(button) {
    const defaultLabel = button ? button.innerHTML : '';
    if (button) {
        button.disabled = true;
        button.innerHTML = 'Mempersiapkan PNG...';
    }
    try {
        const receiptFile = await buildWashReceiptFile();
        if (navigator.share && navigator.canShare && navigator.canShare({ files: [receiptFile] })) {
            await navigator.share({ files: [receiptFile] });
            return;
        }
        downloadWashReceiptFile(receiptFile);
    } finally {
        if (button) {
            button.disabled = false;
            button.innerHTML = defaultLabel;
        }
    }
}

async function buildWashReceiptFile() {
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
    return new File([blob], washReceiptPngName, { type: 'image/png' });
}

function downloadWashReceiptFile(file) {
    const downloadUrl = URL.createObjectURL(file);
    const link = document.createElement('a');
    link.href = downloadUrl;
    link.download = file.name;
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.setTimeout(() => URL.revokeObjectURL(downloadUrl), 1000);
}

// === PRINT FUNCTION ===
async function printBluetoothDirect(){
    const status=document.getElementById('status');
    const paperSize=parseInt(document.querySelector('input[name="paper_size"]:checked').value);
    const bridgePayload=Object.assign({},data,{paper_size:paperSize});
    const bridgePrinter=resolveBluetoothBridge();
    try{
        if(!(navigator.bluetooth && typeof navigator.bluetooth.requestDevice==='function')){
            if(!bridgePrinter) throw new Error('Bluetooth tidak didukung browser ini. Gunakan Chrome (HTTPS) atau aplikasi Android/iOS.');
            status.innerText="Sending via App Bridge...";
            if(!bridgePrinter(bridgePayload)) throw new Error('Bridge Bluetooth tersedia, tetapi gagal mengirim data.');
            status.innerText="Print Success via Bridge!";
            setTimeout(()=>status.innerText="Status: Ready",3000);return;
        }
        status.innerText="Requesting Printer...";
        const device=await navigator.bluetooth.requestDevice({filters:[{services:['000018f0-0000-1000-8000-00805f9b34fb']}],optionalServices:['000018f0-0000-1000-8000-00805f9b34fb']});
        status.innerText="Connecting...";
        const server=await device.gatt.connect();
        const service=await server.getPrimaryService('000018f0-0000-1000-8000-00805f9b34fb');
        const characteristic=await service.getCharacteristic('00002af1-0000-1000-8000-00805f9b34fb');
        status.innerText=`Processing (${paperSize===32?'58mm':'80mm'})...`;
        const esc=new EscPosBuilder(paperSize);
        esc.init();
        const logoImg=document.getElementById('logo-img');
        if(logoImg && logoImg.complete){esc.center();await esc.addImage(logoImg);}
        esc.center();esc.bold(true);esc.add(data.store+"\n");esc.bold(false);
        esc.add(data.address+"\n"+data.phone+"\n");esc.line();
        esc.left();esc.add("Nota : "+data.number+"\nWaktu: "+data.date+"\nKasir: "+data.cashier+"\nCust: "+data.customer+"\n");
        if(data.queue){esc.center();esc.big(true);esc.add("\nANTRIAN #"+data.queue+"\n\n");esc.big(false);}
        esc.line();
        data.items.forEach(item=>{esc.left();esc.add(item.n+"\n");esc.justify(item.q+" x "+formatIdr(item.p),formatIdr(item.s));});
        esc.line();
        if(data.discount>0) esc.justify("Diskon","-"+formatIdr(data.discount));
        esc.bold(true);esc.justify("TOTAL",formatIdr(data.total));esc.bold(false);
        esc.add("Metode: "+data.method+"\n");
        if(data.cash>0){esc.justify("Bayar",formatIdr(data.cash));esc.justify("Kembali",formatIdr(data.change));}
        esc.feed(1);esc.center();
        esc.add("*** TERIMA KASIH ***\nKepuasan Anda Kebanggaan Kami.\nPeriksa kembali barang bawaan Anda\nsebelum meninggalkan lokasi.\n");
        esc.add("Dicetak pada: "+data.printed_at+"\n");esc.feed(3);
        status.innerText="Sending Data...";
        const receiptData=esc.generate();
        const chunkSize=20;
        for(let i=0;i<receiptData.length;i+=chunkSize){await characteristic.writeValue(receiptData.slice(i,i+chunkSize));}
        status.innerText="Print Success!";
        setTimeout(()=>status.innerText="Status: Ready",3000);
    }catch(error){
        if(bridgePrinter && bridgePrinter(bridgePayload)){status.innerText="Print Success via Bridge!";setTimeout(()=>status.innerText="Status: Ready",3000);return;}
        status.innerText="Error: "+error.message;
    }
}

initReceiptPreview();
</script>

</body>
</html>
