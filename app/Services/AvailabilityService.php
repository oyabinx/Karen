<?php

namespace App\Services;

use App\Models\Vehicle;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Ketersediaan kendaraan pada rentang tanggal (docs/tech.md §4.1).
 *
 * Semua peminjaman berbasis hari penuh 00:00–24:00 — overlap dua
 * rentang [A1,A2] vs [B1,B2] terjadi jika A1 <= B2 AND A2 >= B1.
 */
class AvailabilityService
{
    /**
     * Mobil yang tersedia pada rentang [start, end].
     *
     * @return Collection<int, Vehicle>
     */
    public function availableBetween(Carbon $start, Carbon $end, ?int $excludeVehicleId = null): Collection
    {
        return Vehicle::query()
            ->where('status', 'bisa_dipinjam')
            ->where('condition', 'baik')
            ->when($excludeVehicleId, fn ($q) => $q->where('id', '!=', $excludeVehicleId))
            ->whereDoesntHave('bookings', fn ($q) => $q
                ->whereIn('status', ['dipinjam', 'menunggu_penggantian'])
                ->whereDate('start_date', '<=', $end->endOfDay())
                ->whereDate('end_date', '>=', $start->startOfDay()))
            ->whereDoesntHave('maintenances', fn ($q) => $q
                ->where('status', 'terjadwal')
                ->whereDate('start_date', '<=', $end->endOfDay())
                ->whereDate('end_date', '>=', $start->startOfDay()))
            ->whereDoesntHave('events', fn ($q) => $q
                ->where('status', 'terjadwal')
                ->whereDate('start_date', '<=', $end->endOfDay())
                ->whereDate('end_date', '>=', $start->startOfDay()))
            ->orderBy('name')
            ->get();
    }

    /**
     * Apakah sebuah mobil tersedia pada rentang [start, end]?
     */
    public function isAvailable(Vehicle $vehicle, Carbon $start, Carbon $end): bool
    {
        return $this->availableBetween($start, $end)->contains('id', $vehicle->id);
    }

    /**
     * Apakah mobil bebas pada rentang — mengabaikan SATU booking tertentu
     * (dipakai saat menilai kelayakan revert booking menunggu_penggantian
     * ke mobil semula: booking itu sendiri tidak boleh menghalangi).
     */
    public function isFreeIgnoringBooking(Vehicle $vehicle, Carbon $start, Carbon $end, int $ignoreBookingId): bool
    {
        return Vehicle::query()
            ->whereKey($vehicle->id)
            ->where('status', 'bisa_dipinjam')
            ->where('condition', 'baik')
            ->whereDoesntHave('bookings', fn ($q) => $q
                ->whereIn('status', ['dipinjam', 'menunggu_penggantian'])
                ->where('id', '!=', $ignoreBookingId)
                ->whereDate('start_date', '<=', $end->endOfDay())
                ->whereDate('end_date', '>=', $start->startOfDay()))
            ->whereDoesntHave('maintenances', fn ($q) => $q
                ->where('status', 'terjadwal')
                ->whereDate('start_date', '<=', $end->endOfDay())
                ->whereDate('end_date', '>=', $start->startOfDay()))
            ->whereDoesntHave('events', fn ($q) => $q
                ->where('status', 'terjadwal')
                ->whereDate('start_date', '<=', $end->endOfDay())
                ->whereDate('end_date', '>=', $start->startOfDay()))
            ->exists();
    }
}
