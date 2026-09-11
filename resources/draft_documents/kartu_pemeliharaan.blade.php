@php($rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.'))
@php($jenis = ['servis' => 'Servis', 'suku_cadang' => 'Suku Cadang', 'ac' => 'Servis AC', 'pelumas' => 'Pelumas'])
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Kartu Pemeliharaan Kendaraan — {{ $vehicle->name }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
        .kop { text-align: center; margin-bottom: 14px; }
        .kop h1 { font-size: 14px; margin: 0 0 2px; text-decoration: underline; }
        .kop p { margin: 1px 0; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #444; padding: 5px 7px; vertical-align: top; }
        th { background: #eee; text-align: center; font-size: 10px; }
        td.num { text-align: right; white-space: nowrap; }
        td.ctr { text-align: center; }
        thead { display: table-header-group; }
        .rincian { font-size: 10px; }
        .rincian div { margin-bottom: 1px; }
        .footer { position: fixed; bottom: 10px; left: 0; right: 0; text-align: center; font-size: 8px; color: #888; }
    </style>
</head>
<body>
    <div class="kop">
        <h1>Kartu Pemeliharaan Kendaraan</h1>
        <p>Tahun Anggaran {{ $year }}</p>
        <p>{{ $vehicle->name }}</p>
        <p>{{ $vehicle->plate_number }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%">Nomor</th>
                <th width="12%">Tanggal</th>
                <th width="15%">Jenis Perbaikan</th>
                <th>Rincian Pemeliharaan</th>
                <th width="16%">Biaya</th>
            </tr>
        </thead>
        <tbody>
            @php($no = 0)
            @forelse ($maintenances as $m)
                @foreach (\App\Models\VehicleBudget::POSTS as $post)
                    @php($cost = $m->costs->firstWhere('post', $post))
                    @if ($cost && $cost->raw_amount > 0)
                        @php($no++)
                        <tr>
                            <td class="ctr">{{ $no }}</td>
                            <td class="ctr">{{ optional($m->nota_date ?? $m->start_date)->translatedFormat('d/m/Y') }}</td>
                            <td>{{ $jenis[$post] ?? $post }}</td>
                            <td>
                                <div class="rincian">
                                    @if ($cost->details->isNotEmpty())
                                        @foreach ($cost->details as $detail)
                                            <div>{{ $detail->description }}@if ($detail->amount > 0) — {{ $rp($detail->amount) }}@endif</div>
                                        @endforeach
                                    @else
                                        <div>{{ $m->note ?? 'perawatan kendaraan dinas' }}</div>
                                    @endif
                                    <div style="margin-top:2px"><em>Bengkel: {{ $m->workshop_name ?? '-' }}@if($m->nota_number) · {{ $m->nota_number }}@endif</em></div>
                                </div>
                            </td>
                            <td class="num">{{ $rp($cost->taxed_amount) }}</td>
                        </tr>
                    @endif
                @endforeach
            @empty
                <tr>
                    <td colspan="5" style="text-align:center; color:#666">Belum ada perawatan tercatat pada tahun ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">Kartu Pemeliharaan Kendaraan digenerate oleh Karen pada {{ now()->translatedFormat('d/m/Y H:i') }} — biaya merupakan nilai setelah koefisien pajak.</div>
</body>
</html>
