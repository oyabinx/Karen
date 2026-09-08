<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Seksi;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\BookingService;
use App\Services\ReplacementService;
use App\Services\ReturnService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Skema baru (usulan user pasca-UAT 03): tombol adaptif pada
 * peminjaman — "Selesai — Kembalikan Mobil" bila hari ini >= tanggal
 * mulai; "Batalkan Peminjaman" bila hari ini < tanggal mulai, dengan
 * status DIBATALKAN + waktu pembatalan (bukan dikembalikan).
 */
class BatalkanSebelumMulaiTest extends TestCase
{
    use RefreshDatabase;

    private User $pegawai;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pegawai = User::factory()->create(['role' => 'pegawai', 'seksi_id' => Seksi::factory()->create()->id]);
    }

    private function bookingMendatang(): array
    {
        $v = Vehicle::factory()->create(['name' => 'Unit Mendatang']);

        $b = Booking::create([
            'user_id' => $this->pegawai->id,
            'vehicle_id' => $v->id,
            'start_date' => today()->addDays(7)->toDateString(),  // H+7..H+8
            'end_date' => today()->addDays(8)->toDateString(),
            'address' => 'Kantor B', 'purpose' => 'Rapat',
        ]);

        return [$b, $v];
    }

    /** Studi kasus user: booking 22–24 Sep (belum mulai) → tombol BATALKAN, bukan Selesai. */
    public function test_tombol_adaptif_berdasarkan_tanggal_mulai(): void
    {
        [$b] = $this->bookingMendatang();

        $html = $this->actingAs($this->pegawai)
            ->get('/pegawai/bookings')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Batalkan Peminjaman', $html);
        $this->assertStringNotContainsString('Selesai — Kembalikan Mobil', $html);

        // Booking HARI INI → tombol Selesai (bukan Batalkan)
        $v2 = Vehicle::factory()->create();
        Booking::create([
            'user_id' => $this->pegawai === null ? 0 : $this->pegawai->id,
            'vehicle_id' => $v2->id,
            'start_date' => today()->toDateString(),
            'end_date' => today()->toDateString(),
            'address' => 'A', 'purpose' => 'B',
        ]);

        $html2 = $this->actingAs($this->pegawai)->get('/pegawai/bookings')->assertOk()->getContent();
        $this->assertStringContainsString('Selesai — Kembalikan Mobil', $html2);
    }

    /** Pembatalan mandiri: status DIBATALKAN + waktu, kuota lepas, mobil bebas. */
    public function test_pembatalan_mandiri_tercatat_dibatalkan_dan_waktu(): void
    {
        [$b, $v] = $this->bookingMendatang();

        $this->actingAs($this->pegawai)
            ->post("/pegawai/returns/{$b->id}/cancel")
            ->assertSessionHasNoErrors();

        $b->refresh();
        $this->assertSame('dibatalkan', $b->status);
        $this->assertNotNull($b->cancelled_at);
        $this->assertNull($b->returned_at, 'Pembatalan TIDAK boleh mengisi returned_at');

        // Kuota lepas — bisa booking lagi
        $baru = app(BookingService::class)->create(
            $this->pegawai, Vehicle::factory()->create(),
            today()->addDays(10)->toDateString(), today()->addDays(11)->toDateString(),
            'Kantor C', 'Dinas',
        );
        $this->assertSame('dipinjam', $baru->status);

        // Mobil lama langsung tersedia pada rentang lama
        $this->assertTrue(app(\App\Services\AvailabilityService::class)
            ->isAvailable($v, \Illuminate\Support\Carbon::parse(today()->addDays(7)), \Illuminate\Support\Carbon::parse(today()->addDays(8))));
    }

    /** Riwayat menampilkan "Dibatalkan {tanggal}". */
    public function test_riwayat_menampilkan_chip_dibatalkan_waktu(): void
    {
        [$b] = $this->bookingMendatang();

        $this->actingAs($this->pegawai)->post("/pegawai/returns/{$b->id}/cancel");

        $this->actingAs($this->pegawai)
            ->get('/pegawai/bookings')
            ->assertOk()
            ->assertSee('Dibatalkan', false)
            ->assertSee($b->refresh()->cancelled_at->translatedFormat('d M Y'));
    }

    /** Guard silang: Selesai ditolak untuk yang belum mulai; Batalkan ditolak untuk yang sudah mulai. */
    public function test_guard_jalur_selesai_dan_batal_tidak_bisa_disalahgunakan(): void
    {
        [$b] = $this->bookingMendatang();

        // Selesai pada booking belum mulai → ditolak
        $this->actingAs($this->pegawai)
            ->post("/pegawai/returns/{$b->id}", ['complaint' => ''])
            ->assertSessionHas('error');
        $this->assertSame('dipinjam', $b->refresh()->status);

        // Batalkan booking HARI INI → ditolak
        $v2 = Vehicle::factory()->create();
        $hariIni = Booking::create([
            'user_id' => $this->pegawai->id, 'vehicle_id' => $v2->id,
            'start_date' => today()->toDateString(), 'end_date' => today()->toDateString(),
            'address' => 'A', 'purpose' => 'B',
        ]);

        $this->actingAs($this->pegawai)
            ->post("/pegawai/returns/{$hariIni->id}/cancel")
            ->assertSessionHas('error');
        $this->assertSame('dipinjam', $hariIni->refresh()->status);
    }

    /** Booking orang lain tidak bisa dibatalkan (403). */
    public function test_batal_booking_orang_lain_403(): void
    {
        [$b] = $this->bookingMendatang();
        $lain = User::factory()->create(['role' => 'pegawai', 'seksi_id' => Seksi::factory()->create()->id]);

        $this->actingAs($lain)
            ->post("/pegawai/returns/{$b->id}/cancel")
            ->assertForbidden();
    }

    /** Pembatalan oleh pengurus (tanpa pengganti) juga mencatat waktu. */
    public function test_pembatalan_pengurus_dan_scheduler_mencatat_waktu(): void
    {
        // Pengurus membatalkan booking menunggu pengganti
        $v = Vehicle::factory()->create();
        $b = Booking::create([
            'user_id' => $this->pegawai->id, 'vehicle_id' => $v->id,
            'start_date' => today()->toDateString(), 'end_date' => today()->toDateString(),
            'address' => 'A', 'purpose' => 'B',
            'status' => Booking::STATUS_MENUNGGU_PENGGANTIAN,
        ]);

        app(ReplacementService::class)->cancel($b);
        $this->assertNotNull($b->refresh()->cancelled_at);

        // Scheduler membatalkan menunggu_penggantian lewat tempo
        $b2 = Booking::create([
            'user_id' => $this->pegawai->id, 'vehicle_id' => $v->id,
            'start_date' => today()->subDays(4)->toDateString(), 'end_date' => today()->subDays(2)->toDateString(),
            'address' => 'A', 'purpose' => 'B',
            'status' => Booking::STATUS_MENUNGGU_PENGGANTIAN,
        ]);
        app(ReturnService::class)->autoReturn();

        $this->assertSame('dibatalkan', $b2->refresh()->status);
        $this->assertNotNull($b2->cancelled_at);
    }

    /** Booking menunggu_penggantian: tidak ada tombol Selesai/Batalkan (menunggu pengurus). */
    public function test_menunggu_penggantian_tanpa_tombol_aksi(): void
    {
        $v = Vehicle::factory()->create();
        Booking::create([
            'user_id' => $this->pegawai->id, 'vehicle_id' => $v->id,
            'start_date' => today()->toDateString(), 'end_date' => today()->toDateString(),
            'address' => 'A', 'purpose' => 'B',
            'status' => Booking::STATUS_MENUNGGU_PENGGANTIAN,
        ]);

        $html = $this->actingAs($this->pegawai)->get('/pegawai/bookings')->assertOk()->getContent();
        $this->assertStringNotContainsString('Selesai — Kembalikan Mobil', $html);
        $this->assertStringNotContainsString('>Batalkan Peminjaman</button>', $html);
    }
}
