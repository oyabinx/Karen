<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Event;
use App\Models\Vehicle;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Event armada bidang (docs/feature/event_bidang.md) — admin & pengurus.
 *
 * Event meminjamkan banyak mobil sekaligus DI ATAS kuota bidang
 * (pemakaian event tidak dihitung kuota — otomatis, karena event
 * bukan booking). Durasi fleksibel (boleh >3 hari).
 */
class EventService
{
    public function __construct(
        private readonly ReplacementService $replacements,
    ) {}

    /**
     * Opsi armada untuk wizard: kendaraan layak (bisa_dipinjam, baik,
     * tanpa maintenance overlap, tanpa event lain overlap) dibedakan
     * bebas (tanpa booking overlap) vs menabrak (ada booking dipinjam).
     *
     * @return array{bebas: Collection<int, Vehicle>, menabrak: Collection<int, Vehicle>}
     */
    public function armadaOptions(Carbon $start, Carbon $end): array
    {
        $layak = Vehicle::query()
            ->where('status', 'bisa_dipinjam')
            ->where('condition', 'baik')
            ->whereDoesntHave('maintenances', fn ($q) => $q
                ->where('status', 'terjadwal')
                ->whereDate('start_date', '<=', $end->endOfDay())
                ->whereDate('end_date', '>=', $start->startOfDay()))
            ->whereDoesntHave('events', fn ($q) => $q
                ->where('status', 'terjadwal')
                ->whereDate('start_date', '<=', $end->endOfDay())
                ->whereDate('end_date', '>=', $start->startOfDay()))
            ->withCount(['bookings as conflict_count' => fn ($q) => $q
                ->where('status', Booking::STATUS_DIPINJAM)
                ->whereDate('start_date', '<=', $end->endOfDay())
                ->whereDate('end_date', '>=', $start->startOfDay())])
            ->orderBy('name')
            ->get();

        return [
            'bebas' => $layak->filter(fn ($v) => $v->conflict_count === 0)->values(),
            'menabrak' => $layak->filter(fn ($v) => $v->conflict_count > 0)->values(),
        ];
    }

    /**
     * Apakah kendaraan bisa masuk armada event pada rentang tsb
     * (dipakai validasi store — mencegah submit curang).
     */
    public function isEligible(Vehicle $vehicle, Carbon $start, Carbon $end): bool
    {
        return Vehicle::query()
            ->whereKey($vehicle->id)
            ->where('status', 'bisa_dipinjam')
            ->where('condition', 'baik')
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

    /**
     * Buat event: kunci armada + tandai booking yang ditabrak menjadi
     * menunggu_penggantian (reuse ReplacementService).
     *
     * @param  array<int, int>  $vehicleIds
     * @return array{event: Event, terdampak: Booking[]}
     */
    public function createEvent(array $data, array $vehicleIds, int $createdBy): array
    {
        $event = Event::create([
            'name' => $data['name'],
            'bidang_id' => $data['bidang_id'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'note' => $data['note'] ?? null,
            'status' => 'terjadwal',
            'created_by' => $createdBy,
        ]);

        $event->vehicles()->attach($vehicleIds);

        // Booking dipinjam pada armada event yang overlap → menunggu pengganti
        $terdampak = Booking::query()
            ->whereIn('vehicle_id', $vehicleIds)
            ->where('status', Booking::STATUS_DIPINJAM)
            ->whereDate('start_date', '<=', $event->end_date)
            ->whereDate('end_date', '>=', $event->start_date)
            ->get();

        foreach ($terdampak as $booking) {
            $booking->update(['status' => Booking::STATUS_MENUNGGU_PENGGANTIAN]);
        }

        return ['event' => $event, 'terdampak' => $terdampak->all()];
    }

    /**
     * Booking yang masih menunggu penggantian pada armada event ini
     * (rentang overlap dengan event).
     *
     * @return Collection<int, Booking>
     */
    public function unresolvedConflicts(Event $event): Collection
    {
        return Booking::with(['user.seksi.bidang', 'vehicle'])
            ->whereIn('vehicle_id', $event->vehicles->pluck('id'))
            ->where('status', Booking::STATUS_MENUNGGU_PENGGANTIAN)
            ->whereDate('start_date', '<=', $event->end_date)
            ->whereDate('end_date', '>=', $event->start_date)
            ->orderBy('start_date')
            ->get();
    }

    /**
     * Batalkan event: armada lepas (status dibatalkan — ketersediaan
     * berbasis tanggal otomatis bebas). Booking yang SUDAH diganti
     * tetap di penggantinya; yang BELUM dikembalikan ke mobil semula
     * (konsisten pembatalan maintenance — docs event_bidang.md §3).
     */
    public function cancelEvent(Event $event): int
    {
        // Status dahulu — agar event ini tidak ikut memblokir pengecekan
        // ketersediaan saat menilai kelayakan revert booking
        $event->update(['status' => 'dibatalkan']);

        $dikembalikan = 0;

        foreach ($event->vehicles as $vehicle) {
            $dikembalikan += $this->replacements->revertPendingForVehicle($vehicle);
        }

        return $dikembalikan;
    }

    /**
     * Selesaikan otomatis event yang lewat jatuh tempo (idempoten).
     */
    public function autoFinish(): int
    {
        $selesai = Event::where('status', 'terjadwal')
            ->whereDate('end_date', '<', today())
            ->update(['status' => 'selesai']);

        // Jejak kesehatan scheduler (dibaca dashboard admin)
        \App\Models\IntegrationSetting::updateOrCreate(
            ['setting_key' => 'system_last_auto_finish'],
            ['value' => now()->toDateTimeString()],
        );

        return $selesai;
    }
}
