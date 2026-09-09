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
     * Kuota terpakai = MAKSIMUM jumlah mobil BERBEDA yang dipakai
     * BERSAMAAN oleh anggota bidang pada satu tanggal (Opsi A —
     * kesepakatan user).
     *
     * Booking hasil split parsial (2–3 record berurutan) pada saat
     * tertentu hanya memakai 1 mobil → dihitung 1 slot, bukan 3.
     *
     * Implementasi: untuk setiap tanggal yang disentuh booking aktif
     * bidang, hitung DISTINCT vehicle_id; ambil nilai MAXIMUM.
     */
    public function quotaUsed(User $user): int
    {
        $bidangId = $user->seksi?->bidang_id;

        if (! $bidangId) {
            return PHP_INT_MAX; // tanpa seksi = tak ada kuota yang bisa dipakai
        }

        // Ambil semua booking aktif bidang: [start, end, vehicle_id]
        $bookings = Booking::query()
            ->whereIn('status', [Booking::STATUS_DIPINJAM, Booking::STATUS_MENUNGGU_PENGGANTIAN])
            ->whereHas('user.seksi.bidang', fn ($q) => $q->where('id', $bidangId))
            ->get(['start_date', 'end_date', 'vehicle_id']);

        if ($bookings->isEmpty()) {
            return 0;
        }

        // Kumpulkan semua tanggal yang disentuh
        $semuaTanggal = $bookings->flatMap(function ($b) {
            $dates = [];
            $cursor = $b->start_date->copy()->startOfDay();
            $end = $b->end_date->copy()->startOfDay();

            while ($cursor->lte($end)) {
                $dates[] = $cursor->format('Y-m-d');
                $cursor->addDay();
            }

            return $dates;
        })->unique()->values();

        // Hitung DISTINCT vehicle per tanggal; ambil maksimum
        $maks = 0;

        foreach ($semuaTanggal as $tanggal) {
            $mobilHariItu = $bookings->filter(function ($b) use ($tanggal) {
                return $b->start_date->format('Y-m-d') <= $tanggal
                    && $b->end_date->format('Y-m-d') >= $tanggal;
            })->pluck('vehicle_id')->unique()->count();

            $maks = max($maks, $mobilHariItu);
        }

        return $maks;
    }

    /**
     * Cek apakah MENAMBAH booking baru pada rentang [start, end] akan
     * membuat penggunaan mobil bersamaan bidang melebihi kuota.
     * (Opsi A — kuota per tanggal, bukan global.)
     */
    public function wouldExceedQuota(User $user, Carbon $start, Carbon $end): bool
    {
        $bidang = $user->seksi?->bidang;

        if (! $bidang) {
            return true;
        }

        // Booking aktif bidang yang MENYENTUH rentang baru
        $bookings = Booking::query()
            ->whereIn('status', [Booking::STATUS_DIPINJAM, Booking::STATUS_MENUNGGU_PENGGANTIAN])
            ->whereHas('user.seksi.bidang', fn ($q) => $q->where('id', $bidang->id))
            ->whereDate('start_date', '<=', $end->endOfDay())
            ->whereDate('end_date', '>=', $start->startOfDay())
            ->get(['start_date', 'end_date', 'vehicle_id']);

        // Untuk setiap tanggal dalam rentang baru, hitung mobil bersamaan
        $cursor = $start->copy()->startOfDay();
        $akhir = $end->copy()->startOfDay();

        while ($cursor->lte($akhir)) {
            $tgl = $cursor->format('Y-m-d');

            $mobilHariItu = $bookings->filter(fn ($b) =>
                $b->start_date->format('Y-m-d') <= $tgl
                && $b->end_date->format('Y-m-d') >= $tgl
            )->pluck('vehicle_id')->unique()->count();

            // +1 untuk booking baru yang akan ditambahkan
            if ($mobilHariItu + 1 > $bidang->max_active_bookings) {
                return true;
            }

            $cursor->addDay();
        }

        return false;
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

            $startC = Carbon::parse($start);
            $endC = Carbon::parse($end);

            // Guard "1 peminjaman aktif" — OVERLAP TANGGAL, bukan jumlah
            // record (Opsi A): booking hasil split yang TIDAK overlap
            // dengan rentang baru tidak menghalangi (kesepakatan user)
            $overlapAktif = Booking::where('user_id', $user->id)
                ->whereIn('status', [Booking::STATUS_DIPINJAM, Booking::STATUS_MENUNGGU_PENGGANTIAN])
                ->whereDate('start_date', '<=', $endC)
                ->whereDate('end_date', '>=', $startC)
                ->lockForUpdate()
                ->exists();

            if ($overlapAktif) {
                throw new \DomainException('Anda masih memiliki peminjaman yang overlap dengan tanggal ini — selesaikan dahulu atau pilih tanggal lain.');
            }

            // Kuota bidang
            $bidang = $user->seksi?->bidang;

            if (! $bidang) {
                throw new \DomainException('Akun Anda tidak tercatat pada seksi/bidang manapun — hubungi admin.');
            }

            // Kuota bidang — PER TANGGAL rentang booking baru (Opsi A):
            // booking split hari ini tidak menghalangi booking minggu depan
            if ($this->wouldExceedQuota($user, $startC, $endC)) {
                $bidangNama = $user->seksi?->bidang?->name ?? 'bidang';
                $kuota = $user->seksi?->bidang?->max_active_bookings ?? 0;
                throw new \DomainException("Kuota peminjaman {$bidangNama} sudah penuh pada tanggal tersebut (maks {$kuota} mobil bersamaan).");
            }

            // Ketersediaan ulang di dalam transaksi
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
