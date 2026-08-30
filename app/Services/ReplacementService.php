<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Maintenance;
use App\Models\Vehicle;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Penggantian mobil karena maintenance (docs/feature/penggantian_mobil.md).
 *
 * Pemicu (MaintenanceService): maintenance menabrak booking dipinjam
 * → booking menjadi menunggu_penggantian. Kelas ini menyediakan
 * kandidat pengganti, penetapan pengganti, dan pembatalan.
 */
class ReplacementService
{
    public function __construct(
        private readonly AvailabilityService $availability,
    ) {}

    /**
     * Tandai semua booking 'dipinjam' yang menabrak rentang maintenance
     * menjadi 'menunggu_penggantian'.
     *
     * @return Booking[] booking yang terdampak
     */
    public function flagConflictingBookings(Maintenance $maintenance): array
    {
        $affected = Booking::query()
            ->where('vehicle_id', $maintenance->vehicle_id)
            ->where('status', Booking::STATUS_DIPINJAM)
            ->whereDate('start_date', '<=', $maintenance->end_date)
            ->whereDate('end_date', '>=', $maintenance->start_date)
            ->get();

        foreach ($affected as $booking) {
            $booking->update(['status' => Booking::STATUS_MENUNGGU_PENGGANTIAN]);
        }

        return $affected->all();
    }

    /**
     * Kandidat mobil pengganti untuk sebuah booking:
     * tersedia pada rentang booking, tidak termasuk mobil lama.
     *
     * @return Collection<int, Vehicle>
     */
    public function candidates(Booking $booking): Collection
    {
        return $this->availability->availableBetween(
            Carbon::parse($booking->start_date),
            Carbon::parse($booking->end_date),
            $booking->vehicle_id,
        );
    }

    /**
     * Tetapkan mobil pengganti — transaksi + lock agar mobil tidak
     * terbooking pihak lain saat dikonfirmasi (docs/tech.md §4.4).
     */
    public function assign(Booking $booking, Vehicle $replacement): Booking
    {
        return DB::transaction(function () use ($booking, $replacement) {
            // Kunci baris kendaraan pengganti (cegah double-booking)
            Vehicle::whereKey($replacement->id)->lockForUpdate()->first();

            // Cek ulang ketersediaan di dalam transaksi
            $available = $this->availability->availableBetween(
                Carbon::parse($booking->start_date),
                Carbon::parse($booking->end_date),
                $booking->vehicle_id,
            )->contains('id', $replacement->id);

            if (! $available) {
                throw new \InvalidArgumentException(
                    "Mobil {$replacement->name} tidak lagi tersedia pada rentang booking ini."
                );
            }

            $original = $booking->original_vehicle_id ?? $booking->vehicle_id;

            $booking->update([
                'original_vehicle_id' => $original,
                'vehicle_id' => $replacement->id,
                'status' => Booking::STATUS_DIPINJAM,
            ]);

            return $booking->refresh();
        });
    }

    /**
     * Batalkan booking tanpa pengganti — kuota bidang lepas
     * (pengecekan kuota sendiri diimplementasikan Fase 5).
     */
    public function cancel(Booking $booking): Booking
    {
        $booking->update(['status' => Booking::STATUS_DIBATALKAN]);

        return $booking->refresh();
    }

    /**
     * Kembalikan booking yang masih menunggu_penggantian ke mobil
     * semula — dipakai saat jadwal maintenance dibatalkan/dihapus
     * (docs/feature/penggantian_mobil.md langkah 3).
     *
     * @return int jumlah booking yang dikembalikan
     */
    public function revertPendingForVehicle(Vehicle $vehicle): int
    {
        return Booking::query()
            ->where('vehicle_id', $vehicle->id)
            ->where('status', Booking::STATUS_MENUNGGU_PENGGANTIAN)
            ->whereNull('original_vehicle_id') // yang sudah diganti tetap di mobil baru
            ->update(['status' => Booking::STATUS_DIPINJAM]);
    }
}
