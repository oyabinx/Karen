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
     * Guard: peminjaman yang BELUM dimulai tidak boleh "dikembalikan"
     * (mobil belum dipakai) — gunakan cancelByBorrower (usulan user
     * pasca-UAT 03: tombol adaptif berdasarkan tanggal mulai).
     *
     * @throws \DomainException bila booking tidak berstatus dipinjam / belum mulai
     */
    public function manualReturn(Booking $booking, ?string $complaint = null): Booking
    {
        if ($booking->status !== Booking::STATUS_DIPINJAM) {
            throw new \DomainException('Peminjaman ini tidak sedang berstatus dipinjam.');
        }

        if ($booking->belumMulai()) {
            throw new \DomainException('Peminjaman belum dimulai — gunakan tombol "Batalkan Peminjaman".');
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
     * Pembatalan OLEH PEGAWAI SENDIRI untuk peminjaman yang BELUM
     * dimulai (hari ini < tanggal mulai) — tombol "Batalkan Peminjaman"
     * (usulan user pasca-UAT 03). Status → dibatalkan + waktu
     * pembatalan; kuota bidang lepas; mobil langsung tersedia.
     *
     * @throws \DomainException bila sudah dimulai / bukan dipinjam
     */
    public function cancelByBorrower(Booking $booking): Booking
    {
        if ($booking->status !== Booking::STATUS_DIPINJAM) {
            throw new \DomainException('Peminjaman ini tidak berstatus dipinjam.');
        }

        if (! $booking->belumMulai()) {
            throw new \DomainException('Peminjaman sudah dimulai — gunakan tombol "Selesai — Kembalikan Mobil".');
        }

        return DB::transaction(function () use ($booking) {
            $booking->update([
                'status' => Booking::STATUS_DIBATALKAN,
                'cancelled_at' => now(),
            ]);

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
                'cancelled_at' => now(),
            ]);

        // Jejak kesehatan scheduler — dibaca kartu di dashboard admin
        // (deteksi pemicu cron mati; lihat docs/taskplan Fase 10.3)
        \App\Models\IntegrationSetting::updateOrCreate(
            ['setting_key' => 'system_last_auto_return'],
            ['value' => now()->toDateTimeString()],
        );

        // Log aktivitas ringkasan (mass-update tidak memicu event model)
        \App\Models\ActivityLog::create([
            'user_id' => null,
            'action' => \App\Models\ActivityLog::ACTION_SYSTEM,
            'description' => "Sistem: pengembalian otomatis — {$dikembalikan} peminjaman dikembalikan, {$dibatalkan} dibatalkan (menunggu pengganti lewat tempo).",
        ]);

        return ['dikembalikan' => $dikembalikan, 'dibatalkan' => $dibatalkan];
    }
}
