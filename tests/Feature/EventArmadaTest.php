<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Event;
use App\Models\Maintenance;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\EventService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventArmadaTest extends TestCase
{
    use RefreshDatabase;

    private User $pengurus;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pengurus = User::factory()->create(['role' => 'pengurus']);
    }

    private function payload(array $over = []): array
    {
        $bidang = \App\Models\Bidang::factory()->create();

        return array_merge([
            'name' => 'Rapat Koordinasi',
            'bidang_id' => $bidang->id,
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-07', // 7 hari — durasi event fleksibel
            'note' => null,
            'jumlah_mobil' => 1,
        ], $over);
    }

    public function test_admin_dan_pengurus_bisa_membuat_event_pegawai_ditolak(): void
    {
        foreach (['pengurus', 'admin'] as $role) {
            $u = User::factory()->create(['role' => $role]);

            $this->actingAs($u)->get('/pengurus/events')->assertOk();
            $this->actingAs($u)->get('/pengurus/events/create')->assertOk();
        }

        $pegawai = User::factory()->create(['role' => 'pegawai']);
        $this->actingAs($pegawai)->get('/pengurus/events')->assertForbidden();
        $this->actingAs($pegawai)->post('/pengurus/events', [])->assertForbidden();
    }

    public function test_event_boleh_lebih_dari_3_hari_dan_mengunci_armada(): void
    {
        $v = Vehicle::factory()->create();

        $this->actingAs($this->pengurus)
            ->post('/pengurus/events', $this->payload(['vehicles' => [$v->id]]))
            ->assertSessionHasNoErrors();

        $event = Event::first();
        $this->assertSame('terjadwal', $event->status);
        $this->assertTrue($event->vehicles->contains($v->id));

        // Armada event tidak tersedia pada rentang event
        $this->assertFalse(app(\App\Services\AvailabilityService::class)
            ->isAvailable($v, \Illuminate\Support\Carbon::parse('2026-10-02'), \Illuminate\Support\Carbon::parse('2026-10-03')));
        // ... dan tersedia kembali setelahnya
        $this->assertTrue(app(\App\Services\AvailabilityService::class)
            ->isAvailable($v, \Illuminate\Support\Carbon::parse('2026-10-08'), \Illuminate\Support\Carbon::parse('2026-10-09')));
    }

    public function test_event_kedua_tidak_bisa_menabrak_armada_event_pertama(): void
    {
        $v = Vehicle::factory()->create();

        $this->actingAs($this->pengurus)
            ->post('/pengurus/events', $this->payload(['vehicles' => [$v->id]]));

        $this->actingAs($this->pengurus)
            ->post('/pengurus/events', $this->payload([
                'start_date' => '2026-10-05', 'end_date' => '2026-10-06',
                'vehicles' => [$v->id],
            ]))
            ->assertSessionHasErrors('vehicles.0');
    }

    public function test_kendaraan_maintenance_tidak_layak_untuk_event(): void
    {
        $v = Vehicle::factory()->create();
        Maintenance::create(['vehicle_id' => $v->id, 'start_date' => '2026-10-02', 'end_date' => '2026-10-03']);

        $this->actingAs($this->pengurus)
            ->post('/pengurus/events', $this->payload(['vehicles' => [$v->id]]))
            ->assertSessionHasErrors('vehicles.0');
    }

    public function test_jumlah_armada_harus_tepat_n(): void
    {
        $a = Vehicle::factory()->create();
        $b = Vehicle::factory()->create();

        $this->actingAs($this->pengurus)
            ->post('/pengurus/events', $this->payload(['jumlah_mobil' => 2, 'vehicles' => [$a->id]]))
            ->assertSessionHasErrors('vehicles');
    }

    public function test_event_menabrak_booking_lengkap_dari_flag_hingga_konfirmasi(): void
    {
        $v = Vehicle::factory()->create();
        $pengganti = Vehicle::factory()->create();

        $booking = Booking::create([
            'user_id' => User::factory()->create()->id,
            'vehicle_id' => $v->id,
            'start_date' => '2026-10-02',
            'end_date' => '2026-10-03',
            'address' => 'Kantor B',
            'purpose' => 'Rapat',
        ]);

        // Buat event menabrak booking
        $this->actingAs($this->pengurus)
            ->post('/pengurus/events', $this->payload(['vehicles' => [$v->id]]))
            ->assertRedirect(route('pengurus.events.conflicts', Event::first()));

        $this->assertSame(Booking::STATUS_MENUNGGU_PENGGANTIAN, $booking->refresh()->status);

        $event = Event::first();

        // Halaman konflik menampilkan booking + kandidat (termasuk pengganti)
        $this->actingAs($this->pengurus)
            ->get("/pengurus/events/{$event->id}/conflicts")
            ->assertOk()
            ->assertSee($booking->user->name)
            ->assertSee($pengganti->name);

        // Konfirmasi event DITOLAK selama masih ada konflik
        $this->actingAs($this->pengurus)
            ->patch("/pengurus/events/{$event->id}/confirm")
            ->assertSessionHas('error');

        // Tetapkan pengganti
        $this->actingAs($this->pengurus)
            ->patch("/pengurus/events/{$event->id}/conflicts/{$booking->id}", ['vehicle_id' => $pengganti->id])
            ->assertSessionHasNoErrors();

        $booking->refresh();
        $this->assertSame($pengganti->id, $booking->vehicle_id);
        $this->assertSame($v->id, $booking->original_vehicle_id);
        $this->assertSame(Booking::STATUS_DIPINJAM, $booking->status);

        // Konfirmasi kini berhasil
        $this->actingAs($this->pengurus)
            ->patch("/pengurus/events/{$event->id}/confirm")
            ->assertSessionHas('success');
    }

    public function test_batalkan_peminjaman_tanpa_pengganti_dari_halaman_konflik(): void
    {
        $v = Vehicle::factory()->create();
        $booking = Booking::create([
            'user_id' => User::factory()->create()->id,
            'vehicle_id' => $v->id,
            'start_date' => '2026-10-02',
            'end_date' => '2026-10-03',
            'address' => 'Kantor B',
            'purpose' => 'Rapat',
        ]);

        $this->actingAs($this->pengurus)
            ->post('/pengurus/events', $this->payload(['vehicles' => [$v->id]]));

        $event = Event::first();

        $this->actingAs($this->pengurus)
            ->patch("/pengurus/events/{$event->id}/conflicts/{$booking->id}/cancel")
            ->assertSessionHasNoErrors();

        $this->assertSame(Booking::STATUS_DIBATALKAN, $booking->refresh()->status);
    }

    public function test_batalkan_event_melepas_armada_dan_mengembalikan_booking_belum_diganti(): void
    {
        $v = Vehicle::factory()->create();

        $belumDiganti = Booking::create([
            'user_id' => User::factory()->create()->id,
            'vehicle_id' => $v->id,
            'start_date' => '2026-10-02',
            'end_date' => '2026-10-03',
            'address' => 'Kantor B',
            'purpose' => 'Rapat',
            'status' => Booking::STATUS_MENUNGGU_PENGGANTIAN,
        ]);

        $this->actingAs($this->pengurus)
            ->post('/pengurus/events', $this->payload(['vehicles' => [$v->id]]));
        $event = Event::first();

        $this->actingAs($this->pengurus)
            ->patch("/pengurus/events/{$event->id}/cancel")
            ->assertSessionHasNoErrors();

        $this->assertSame('dibatalkan', $event->refresh()->status);
        $this->assertSame(Booking::STATUS_DIPINJAM, $belumDiganti->refresh()->status);

        // Armada lepas kembali pada tanggal tanpa peminjaman (10-02..03
        // kini sah milik booking yang dikembalikan — bukan blokir event)
        $this->assertTrue(app(\App\Services\AvailabilityService::class)
            ->isAvailable($v, \Illuminate\Support\Carbon::parse('2026-10-05'), \Illuminate\Support\Carbon::parse('2026-10-06')));
        $this->assertFalse(app(\App\Services\AvailabilityService::class)
            ->isAvailable($v, \Illuminate\Support\Carbon::parse('2026-10-02'), \Illuminate\Support\Carbon::parse('2026-10-03')));
    }

    public function test_booking_sudah_diganti_tetap_di_pengganti_saat_event_dibatalkan(): void
    {
        $v = Vehicle::factory()->create();
        $pengganti = Vehicle::factory()->create();

        $booking = Booking::create([
            'user_id' => User::factory()->create()->id,
            'vehicle_id' => $v->id,
            'start_date' => '2026-10-02',
            'end_date' => '2026-10-03',
            'address' => 'Kantor B',
            'purpose' => 'Rapat',
        ]);

        $this->actingAs($this->pengurus)->post('/pengurus/events', $this->payload(['vehicles' => [$v->id]]));
        $event = Event::first();

        $this->actingAs($this->pengurus)
            ->patch("/pengurus/events/{$event->id}/conflicts/{$booking->id}", ['vehicle_id' => $pengganti->id]);

        $this->actingAs($this->pengurus)
            ->patch("/pengurus/events/{$event->id}/cancel");

        $booking->refresh();
        $this->assertSame($pengganti->id, $booking->vehicle_id);
        $this->assertSame(Booking::STATUS_DIPINJAM, $booking->status);
    }

    public function test_auto_finish_event_lewat_jatuh_tempo(): void
    {
        $v = Vehicle::factory()->create();
        $bidang = \App\Models\Bidang::factory()->create();

        $lewat = Event::create([
            'name' => 'Event Lama', 'bidang_id' => $bidang->id,
            'start_date' => '2026-08-01', 'end_date' => '2026-08-05',
            'created_by' => $this->pengurus->id,
        ]);
        $lewat->vehicles()->attach($v->id);

        $berjalan = Event::create([
            'name' => 'Event Hari Ini', 'bidang_id' => $bidang->id,
            'start_date' => today()->toDateString(), 'end_date' => today()->toDateString(),
            'created_by' => $this->pengurus->id,
        ]);

        $service = app(EventService::class);
        $service->autoFinish();
        $service->autoFinish(); // idempoten

        $this->assertSame('selesai', $lewat->refresh()->status);
        $this->assertSame('terjadwal', $berjalan->refresh()->status); // end_date hari ini ≠ lewat
    }

    public function test_armada_options_dikelompokkan_bebas_dan_menabrak(): void
    {
        $bebas = Vehicle::factory()->create();
        $ditabrak = Vehicle::factory()->create();

        Booking::create([
            'user_id' => User::factory()->create()->id,
            'vehicle_id' => $ditabrak->id,
            'start_date' => '2026-10-02',
            'end_date' => '2026-10-03',
            'address' => 'X', 'purpose' => 'Y',
        ]);

        $options = app(EventService::class)->armadaOptions(
            \Illuminate\Support\Carbon::parse('2026-10-01'),
            \Illuminate\Support\Carbon::parse('2026-10-07'),
        );

        $this->assertTrue($options['bebas']->contains('id', $bebas->id));
        $this->assertTrue($options['menabrak']->contains('id', $ditabrak->id));
    }
}
