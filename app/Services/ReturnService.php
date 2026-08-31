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
 * Pengembalian otomatis (scheduler) menyusul di Fase 7.
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
}
