<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Seksi;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Revisi UI riwayat peminjaman (UAT user, pasca-UAT 03):
 * (1) peminjaman AKTIF selalu paling atas — studi kasus: booking
 *     22–24 Sep yang dibatalkan tidak menindih booking aktif 14–15 Sep;
 * (2) pagination: tombol Sebelumnya hilang di halaman 1, Berikutnya
 *     hilang di halaman terakhir (bukan disabled).
 */
class RiwayatUrutanDanPaginationTest extends TestCase
{
    use RefreshDatabase;

    private User $pegawai;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pegawai = User::factory()->create(['role' => 'pegawai', 'seksi_id' => Seksi::factory()->create()->id]);
    }

    /**
     * Studi kasus user persis: booking MENDATANG (dibatalkan) tanggal
     * besar, booking AKTIF tanggal lebih kecil → AKTIF harus di atas.
     */
    public function test_peminjaman_aktif_selalu_paling_atas(): void
    {
        $aktif = Vehicle::factory()->create(['name' => 'Mobil Aktif Utama']);
        $batal = Vehicle::factory()->create(['name' => 'Mobil Batal Nanti']);

        // Aktif 14–15 Sep (relatif: H+2..H+3)
        Booking::create([
            'user_id' => $this->pegawai->id,
            'vehicle_id' => $aktif->id,
            'start_date' => today()->addDays(2)->toDateString(),
            'end_date' => today()->addDays(3)->toDateString(),
            'address' => 'Kantor A', 'purpose' => 'Aktif duluan',
        ]);

        // Dibatalkan 22–24 Sep (H+10..H+12) — tanggal LEBIH BESAR
        Booking::create([
            'user_id' => $this->pegawai->id,
            'vehicle_id' => $batal->id,
            'start_date' => today()->addDays(10)->toDateString(),
            'end_date' => today()->addDays(12)->toDateString(),
            'address' => 'Kantor B', 'purpose' => 'Batal terakhir',
            'status' => 'dibatalkan',
        ]);

        $html = $this->actingAs($this->pegawai)
            ->get('/pegawai/bookings')
            ->assertOk()
            ->getContent();

        $posAktif = strpos($html, 'Mobil Aktif Utama');
        $posBatal = strpos($html, 'Mobil Batal Nanti');

        $this->assertNotFalse($posAktif);
        $this->assertNotFalse($posBatal);
        $this->assertLessThan($posBatal, $posAktif, 'Peminjaman aktif harus tampil di atas yang dibatalkan.');
    }

    /** menunggu_penggantian juga kelompok "aktif" (masih berjalan). */
    public function test_menunggu_penggantian_juga_di_atas_riwayat(): void
    {
        $a = Vehicle::factory()->create(['name' => 'Unit Menunggu Ganti']);
        $b = Vehicle::factory()->create(['name' => 'Unit Riwayat Lama']);

        Booking::create([
            'user_id' => $this->pegawai->id, 'vehicle_id' => $a->id,
            'start_date' => today()->toDateString(), 'end_date' => today()->toDateString(),
            'address' => 'A', 'purpose' => 'A',
            'status' => Booking::STATUS_MENUNGGU_PENGGANTIAN,
        ]);
        Booking::create([
            'user_id' => $this->pegawai->id, 'vehicle_id' => $b->id,
            'start_date' => today()->addDays(5)->toDateString(), 'end_date' => today()->addDays(6)->toDateString(),
            'address' => 'B', 'purpose' => 'B',
            'status' => Booking::STATUS_DIKEMBALIKAN, 'returned_at' => now(),
        ]);

        $html = $this->actingAs($this->pegawai)->get('/pegawai/bookings')->assertOk()->getContent();
        $this->assertLessThan(
            strpos($html, 'Unit Riwayat Lama'),
            strpos($html, 'Unit Menunggu Ganti'),
        );
    }

    /** Dashboard "Riwayat Terakhir" ikut memprioritaskan aktif. */
    public function test_dashboard_riwayat_singkat_ikut_urutan(): void
    {
        $a = Vehicle::factory()->create(['name' => 'Dash Aktif']);
        $b = Vehicle::factory()->create(['name' => 'Dash Riwayat']);

        Booking::create([
            'user_id' => $this->pegawai->id, 'vehicle_id' => $a->id,
            'start_date' => today()->toDateString(), 'end_date' => today()->toDateString(),
            'address' => 'A', 'purpose' => 'A',
        ]);
        Booking::create([
            'user_id' => $this->pegawai->id, 'vehicle_id' => $b->id,
            'start_date' => today()->addDays(9)->toDateString(), 'end_date' => today()->addDays(10)->toDateString(),
            'address' => 'B', 'purpose' => 'B', 'status' => 'dibatalkan',
        ]);

        $html = $this->actingAs($this->pegawai)->get('/dashboard')->assertOk()->getContent();
        $this->assertLessThan(strpos($html, 'Dash Riwayat'), strpos($html, 'Dash Aktif'));
    }

    /**
     * Pagination: halaman 1 TANPA tombol Sebelumnya; halaman terakhir
     * TANPA tombol Berikutnya (hilang, bukan disabled).
     */
    public function test_pagination_tombol_hilang_di_ujung(): void
    {
        $v = Vehicle::factory()->create();

        for ($i = 1; $i <= 12; $i++) { // 2 halaman (10/halaman)
            Booking::create([
                'user_id' => $this->pegawai->id, 'vehicle_id' => $v->id,
                'start_date' => today()->addDays($i)->toDateString(),
                'end_date' => today()->addDays($i)->toDateString(),
                'address' => "Alamat $i", 'purpose' => "Uji $i",
                'status' => 'dikembalikan', 'returned_at' => now(),
            ]);
        }

        // Halaman 1: ada Berikutnya, TIDAK ada Sebelumnya
        $hal1 = $this->actingAs($this->pegawai)->get('/pegawai/bookings')->assertOk()->getContent();
        $this->assertStringContainsString('Berikutnya', $hal1);
        $this->assertStringNotContainsString('Sebelumnya', $hal1);

        // Halaman 2 (terakhir): ada Sebelumnya, TIDAK ada Berikutnya
        $hal2 = $this->actingAs($this->pegawai)->get('/pegawai/bookings?page=2')->assertOk()->getContent();
        $this->assertStringContainsString('Sebelumnya', $hal2);
        $this->assertStringNotContainsString('Berikutnya', $hal2);
    }
}
