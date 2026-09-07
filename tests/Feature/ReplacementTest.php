<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Maintenance;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReplacementTest extends TestCase
{
    use RefreshDatabase;

    private User $pengurus;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pengurus = User::factory()->create(['role' => 'pengurus']);
    }

    private function pendingBooking(): Booking
    {
        $v = Vehicle::factory()->create();

        return Booking::create([
            'user_id' => User::factory()->create()->id,
            'vehicle_id' => $v->id,
            'start_date' => '2026-09-10',
            'end_date' => '2026-09-11',
            'address' => 'Kantor B',
            'purpose' => 'Rapat',
            'status' => Booking::STATUS_MENUNGGU_PENGGANTIAN,
        ]);
    }

    public function test_daftar_dan_detail_kandidat(): void
    {
        $booking = $this->pendingBooking();
        $pengganti = Vehicle::factory()->create();

        $this->actingAs($this->pengurus)
            ->get('/pengurus/replacements')
            ->assertOk()
            ->assertSee($booking->vehicle->name);

        $this->actingAs($this->pengurus)
            ->get("/pengurus/replacements/{$booking->id}")
            ->assertOk()
            ->assertSee('Pilih Mobil Pengganti')
            ->assertSee($pengganti->name);
    }

    public function test_kandidat_tidak_menyertakan_mobil_lama(): void
    {
        $booking = $this->pendingBooking();

        $response = $this->actingAs($this->pengurus)->get("/pengurus/replacements/{$booking->id}");

        $response->assertOk();
        $response->assertDontSee($booking->vehicle->name.' </p>'); // kartu kandidat tidak memuat mobil lama
    }

    public function test_penetapan_pengganti(): void
    {
        $booking = $this->pendingBooking();
        $lama = $booking->vehicle;
        $pengganti = Vehicle::factory()->create();

        $this->actingAs($this->pengurus)
            ->patch("/pengurus/replacements/{$booking->id}/assign", ['vehicle_id' => $pengganti->id])
            ->assertSessionHasNoErrors();

        $booking->refresh();
        $this->assertSame($pengganti->id, $booking->vehicle_id);
        $this->assertSame($lama->id, $booking->original_vehicle_id);
        $this->assertSame(Booking::STATUS_DIPINJAM, $booking->status);
    }

    public function test_penetapan_mobil_yang_sudah_dibooking_orang_lain_gagal(): void
    {
        $booking = $this->pendingBooking();

        $pengganti = Vehicle::factory()->create();
        // Mobil pengganti ternyata sudah dibooking overlap oleh user lain
        Booking::create([
            'user_id' => User::factory()->create()->id,
            'vehicle_id' => $pengganti->id,
            'start_date' => '2026-09-11',
            'end_date' => '2026-09-12',
            'address' => 'Kantor C',
            'purpose' => 'Dinas',
        ]);

        $this->actingAs($this->pengurus)
            ->patch("/pengurus/replacements/{$booking->id}/assign", ['vehicle_id' => $pengganti->id])
            ->assertSessionHas('error');

        $this->assertSame(Booking::STATUS_MENUNGGU_PENGGANTIAN, $booking->refresh()->status);
        $this->assertNotSame($pengganti->id, $booking->refresh()->vehicle_id);
    }

    public function test_pembatalan_tanpa_pengganti(): void
    {
        $booking = $this->pendingBooking();

        $this->actingAs($this->pengurus)
            ->patch("/pengurus/replacements/{$booking->id}/cancel")
            ->assertSessionHasNoErrors();

        $this->assertSame(Booking::STATUS_DIBATALKAN, $booking->refresh()->status);
    }

    public function test_booking_sudah_diproses_tidak_bisa_diproses_lagi(): void
    {
        $booking = $this->pendingBooking();
        $booking->update(['status' => Booking::STATUS_DIKEMBALIKAN]);

        $this->actingAs($this->pengurus)
            ->patch("/pengurus/replacements/{$booking->id}/cancel")
            ->assertSessionHas('error');
    }

    public function test_booking_sudah_diganti_tidak_kembali_ke_mobil_lama_saat_maintenance_dihapus(): void
    {
        $booking = $this->pendingBooking();
        $lama = $booking->vehicle;

        Maintenance::create(['vehicle_id' => $lama->id, 'start_date' => '2026-09-10', 'end_date' => '2026-09-11']);

        $pengganti = Vehicle::factory()->create();
        $this->actingAs($this->pengurus)
            ->patch("/pengurus/replacements/{$booking->id}/assign", ['vehicle_id' => $pengganti->id]);

        // Hapus maintenance — booking sudah pindah, TIDAK boleh balik ke mobil lama
        $m = Maintenance::where('vehicle_id', $lama->id)->first();
        $this->actingAs($this->pengurus)->delete("/pengurus/maintenances/{$m->id}");

        $booking->refresh();
        $this->assertSame($pengganti->id, $booking->vehicle_id);
        $this->assertSame(Booking::STATUS_DIPINJAM, $booking->status);
    }

    public function test_pegawai_ditolak_admin_boleh(): void
    {
        $booking = $this->pendingBooking();

        $pegawai = User::factory()->create(['role' => 'pegawai']);
        $this->actingAs($pegawai)->get('/pengurus/replacements')->assertForbidden();
        $this->actingAs($pegawai)->patch("/pengurus/replacements/{$booking->id}/cancel")->assertForbidden();

        // Pasca-UAT 03: admin diberi akses menu Penggantian Mobil
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get('/pengurus/replacements')->assertOk();
    }
}
