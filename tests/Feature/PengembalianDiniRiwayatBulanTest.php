<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Seksi;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\AvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * D10 (klarifikasi user): pengembalian DINI — booking 3 hari, selesai
 * setelah 2 hari → status Dikembalikan + jam aktual; mobil LANGSUNG
 * tersedia untuk sisa hari (hari ke-3) bagi pegawai lain.
 *
 * F7 (revisi user): riwayat peminjaman pegawai dibatasi 1 bulan
 * terakhir secara default — data lebih lama via filter bulan.
 */
class PengembalianDiniRiwayatBulanTest extends TestCase
{
    use RefreshDatabase;

    private User $pegawai;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pegawai = User::factory()->create(['role' => 'pegawai', 'seksi_id' => Seksi::factory()->create()->id]);
    }

    /** D10: selesai di tengah rentang — mobil langsung bebas untuk sisa hari. */
    public function test_d10_pengembalian_dini_mobil_langsung_tersedia_sisa_hari(): void
    {
        $v = Vehicle::factory()->create(['name' => 'Unit Dini']);

        // Booking 3 hari: kemarin..besok
        $booking = Booking::create([
            'user_id' => $this->pegawai->id,
            'vehicle_id' => $v->id,
            'start_date' => today()->subDay()->toDateString(),
            'end_date' => today()->addDay()->toDateString(),
            'address' => 'Kantor B', 'purpose' => 'Rapat 3 hari',
        ]);

        // Selesai setelah hari ke-2 (hari ini masih tengah rentang)
        $this->actingAs($this->pegawai)
            ->post("/pegawai/returns/{$booking->id}", ['complaint' => ''])
            ->assertSessionHasNoErrors();

        $booking->refresh();
        $this->assertSame('dikembalikan', $booking->status);
        $this->assertNotNull($booking->returned_at);

        // Riwayat menampilkan rentang ASLI (kemarin..besok) + status Dikembalikan
        $this->actingAs($this->pegawai)
            ->get('/pegawai/bookings')
            ->assertOk()
            ->assertSee('Unit Dini')
            ->assertSee('Dikembalikan', false);

        // ★ INTI: mobil LANGSUNG tersedia untuk HARI TERAKHIR (besok)
        // bagi pegawai lain — tidak menunggu end_date asli
        $this->assertTrue(app(AvailabilityService::class)
            ->isAvailable($v, Carbon::parse(today()), Carbon::parse(today()->addDay())));

        // Pegawai lain bisa langsung booking hari terakhir itu
        $lain = User::factory()->create(['role' => 'pegawai', 'seksi_id' => Seksi::factory()->create()->id]);
        $baru = app(\App\Services\BookingService::class)->create(
            $lain, $v,
            today()->toDateString(), today()->toDateString(),
            'Kantor C', 'Ambil sisa hari',
        );
        $this->assertSame('dipinjam', $baru->status);
    }

    /** F7: riwayat default hanya bulan berjalan; bulan lama via filter. */
    public function test_f7_riwayat_default_bulan_berjalan_dan_filter_bulan(): void
    {
        $v = Vehicle::factory()->create();

        // Booking bulan INI (kemarin) — tampil default
        Booking::create([
            'user_id' => $this->pegawai->id, 'vehicle_id' => $v->id,
            'start_date' => today()->toDateString(), 'end_date' => today()->toDateString(),
            'address' => 'Bulan Ini', 'purpose' => 'X',
            'status' => 'dikembalikan', 'returned_at' => now(),
        ]);

        // Booking 2 bulan lalu — TIDAK tampil default, tampil via filter
        Booking::create([
            'user_id' => $this->pegawai->id, 'vehicle_id' => $v->id,
            'start_date' => today()->subMonths(2)->toDateString(),
            'end_date' => today()->subMonths(2)->toDateString(),
            'address' => 'Dua Bulan Lalu', 'purpose' => 'Y',
            'status' => 'dikembalikan', 'returned_at' => now(),
        ]);

        // Default: hanya bulan berjalan
        $default = $this->actingAs($this->pegawai)->get('/pegawai/bookings')->assertOk()->getContent();
        $this->assertStringContainsString('Bulan Ini', $default);
        $this->assertStringNotContainsString('Dua Bulan Lalu', $default);
        $this->assertStringContainsString('Menampilkan riwayat', $default); // info filter aktif

        // Filter bulan 2 bulan lalu → tampil
        $bulanLalu = today()->subMonths(2)->format('Y-m');
        $filtered = $this->actingAs($this->pegawai)
            ->get("/pegawai/bookings?bulan={$bulanLalu}")
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('Dua Bulan Lalu', $filtered);
        $this->assertStringNotContainsString('Bulan Ini', $filtered);
    }

    /** F7: booking AKTIF dari bulan lalu TETAP tampil di default (di atas). */
    public function test_f7_booking_aktif_bulan_lalu_tetap_tampil(): void
    {
        $v = Vehicle::factory()->create();

        // Booking aktif yang dimulai bulan lalu tapi belum selesai
        Booking::create([
            'user_id' => $this->pegawai->id, 'vehicle_id' => $v->id,
            'start_date' => today()->subDays(40)->toDateString(),
            'end_date' => today()->addDay()->toDateString(),
            'address' => 'Aktif Lintas Bulan', 'purpose' => 'Z',
        ]);

        $html = $this->actingAs($this->pegawai)->get('/pegawai/bookings')->assertOk()->getContent();
        $this->assertStringContainsString('Aktif Lintas Bulan', $html,
            'Booking AKTIF harus selalu tampil di default meski mulai bulan lalu.');
    }
}
