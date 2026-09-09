<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Maintenance;
use App\Models\Seksi;
use App\Models\User;
use App\Models\Vehicle;
use App\Support\AppSettings;
use App\Services\BookingService;
use App\Services\ReplacementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tindak lanjut UAT 02 & 03 ulangan:
 * A: Parsial tepi + TENGAH (3 booking) + UX direkomendasikan
 * B: Test koneksi warna merah + teks jelas
 * C: Pesan error maintenance banner
 * D: Durasi maksimal peminjaman dapat diatur admin
 */
class Uat0203TindakLanjutTest extends TestCase
{
    use RefreshDatabase;

    private User $pengurus;

    private User $pegawai;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pengurus = User::factory()->create(['role' => 'pengurus']);
        $this->pegawai = User::factory()->create(['role' => 'pegawai', 'seksi_id' => Seksi::factory()->create()->id]);
    }

    private function d(int $hari): string
    {
        return today()->addDays($hari)->toDateString();
    }

    // ═══════════════════════════════════════════════════════════════
    // A: PARSIAL TENGAH (3 booking) — UAT 03 B7b/d + D3b
    // ═══════════════════════════════════════════════════════════════

    /** Studi kasus user B7b: booking 9–11 Sep, maintenance 11–13 Sep → tepi-akhir 2 booking. */
    public function test_a_parsial_tepi_akhir_dua_booking(): void
    {
        $a = Vehicle::factory()->create(['name' => 'AB 1608 UH']);
        $b = Vehicle::factory()->create(['name' => 'AB 1609 UH']);

        $booking = Booking::create([
            'user_id' => $this->pegawai->id, 'vehicle_id' => $a->id,
            'start_date' => $this->d(9), 'end_date' => $this->d(11),
            'address' => 'Kantor', 'purpose' => 'Rapat',
        ]);
        Maintenance::create(['vehicle_id' => $a->id, 'start_date' => $this->d(11), 'end_date' => $this->d(13)]);
        $booking->update(['status' => Booking::STATUS_MENUNGGU_PENGGANTIAN]);

        $result = app(ReplacementService::class)->assignPartial(
            $booking,
            \Illuminate\Support\Carbon::parse($this->d(11)),
            \Illuminate\Support\Carbon::parse($this->d(11)),
            $b,
        );

        $this->assertCount(2, $result['segments']);
        [$s1, $s2] = $result['segments'];

        // Segmen 1: 9–10 Sep tetap AB 1608 UH
        $this->assertSame($this->d(9), $s1->start_date->toDateString());
        $this->assertSame($this->d(10), $s1->end_date->toDateString());
        $this->assertSame($a->id, $s1->vehicle_id);

        // Segmen 2: 11 Sep pakai AB 1609 UH
        $this->assertSame($this->d(11), $s2->start_date->toDateString());
        $this->assertSame($b->id, $s2->vehicle_id);
        $this->assertSame($a->id, $s2->original_vehicle_id);

        // AB 1608 UH TIDAK tersedia 9–10 Sep (milik s1) tapi tersedia 12 Sep
        $avail = app(\App\Services\AvailabilityService::class);
        $this->assertFalse($avail->isAvailable($a, \Illuminate\Support\Carbon::parse($this->d(9)), \Illuminate\Support\Carbon::parse($this->d(10))));
    }

    /** B7d: tabrakan TENGAH — booking 9–11 Sep, maintenance 10 Sep → 3 booking. */
    public function test_a_parsial_tengah_tiga_booking(): void
    {
        $a = Vehicle::factory()->create(['name' => 'Mobil Tengah']);
        $b = Vehicle::factory()->create(['name' => 'Mobil Pengganti']);

        $booking = Booking::create([
            'user_id' => $this->pegawai->id, 'vehicle_id' => $a->id,
            'start_date' => $this->d(9), 'end_date' => $this->d(11),
            'address' => 'Kantor', 'purpose' => 'Rapat',
        ]);
        Maintenance::create(['vehicle_id' => $a->id, 'start_date' => $this->d(10), 'end_date' => $this->d(10)]);
        $booking->update(['status' => Booking::STATUS_MENUNGGU_PENGGANTIAN]);

        // Parsial kini tersedia untuk tengah
        $partial = app(ReplacementService::class)->partialRange($booking);
        $this->assertNotNull($partial, 'Parsial TENGAH harus tersedia');
        $this->assertSame($this->d(10), $partial['os']->toDateString());
        $this->assertSame($this->d(10), $partial['oe']->toDateString());

        $result = app(ReplacementService::class)->assignPartial(
            $booking,
            $partial['os'], $partial['oe'], $b,
        );

        $this->assertCount(3, $result['segments']);
        [$s1, $s2, $s3] = $result['segments'];

        // 9 Sep: mobil asli
        $this->assertSame($this->d(9), $s1->start_date->toDateString());
        $this->assertSame($a->id, $s1->vehicle_id);
        // 10 Sep: pengganti
        $this->assertSame($this->d(10), $s2->start_date->toDateString());
        $this->assertSame($b->id, $s2->vehicle_id);
        // 11 Sep: kembali mobil asli
        $this->assertSame($this->d(11), $s3->start_date->toDateString());
        $this->assertSame($a->id, $s3->vehicle_id);
    }

    /** D3b: parsial dari event 1 hari di tengah booking. */
    public function test_a_parsial_event_satu_hari_tengah(): void
    {
        $a = Vehicle::factory()->create();
        $b = Vehicle::factory()->create();

        $booking = Booking::create([
            'user_id' => $this->pegawai->id, 'vehicle_id' => $a->id,
            'start_date' => $this->d(20), 'end_date' => $this->d(22),
            'address' => 'X', 'purpose' => 'Y',
        ]);
        $event = \App\Models\Event::create([
            'name' => 'Event 1 Hari', 'bidang_id' => \App\Models\Bidang::factory()->create()->id,
            'start_date' => $this->d(21), 'end_date' => $this->d(21),
            'created_by' => $this->pengurus->id,
        ]);
        $event->vehicles()->attach($a->id);
        $booking->update(['status' => Booking::STATUS_MENUNGGU_PENGGANTIAN]);

        $this->actingAs($this->pengurus)
            ->patch("/pengurus/events/{$event->id}/conflicts/{$booking->id}/partial", ['vehicle_id' => $b->id])
            ->assertSessionHasNoErrors();

        $this->assertSame(3, Booking::where('user_id', $this->pegawai->id)->count());
    }

    /** UX: halaman penggantian menampilkan parsial DIREKOMENDASIKAN di atas penuh. */
    public function test_a_ux_parsial_direkomendasikan_di_atas(): void
    {
        $a = Vehicle::factory()->create();
        $b = Vehicle::factory()->create();
        $booking = Booking::create([
            'user_id' => $this->pegawai->id, 'vehicle_id' => $a->id,
            'start_date' => $this->d(20), 'end_date' => $this->d(21),
            'address' => 'X', 'purpose' => 'Y',
            'status' => Booking::STATUS_MENUNGGU_PENGGANTIAN,
        ]);
        Maintenance::create(['vehicle_id' => $a->id, 'start_date' => $this->d(21), 'end_date' => $this->d(22)]);

        $html = $this->actingAs($this->pengurus)
            ->get("/pengurus/replacements/{$booking->id}")
            ->assertOk()
            ->getContent();

        $posParsial = strpos($html, 'DIREKOMENDASIKAN');
        $posPenuh = strpos($html, 'Ganti Seluruh Rentang');
        $this->assertNotFalse($posParsial);
        $this->assertNotFalse($posPenuh);
        $this->assertLessThan($posPenuh, $posParsial, 'Parsial harus tampil DI ATAS penuh');
    }

    // ═══════════════════════════════════════════════════════════════
    // D: DURASI MAKSIMAL DAPAT DIATUR ADMIN
    // ═══════════════════════════════════════════════════════════════

    public function test_d_default_3_hari_booking_4_ditolak(): void
    {
        $v = Vehicle::factory()->create();

        $this->actingAs($this->pegawai)
            ->post('/pegawai/bookings', [
                'vehicle_id' => $v->id,
                'start_date' => $this->d(1), 'end_date' => $this->d(4), // 4 hari
                'address' => 'X', 'purpose' => 'Y',
            ])
            ->assertSessionHasErrors('end_date');
    }

    public function test_d_ubah_ke_5_hari_via_admin_booking_5_diterima(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $v = Vehicle::factory()->create();

        // Admin ubah ke 5 hari
        $this->actingAs($admin)
            ->post('/admin/settings', ['max_booking_days' => 5])
            ->assertSessionHasNoErrors();

        $this->assertSame(5, AppSettings::maxBookingDays());

        // Booking 5 hari diterima
        $this->actingAs($this->pegawai)
            ->post('/pegawai/bookings', [
                'vehicle_id' => $v->id,
                'start_date' => $this->d(1), 'end_date' => $this->d(5), // 5 hari
                'address' => 'X', 'purpose' => 'Y',
            ])
            ->assertSessionHasNoErrors();

        // Booking 6 hari ditolak
        $v2 = Vehicle::factory()->create();
        $this->actingAs($this->pegawai)
            ->post('/pegawai/bookings', [
                'vehicle_id' => $v2->id,
                'start_date' => $this->d(1), 'end_date' => $this->d(6), // 6 hari
                'address' => 'X', 'purpose' => 'Y',
            ])
            ->assertSessionHasErrors('end_date');
    }

    public function test_d_kembalikan_ke_3_dan_validasi_batas(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $v = Vehicle::factory()->create();

        // Kembalikan ke 3
        $this->actingAs($admin)->post('/admin/settings', ['max_booking_days' => 3]);

        // Booking 4 hari ditolak lagi
        $this->actingAs($this->pegawai)
            ->post('/pegawai/bookings', [
                'vehicle_id' => $v->id,
                'start_date' => $this->d(1), 'end_date' => $this->d(4),
                'address' => 'X', 'purpose' => 'Y',
            ])
            ->assertSessionHasErrors('end_date');

        // Batas: 0 dan 31 ditolak
        $this->actingAs($admin)->post('/admin/settings', ['max_booking_days' => 0])
            ->assertSessionHasErrors('max_booking_days');
        $this->actingAs($admin)->post('/admin/settings', ['max_booking_days' => 31])
            ->assertSessionHasErrors('max_booking_days');
    }

    public function test_d_halaman_pencarian_menampilkan_durasi_dinamis(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post('/admin/settings', ['max_booking_days' => 7]);

        $this->actingAs($this->pegawai)
            ->get('/pegawai/search')
            ->assertOk()
            ->assertSee('Maksimal 7 hari', false);
    }

    public function test_d_hanya_admin_yang_bisa_mengubah(): void
    {
        $this->actingAs($this->pegawai)->get('/admin/settings')->assertForbidden();
        $this->actingAs($this->pengurus)->post('/admin/settings', ['max_booking_days' => 5])->assertForbidden();
    }
}
