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
     * Batalkan booking tanpa pengganti — kuota bidang otomatis lepas
     * karena kuota menghitung booking aktif (BookingService::quotaUsed).
     */
    public function cancel(Booking $booking): Booking
    {
        $booking->update(['status' => Booking::STATUS_DIBATALKAN]);

        return $booking->refresh();
    }

    /**
     * Penggantian PARSIAL (UAT 03-B7): tabrakan hanya menimpa bagian
     * rentang di TEPI (awal/akhir) → tanggal yang menabrak memakai
     * mobil pengganti (booking baru), sisa tanggal TETAP mobil lama
     * (booking asli dipangkas). Tabrakan penuh/m tengah → gunakan
     * assign() biasa.
     *
     * @return array{original: Booking, split: Booking}
     */
    public function assignPartial(Booking $booking, Carbon $overlapStart, Carbon $overlapEnd, Vehicle $replacement): array
    {
        if ($booking->status !== Booking::STATUS_MENUNGGU_PENGGANTIAN) {
            throw new \DomainException('Peminjaman ini tidak menunggu penggantian.');
        }

        $bs = Carbon::parse($booking->start_date)->startOfDay();
        $be = Carbon::parse($booking->end_date)->startOfDay();
        $os = $overlapStart->copy()->startOfDay();
        $oe = $overlapEnd->copy()->startOfDay();

        if ($os->lt($bs) || $oe->gt($be) || $oe->lt($os)) {
            throw new \DomainException('Rentang parsial tidak valid.');
        }

        if ($os->equalTo($bs) && $oe->equalTo($be)) {
            throw new \DomainException('Seluruh rentang tertabrak — gunakan penggantian penuh.');
        }

        if (! ($os->equalTo($bs) || $oe->equalTo($be))) {
            throw new \DomainException('Tabrakan di tengah rentang tidak didukung penggantian parsial — gunakan penggantian penuh.');
        }

        return DB::transaction(function () use ($booking, $bs, $be, $os, $oe, $replacement) {
            Vehicle::whereKey($replacement->id)->lockForUpdate()->first();

            $tersedia = $this->availability->availableBetween($os, $oe, $booking->vehicle_id)
                ->contains('id', $replacement->id);

            if (! $tersedia) {
                throw new \InvalidArgumentException(
                    "Mobil {$replacement->name} tidak tersedia pada {$os->translatedFormat('d M')}–{$oe->translatedFormat('d M Y')}."
                );
            }

            $mobilLama = $booking->vehicle_id;

            // Pangkas booking asli ke sisa tanggal di sisi yang bebas
            if ($os->equalTo($bs)) {
                $sisaStart = $oe->copy()->addDay();
                $sisaEnd = $be;
            } else {
                $sisaStart = $bs;
                $sisaEnd = $os->copy()->subDay();
            }

            $booking->update([
                'start_date' => $sisaStart->toDateString(),
                'end_date' => $sisaEnd->toDateString(),
                'status' => Booking::STATUS_DIPINJAM,
            ]);

            // Booking baru untuk bagian yang menabrak — memakai pengganti
            $split = Booking::create([
                'user_id' => $booking->user_id,
                'vehicle_id' => $replacement->id,
                'original_vehicle_id' => $mobilLama,
                'start_date' => $os->toDateString(),
                'end_date' => $oe->toDateString(),
                'address' => $booking->address,
                'purpose' => $booking->purpose,
                'status' => Booking::STATUS_DIPINJAM,
            ]);

            return ['original' => $booking->refresh(), 'split' => $split];
        });
    }

    /**
     * Rentang pemblokir tunggal sebuah booking menunggu_penggantian
     * (maintenance ATAU event pada mobilnya yang overlap). Dipakai
     * untuk menawarkan penggantian parsial — hanya bila pemblokirnya
     * TUNGGAL.
     *
     * @return array{start: Carbon, end: Carbon, sumber: string}|null
     */
    public function singleBlocker(Booking $booking): ?array
    {
        $bs = Carbon::parse($booking->start_date)->startOfDay();
        $be = Carbon::parse($booking->end_date)->startOfDay();

        $blockers = [];

        $maintenances = \App\Models\Maintenance::query()
            ->where('vehicle_id', $booking->vehicle_id)
            ->where('status', 'terjadwal')
            ->whereDate('start_date', '<=', $be)
            ->whereDate('end_date', '>=', $bs)
            ->get();

        foreach ($maintenances as $m) {
            $blockers[] = ['start' => Carbon::parse($m->start_date)->startOfDay(), 'end' => Carbon::parse($m->end_date)->startOfDay(), 'sumber' => 'maintenance'];
        }

        $events = \App\Models\Event::query()
            ->where('status', 'terjadwal')
            ->whereHas('vehicles', fn ($q) => $q->where('vehicles.id', $booking->vehicle_id))
            ->whereDate('start_date', '<=', $be)
            ->whereDate('end_date', '>=', $bs)
            ->get();

        foreach ($events as $e) {
            $blockers[] = ['start' => Carbon::parse($e->start_date)->startOfDay(), 'end' => Carbon::parse($e->end_date)->startOfDay(), 'sumber' => 'event'];
        }

        if (count($blockers) !== 1) {
            return null; // tanpa pemblokir / lebih dari satu → parsial tidak ditawarkan
        }

        return $blockers[0];
    }

    /**
     * Rentang parsial yang layak (tabrakan tepi, bukan penuh, bukan
     * tengah) atau null bila tidak tersedia.
     *
     * @return array{os: Carbon, oe: Carbon}|null
     */
    public function partialRange(Booking $booking): ?array
    {
        $blocker = $this->singleBlocker($booking);

        if (! $blocker) {
            return null;
        }

        $bs = Carbon::parse($booking->start_date)->startOfDay();
        $be = Carbon::parse($booking->end_date)->startOfDay();
        $os = $bs->max($blocker['start']);
        $oe = $be->min($blocker['end']);

        $penuh = $os->equalTo($bs) && $oe->equalTo($be);
        $tepi = $os->equalTo($bs) || $oe->equalTo($be);

        if ($penuh || ! $tepi) {
            return null;
        }

        return ['os' => $os, 'oe' => $oe];
    }

    /**
     * Kembalikan booking yang masih menunggu_penggantian ke mobil
     * semula — dipakai saat jadwal maintenance dibatalkan/dihapus
     * atau event dibatalkan (docs/feature/penggantian_mobil.md).
     *
     * AVAILABILITY-AWARE: booking hanya dikembalikan bila mobil semula
     * benar-benar bebas pada rentangnya (tanpa maintenance/event lain
     * yang masih menahan). Bila tidak bebas, booking TETAP
     * menunggu_penggantian untuk diselesaikan manual — mencegah
     * booking dipinjam di mobil yang ternyata masih dikuasai blokir
     * lain. Booking yang sudah diganti tidak disentuh.
     *
     * @return int jumlah booking yang berhasil dikembalikan
     */
    public function revertPendingForVehicle(Vehicle $vehicle): int
    {
        $pending = Booking::query()
            ->where('vehicle_id', $vehicle->id)
            ->where('status', Booking::STATUS_MENUNGGU_PENGGANTIAN)
            ->whereNull('original_vehicle_id') // yang sudah diganti tetap di mobil baru
            ->get();

        $dikembalikan = 0;

        foreach ($pending as $booking) {
            $bebas = $this->availability->isFreeIgnoringBooking(
                $vehicle,
                Carbon::parse($booking->start_date),
                Carbon::parse($booking->end_date),
                $booking->id,
            );

            if (! $bebas) {
                continue;
            }

            $booking->update(['status' => Booking::STATUS_DIPINJAM]);
            $dikembalikan++;
        }

        return $dikembalikan;
    }
}
