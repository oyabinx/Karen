<?php

namespace App\Support;

use NumberFormatter;

/**
 * Identitas & perhitungan dokumen bend26 (BKPN) bulanan per pos —
 * UAT 04 rev-2, format mengikuti file contoh docs/Bend26/.
 *
 * Seluruh nilai dapat diubah admin via Pengaturan Aplikasi
 * (aturan: konfigurasi runtime hanya lewat UI).
 */
class Bend26Identity
{
    /**
     * Nilai bawaan dari file contoh (docs/Bend26/*.pdf).
     * No. Rek Pelumas & Servis AC tidak ada di contoh — dikosongkan,
     * diisi admin.
     *
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'terima_dari' => 'DINAS TENAGA KERJA & TRANSMIGRASI DIY',
            'kota' => 'Yogyakarta',
            'pejabat' => [
                'pengguna_anggaran' => ['nama' => 'Ariyanto Wibowo, S.H., M.Hum.', 'nip' => '197304231997031001'],
                'bendahara' => ['nama' => 'Nabila Amalia Dewi, A.Md.Kb.N.', 'nip' => '200003072022012002'],
                'penerima' => ['nama' => '', 'nip' => '19700510 200701 1 003'],
                'pptk' => ['nama' => 'Reni Margatina, S.Kom.', 'nip' => '199305092019032013'],
            ],
            'kode_kegiatan' => '2.07.01.1.09.0002',
            // Tarif PPN per pos (B6c UAT 04-3): pelumas TIDAK dikenakan PPN
            // (default 0) — "rumah" tetap disiapkan bila kelak kena pajak.
            'ppn_percent' => [
                'servis' => 11.0,
                'suku_cadang' => 11.0,
                'ac' => 11.0,
                'pelumas' => 0.0,
            ],
            // Tarif PPh (PPh 22) per pos — % dari DPP.
            // Jasa (servis, servis AC) 2%; barang (suku cadang, pelumas) 1,5%.
            'pph_percent' => [
                'servis' => 2.0,
                'suku_cadang' => 1.5,
                'ac' => 2.0,
                'pelumas' => 1.5,
            ],
            'no_rek' => [
                'servis' => '5.1.02.03.002.00035',
                'suku_cadang' => '5.1.02.01.001.00013',
                'ac' => '',
                'pelumas' => '',
            ],
        ];
    }

    /**
     * Label "Yaitu untuk pembayaran" per pos.
     */
    public static function posLabel(string $post): string
    {
        return match ($post) {
            'servis' => 'Belanja Servis Kendaraan Operasional Roda 4',
            'suku_cadang' => 'Belanja Suku Cadang Kendaraan Operasional Roda 4',
            'ac' => 'Belanja Servis AC Kendaraan Operasional Roda 4',
            'pelumas' => 'Belanja Pelumas Kendaraan Operasional Roda 4',
            default => 'Belanja Pemeliharaan Kendaraan Operasional Roda 4',
        };
    }

    /**
     * PPN & PPh dari nilai total (cara file contoh), tarif PER POS:
     * DPP = total / (1 + ppn%); PPN = DPP × ppn% (pos tanpa PPN, mis.
     * pelumas, memakai DPP = total & PPN 0); PPh = DPP × pph% pos.
     * Dibulatkan ke rupiah utuh.
     *
     * @return array{dpp: float, ppn: float, pph: float, jumlah: float}
     */
    public static function pajak(float $total, string $post, ?array $identity = null): array
    {
        $identity ??= AppSettings::bend26Identity();
        $ppnPercent = max(0.0, (float) (self::percent($identity, 'ppn_percent', $post, 0)));
        $pphPercent = max(0.0, (float) (self::percent($identity, 'pph_percent', $post, 0)));

        $dpp = $ppnPercent > 0 ? $total / (1 + $ppnPercent / 100) : $total;
        $ppn = (int) round($dpp * $ppnPercent / 100);
        $pph = (int) round($dpp * $pphPercent / 100);

        return ['dpp' => round($dpp, 2), 'ppn' => (float) $ppn, 'pph' => (float) $pph, 'jumlah' => (float) ($ppn + $pph)];
    }

    /**
     * Ambil tarif per pos — kompatibel dgn format lama (scalar) & baru
     * (array per pos).
     */
    private static function percent(array $identity, string $key, string $post, float $fallback): float
    {
        $value = $identity[$key] ?? null;

        if (is_array($value)) {
            return (float) ($value[$post] ?? $fallback);
        }

        return $value === null ? $fallback : (float) $value;
    }

    /**
     * Terbilang angka → huruf Indonesia ("3.260.000" →
     * "tiga juta dua ratus enam puluh ribu"). Desimal dibuang
     * (form resmi memakai rupiah utuh).
     */
    public static function terbilang(float $angka): string
    {
        $bulat = (int) round($angka);

        if ($bulat === 0) {
            return 'nol';
        }

        $fmt = new NumberFormatter('id_ID', NumberFormatter::SPELLOUT);

        return $fmt->format($bulat);
    }
}
