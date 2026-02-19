<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Tagihan</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-800">
    <div class="max-w-5xl mx-auto py-8 px-4">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-semibold">Daftar Tagihan</h1>
        </div>
        <div class="overflow-x-auto bg-white shadow rounded-lg">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="bg-gray-100 text-gray-600 uppercase text-xs tracking-wider">
                        <th class="py-3 px-4 text-left">Kode Invoice</th>
                        <th class="py-3 px-4 text-right">Jumlah</th>
                        <th class="py-3 px-4 text-left">Jatuh Tempo</th>
                        <th class="py-3 px-4 text-left">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($invoices as $inv)
                        <tr class="hover:bg-gray-50">
                            <td class="py-3 px-4 font-medium">{{ $inv->code }}</td>
                            <td class="py-3 px-4 text-right">Rp {{ number_format($inv->amount, 0, ',', '.') }}</td>
                            <td class="py-3 px-4">{{ optional($inv->due_date)->format('d M Y') ?? '-' }}</td>
                            <td class="py-3 px-4">
                                @php
                                    $isPaid = $inv->status === 'paid';
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-1 border rounded-full text-xs font-semibold
                                    {{ $isPaid ? 'bg-green-100 text-green-700 border-green-300' : 'bg-red-100 text-red-700 border-red-300' }}">
                                    {{ $inv->status }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-6 px-4 text-center text-gray-500">Belum ada tagihan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
