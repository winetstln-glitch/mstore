@extends('layouts.app')

@section('title', __('Product Barcodes'))

@section('content')
<div class="container-fluid">

   <div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <h1 class="h4 mb-0">{{ __('Product Barcodes') }}</h1>
<div class="d-flex gap-2 mb-3">

    <a href="{{ route('atk.products.barcodes.pdf', ['paper'=>'a4','mode'=>'preview']) }}"
       target="_blank"
       class="btn btn-primary">
        Preview A4
    </a>
    <a href="{{ route('atk.products.barcodes.pdf', ['paper'=>'a4','mode'=>'print']) }}"
       target="_blank"
       class="btn btn-warning">
        Print A4
    </a>  
    <a href="{{ route('atk.products.index') }}" class="btn btn-secondary" title="{{ __('Back') }}">
            <i class="fa-solid fa-arrow-left"></i>
            <span class="d-none d-md-inline ms-2">{{ __('Back') }}</span>
     </a>

 </div>
    
</div>

    {{-- Quantity Form --}}
    <!-- <form method="GET" class="mb-3 no-print">
        <div class="row g-2">
            @foreach($products as $product)
                <div class="col-md-3">
                    <label class="form-label small">{{ $product->name }}</label>
                    <input type="number"
                           name="qty[{{ $product->id }}]"
                           value="{{ request('qty.' . $product->id, 1) }}"
                           min="1"
                           class="form-control form-control-sm">
                </div>
            @endforeach
        </div>

        <button type="submit" class="btn btn-primary btn-sm mt-2">
            Generate Labels
        </button>
    </form> -->

    {{-- PRINT AREA --}}
    <div id="printArea">
        <div class="label-grid">
            @foreach($products as $product)
                @php
                    $qty = request('qty.' . $product->id, 1);
                @endphp

                @for($i = 0; $i < $qty; $i++)
                    <div class="label-item">
                        <div class="product-name">{{ $product->name }}</div>
                        <div class="product-code">{{ $product->code }}</div>
                        <svg class="barcode"
                             data-code="{{ $product->code }}"></svg>
                    </div>
                @endfor
            @endforeach
        </div>
    </div>

</div>
@endsection

@push('styles')
<style>

/* ===========================
   SCREEN VIEW
=========================== */

.label-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.label-item {
    width: 200px;
    border: 1px solid #ddd;
    padding: 8px;
    text-align: center;
    border-radius: 6px;
}

.product-name {
    font-size: 12px;
    font-weight: 600;
}

.product-code {
    font-size: 11px;
    color: #777;
}

.barcode {
    width: 100%;
    height: 60px;
}

/* ===========================
   PRINT VIEW (A4 - 40 LABEL)
=========================== */

@media print {

    .no-print {
        display: none !important;
    }

    body {
        margin: 0;
    }

    @page {
        size: A4;
        margin: 10mm;
    }

    .label-grid {
        display: grid;
        grid-template-columns: repeat(5, 38mm);
        grid-auto-rows: 25mm;
        gap: 4mm;
    }

    .label-item {
        width: 38mm;
        height: 25mm;
        border: 0;
        padding: 2mm;
        break-inside: avoid;
    }

    .product-name {
        font-size: 9pt;
    }

    .product-code {
        font-size: 8pt;
    }

    .barcode {
        height: 12mm;
    }
}

</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    document.querySelectorAll('svg.barcode').forEach(function(svg){

        let code = svg.getAttribute('data-code');

        if (!code || code.length > 50) {
            svg.innerHTML = '<text x="0" y="15" fill="red">Invalid Code</text>';
            return;
        }

        try {
            JsBarcode(svg, code, {
                format: 'CODE128',
                displayValue: true,
                fontSize: 10,
                width: 1.5,     // lebih aman agar tidak overflow
                height: 40,
                margin: 0
            });
        } catch (e) {
            svg.innerHTML = '<text x="0" y="15" fill="red">Barcode Error</text>';
        }

    });

});
</script>
@endpush