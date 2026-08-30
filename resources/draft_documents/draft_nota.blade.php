@php($rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.'))
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Draft Nota — {{ strtoupper(str_replace('_', ' ', $cost->post)) }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
        .kop { text-align: center; border-bottom: 2px solid #111; padding-bottom: 8px; margin-bottom: 14px; }
        .kop h1 { font-size: 14px; margin: 0; }
        .kop p { margin: 2px 0; font-size: 10px; color: #333; }
        h2.title { text-align: center; font-size: 12px; margin: 0 0 12px; text-decoration: underline; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #444; padding: 5px 7px; }
        th { background: #eee; text-align: left; font-size: 10px; }
        td.num, th.num { text-align: right; }
        .info { margin-bottom: 12px; }
        .info td { border: none; padding: 1px 4px 1px 0; }
        .ttd { margin-top: 30px; width: 100%; }
        .ttd td { border: none; text-align: center; font-size: 10px; }
        .footer { position: fixed; bottom: 12px; left: 0; right: 0; text-align: center; font-size: 8px; color: #888; }
        .draft { border: 2px dashed #b45309; padding: 3px 10px; display: inline-block; color: #b45309; font-weight: bold; font-size: 9px; }
    </style>
</head>
<body>
    <div class="kop">
        <h1>DRAFT NOTA — POS {{ strtoupper(str_replace('_', ' ', $cost->post)) }}</h1>
        <p>Digenerate sistem Karen untuk dibuat ulang oleh bengkel sesuai rincian ini</p>
    </div>

    <p style="text-align:right"><span class="draft">DRAFT — BUKAN NOTA RESMI</span></p>

    <table class="info">
        <tr>
            <td width="20%"><strong>Kendaraan</strong></td>
            <td>: {{ $vehicle->name }} ({{ $vehicle->plate_number }})</td>
        </tr>
        <tr>
            <td><strong>Bengkel</strong></td>
            <td>: {{ $maintenance->workshop_name ?? '-' }}</td>
        </tr>
        <tr>
            <td><strong>Rujukan Nota Asli</strong></td>
            <td>: {{ $maintenance->nota_number ?? '-' }} — {{ optional($maintenance->nota_date)->translatedFormat('d/m/Y') ?? '-' }}</td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th width="6%">No</th>
                <th>Uraian</th>
                <th class="num" width="22%">Nilai (× {{ $koefisien }})</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>1</td>
                <td>Pemeliharaan pos {{ strtolower(str_replace('_', ' ', $cost->post)) }} — {{ $maintenance->note ?? 'perawatan kendaraan dinas' }}</td>
                <td class="num">{{ $rp($cost->taxed_amount) }}</td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2"><strong>JUMLAH</strong></td>
                <td class="num"><strong>{{ $rp($cost->taxed_amount) }}</strong></td>
            </tr>
        </tfoot>
    </table>

    <table class="ttd">
        <tr>
            <td width="50%">Disetujui,<div style="height:52px"></div>( Pengurus Kendaraan )</td>
            <td>Dibuat ulang oleh,<div style="height:52px"></div>( Bengkel )</td>
        </tr>
    </table>

    <div class="footer">Draft nota digenerate oleh Karen pada {{ now()->translatedFormat('d/m/Y H:i') }} — nilai sudah termasuk koefisien {{ $koefisien }}.</div>
</body>
</html>
