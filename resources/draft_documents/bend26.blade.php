@php($rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.'))
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Bukti Pengeluaran Bendahara (Bend26)</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
        .kop { text-align: center; border-bottom: 3px double #111; padding-bottom: 8px; margin-bottom: 14px; }
        .kop h1 { font-size: 14px; margin: 0; letter-spacing: 1px; }
        .kop p { margin: 2px 0; font-size: 10px; color: #333; }
        h2.title { text-align: center; font-size: 13px; margin: 0 0 4px; text-decoration: underline; }
        p.nomor { text-align: center; margin: 0 0 14px; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #444; padding: 5px 7px; }
        th { background: #eee; text-align: left; font-size: 10px; }
        td.num, th.num { text-align: right; }
        tfoot td { font-weight: bold; background: #f7f7f7; }
        .info { margin-bottom: 12px; }
        .info td { border: none; padding: 1px 4px 1px 0; font-size: 11px; }
        .ttd { margin-top: 28px; width: 100%; }
        .ttd td { border: none; text-align: center; vertical-align: top; font-size: 10px; width: 33%; }
        .footer { position: fixed; bottom: 12px; left: 0; right: 0; text-align: center; font-size: 8px; color: #888; }
    </style>
</head>
<body>
    <div class="kop">
        <h1>BUKTI PENGELUARAN BENDAHARA (BEND26)</h1>
        <p>Dihasilkan otomatis oleh sistem Karen — koefisien pajak {{ $koefisien }}</p>
    </div>

    <h2 class="title">Kwitansi Belanja Maintenance Kendaraan</h2>
    <p class="nomor">Maintenance #{{ $maintenance->id }} — Nota {{ $maintenance->nota_number ?? '-' }}</p>

    <table class="info">
        <tr>
            <td width="18%"><strong>Kendaraan</strong></td>
            <td width="32%">: {{ $vehicle->name }} ({{ $vehicle->plate_number }}), tahun {{ $vehicle->year }}</td>
            <td width="18%"><strong>Bengkel</strong></td>
            <td>: {{ $maintenance->workshop_name ?? '-' }}</td>
        </tr>
        <tr>
            <td><strong>Perawatan</strong></td>
            <td>: {{ $maintenance->start_date->translatedFormat('d/m/Y') }} s.d. {{ $maintenance->end_date->translatedFormat('d/m/Y') }}</td>
            <td><strong>Tgl Nota</strong></td>
            <td>: {{ optional($maintenance->nota_date)->translatedFormat('d/m/Y') ?? '-' }}</td>
        </tr>
        <tr>
            <td><strong>Catatan</strong></td>
            <td>: {{ $maintenance->note ?? '-' }}</td>
            <td></td>
            <td></td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th width="6%">No</th>
                <th>Pos Anggaran</th>
                <th class="num">Nilai Nota</th>
                <th class="num">Pajak (13%)</th>
                <th class="num">Nilai × {{ $koefisien }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach (\App\Models\VehicleBudget::POSTS as $i => $post)
                @php($c = $costs->firstWhere('post', $post))
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ strtoupper(str_replace('_', ' ', $post)) }}</td>
                    <td class="num">{{ $rp($c->raw_amount ?? 0) }}</td>
                    <td class="num">{{ $rp(($c->raw_amount ?? 0) * ($koefisien - 1)) }}</td>
                    <td class="num">{{ $rp($c->taxed_amount ?? 0) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2">JUMLAH</td>
                <td class="num">{{ $rp($totalRaw) }}</td>
                <td class="num">{{ $rp($totalRaw * ($koefisien - 1)) }}</td>
                <td class="num">{{ $rp($totalTaxed) }}</td>
            </tr>
        </tfoot>
    </table>

    <table class="ttd">
        <tr>
            <td>Diterima,<div style="height:52px"></div>( ................ )</td>
            <td>Bendahara Pengeluaran,<div style="height:52px"></div>( ................ )</td>
            <td>Pengurus Kendaraan,<div style="height:52px"></div>( ................ )</td>
        </tr>
    </table>

    <div class="footer">Dokumen digenerate oleh Karen pada {{ now()->translatedFormat('d/m/Y H:i') }} — versi otomatis, tanda tangan basah dibubuhkan setelah cetak.</div>
</body>
</html>
