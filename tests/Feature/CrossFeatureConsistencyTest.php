<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Event;
use App\Models\Maintenance;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Review pasca-Fase 4 — uji tabrakan logika lintas fitur
 * (maintenance ↔ event ↔ revert booking).
 */
class CrossFeatureConsistencyTest extends TestCase
{
    use RefreshDatabase;

    private User $pengurus;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pengurus = User::factory()->create(['role' => 'pengurus']);
    }

    /**
     * TABRAKAN A — aturan dua arah maintenance ↔ event:
     * pembuatan maintenance ditolak bila menabrak armada event.
     */
    public function test_maintenance_ditolak_bila_menabrak_armada_event(): void
    {
        $v = Vehicle::factory()->create();
        $bidang = \App\Models\Bidang::factory()->create();

        $event = Event::create([
            'name' => 'Event X', 'bidang_id' => $bidang->id,
            'start_date' => '2026-11-10', 'end_date' => '2026-11-12',
            'created_by' => $this->pengurus->id,
        ]);
        $event->vehicles()->attach($v->id);

        // Menabrak rentang event → ditolak
        $this->actingAs($this->pengurus)
            ->post('/pengurus/maintenances', [
                'vehicle_id' => $v->id,
                'start_date' => '2026-11-11',
                'end_date' => '2026-11-13',
            ])
            ->assertSessionHasErrors('end_date');

        // Di luar rentang event → diterima
        $this->actingAs($this->pengurus)
            ->post('/pengurus/maintenances', [
                'vehicle_id' => $v->id,
                'start_date' => '2026-11-14',
                'end_date' => '2026-11-15',
            ])
            ->assertSessionHasNoErrors();
    }

    /**
     * TABRAKAN B — revert availability-aware:
     * booking ditandai EVENT; maintenance lain (tanggal beda) pada mobil
     * sama di-update → booking TIDAK boleh kembali dipinjam karena mobil
     * masih dikuasai event.
     */
    public function test_revert_tidak_mengembalikan_booking_saat_mobil_masih_dikuasai_event(): void
    {
        $v = Vehicle::factory()->create();
        $bidang = \App\Models\Bidang::factory()->create();

        // Event menandai booking 10-11 Nov
        $booking = Booking::create([
            'user_id' => User::factory()->create()->id,
            'vehicle_id' => $v->id,
            'start_date' => '2026-11-10',
            'end_date' => '2026-11-11',
            'address' => 'Kantor B', 'purpose' => 'Rapat',
        ]);
        $event = Event::create([
            'name' => 'Event Y', 'bidang_id' => $bidang->id,
            'start_date' => '2026-11-10', 'end_date' => '2026-11-12',
            'created_by' => $this->pengurus->id,
        ]);
        $event->vehicles()->attach($v->id);
        $booking->update(['status' => Booking::STATUS_MENUNGGU_PENGGANTIAN]);

        // Maintenance pada tanggal LAIN (di luar event — sah) lalu di-update
        $m = Maintenance::create(['vehicle_id' => $v->id, 'start_date' => '2026-11-20', 'end_date' => '2026-11-21']);

        $this->actingAs($this->pengurus)
            ->put("/pengurus/maintenances/{$m->id}", [
                'vehicle_id' => $v->id,
                'start_date' => '2026-11-20',
                'end_date' => '2026-11-22',
            ])
            ->assertSessionHasNoErrors();

        // Booking TETAP menunggu penggantian — mobil masih untuk event
        $this->assertSame(Booking::STATUS_MENUNGGU_PENGGANTIAN, $booking->refresh()->status);
    }

    /**
     * Kontrol positif untuk revert availability-aware: blokir hilang
     * → booking kembali dipinjam.
     */
    public function test_revert_kembali_dipinjam_bila_blokir_benar_benar_hilang(): void
    {
        $v = Vehicle::factory()->create();

        $m = Maintenance::create(['vehicle_id' => $v->id, 'start_date' => '2026-11-10', 'end_date' => '2026-11-11']);

        $booking = Booking::create([
            'user_id' => User::factory()->create()->id,
            'vehicle_id' => $v->id,
            'start_date' => '2026-11-10',
            'end_date' => '2026-11-11',
            'address' => 'Kantor B', 'purpose' => 'Rapat',
            'status' => Booking::STATUS_MENUNGGU_PENGGANTIAN,
        ]);

        $this->actingAs($this->pengurus)
            ->delete("/pengurus/maintenances/{$m->id}")
            ->assertSessionHasNoErrors();

        $this->assertSame(Booking::STATUS_DIPINJAM, $booking->refresh()->status);
    }
}
