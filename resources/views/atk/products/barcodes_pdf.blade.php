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
    height: 25mm;
    padding: 0;
}

.item {
    width: 10mm;
    height: 25mm;
    padding: 1mm;
    box-sizing: border-box;
    page-break-inside: avoid;
}

.name {
    font-size: 8pt;
    font-weight: bold;
    margin-bottom: 1mm;
    line-height: 1.1;
}

.code {
    font-size: 5pt;
    margin-bottom: 1mm;
}

svg {
    width: 100%;
    height: 12mm;
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
            <!-- <div class="name">
                {{ \Illuminate\Support\Str::limit($product->name, 40) }}
            </div> -->
            <div class="code">
                {{ $product->code }}
            </div>

            {!! $barcodes[$product->id] ?? '' !!}
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