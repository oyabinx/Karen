@php($rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.'))
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Kartu Inventaris Pemeliharaan Kendaraan</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
        .kop { text-align: center; border-bottom: 3px double #111; padding-bottom: 8px; margin-bottom: 14px; }
        .kop h1 { font-size: 14px; margin: 0; }
        .kop p { margin: 2px 0; font-size: 10px; color: #333; }
        h2.title { text-align: center; font-size: 13px; margin: 0 0 12px; text-decoration: underline; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #444; padding: 4px 6px; }
        th { background: #eee; text-align: left; font-size: 10px; }
        td.num, th.num { text-align: right; }
        tfoot.summary td { font-weight: bold; background: #f7f7f7; }
        .neg { color: #b91c1c; }
        .info { margin-bottom: 12px; }
        .info td { border: none; padding: 1px 4px 1px 0; }
        .footer { position: fixed; bottom: 12px; left: 0; right: 0; text-align: center; font-size: 8px; color: #888; }
        thead { display: table-header-group; }
    </style>
</head>
<body>
    <div class="kop">
        <h1>KARTU INVENTARIS PEMELIHARAAN KENDARAAN</h1>
        <p>Tahun Anggaran {{ $year }}</p>
    </div>

    <table class="info">
        <tr>
            <td width="20%"><strong>Kendaraan</strong></td>
            <td width="40%">: {{ $vehicle->name }}</td>
            <td width="20%"><strong>Plat</strong></td>
            <td>: {{ $vehicle->plate_number }}</td>
        </tr>
        <tr>
            <td><strong>Tahun Buat</strong></td>
            <td>: {{ $vehicle->year }}</td>
            <td><strong>Kapasitas</strong></td>
            <td>: {{ $vehicle->capacity }} kursi</td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="14%">Tanggal</th>
                <th>Bengkel / Nota</th>
                @foreach (\App\Models\VehicleBudget::POSTS as $post)
                    <th class="num">{{ ucfirst(str_replace('_', ' ', $post)) }}</th>
                @endforeach
                <th class="num">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($maintenances as $i => $m)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $m->start_date->translatedFormat('d/m/y') }}–{{ $m->end_date->translatedFormat('d/m/y') }}</td>
                    <td>{{ $m->workshop_name ?? '-' }}@if ($m->nota_number) · {{ $m->nota_number }}@endif</td>
                    @foreach (\App\Models\VehicleBudget::POSTS as $post)
                        @php($c = $m->costs->firstWhere('post', $post))
                        <td class="num">{{ ($c && $c->raw_amount > 0) ? $rp($c->taxed_amount) : '—' }}</td>
                    @endforeach
                    <td class="num">{{ $rp($m->costs->sum('taxed_amount')) }}</td>
                </tr>
            @empty
                <tr><td colspan="8" style="text-align:center">Belum ada perawatan tercatat pada tahun ini.</td></tr>
            @endforelse
        </tbody>
        <tfoot class="summary">
            <tr>
                <td colspan="3">ANGGARAN TAHUN {{ $year }}</td>
                @foreach (\App\Models\VehicleBudget::POSTS as $post)
                    <td class="num">{{ $rp($summary[$post]['anggaran']) }}</td>
                @endforeach
                <td class="num">{{ $rp(collect($summary)->sum('anggaran')) }}</td>
            </tr>
            <tr>
                <td colspan="3">REALISASI (×{{ number_format(App\Services\BudgetService::koefisienPajak(), 2, ', ', '.') }})</td>
                @foreach (\App\Models\VehicleBudget::POSTS as $post)
                    <td class="num">{{ $rp($summary[$post]['realisasi']) }}</td>
                @endforeach
                <td class="num">{{ $rp(collect($summary)->sum('realisasi')) }}</td>
            </tr>
            <tr>
                <td colspan="3">SISA ANGGARAN</td>
                @foreach (\App\Models\VehicleBudget::POSTS as $post)
                    <td class="num {{ $summary[$post]['sisa'] < 0 ? 'neg' : '' }}">{{ $rp($summary[$post]['sisa']) }}</td>
                @endforeach
                <td class="num {{ collect($summary)->sum('sisa') < 0 ? 'neg' : '' }}">{{ $rp(collect($summary)->sum('sisa')) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">Kartu inventaris digenerate oleh Karen pada {{ now()->translatedFormat('d/m/Y H:i') }}.</div>
</body>
</html>
