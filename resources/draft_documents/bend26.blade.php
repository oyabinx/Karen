@php($rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.'))
@php($bulanTahun = \Illuminate\Support\Carbon::create($year, $month, 1)->translatedFormat('F Y'))
@php($pejabat = $identity['pejabat'] ?? [])
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Bend26 — {{ $posLabel }} — {{ $bulanTahun }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
        h1.title { text-align: center; font-size: 14px; text-decoration: underline; margin: 0 0 16px; }
        table.form { width: 100%; border-collapse: collapse; }
        table.form td { padding: 2px 4px; vertical-align: top; border: none; }
        td.label { width: 165px; }
        td.semicolon { width: 8px; }
        .terbilang-row td { padding-top: 0; }
        .ttd { margin-top: 4px; width: 100%; border-collapse: collapse; }
        .ttd td { border: none; text-align: center; font-size: 11px; width: 33.33%; vertical-align: top; padding: 2px 6px; }
        .ttd .space { height: 58px; }
        .bawah { margin-top: 14px; width: 100%; border-collapse: collapse; }
        .bawah td { border: 1px solid #444; vertical-align: top; font-size: 10.5px; padding: 6px 8px; }
        .bawah .space { height: 46px; }
        .footer { position: fixed; bottom: 10px; left: 0; right: 0; text-align: center; font-size: 8px; color: #888; }
    </style>
</head>
<body>
    <h1 class="title">BUKTI KAS PENGELUARAN</h1>

    <table class="form">
        <tr>
            <td class="label">Terima dari</td>
            <td class="semicolon">:</td>
            <td>{{ $identity['terima_dari'] }}</td>
        </tr>
        <tr>
            <td class="label">Uang sebesar</td>
            <td class="semicolon">:</td>
            <td>{{ $rp($total) }}</td>
        </tr>
        <tr class="terbilang-row">
            <td class="label" style="text-align:right">dengan huruf</td>
            <td class="semicolon">:</td>
            <td>{{ $terbilang }} rupiah</td>
        </tr>
        <tr>
            <td class="label">Yaitu untuk pembayaran</td>
            <td class="semicolon">:</td>
            <td>
                {{ $posLabel }}<br>
                {{ $vehicles->pluck('plate_number')->implode(', ') }}, Sub Kegiatan Penyediaan Jasa Pemeliharaan,
                Biaya Pemeliharaan, Pajak dan Perizinan Kendaraan Dinas Operasional atau Lapangan, Kegiatan
                Pemeliharaan Barang Milik Daerah Penunjang Urusan Pemerintahan Daerah.
                Bulan {{ $bulanTahun }}, Nota terlampir
            </td>
        </tr>
        <tr>
            <td class="label">Terbilang</td>
            <td class="semicolon">:</td>
            <td>{{ $rp($total) }}</td>
        </tr>
    </table>

    <p style="text-align:right; margin: 6px 0 0">{{ $identity['kota'] }}, {{ $bulanTahun }}</p>

    <table class="ttd">
        <tr>
            <td>Mengetahui dan menyetujui</td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td>Pengguna Anggaran,</td>
            <td>Bendahara Pengeluaran,</td>
            <td>Yang menerima,</td>
        </tr>
        <tr>
            <td><div class="space"></div></td>
            <td><div class="space"></div></td>
            <td><div class="space"></div></td>
        </tr>
        <tr>
            <td>
                <strong>{{ $pejabat['pengguna_anggaran']['nama'] ?? '' }}</strong><br>
                NIP. {{ $pejabat['pengguna_anggaran']['nip'] ?? '' }}
            </td>
            <td>
                <strong>{{ $pejabat['bendahara']['nama'] ?? '' }}</strong><br>
                NIP. {{ $pejabat['bendahara']['nip'] ?? '' }}
            </td>
            <td>
                @if (!empty($pejabat['penerima']['nama']))<strong>{{ $pejabat['penerima']['nama'] }}</strong><br>@endif
                Alamat : {{ $pejabat['penerima']['nip'] ?? '' }}
            </td>
        </tr>
    </table>

    <table class="bawah">
        <tr>
            <td style="text-align:center; font-weight:bold">Barang tersebut sudah diterima<br>dengan cukup dan baik</td>
            <td style="text-align:center; font-weight:bold">Telah dipungut :</td>
            <td style="text-align:center; font-weight:bold">Telah dibukukan :</td>
        </tr>
        <tr style="height:64px">
            <td></td>
            <td>
                PPN &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{ number_format($pajak['ppn'], 0, ',', '.') }}<br>
                PPh &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{ number_format($pajak['pph'], 0, ',', '.') }} +<br>
                Jml. &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{ number_format($pajak['jumlah'], 0, ',', '.') }}
            </td>
            <td>
                BK. Tgl. &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; No.<br>
                No. Rek. &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{ $identity['no_rek'][$post] ?? '' }}<br>
                Kode Kegiatan &nbsp;{{ $identity['kode_kegiatan'] ?? '' }}<br>
                Tahun Anggaran : {{ $year }}
            </td>
        </tr>
        <tr>
            <td style="text-align:center">
                <strong>{{ $pejabat['pptk']['nama'] ?? '' }}</strong><br>
                NIP. {{ $pejabat['pptk']['nip'] ?? '' }}
            </td>
            <td style="text-align:center">
                <strong>{{ $pejabat['bendahara']['nama'] ?? '' }}</strong><br>
                NIP. {{ $pejabat['bendahara']['nip'] ?? '' }}
            </td>
            <td style="text-align:center">
                <strong>{{ $pejabat['bendahara']['nama'] ?? '' }}</strong><br>
                NIP. {{ $pejabat['bendahara']['nip'] ?? '' }}
            </td>
        </tr>
    </table>

    <div class="footer">Bend26 bulanan digenerate oleh Karen pada {{ now()->translatedFormat('d/m/Y H:i') }} — total = akumulasi realisasi pos {{ strtolower(str_replace('_', ' ', $post)) }} bulan {{ $bulanTahun }} (termasuk koefisien pajak).</div>
</body>
</html>
