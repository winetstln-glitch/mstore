<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('Inventory Items') }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .text-right {
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ __('Inventory Items Report') }}</h2>
        <p>{{ __('Date') }}: {{ now()->format('d M Y') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>{{ __('No') }}</th>
                <th>{{ __('Name') }}</th>
                <th>{{ __('Description') }}</th>
                <th>{{ __('Stock') }}</th>
                <th>{{ __('Unit') }}</th>
                <th>{{ __('Price') }}</th>
                <th>{{ __('Total Value') }}</th>
            </tr>
        </thead>
        <tbody>
            @php $totalValue = 0; @endphp
            @foreach($items as $index => $item)
                @php 
                    $value = $item->stock * $item->price;
                    $totalValue += $value;
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->name }}</td>
                    <td>{{ $item->description ?: '-' }}</td>
                    <td>{{ $item->stock }}</td>
                    <td>{{ $item->unit }}</td>
                    <td class="text-right">{{ number_format($item->price, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($value, 0, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr>
                <td colspan="6" class="text-right"><strong>{{ __('Total Asset Value') }}</strong></td>
                <td class="text-right"><strong>{{ number_format($totalValue, 0, ',', '.') }}</strong></td>
            </tr>
        </tbody>
    </table>
</body>
</html>
