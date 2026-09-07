<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Event;
use App\Models\Maintenance;
use App\Models\Seksi;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\ReplacementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Revisi & skema baru hasil UAT Skenario 03
 * (file UAT/03-pengurus-kendaraan-maintenance.md).
 */
class UatSkenario03RevisiTest extends TestCase
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

    /** A3 — pesan foto memakai istilah manusiawi "2 MB". */
    public function test_a3_pesan_foto_2mb(): void
    {
        $messages = (new \App\Http\Requests\Pengurus\VehicleStoreRequest())->messages();

        $this->assertSame('Foto maksimal berukuran 2 MB.', $messages['photo.max']);
    }

    /** A11 — data sekunder tersimpan & tampil di Detail Kendaraan. */
    public function test_a11_data_sekunder_tersimpan_dan_tampil(): void
    {
        $v = Vehicle::factory()->create();

        $this->actingAs($this->pengurus)
            ->put("/pengurus/vehicles/{$v->id}", [
                'name' => $v->name,
                'plate_number' => $v->plate_number,
                'year' => $v->year,
                'capacity' => $v->capacity,
                'nomor_rangka' => 'MHF1A5G5JHK123456',
                'nomor_mesin' => '2NRVE1234567',
                'pajak_tahunan' => '2026-12-20',
                'pajak_lima_tahunan' => '2030-06-15',
            ])
            ->assertSessionHasNoErrors();

        $v->refresh();
        $this->assertSame('MHF1A5G5JHK123456', $v->nomor_rangka);
        $this->assertSame('2026-12-20', $v->pajak_tahunan->toDateString());

        // Modal Detail pada halaman Data Kendaraan memuat data sekunder
        $this->actingAs($this->pengurus)
            ->get('/pengurus/vehicles')
            ->assertOk()
            ->assertSee('Detail Kendaraan')
            ->assertSee('MHF1A5G5JHK123456')
            ->assertSee('Nomor Rangka');
    }

    /** A11 — notifikasi pajak ≤3 minggu & lewat tempo. */
    public function test_a11_notifikasi_pajak_3_minggu_dan_lewat(): void
    {
        Vehicle::factory()->create(['name' => 'Pajak Dekat', 'pajak_tahunan' => $this->d(10)]);
        Vehicle::factory()->create(['name' => 'Pajak Lewat', 'pajak_lima_tahunan' => today()->subDays(3)->toDateString()]);
        Vehicle::factory()->create(['name' => 'Pajak Aman', 'pajak_tahunan' => $this->d(60)]);

        $this->actingAs($this->pengurus)
            ->get('/pengurus/vehicles')
            ->assertOk()
            ->assertSee('Peringatan Pajak Kendaraan')
            ->assertSee('Pajak Dekat')
            ->assertSee('10 hari lagi')
            ->assertSee('LEWAT 3 hari')
            ->assertDontSee('60 hari lagi'); // unit jauh dari tempo tak masuk peringatan

        // Dashboard pengurus juga menampilkan peringatan
        $this->actingAs($this->pengurus)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Peringatan Pajak');
    }

    /** B4 — jadwal maintenance mulai masa lalu ditolak. */
    public function test_b4_maintenance_masa_lalu_ditolak(): void
    {
        $v = Vehicle::factory()->create();

        $this->actingAs($this->pengurus)
            ->post('/pengurus/maintenances', [
                'vehicle_id' => $v->id,
                'start_date' => today()->subDay()->toDateString(),
                'end_date' => today()->toDateString(),
            ])
            ->assertSessionHasErrors('start_date');
    }

    /** A12 — pencarian: mobil maintenance TAMPIL nonaktif; dipinjam tetap tersembunyi. */
    public function test_a12_pencarian_maintenance_tampil_nonaktif(): void
    {
        $bebas = Vehicle::factory()->create(['name' => 'Mobil Bebas']);
        $diBengkel = Vehicle::factory()->create(['name' => 'Mobil Bengkel']);
        $dipinjam = Vehicle::factory()->create(['name' => 'Mobil Sibuk']);

        Maintenance::create(['vehicle_id' => $diBengkel->id, 'start_date' => $this->d(5), 'end_date' => $this->d(6)]);
        Booking::create([
            'user_id' => User::factory()->create(['seksi_id' => Seksi::factory()->create()->id])->id,
            'vehicle_id' => $dipinjam->id,
            'start_date' => $this->d(5), 'end_date' => $this->d(6),
            'address' => 'X', 'purpose' => 'Y',
        ]);

        $response = $this->actingAs($this->pegawai)
            ->get('/pegawai/search?start_date='.$this->d(5).'&end_date='.$this->d(6))
            ->assertOk();

        $response->assertSee('Mobil Bebas')                       // tersedia → klikabel
            ->assertSee('Mobil Bengkel')                          // maintenance → tampil
            ->assertSee('Sedang Maintenance')                     // label seksi baru
            ->assertSee('Tidak dapat dipilih')                    // tombol nonaktif
            ->assertDontSee('Mobil Sibuk');                       // dipinjam → tersembunyi
    }

    /** D2 — wizard event: kelompok maintenance tampil nonaktif + label rentang. */
    public function test_d2_wizard_event_maintenance_disabled(): void
    {
        $bebas = Vehicle::factory()->create(['name' => 'Avansa']);
        $diBengkel = Vehicle::factory()->create(['name' => 'Bengkelio']);
        Maintenance::create(['vehicle_id' => $diBengkel->id, 'start_date' => $this->d(10), 'end_date' => $this->d(11)]);

        $this->actingAs($this->pengurus)
            ->get('/pengurus/events/create?start_date='.$this->d(10).'&end_date='.$this->d(12))
            ->assertOk()
            ->assertSee('Bengkelio')
            ->assertSee('Maintenance:', false)
            ->assertSee('disabled', false); // checkbox nonaktif

        // Submit memilih unit maintenance tetap ditolak
        $this->actingAs($this->pengurus)
            ->post('/pengurus/events', [
                'name' => 'Event X',
                'bidang_id' => \App\Models\Bidang::factory()->create()->id,
                'start_date' => $this->d(10),
                'end_date' => $this->d(12),
                'jumlah_mobil' => 1,
                'vehicles' => [$diBengkel->id],
            ])
            ->assertSessionHasErrors('vehicles.0');
        $this->assertDatabaseMissing('events', ['name' => 'Event X']);
    }

    /**
     * B7 — penggantian PARSIAL tepi-akhir (kasus user):
     * booking A d20–21, maintenance A d21–22 → 20 tetap A, 21 pakai B.
     */
    public function test_b7_parsial_maintenance_akhir_rentang(): void
    {
        $a = Vehicle::factory()->create(['name' => 'Mobil A']);
        $b = Vehicle::factory()->create(['name' => 'Mobil B']);

        $booking = Booking::create([
            'user_id' => $this->pegawai->id,
            'vehicle_id' => $a->id,
            'start_date' => $this->d(20), 'end_date' => $this->d(21),
            'address' => 'Kantor B', 'purpose' => 'Rapat',
        ]);

        // Maintenance menabrak HARI TERAKHIR saja
        $this->actingAs($this->pengurus)
            ->post('/pengurus/maintenances', [
                'vehicle_id' => $a->id,
                'start_date' => $this->d(21), 'end_date' => $this->d(22),
            ]);

        $booking->refresh();
        $this->assertSame(Booking::STATUS_MENUNGGU_PENGGANTIAN, $booking->status);

        // Halaman penggantian menawarkan parsial
        $this->actingAs($this->pengurus)
            ->get("/pengurus/replacements/{$booking->id}")
            ->assertOk()
            ->assertSee('Pengganti sebagian')
            ->assertSee('sisa tanggal tetap Mobil A', false);

        // Jalankan parsial dengan Mobil B
        $this->actingAs($this->pengurus)
            ->patch("/pengurus/replacements/{$booking->id}/assign-partial", ['vehicle_id' => $b->id])
            ->assertSessionHasNoErrors();

        $booking->refresh();
        $this->assertSame($this->d(20), $booking->start_date->toDateString());
        $this->assertSame($this->d(20), $booking->end_date->toDateString()); // pangkas sisa
        $this->assertSame($a->id, $booking->vehicle_id);
        $this->assertSame(Booking::STATUS_DIPINJAM, $booking->status);

        $split = Booking::where('original_vehicle_id', $a->id)->first();
        $this->assertNotNull($split, 'Booking parsial baru harus terbentuk');
        $this->assertSame($this->d(21), $split->start_date->toDateString());
        $this->assertSame($this->d(21), $split->end_date->toDateString());
        $this->assertSame($b->id, $split->vehicle_id);
        $this->assertSame($this->pegawai->id, $split->user_id);
        $this->assertSame('Rapat', $split->purpose);
        $this->assertSame(Booking::STATUS_DIPINJAM, $split->status);

        // Riwayat pegawai menampilkan dua bagian + badge Diganti dari
        $this->actingAs($this->pegawai)
            ->get('/pegawai/bookings')
            ->assertOk()
            ->assertSee('Diganti dari Mobil A');
    }

    /** B7 — parsial dari konflik EVENT (tepi-awal). */
    public function test_b7_parsial_event_awal_rentang(): void
    {
        $a = Vehicle::factory()->create();
        $b = Vehicle::factory()->create();

        $booking = Booking::create([
            'user_id' => $this->pegawai->id,
            'vehicle_id' => $a->id,
            'start_date' => $this->d(15), 'end_date' => $this->d(16),
            'address' => 'X', 'purpose' => 'Y',
        ]);

        $event = Event::create([
            'name' => 'Event Awal', 'bidang_id' => \App\Models\Bidang::factory()->create()->id,
            'start_date' => $this->d(14), 'end_date' => $this->d(15), // menabrak hari PERTAMA
            'created_by' => $this->pengurus->id,
        ]);
        $event->vehicles()->attach($a->id);
        $booking->update(['status' => Booking::STATUS_MENUNGGU_PENGGANTIAN]);

        $this->actingAs($this->pengurus)
            ->patch("/pengurus/events/{$event->id}/conflicts/{$booking->id}/partial", ['vehicle_id' => $b->id])
            ->assertSessionHasNoErrors();

        $booking->refresh();
        $this->assertSame($this->d(16), $booking->start_date->toDateString()); // sisa di belakang
        $this->assertSame($this->d(16), $booking->end_date->toDateString());
        $this->assertSame($a->id, $booking->vehicle_id);

        $split = Booking::where('original_vehicle_id', $a->id)->first();
        $this->assertSame($this->d(15), $split->start_date->toDateString());
        $this->assertSame($b->id, $split->vehicle_id);
    }

    /** B7 batasan — tabrakan TENGAH tidak ditawarkan parsial. */
    public function test_b7_parsial_tengah_tidak_tersedia(): void
    {
        $a = Vehicle::factory()->create();

        $booking = Booking::create([
            'user_id' => $this->pegawai->id,
            'vehicle_id' => $a->id,
            'start_date' => $this->d(20), 'end_date' => $this->d(22),
            'address' => 'X', 'purpose' => 'Y',
            'status' => Booking::STATUS_MENUNGGU_PENGGANTIAN,
        ]);
        Maintenance::create(['vehicle_id' => $a->id, 'start_date' => $this->d(21), 'end_date' => $this->d(21)]); // tengah

        $this->actingAs($this->pengurus)
            ->get("/pengurus/replacements/{$booking->id}")
            ->assertOk()
            ->assertDontSee('Pengganti sebagian');

        $this->actingAs($this->pengurus)
            ->patch("/pengurus/replacements/{$booking->id}/assign-partial", [
                'vehicle_id' => Vehicle::factory()->create()->id,
            ])
            ->assertSessionHas('error'); // parsial tidak tersedia → gunakan penuh
    }

    /** D3 — menu mandiri "Penggantian Mobil" tampil di sidebar pengurus. */
    public function test_d3_menu_penggantian_mobil_mandiri(): void
    {
        $this->actingAs($this->pengurus)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Penggantian Mobil');
    }

    /** Regresi — penggantian PENUH tetap bekerja normal. */
    public function test_regresi_penggantian_penuh_tetap_bekerja(): void
    {
        $a = Vehicle::factory()->create();
        $b = Vehicle::factory()->create();

        $booking = Booking::create([
            'user_id' => $this->pegawai->id,
            'vehicle_id' => $a->id,
            'start_date' => $this->d(20), 'end_date' => $this->d(21),
            'address' => 'X', 'purpose' => 'Y',
            'status' => Booking::STATUS_MENUNGGU_PENGGANTIAN,
        ]);
        Maintenance::create(['vehicle_id' => $a->id, 'start_date' => $this->d(20), 'end_date' => $this->d(21)]);

        $this->actingAs($this->pengurus)
            ->patch("/pengurus/replacements/{$booking->id}/assign", ['vehicle_id' => $b->id])
            ->assertSessionHasNoErrors();

        $booking->refresh();
        $this->assertSame($b->id, $booking->vehicle_id);
        $this->assertSame($this->d(20), $booking->start_date->toDateString()); // rentang tidak berubah
        $this->assertSame(1, Booking::count()); // TIDAK terpecah
    }
}
