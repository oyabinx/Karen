<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Maintenance;
use App\Models\Seksi;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\BookingService;
use App\Services\ReplacementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Opsi A (kesepakatan user): kuota & guard "1 booking" berbasis
 * OVERLAP TANGGAL / mobil unik bersamaan — bukan jumlah record.
 *
 * S3: 3 booking split berurutan = 1 slot kuota
 * S4: pegawai dengan split aktif BISA booking non-overlap
 * S5: pegawai dengan split aktif DITOLAK booking overlap
 * S6: 2 anggota dengan split → tetap maks N mobil bersamaan
 */
class KuotaOverlapTanggalTest extends TestCase
{
    use RefreshDatabase;

    private User $pegawai;

    private User $anggotaLain;

    protected function setUp(): void
    {
        parent::setUp();

        $seksi = Seksi::factory()->create();
        $this->pegawai = User::factory()->create(['role' => 'pegawai', 'seksi_id' => $seksi->id]);
        $this->anggotaLain = User::factory()->create(['role' => 'pegawai', 'seksi_id' => $seksi->id]);
    }

    private function d(int $hari): string
    {
        return today()->addDays($hari)->toDateString();
    }

    /**
     * S3: Split 3 booking berurutan (9 Sep, 10 Sep, 11 Sep) pada
     * mobil yang berbeda = hanya 1 slot kuota pada satu titik waktu.
     */
    public function test_s3_split_tiga_booking_satu_slot_kuota(): void
    {
        $a = Vehicle::factory()->create();
        $b = Vehicle::factory()->create();

        // Booking asli 9–11 Sep (3 hari)
        $booking = Booking::create([
            'user_id' => $this->pegawai->id,
            'vehicle_id' => $a->id,
            'start_date' => $this->d(9), 'end_date' => $this->d(11),
            'address' => 'Kantor', 'purpose' => 'Rapat 3 hari',
        ]);

        // Maintenance 10 Sep → split jadi 3 booking
        Maintenance::create(['vehicle_id' => $a->id, 'start_date' => $this->d(10), 'end_date' => $this->d(10)]);
        $booking->update(['status' => Booking::STATUS_MENUNGGU_PENGGANTIAN]);

        app(ReplacementService::class)->assignPartial(
            $booking,
            \Illuminate\Support\Carbon::parse($this->d(10)),
            \Illuminate\Support\Carbon::parse($this->d(10)),
            $b,
        );

        // 3 booking eksis
        $this->assertSame(3, Booking::where('user_id', $this->pegawai->id)
            ->whereIn('status', ['dipinjam'])->count());

        // ★ Kuota terpakai = 1 (bukan 3) — hanya 1 mobil bersamaan per hari
        $quota = app(BookingService::class)->quotaInfo($this->pegawai);
        $this->assertSame(1, $quota['used'], "3 booking split berurutan harus dihitung 1 slot kuota, bukan {$quota['used']}");
    }

    /**
     * S4: Pegawai dengan 3 booking split AKTIF hari ini
     * → bisa booking untuk minggu depan (non-overlap).
     */
    public function test_s4_split_aktif_bisa_booking_non_overlap(): void
    {
        $a = Vehicle::factory()->create();
        $b = Vehicle::factory()->create();
        $c = Vehicle::factory()->create();

        // Split booking hari ini (kemarin–besar)
        Booking::create([
            'user_id' => $this->pegawai->id, 'vehicle_id' => $a->id,
            'start_date' => today()->subDay()->toDateString(),
            'end_date' => today()->toDateString(),
            'address' => 'A', 'purpose' => 'X',
        ]);
        Booking::create([
            'user_id' => $this->pegawai->id, 'vehicle_id' => $b->id,
            'start_date' => today()->toDateString(),
            'end_date' => today()->toDateString(),
            'address' => 'A', 'purpose' => 'X',
        ]);

        // ★ Booking minggu depan (non-overlap) → HARUS DITERIMA
        $baru = app(BookingService::class)->create(
            $this->pegawai, $c,
            $this->d(7), $this->d(8),
            'Kantor C', 'Rapat minggu depan',
        );
        $this->assertSame('dipinjam', $baru->status);
    }

    /**
     * S5: Pegawai dengan split aktif hari ini
     * → DITOLAK booking yang OVERLAP dengan hari ini.
     */
    public function test_s5_split_aktif_ditolak_booking_overlap(): void
    {
        $a = Vehicle::factory()->create();
        $b = Vehicle::factory()->create();

        // Split aktif hari ini
        Booking::create([
            'user_id' => $this->pegawai->id, 'vehicle_id' => $a->id,
            'start_date' => today()->toDateString(),
            'end_date' => today()->toDateString(),
            'address' => 'A', 'purpose' => 'X',
        ]);

        // Booking yang OVERLAP hari ini → ditolak
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('overlap');

        app(BookingService::class)->create(
            $this->pegawai, $b,
            today()->toDateString(), today()->toDateString(),
            'Kantor', 'Tabrakan',
        );
    }

    /**
     * S6: 2 anggota dengan split → tetap maks N mobil bersamaan.
     * Bidang kuota 2: anggota A punya split (3 booking, 1 mobil/hari),
     * anggota B punya 1 booking → total 2 mobil/hari = penuh.
     * Anggota ketiga DITOLAK.
     */
    public function test_s6_dua_anggota_split_maks_n_bersamaan(): void
    {
        $a = Vehicle::factory()->create();
        $b = Vehicle::factory()->create();
        $c = Vehicle::factory()->create();
        $d = Vehicle::factory()->create();

        // Anggota A: split 3 booking (9–11 Sep) — 1 mobil/hari
        Booking::create([
            'user_id' => $this->pegawai->id, 'vehicle_id' => $a->id,
            'start_date' => $this->d(9), 'end_date' => $this->d(9),
            'address' => 'A', 'purpose' => 'X',
        ]);
        Booking::create([
            'user_id' => $this->pegawai->id, 'vehicle_id' => $b->id,
            'start_date' => $this->d(10), 'end_date' => $this->d(10),
            'address' => 'A', 'purpose' => 'X',
        ]);
        Booking::create([
            'user_id' => $this->pegawai->id, 'vehicle_id' => $a->id,
            'start_date' => $this->d(11), 'end_date' => $this->d(11),
            'address' => 'A', 'purpose' => 'X',
        ]);

        // Anggota B: 1 booking 9–11 Sep
        Booking::create([
            'user_id' => $this->anggotaLain->id, 'vehicle_id' => $c->id,
            'start_date' => $this->d(9), 'end_date' => $this->d(11),
            'address' => 'B', 'purpose' => 'Y',
        ]);

        // Kuota (2) penuh: setiap hari ada 2 mobil dipakai
        $quota = app(BookingService::class)->quotaInfo($this->pegawai);
        $this->assertSame(2, $quota['used']);

        // Anggota ketiga → DITOLAK
        $ketiga = User::factory()->create(['role' => 'pegawai', 'seksi_id' => $this->pegawai->seksi_id]);
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('penuh');

        app(BookingService::class)->create(
            $ketiga, $d,
            $this->d(10), $this->d(10),
            'Kantor', 'Ketiga',
        );
    }

    /**
     * Kontrol: kuota lama tetap bekerja — 2 anggota biasa (tanpa split)
     * = 2 slot → ketiga ditolak; salah satu selesai → ketiga bisa.
     */
    public function test_kontrol_kuota_dua_anggota_biasa(): void
    {
        $a = Vehicle::factory()->create();
        $b = Vehicle::factory()->create();
        $c = Vehicle::factory()->create();

        Booking::create([
            'user_id' => $this->pegawai->id, 'vehicle_id' => $a->id,
            'start_date' => $this->d(5), 'end_date' => $this->d(6),
            'address' => 'A', 'purpose' => 'X',
        ]);
        Booking::create([
            'user_id' => $this->anggotaLain->id, 'vehicle_id' => $b->id,
            'start_date' => $this->d(5), 'end_date' => $this->d(6),
            'address' => 'B', 'purpose' => 'Y',
        ]);

        // Kuota 2 → penuh
        $this->assertSame(2, app(BookingService::class)->quotaUsed($this->pegawai));

        // Anggota ketiga ditolak
        $ketiga = User::factory()->create(['role' => 'pegawai', 'seksi_id' => $this->pegawai->seksi_id]);
        try {
            app(BookingService::class)->create($ketiga, $c, $this->d(5), $this->d(5), 'C', 'Z');
            $this->fail('Seharusnya ditolak kuota penuh');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('penuh', $e->getMessage());
        }

        // Salah satu selesai → ketiga bisa
        Booking::where('user_id', $this->anggotaLain->id)->first()
            ->update(['status' => 'dikembalikan', 'returned_at' => now()]);

        $baru = app(BookingService::class)->create($ketiga, $c, $this->d(5), $this->d(5), 'C', 'Z');
        $this->assertSame('dipinjam', $baru->status);
    }
}
