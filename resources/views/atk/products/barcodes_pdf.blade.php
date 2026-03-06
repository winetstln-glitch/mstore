<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>{{ __('ATK Barcodes Legal') }}</title>

<style>

@page {
    size: legal;
    margin: 5mm;
}

body {
    font-family: DejaVu Sans, sans-serif;
    font-size: 9pt;
    margin: 0;
}

table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 1mm;
}

td.cell {
    width: 10mm;
    height: 20mm;
    padding: 0;
}

.item {
    width: 30mm;
    height: 22mm;
    padding: 1mm;
    box-sizing: border-box;
    page-break-inside: avoid;
}

.name {
    font-size: 4pt;
    margin-bottom: 0.5mm;
    line-height: 1.1;
    text-align: center;
}

.code {
    font-size: 5pt;
    margin-bottom: 1mm;
}

.price {
    font-size: 5pt;
    font-weight: bold;
    margin-top: 1mm;
    text-align: center;
}

svg {
    width: 100%;
    height: 3mm;
}

</style>
</head>

<body>

<table>

@foreach($products->chunk(5) as $row)
<tr>
    @foreach($row as $product)
    <td class="cell">
        <div class="item">
            <div class="name">
                {{ \Illuminate\Support\Str::limit($product->name, 40) }}
            </div>
            
            {!! $barcodes[$product->id] ?? '' !!}

            <div class="price">
                Rp {{ number_format($product->price, 0, ',', '.') }}
            </div>
        </div>
    </td>
    @endforeach

    @for($i = $row->count(); $i < 5; $i++)
        <td class="cell"></td>
    @endfor
</tr>
@endforeach

</table>

</body>
</html>