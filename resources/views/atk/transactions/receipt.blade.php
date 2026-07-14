@php
    $generalStoreName = \App\Models\Setting::getValue('store_name', config('app.name', 'MStore'));
    $generalStoreAddress = \App\Models\Setting::getValue('store_address', 'Jl. Contoh No. 1');
    $generalStorePhone = \App\Models\Setting::getValue('store_phone', '081234567890');
    $generalStoreLogo = \App\Models\Setting::getValue('store_logo', '');
    $generalStoreLogo = $generalStoreLogo && !str_starts_with($generalStoreLogo, 'http') && !str_starts_with($generalStoreLogo, 'data:') && !str_starts_with($generalStoreLogo, '/')
        ? asset($generalStoreLogo)
        : $generalStoreLogo;

    $receiptStoreName = \App\Models\Setting::getValue('atk_store_name', $generalStoreName ?: 'ATK PREMIUM');
    $receiptStoreAddress = \App\Models\Setting::getValue('atk_store_address', $generalStoreAddress ?: 'Jl. Contoh ATK No. 456');
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

    $cashierName = strtoupper(trim((string) ($transaction->user->name ?? '')));
    $cashierName = $cashierName !== '' ? $cashierName : '-';
    $printedAt = date('d/m/Y H:i:s');
    $queueDisplay = $transaction->queue_display ?? (string) ($transaction->queue_number ?? $transaction->transaction_number);
@endphp

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk #{{ $transaction->transaction_number }}</title>
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
    
    <button class="btn btn-blue" onclick="printBluetoothDirect()">Hubungkan & Cetak Bluetooth</button>
    <button class="btn" onclick="window.print()">Cetak via Peramban (PDF/Sistem)</button>
    <button class="btn btn-green" onclick="shareAtkReceiptNow(this)">Bagikan Struk (PNG)</button>
    
    <div id="status" style="font-size: 10px; color: #666; text-align: center;">Status: Siap</div>
</div>

<div id="receipt-wrapper" class="size-80mm" style="--receipt-watermark: {{ !empty($receiptStoreLogo) ? 'url(\''.$receiptStoreLogo.'\')' : 'none' }};">
    <div id="receipt-content">
        <div class="header">
            @if(!empty($receiptStoreLogo))
                <img id="logo-img" src="{{ $receiptStoreLogo }}" class="header-logo" crossorigin="anonymous">
            @endif
            <h2>{{ $receiptStoreName }}</h2>
            <p>{{ $receiptStoreAddress }}</p>
            <p>{{ $receiptStorePhoneLabel }}</p>
            <p><strong>{{ $receiptTitle }}</strong></p>
        </div>

        <div class="divider"></div>

        <table class="info-table">
            <tr><td class="label">Nota</td><td>: {{ $queueDisplay }}</td></tr>
            <tr><td class="label">Waktu</td><td>: {{ $transaction->created_at->format('d/m/y H:i') }}</td></tr>
            <tr><td class="label">Kasir</td><td>: {{ $cashierName }}</td></tr>
            @if(!empty($transaction->customer_name))
                <tr><td class="label">Pelanggan</td><td>: {{ $transaction->customer_name }}</td></tr>
            @endif
        </table>

        @if(!empty($transaction->queue_number))
            <div class="queue-badge">ANTRIAN: #{{ $queueDisplay }}</div>
        @endif

        <div class="divider"></div>

        @php
            $hasCashOut = $transaction->items->contains(fn($i) => $i->item_type === 'cash_out');
        @endphp
        @foreach($transaction->items as $item)
            <div class="item-row">
                <div class="item-main">
                    <span>{{ strtoupper($item->product_name) }}</span>
                    <span>{{ number_format($item->subtotal, 0, ',', '.') }}</span>
                </div>
                @php
                    $itemType = $item->item_type ?? '';
                @endphp
                @if(in_array($itemType, ['cash_out', 'top_up', 'ppob', 'bank', 'customer_payment']))
                    <div class="item-sub">
                        [{{ $itemType }}]
                        @if($item->nominal_transaksi > 0)
                            Nominal: {{ number_format($item->nominal_transaksi, 0, ',', '.') }}
                        @endif
                        @if($item->fee > 0)
                            | Biaya Admin: {{ number_format($item->fee, 0, ',', '.') }}
                            @if($item->fee_type)
                                ({{ $item->fee_type === 'inside' ? 'Di Dalam' : 'Di Luar' }})
                            @endif
                        @endif
                    </div>
                @else
                    <div class="item-sub">
                        {{ (float)$item->quantity }} x {{ number_format($item->price, 0, ',', '.') }}
                    </div>
                @endif
            </div>
        @endforeach

        <div class="divider"></div>

        <div class="total-row grand-total">
            <span>TOTAL</span>
            <span>Rp {{ number_format($transaction->total_amount, 0, ',', '.') }}</span>
        </div>

        <div class="total-row">
            <span>Metode</span>
            <span>{{ strtoupper($transaction->payment_method ?? 'CASH') }}</span>
        </div>

        @if($hasCashOut)
            @php
                $totalNominal = $transaction->items->where('item_type', 'cash_out')->sum('nominal_transaksi');
                $totalFee = $transaction->items->where('item_type', 'cash_out')->sum('fee');
                $hasCashOutOutside = $transaction->items->where('item_type', 'cash_out')->where('fee_type', 'outside')->count() > 0;
            @endphp
            <div class="total-row">
                <span>TUNAI DIBERIKAN</span>
                <span>Rp {{ number_format($totalNominal, 0, ',', '.') }}</span>
            </div>
            <div class="total-row">
                <span>BIAYA ADMIN</span>
                <span>Rp {{ number_format($totalFee, 0, ',', '.') }}</span>
            </div>
            @if($hasCashOutOutside && ($transaction->cash_amount ?? 0) > 0)
                <div class="total-row">
                    <span>PEMBAYARAN ADMIN</span>
                    <span>Rp {{ number_format($transaction->cash_amount, 0, ',', '.') }}</span>
                </div>
            @endif
            @if(!$hasCashOutOutside && ($transaction->cash_amount ?? 0) > 0)
                <div class="total-row">
                    <span>Bayar</span>
                    <span>{{ number_format($transaction->cash_amount, 0, ',', '.') }}</span>
                </div>
                <div class="total-row">
                    <span>Kembali</span>
                    <span>{{ number_format($transaction->change_amount, 0, ',', '.') }}</span>
                </div>
            @endif
        @else
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
        @endif

        <div class="footer">
            <h2>{{ $receiptFooterTitle }}</h2>
            <p>{!! nl2br(e($receiptFooterMessage)) !!}</p>
            @if(trim((string) $receiptFooterNote) !== '')
                <p>{!! nl2br(e($receiptFooterNote)) !!}</p>
            @endif
            @if(trim((string) $receiptPoweredBy) !== '')
                <p>{{ $receiptPoweredBy }}</p>
            @endif
            <p class="timestamp">Dicetak pada: {{ $printedAt }}</p>
        </div>
    </div>
</div>

<canvas id="canvas-logo" style="display:none;"></canvas>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
const atkReceiptPngName = @json('struk-atk-' . $transaction->transaction_number . '.png');
const data = {{ Js::from([
    'logo' => !empty($receiptStoreLogo) ? $receiptStoreLogo : null,
    'store' => $receiptStoreName,
    'address' => $receiptStoreAddress,
    'phone' => $receiptStorePhoneLabel,
    'number' => $queueDisplay,
    'date' => $transaction->created_at->format('d/m/y H:i'),
    'cashier' => $cashierName,
    'customer' => $transaction->customer_name ?? '-',
    'queue' => $queueDisplay,
    'items' => $transaction->items->map(fn($i)=>[
        'n'=>strtoupper($i->product_name),
        'q'=>(float)$i->quantity,
        'p'=>(float)$i->price,
        's'=>(float)$i->subtotal,
        'type'=>$i->item_type,
        'nominal'=>(float)$i->nominal_transaksi,
        'fee'=>(float)$i->fee,
        'fee_type'=>$i->fee_type
    ]),
    'receipt_title'=>$receiptTitle,
    'receipt_footer_title'=>$receiptFooterTitle,
    'receipt_footer_message'=>$receiptFooterMessage,
    'receipt_footer_note'=>$receiptFooterNote,
    'receipt_powered_by'=>$receiptPoweredBy,
    'total'=>(float)$transaction->total_amount,
    'method'=>strtoupper($transaction->payment_method ?? 'CASH'),
    'cash'=>(float)($transaction->cash_amount ?? 0),
    'change'=>(float)($transaction->change_amount ?? 0),
    'printed_at'=>$printedAt
]) }};

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

const formatIdr = n => new Intl.NumberFormat('id-ID').format(n);

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
    if(window.webkit && window.webkit.messageHandlers){
        const iosHandlerNames=['printBluetooth','bluetoothPrint','printReceipt','printStruk','cetakBluetooth','handlePrint','print'];
        for(const handlerName of iosHandlerNames){
            if(window.webkit.messageHandlers[handlerName] && typeof window.webkit.messageHandlers[handlerName].postMessage==='function'){
                return data=>{
                    try{
                        window.webkit.messageHandlers[handlerName].postMessage(typeof data === 'string' ? data : JSON.stringify(data));
                        return true;
                    }catch(e){
                        return false;
                    }
                };
            }
        }
    }
    if(window.ReactNativeWebView && typeof window.ReactNativeWebView.postMessage==='function'){return data=>invokeBridgePrinter(d=>window.ReactNativeWebView.postMessage(JSON.stringify({action:'printBluetooth',payload:d})),data);}
    return null;
}

function buildEscPosText(data){
    let txt="[C]<b>"+data.store+"</b>\n";
    txt+="[C]"+data.address+"\n";
    txt+="[C]"+data.phone+"\n";
    txt+="[L]--------------------------------\n";
    if(data.receipt_title){txt+="[C]<b>"+data.receipt_title+"</b>\n";}
    txt+="[L]Nota : "+data.number+"\n";
    txt+="[L]Waktu: "+data.date+"\n";
    txt+="[L]Kasir: "+data.cashier+"\n";
    if(data.customer && data.customer !== '-'){txt+="[L]Pelanggan : "+data.customer+"\n";}
    if(data.queue){txt+="\n[C]<font size='big'>ANTRIAN #"+data.queue+"</font>\n\n";}
    txt+="[L]--------------------------------\n";
    
    // Cek apakah ada cash_out
    const hasCashOut = data.items.some(item => item.type === 'cash_out');
    
    data.items.forEach(item=>{
        txt+="[L]"+item.n+"\n";
        if(['cash_out','top_up','ppob','bank','customer_payment'].includes(item.type)){
            let sub = "["+item.type+"]";
            if(item.nominal>0) sub += " Nominal: "+formatIdr(item.nominal);
            if(item.fee>0){
                sub += " | Admin: "+formatIdr(item.fee);
                if(item.fee_type){
                    sub += " ("+(item.fee_type === 'inside' ? 'Di Dalam' : 'Di Luar')+")";
                }
            }
            txt+="[L]"+sub+"\n";
        }else{
            txt+="[L]"+item.q+" x "+item.p+"\n";
        }
        txt+="[R]"+formatIdr(item.s)+"\n";
    });
    txt+="[L]--------------------------------\n";
    txt+="[L]TOTAL [R]"+formatIdr(data.total)+"\n";
    txt+="[L]Metode: "+data.method+"\n";
    
    if(hasCashOut){
        const totalNominal = data.items.filter(i => i.type === 'cash_out').reduce((sum, i) => sum + i.nominal, 0);
        const totalFee = data.items.filter(i => i.type === 'cash_out').reduce((sum, i) => sum + i.fee, 0);
        const hasCashOutOutside = data.items.some(item => item.type === 'cash_out' && item.fee_type === 'outside');
        
        txt+="[L]TUNAI DIBERIKAN [R]"+formatIdr(totalNominal)+"\n";
        txt+="[L]BIAYA ADMIN [R]"+formatIdr(totalFee)+"\n";
        if(hasCashOutOutside && data.cash>0){
            txt+="[L]PEMBAYARAN ADMIN [R]"+formatIdr(data.cash)+"\n";
        }
        if(!hasCashOutOutside && data.cash>0){
            txt+="[L]Bayar [R]"+formatIdr(data.cash)+"\n[L]Kembali [R]"+formatIdr(data.change)+"\n";
        }
    }else{
        if(data.cash>0){txt+="[L]Bayar [R]"+formatIdr(data.cash)+"\n[L]Kembali [R]"+formatIdr(data.change)+"\n";}
    }
    
    txt+="\n[C]"+(data.receipt_footer_title || "*** TERIMA KASIH ***")+"\n";
    if(data.receipt_footer_message){txt+="[C]"+String(data.receipt_footer_message).replace(/\n/g,"\n[C]")+"\n";}
    if(data.receipt_footer_note){txt+="[C]"+String(data.receipt_footer_note).replace(/\n/g,"\n[C]")+"\n";}
    if(data.receipt_powered_by){txt+="[C]"+data.receipt_powered_by+"\n";}
    txt+="[C]Dicetak pada: "+data.printed_at+"\n\n\n";
    return txt;
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
        throw new Error('penangkapan struk tidak tersedia');
    }
    const canvas = await html2canvas(captureTarget, {
        useCORS: true,
        scale: 2,
        backgroundColor: '#ffffff'
    });
    const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/png'));
    if (!blob) {
        throw new Error('gagal membuat berkas gambar');
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

async function printBluetoothDirect(){
    const status=document.getElementById('status');
    const paperSizeInput = document.querySelector('input[name="paper_size"]:checked');
    const paperSize = paperSizeInput ? parseInt(paperSizeInput.value) : 48;
    const bridgePayload=Object.assign({},data,{
        paper_size:paperSize,
        printer_type:'escpos',
        escpos_text: buildEscPosText(data)
    });
    const bridgePrinter=resolveBluetoothBridge();
    
    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
    
    try{
        if(bridgePrinter){
            status.innerText="Mengirim ke printer via App...";
            if(bridgePrinter(bridgePayload)){
                status.innerText="Cetak berhasil! Mengalihkan ke POS...";
                setTimeout(() => {
                    window.location.href = "{{ route('atk.pos') }}";
                }, 1500);
                return;
            }
        }
        
        if(!(navigator.bluetooth && typeof navigator.bluetooth.requestDevice==='function')){
            throw new Error('Bluetooth tidak didukung browser ini. Gunakan Chrome (HTTPS) atau aplikasi Android/iOS.');
        }
        
        status.innerText="Meminta printer...";
        const device=await navigator.bluetooth.requestDevice({
            filters: [
                {services:['000018f0-0000-1000-8000-00805f9b34fb']},
                {namePrefix: 'Printer'},
                {namePrefix: 'XP'},
                {namePrefix: 'Mprint'}
            ],
            optionalServices: ['000018f0-0000-1000-8000-00805f9b34fb', '0000ffe0-0000-1000-8000-00805f9b34fb']
        });
        
        status.innerText="Menghubungkan...";
        const server=await device.gatt.connect();
        
        let service = null;
        let characteristic = null;
        
        const possibleServices = ['000018f0-0000-1000-8000-00805f9b34fb', '0000ffe0-0000-1000-8000-00805f9b34fb'];
        const possibleCharacteristics = ['00002af1-0000-1000-8000-00805f9b34fb', '0000ffe1-0000-1000-8000-00805f9b34fb'];
        
        for(const serviceId of possibleServices){
            try{
                service = await server.getPrimaryService(serviceId);
                for(const charId of possibleCharacteristics){
                    try{
                        characteristic = await service.getCharacteristic(charId);
                        break;
                    }catch(e){}
                }
                if(characteristic) break;
            }catch(e){}
        }
        
        if(!service || !characteristic){
            throw new Error('Printer tidak mendukung layanan Bluetooth standar');
        }
        
        status.innerText=`Memproses (ESC/POS, ${paperSize===32?'58mm':'80mm'})...`;
        
        const esc=new EscPosBuilder(paperSize);
        esc.init();
        const logoImg=document.getElementById('logo-img');
        if(logoImg && logoImg.complete){esc.center();await esc.addImage(logoImg);}
        esc.center();esc.bold(true);esc.add(data.store+"\n");esc.bold(false);
        esc.add(data.address+"\n"+data.phone+"\n");esc.line();
        if(data.receipt_title){esc.center();esc.bold(true);esc.add(data.receipt_title+"\n");esc.bold(false);}
        esc.left();esc.add("Nota : "+data.number+"\nWaktu: "+data.date+"\nKasir: "+data.cashier+"\n");
        if(data.customer && data.customer !== '-'){esc.add("Pelanggan: "+data.customer+"\n");}
        if(data.queue){esc.center();esc.big(true);esc.add("\nANTRIAN #"+data.queue+"\n\n");esc.big(false);}
        esc.line();
        data.items.forEach(item=>{
            esc.left();esc.add(item.n+"\n");
            if(['cash_out','top_up','ppob','bank','customer_payment'].includes(item.type)){
                let sub = "["+item.type+"]";
                if(item.nominal>0) sub += " Nominal: "+formatIdr(item.nominal);
                if(item.fee>0){
                    sub += " | Admin: "+formatIdr(item.fee);
                    if(item.fee_type){
                        sub += " ("+(item.fee_type === 'inside' ? 'Di Dalam' : 'Di Luar')+")";
                    }
                }
                esc.add(sub+"\n");
            }else{
                esc.justify(item.q+" x "+formatIdr(item.p),formatIdr(item.s));
            }
        });
        esc.line();
        esc.bold(true);esc.justify("TOTAL",formatIdr(data.total));esc.bold(false);
        esc.add("Metode: "+data.method+"\n");
        
        const hasCashOutEsc = data.items.some(item => item.type === 'cash_out');
        if(hasCashOutEsc){
            const totalNominalEsc = data.items.filter(i => i.type === 'cash_out').reduce((sum, i) => sum + i.nominal, 0);
            const totalFeeEsc = data.items.filter(i => i.type === 'cash_out').reduce((sum, i) => sum + i.fee, 0);
            const hasCashOutOutsideEsc = data.items.some(item => item.type === 'cash_out' && item.fee_type === 'outside');
            
            esc.justify("TUNAI DIBERIKAN", formatIdr(totalNominalEsc));
            esc.justify("BIAYA ADMIN", formatIdr(totalFeeEsc));
            if(hasCashOutOutsideEsc && data.cash>0){
                esc.justify("PEMBAYARAN ADMIN", formatIdr(data.cash));
            }
            if(!hasCashOutOutsideEsc && data.cash>0){
                esc.justify("Bayar",formatIdr(data.cash));
                esc.justify("Kembali",formatIdr(data.change));
            }
        }else{
            if(data.cash>0){esc.justify("Bayar",formatIdr(data.cash));esc.justify("Kembali",formatIdr(data.change));}
        }
        esc.feed(1);esc.center();
        esc.add((data.receipt_footer_title || "*** TERIMA KASIH ***")+"\n");
        if(data.receipt_footer_message){esc.add(String(data.receipt_footer_message)+"\n");}
        if(data.receipt_footer_note){esc.add(String(data.receipt_footer_note)+"\n");}
        if(data.receipt_powered_by){esc.add(String(data.receipt_powered_by)+"\n");}
        esc.add("Dicetak pada: "+data.printed_at+"\n");esc.feed(3);
        const receiptData=esc.generate();
        
        status.innerText="Mengirim data...";
        const chunkSize = isIOS ? 10 : 20;
        for(let i=0;i<receiptData.length;i+=chunkSize){
            await characteristic.writeValue(receiptData.slice(i,i+chunkSize));
        }
        
        status.innerText="Cetak berhasil! Mengalihkan ke POS...";
        setTimeout(() => {
            window.location.href = "{{ route('atk.pos') }}";
        }, 1500);
    }catch(error){
        if(bridgePrinter && bridgePrinter(bridgePayload)){
            status.innerText="Cetak berhasil melalui Bridge! Mengalihkan ke POS...";
            setTimeout(() => {
                window.location.href = "{{ route('atk.pos') }}";
            }, 1500);
            return;
        }
        status.innerText="Kesalahan: "+error.message;
    }
}

initReceiptPreview();

function checkAutoprint() {
    const urlParams = new URLSearchParams(window.location.search);
    const autoprint = urlParams.get('autoprint');
    if (autoprint === '1') {
        setTimeout(() => {
            printBluetoothDirect();
        }, 500);
    }
}

checkAutoprint();
</script>

</body>
</html>