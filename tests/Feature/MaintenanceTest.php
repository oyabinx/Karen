<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Maintenance;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\AvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MaintenanceTest extends TestCase
{
    use RefreshDatabase;

    private User $pengurus;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pengurus = User::factory()->create(['role' => 'pengurus']);
    }

    public function test_pengurus_dapat_membuat_jadwal_dan_rentang_valid(): void
    {
        $v = Vehicle::factory()->create();

        $this->actingAs($this->pengurus)
            ->post('/pengurus/maintenances', [
                'vehicle_id' => $v->id,
                'start_date' => '2026-09-10',
                'end_date' => '2026-09-12',
                'note' => 'Servis rutin',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('maintenances', ['vehicle_id' => $v->id, 'status' => 'terjadwal']);
    }

    public function test_end_date_sebelum_start_date_ditolak(): void
    {
        $v = Vehicle::factory()->create();

        $this->actingAs($this->pengurus)
            ->post('/pengurus/maintenances', [
                'vehicle_id' => $v->id,
                'start_date' => '2026-09-12',
                'end_date' => '2026-09-10',
            ])
            ->assertSessionHasErrors('end_date');
    }

    public function test_dua_jadwal_overlap_satu_kendaraan_ditolak(): void
    {
        $v = Vehicle::factory()->create();
        Maintenance::create(['vehicle_id' => $v->id, 'start_date' => '2026-09-10', 'end_date' => '2026-09-12']);

        $this->actingAs($this->pengurus)
            ->post('/pengurus/maintenances', [
                'vehicle_id' => $v->id,
                'start_date' => '2026-09-12',
                'end_date' => '2026-09-14',
            ])
            ->assertSessionHasErrors('end_date');
    }

    public function test_kendaraan_maintenance_tidak_tersedia_pada_rentangnya(): void
    {
        $v = Vehicle::factory()->create();

        $this->actingAs($this->pengurus)
            ->post('/pengurus/maintenances', [
                'vehicle_id' => $v->id,
                'start_date' => '2026-09-10',
                'end_date' => '2026-09-12',
            ]);

        $service = app(AvailabilityService::class);

        $this->assertFalse($service->isAvailable($v, Carbon::parse('2026-09-10'), Carbon::parse('2026-09-12')));
        $this->assertTrue($service->isAvailable($v, Carbon::parse('2026-09-13'), Carbon::parse('2026-09-14')));
    }

    public function test_jadwal_menabrak_booking_menandai_menunggu_penggantian(): void
    {
        $v = Vehicle::factory()->create();
        $booking = Booking::create([
            'user_id' => User::factory()->create()->id,
            'vehicle_id' => $v->id,
            'start_date' => '2026-09-10',
            'end_date' => '2026-09-11',
            'address' => 'Kantor B',
            'purpose' => 'Rapat',
        ]);

        $response = $this->actingAs($this->pengurus)
            ->post('/pengurus/maintenances', [
                'vehicle_id' => $v->id,
                'start_date' => '2026-09-11',
                'end_date' => '2026-09-12',
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame(Booking::STATUS_MENUNGGU_PENGGANTIAN, $booking->refresh()->status);
    }

    public function test_update_rentang_bisa_menandai_booking_baru(): void
    {
        $v = Vehicle::factory()->create();
        $m = Maintenance::create(['vehicle_id' => $v->id, 'start_date' => '2026-09-01', 'end_date' => '2026-09-02']);

        $booking = Booking::create([
            'user_id' => User::factory()->create()->id,
            'vehicle_id' => $v->id,
            'start_date' => '2026-09-10',
            'end_date' => '2026-09-11',
            'address' => 'Kantor B',
            'purpose' => 'Rapat',
        ]);

        // Geser jadwal ke 09-10..09-12 → menabrak booking
        $this->actingAs($this->pengurus)
            ->put("/pengurus/maintenances/{$m->id}", [
                'vehicle_id' => $v->id,
                'start_date' => '2026-09-10',
                'end_date' => '2026-09-12',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(Booking::STATUS_MENUNGGU_PENGGANTIAN, $booking->refresh()->status);
    }

    public function test_update_menjauh_dari_booking_mengembalikan_status_dipinjam(): void
    {
        $v = Vehicle::factory()->create();
        $m = Maintenance::create(['vehicle_id' => $v->id, 'start_date' => '2026-09-10', 'end_date' => '2026-09-11']);

        $booking = Booking::create([
            'user_id' => User::factory()->create()->id,
            'vehicle_id' => $v->id,
            'start_date' => '2026-09-10',
            'end_date' => '2026-09-11',
            'address' => 'Kantor B',
            'purpose' => 'Rapat',
            'status' => Booking::STATUS_MENUNGGU_PENGGANTIAN,
        ]);

        // Geser jadwal menjauh (20-21 Sept) → booking tidak lagi tertabrak
        $this->actingAs($this->pengurus)
            ->put("/pengurus/maintenances/{$m->id}", [
                'vehicle_id' => $v->id,
                'start_date' => '2026-09-20',
                'end_date' => '2026-09-21',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(Booking::STATUS_DIPINJAM, $booking->refresh()->status);
    }

    public function test_tandai_selesai_dan_hapus_jadwal(): void
    {
        $v = Vehicle::factory()->create();
        $m = Maintenance::create(['vehicle_id' => $v->id, 'start_date' => '2026-09-01', 'end_date' => '2026-09-02']);

        $this->actingAs($this->pengurus)
            ->patch("/pengurus/maintenances/{$m->id}/finish")
            ->assertSessionHasNoErrors();
        $this->assertSame('selesai', $m->refresh()->status);

        $this->actingAs($this->pengurus)
            ->delete("/pengurus/maintenances/{$m->id}")
            ->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('maintenances', ['id' => $m->id]);
    }

    public function test_hapus_jadwal_mengembalikan_booking_menunggu_ke_dipinjam(): void
    {
        $v = Vehicle::factory()->create();
        $m = Maintenance::create(['vehicle_id' => $v->id, 'start_date' => '2026-09-10', 'end_date' => '2026-09-11']);

        $booking = Booking::create([
            'user_id' => User::factory()->create()->id,
            'vehicle_id' => $v->id,
            'start_date' => '2026-09-10',
            'end_date' => '2026-09-11',
            'address' => 'Kantor B',
            'purpose' => 'Rapat',
            'status' => Booking::STATUS_MENUNGGU_PENGGANTIAN,
        ]);

        $this->actingAs($this->pengurus)
            ->delete("/pengurus/maintenances/{$m->id}")
            ->assertSessionHasNoErrors();

        $this->assertSame(Booking::STATUS_DIPINJAM, $booking->refresh()->status);
    }

    public function test_pegawai_ditolak_admin_boleh(): void
    {
        $pegawai = User::factory()->create(['role' => 'pegawai']);
        $this->actingAs($pegawai)->get('/pengurus/maintenances')->assertForbidden();

        // Pasca-UAT 03: admin diberi akses menu maintenance
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get('/pengurus/maintenances')->assertOk();
    }
}
