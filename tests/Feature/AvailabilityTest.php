<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Event;
use App\Models\Maintenance;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\AvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AvailabilityTest extends TestCase
{
    use RefreshDatabase;

    private AvailabilityService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AvailabilityService::class);
    }

    public function test_mobil_normal_tersedia(): void
    {
        $v = Vehicle::factory()->create();

        $this->assertTrue($this->service->isAvailable($v, Carbon::parse('2026-09-01'), Carbon::parse('2026-09-02')));
    }

    public function test_booking_dipinjam_overlap_memblokir(): void
    {
        $v = Vehicle::factory()->create();
        Booking::create([
            'user_id' => User::factory()->create()->id,
            'vehicle_id' => $v->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-03',
            'address' => 'Kantor B',
            'purpose' => 'Rapat',
        ]);

        // Rentang bersinggungan (10-01 s.d. 09-03 vs 09-03 s.d. 09-05 → overlap di 09-03)
        $this->assertFalse($this->service->isAvailable($v, Carbon::parse('2026-09-03'), Carbon::parse('2026-09-05')));
        // Rentang berdekatan TANPA overlap tetap tersedia (berakhir 08-31 < mulai 09-01)
        $this->assertTrue($this->service->isAvailable($v, Carbon::parse('2026-08-30'), Carbon::parse('2026-08-31')));
    }

    public function test_booking_menunggu_penggantian_juga_memblokir(): void
    {
        $v = Vehicle::factory()->create();
        Booking::create([
            'user_id' => User::factory()->create()->id,
            'vehicle_id' => $v->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-02',
            'address' => 'Kantor B',
            'purpose' => 'Rapat',
            'status' => Booking::STATUS_MENUNGGU_PENGGANTIAN,
        ]);

        $this->assertFalse($this->service->isAvailable($v, Carbon::parse('2026-09-01'), Carbon::parse('2026-09-02')));
    }

    public function test_maintenance_overlap_memblokir(): void
    {
        $v = Vehicle::factory()->create();
        Maintenance::create([
            'vehicle_id' => $v->id,
            'start_date' => '2026-09-10',
            'end_date' => '2026-09-12',
        ]);

        $this->assertFalse($this->service->isAvailable($v, Carbon::parse('2026-09-11'), Carbon::parse('2026-09-13')));
        $this->assertTrue($this->service->isAvailable($v, Carbon::parse('2026-09-13'), Carbon::parse('2026-09-14')));
    }

    public function test_status_dan_kondisi_memblokir(): void
    {
        $blokir = Vehicle::factory()->unavailable()->create();
        $periksa = Vehicle::factory()->needsInspection()->create();

        $range = [Carbon::parse('2026-09-01'), Carbon::parse('2026-09-02')];

        $this->assertFalse($this->service->isAvailable($blokir, ...$range));
        $this->assertFalse($this->service->isAvailable($periksa, ...$range));
    }

    public function test_event_terjadwal_memblokir(): void
    {
        $v = Vehicle::factory()->create();
        $event = Event::create([
            'name' => 'Rapat Koordinasi',
            'bidang_id' => \App\Models\Bidang::factory()->create()->id,
            'start_date' => '2026-09-05',
            'end_date' => '2026-09-06',
            'created_by' => User::factory()->create(['role' => 'pengurus'])->id,
        ]);
        $event->vehicles()->attach($v->id);

        $this->assertFalse($this->service->isAvailable($v, Carbon::parse('2026-09-05'), Carbon::parse('2026-09-06')));
        $this->assertTrue($this->service->isAvailable($v, Carbon::parse('2026-09-07'), Carbon::parse('2026-09-08')));
    }

    public function test_exclude_vehicle_id(): void
    {
        $v = Vehicle::factory()->create();

        // Mobil dikecualikan dari daftar kandidat (mis. mobil lama saat penggantian)
        $hasil = $this->service->availableBetween(
            Carbon::parse('2026-09-01'),
            Carbon::parse('2026-09-02'),
            $v->id,
        );

        $this->assertFalse($hasil->contains('id', $v->id));
    }
}
