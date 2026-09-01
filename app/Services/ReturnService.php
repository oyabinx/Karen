<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Complaint;
use Illuminate\Support\Facades\DB;

/**
 * Pengembalian kendaraan (docs/feature/pengembalian.md &
 * pengembalian_otomatis.md).
 *
 * Tombol "Selesai": keluhan OPSIONAL dan bersifat CATATAN SAJA
 * (kesepakatan revisi) — tidak mengubah kondisi/status kendaraan;
 * mobil langsung tersedia kembali. Tindak lanjut keluhan diputuskan
 * manual oleh pengurus (jadwalkan maintenance bila perlu).
 * Pengembalian otomatis (autoReturn) dijalankan scheduler 00:01.
 */
class ReturnService
{
    /**
     * Pengembalian manual via tombol "Selesai".
     *
     * @throws \DomainException bila booking tidak berstatus dipinjam
     */
    public function manualReturn(Booking $booking, ?string $complaint = null): Booking
    {
        if ($booking->status !== Booking::STATUS_DIPINJAM) {
            throw new \DomainException('Peminjaman ini tidak sedang berstatus dipinjam.');
        }

        return DB::transaction(function () use ($booking, $complaint) {
            $booking->update([
                'status' => Booking::STATUS_DIKEMBALIKAN,
                'returned_at' => now(),
            ]);

            $pesan = trim((string) $complaint);

            if ($pesan !== '') {
                // Catatan keluhan untuk pengurus — TANPA menyentuh
                // kondisi/status kendaraan (mobil tetap bisa dipinjam)
                Complaint::create([
                    'booking_id' => $booking->id,
                    'message' => $pesan,
                ]);
            }

            return $booking->refresh();
        });
    }

    /**
     * Pengembalian & penutupan otomatis (scheduler harian 00:01 —
     * docs/feature/pengembalian_otomatis.md). Idempoten.
     *
     * 1. Booking `dipinjam` yang melewati end_date tanpa tombol
     *    "Selesai" → `dikembalikan` (auto_returned, returned_at).
     * 2. Booking `menunggu_penggantian` yang melewati end_date tanpa
     *    keputusan pengurus → `dibatalkan` — kuota bidang lepas dan
     *    mobil lama tidak terkunci selamanya. Booking end_date HARI
     *    INI tidak disentuh (masih berlaku sampai 24:00).
     *
     * @return array{dikembalikan: int, dibatalkan: int}
     */
    public function autoReturn(): array
    {
        $dikembalikan = Booking::query()
            ->where('status', Booking::STATUS_DIPINJAM)
            ->whereDate('end_date', '<', today())
            ->update([
                'status' => Booking::STATUS_DIKEMBALIKAN,
                'auto_returned' => true,
                'returned_at' => now(),
            ]);

        $dibatalkan = Booking::query()
            ->where('status', Booking::STATUS_MENUNGGU_PENGGANTIAN)
            ->whereDate('end_date', '<', today())
            ->update([
                'status' => Booking::STATUS_DIBATALKAN,
                'auto_returned' => true,
            ]);

        // Jejak kesehatan scheduler — dibaca kartu di dashboard admin
        // (deteksi pemicu cron mati; lihat docs/taskplan Fase 10.3)
        \App\Models\IntegrationSetting::updateOrCreate(
            ['setting_key' => 'system_last_auto_return'],
            ['value' => now()->toDateTimeString()],
        );

        return ['dikembalikan' => $dikembalikan, 'dibatalkan' => $dibatalkan];
    }
}
