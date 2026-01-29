<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Vouchers</title>
    <style>
        body {
            font-family: sans-serif;
            background-color: #f0f0f0;
            margin: 0;
            padding: 20px;
        }
        .no-print {
            margin-bottom: 20px;
            text-align: center;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .voucher-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 10px;
        }
        @media print {
            .no-print {
                display: none;
            }
            body {
                background-color: #fff;
                padding: 0;
            }
            .voucher-container {
                gap: 0;
                display: block; /* Often better for page breaks */
            }
            /* If we use inline-block in templates, we can keep flex or block */
        }
    </style>
</head>
<body>
    <div class="no-print">
        <form method="GET" action="{{ route('hotspot.print') }}">
            <input type="hidden" name="batch_id" value="{{ request('batch_id') }}">
            <label for="template_id">Select Template:</label>
            <select name="template_id" id="template_id" onchange="this.form.submit()" style="padding: 5px; margin-right: 10px;">
                @if(isset($templates))
                    @foreach($templates as $tpl)
                        <option value="{{ $tpl->id }}" {{ (isset($selectedTemplate) && $selectedTemplate->id == $tpl->id) ? 'selected' : '' }}>
                            {{ $tpl->name }}
                        </option>
                    @endforeach
                @endif
            </select>
            <button type="button" onclick="window.print()" style="padding: 5px 20px; cursor: pointer; background: #007bff; color: white; border: none; border-radius: 4px;">Print Now</button>
            <a href="{{ route('hotspot.index') }}" style="margin-left: 20px; text-decoration: none; color: #666;">Back to Dashboard</a>
        </form>
    </div>

    <div class="voucher-container">
        @if(isset($selectedTemplate) && $selectedTemplate)
            @foreach($vouchers as $voucher)
                @php
                    $content = $selectedTemplate->html_content;
                    $content = str_replace('%code%', $voucher->code, $content);
                    $content = str_replace('%password%', $voucher->password, $content);
                    $content = str_replace('%price%', number_format($voucher->price, 0, ',', '.'), $content);
                    $content = str_replace('%profile%', $voucher->profile->name ?? 'N/A', $content);
                    $content = str_replace('%validity%', ($voucher->profile->validity_value ?? '-') . ' ' . ($voucher->profile->validity_unit ?? ''), $content);
                @endphp
                {!! $content !!}
            @endforeach
        @else
            <div style="text-align: center; color: red;">No Template Selected or Found</div>
        @endif
    </div>
</body>
</html>
