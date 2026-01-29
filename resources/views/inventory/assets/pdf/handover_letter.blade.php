<!DOCTYPE html>
<html>
<head>
    <title>Surat Pemegangan Alat - {{ $user->name }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2 { margin: 0; }
        .header p { margin: 5px 0; font-weight: bold; }
        .content { margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #000; padding: 5px; text-align: left; }
        th { background-color: #f2f2f2; }
        .signature { margin-top: 50px; width: 100%; }
        .signature td { border: none; text-align: center; vertical-align: top; }
        .info-table td { border: none; padding: 2px 5px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>BERITA ACARA SERAH TERIMA ALAT KERJA</h2>
        <p>PT. WINETS (WIFI NETWORKS)</p>
    </div>

    <div class="content">
        <p>Yang bertanda tangan di bawah ini:</p>
        <table class="info-table" style="width: auto; margin-bottom: 10px;">
            <tr>
                <td style="width: 100px;">Nama</td>
                <td>: {{ $user->name }}</td>
            </tr>
            <tr>
                <td>Jabatan</td>
                <td>: {{ $user->role?->label ?? '-' }}</td>
            </tr>
             @if($coordinator)
            <tr>
                <td>Wilayah</td>
                <td>: {{ $coordinator->region?->name ?? '-' }}</td>
            </tr>
            @endif
        </table>

        <p>Dengan ini menyatakan telah menerima dan bertanggung jawab atas alat-alat kerja inventaris perusahaan sebagai berikut:</p>

        <table>
            <thead>
                <tr>
                    <th style="width: 5%; text-align: center;">No</th>
                    <th>Nama Alat</th>
                    <th>Kode Aset</th>
                    <th>Serial Number</th>
                    <th>Kondisi</th>
                    <th>Status Kepemilikan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($assets as $index => $asset)
                <tr>
                    <td style="text-align: center;">{{ $index + 1 }}</td>
                    <td>{{ $asset->item->name ?? '-' }}</td>
                    <td>{{ $asset->asset_code }}</td>
                    <td>{{ $asset->serial_number ?? '-' }}</td>
                    <td>{{ ucfirst($asset->condition) }}</td>
                    <td>
                        @if($asset->is_returnable)
                            Inventaris (Wajib Kembali)
                        @else
                            Hak Milik (Tidak Kembali)
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align: center;">Tidak ada alat yang terdaftar.</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <p>Demikian berita acara ini dibuat untuk dapat dipergunakan sebagaimana mestinya. Alat-alat tersebut di atas adalah tanggung jawab pemegang dan wajib dijaga serta dikembalikan apabila sudah tidak bekerja atau dimutasi (kecuali status Hak Milik).</p>
    </div>

    <table class="signature">
        <tr>
            <td style="width: 50%;">
                Mengetahui,<br>
                Manager Operasional
                <br><br><br><br><br>
                (_______________________)
            </td>
            <td style="width: 50%;">
                {{ date('d F Y') }}<br>
                Penerima,
                <br><br><br><br><br>
                <b>{{ $user->name }}</b>
            </td>
        </tr>
    </table>
</body>
</html>
