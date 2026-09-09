<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Peminjaman self-service (docs/feature/peminjaman.md & kuota_bidang.md).
 *
 * Untuk pegawai DAN pengurus (pengurus = user pegawai juga).
 * Aturan: hari penuh 00:00–24:00 (tanpa satuan jam), durasi maks
 * 3 hari kalender termasuk Sabtu-Minggu, start >= hari ini (booking
 * hari-H tetap tercatat mulai 00:00), satu booking aktif per user,
 * kuota bidang (default 2 / khusus 3 — booking event tidak dihitung
 * karena bukan booking).
 */
class BookingService
{
    /**
     * Durasi maksimal peminjaman — TIDAK LAGI konstanta tetap.
     * Dibaca dinamis dari AppSettings (admin dapat mengubahnya via UI,
     * skema baru UAT 03). Fallback 3 hari bila belum diatur.
     */
    public static function maxDurasiHari(): int
    {
        return \App\Support\AppSettings::maxBookingDays();
    }

    public function __construct(
        private readonly AvailabilityService $availability,
    ) {}

    /**
     * Kesalahan rentang tanggal (kosong = valid).
     *
     * @return array<int, string>
     */
    public function rangeErrors(?string $start, ?string $end): array
    {
        $errors = [];

        if (! $start || ! $end) {
            return []; // kelengkapan divalidasi rule 'required'
        }

        $start = Carbon::parse($start);
        $end = Carbon::parse($end);

        if ($end->lt($start)) {
            $errors[] = 'Tanggal selesai tidak boleh sebelum tanggal mulai.';

            return $errors;
        }

        if ($start->lt(today())) {
            $errors[] = 'Tanggal mulai tidak boleh di masa lalu.';
        }

        // Durasi inklusif: 1 Feb–3 Feb = 3 hari
        $durasi = $start->diffInDays($end) + 1;

        if ($durasi > self::maxDurasiHari()) {
            $errors[] = "Durasi maksimal peminjaman ".self::maxDurasiHari()." hari (termasuk Sabtu–Minggu) — rentang Anda {$durasi} hari.";
        }

        return $errors;
    }

    /**
     * Informasi kuota bidang peminjam.
     *
     * @return array{bidang: string, used: int, limit: int, remaining: int}
     */
    public function quotaInfo(User $user): array
    {
        $bidang = $user->seksi?->bidang;

        $used = $this->quotaUsed($user);

        return [
            'bidang' => $bidang?->name ?? '—',
            'used' => $used,
            'limit' => $bidang?->max_active_bookings ?? 0,
            'remaining' => max(0, ($bidang?->max_active_bookings ?? 0) - $used),
        ];
    }

    /**
     * Jumlah booking aktif (dipinjam + menunggu_penggantian) milik
     * seluruh anggota bidang peminjam — termasuk booking mendatang
     * (langsung terkonfirmasi = mengunci jatah). Event TIDAK dihitung
     * karena bukan booking (docs/feature/kuota_bidang.md).
     */
    public function quotaUsed(User $user): int
    {
        $bidangId = $user->seksi?->bidang_id;

        if (! $bidangId) {
            return PHP_INT_MAX; // tanpa seksi = tak ada kuota yang bisa dipakai
        }

        return Booking::query()
            ->whereIn('status', [Booking::STATUS_DIPINJAM, Booking::STATUS_MENUNGGU_PENGGANTIAN])
            ->whereHas('user.seksi.bidang', fn ($q) => $q->where('id', $bidangId))
            ->count();
    }

    /**
     * Buat peminjaman — seluruh validasi dijalankan DALAM TRANSAKSI
     * dengan lock pada baris kendaraan (docs/tech.md §4.4) untuk
     * mencegah double-booking saat dua submit nyaris bersamaan.
     *
     * @throws \DomainException dengan pesan Bahasa Indonesia
     */
    public function create(User $user, Vehicle $vehicle, string $start, string $end, string $address, string $purpose): Booking
    {
        foreach ($this->rangeErrors($start, $end) as $error) {
            throw new \DomainException($error);
        }

        return DB::transaction(function () use ($user, $vehicle, $start, $end, $address, $purpose) {
            // Kunci baris kendaraan — check-point race-condition
            Vehicle::whereKey($vehicle->id)->lockForUpdate()->first();

            // Satu peminjaman aktif per user (pegawai maupun pengurus)
            $masihAktif = Booking::where('user_id', $user->id)
                ->whereIn('status', [Booking::STATUS_DIPINJAM, Booking::STATUS_MENUNGGU_PENGGANTIAN])
                ->lockForUpdate()
                ->exists();

            if ($masihAktif) {
                throw new \DomainException('Anda masih memiliki peminjaman aktif — selesaikan dahulu sebelum meminjam lagi.');
            }

            // Kuota bidang
            $bidang = $user->seksi?->bidang;

            if (! $bidang) {
                throw new \DomainException('Akun Anda tidak tercatat pada seksi/bidang manapun — hubungi admin.');
            }

            $used = $this->quotaUsed($user);

            if ($used >= $bidang->max_active_bookings) {
                throw new \DomainException("Kuota peminjaman {$bidang->name} sudah penuh (maks {$bidang->max_active_bookings} mobil bersamaan).");
            }

            // Ketersediaan ulang di dalam transaksi
            $startC = Carbon::parse($start);
            $endC = Carbon::parse($end);

            if (! $this->availability->isAvailable($vehicle, $startC, $endC)) {
                throw new \DomainException("Mobil {$vehicle->name} tidak lagi tersedia pada rentang tanggal tersebut.");
            }

            return Booking::create([
                'user_id' => $user->id,
                'vehicle_id' => $vehicle->id,
                // Hari penuh 00:00–24:00 — booking hari-H tetap tercatat
                // mulai 00:00 hari itu, bukan jam submit
                'start_date' => $startC->toDateString(),
                'end_date' => $endC->toDateString(),
                'address' => $address,
                'purpose' => $purpose,
                'status' => Booking::STATUS_DIPINJAM,
            ]);
        });
    }
}
