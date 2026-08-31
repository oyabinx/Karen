<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Complaint;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\AvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Alur penyelesaian peminjaman (tombol "Selesai" + pop-up keluhan)
 * — kesepakatan revisi: keluhan = CATATAN SAHAJA, mobil tetap bisa
 * dipinjam, tidak otomatis maintenance/perlu_diperiksa.
 */
class ReturnFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $pegawai;

    private User $pengurus;

    protected function setUp(): void
    {
        parent::setUp();

        $seksi = \App\Models\Seksi::factory()->create();
        $this->pegawai = User::factory()->create(['role' => 'pegawai', 'seksi_id' => $seksi->id]);
        $this->pengurus = User::factory()->create(['role' => 'pengurus']);
    }

    private function bookingAktif(): array
    {
        $v = Vehicle::factory()->create();
        $b = Booking::create([
            'user_id' => $this->pegawai->id,
            'vehicle_id' => $v->id,
            'start_date' => today()->toDateString(),
            'end_date' => today()->addDay()->toDateString(),
            'address' => 'Kantor B',
            'purpose' => 'Rapat',
        ]);

        return [$b, $v];
    }

    public function test_selesai_tanpa_keluhan_membuat_mobil_langsung_tersedia(): void
    {
        [$b, $v] = $this->bookingAktif();

        $this->actingAs($this->pegawai)
            ->post("/pegawai/returns/{$b->id}", ['complaint' => ''])
            ->assertSessionHasNoErrors();

        $b->refresh();
        $this->assertSame('dikembalikan', $b->status);
        $this->assertNotNull($b->returned_at);
        $this->assertSame(0, Complaint::count());

        $this->assertTrue(app(AvailabilityService::class)
            ->isAvailable($v, Carbon::parse(today()->addDays(3)), Carbon::parse(today()->addDays(4))));
    }

    public function test_keluhan_hanya_catatan_mobil_tetap_bisa_dipinjam_dan_tidak_jadi_maintenance(): void
    {
        [$b, $v] = $this->bookingAktif();

        $this->actingAs($this->pegawai)
            ->post("/pegawai/returns/{$b->id}", ['complaint' => 'Ban depan aus, AC kurang dingin'])
            ->assertSessionHasNoErrors();

        // Keluhan tercatat & belum selesai
        $this->assertSame(1, Complaint::count());
        $keluhan = Complaint::first();
        $this->assertSame($b->id, $keluhan->booking_id);
        $this->assertFalse($keluhan->resolved);

        // ATURAN BARU: mobil TETAP berkondisi baik & TIDAK punya jadwal
        // maintenance — tidak otomatis masuk bengkel
        $v->refresh();
        $this->assertSame('baik', $v->condition);
        $this->assertSame('bisa_dipinjam', $v->status);
        $this->assertSame(0, \App\Models\Maintenance::where('vehicle_id', $v->id)->count());

        // Dan tetap bisa dipinjam orang lain segera
        $this->assertTrue(app(AvailabilityService::class)
            ->isAvailable($v, Carbon::parse(today()->addDays(3)), Carbon::parse(today()->addDays(4))));
    }

    public function test_keluhan_muncul_di_daftar_pengurus_dan_bisa_ditandai_selesai(): void
    {
        [$b] = $this->bookingAktif();

        $this->actingAs($this->pegawai)
            ->post("/pegawai/returns/{$b->id}", ['complaint' => 'Rem terasa blong']);

        $this->actingAs($this->pengurus)
            ->get('/pengurus/complaints')
            ->assertOk()
            ->assertSee('Rem terasa blong')
            ->assertSee($this->pegawai->name);

        $keluhan = Complaint::first();

        $this->actingAs($this->pengurus)
            ->patch("/pengurus/complaints/{$keluhan->id}/resolve")
            ->assertSessionHasNoErrors();

        $this->assertTrue($keluhan->refresh()->resolved);

        // Daftar "belum" kini kosong, tab selesai memuatnya
        $this->actingAs($this->pengurus)
            ->get('/pengurus/complaints?status=belum')
            ->assertOk()
            ->assertDontSee('Rem terasa blong');
    }

    public function test_submit_ganda_dan_status_bukan_dipinjam_ditolak(): void
    {
        [$b] = $this->bookingAktif();

        $this->actingAs($this->pegawai)->post("/pegawai/returns/{$b->id}", ['complaint' => '']);
        $this->actingAs($this->pegawai)
            ->post("/pegawai/returns/{$b->id}", ['complaint' => ''])
            ->assertSessionHas('error');

        // Booking menunggu_penggantian juga tidak bisa diselesaikan pegawai
        $v2 = Vehicle::factory()->create();
        $b2 = Booking::create([
            'user_id' => $this->pegawai->id,
            'vehicle_id' => $v2->id,
            'start_date' => today()->toDateString(),
            'end_date' => today()->toDateString(),
            'address' => 'X', 'purpose' => 'Y',
            'status' => Booking::STATUS_MENUNGGU_PENGGANTIAN,
        ]);

        $this->actingAs($this->pegawai)
            ->post("/pegawai/returns/{$b2->id}", ['complaint' => ''])
            ->assertSessionHas('error');
    }

    public function test_booking_milik_orang_lain_ditolak_403(): void
    {
        [$b] = $this->bookingAktif();
        $lain = User::factory()->create(['role' => 'pegawai']);

        $this->actingAs($lain)
            ->post("/pegawai/returns/{$b->id}", ['complaint' => ''])
            ->assertForbidden();
    }

    public function test_pengurus_bisa_menyelesaikan_peminjaman_sendiri(): void
    {
        $seksi = \App\Models\Seksi::factory()->create();
        $pengurusPeminjam = User::factory()->create(['role' => 'pengurus', 'seksi_id' => $seksi->id]);
        $v = Vehicle::factory()->create();

        $b = Booking::create([
            'user_id' => $pengurusPeminjam->id,
            'vehicle_id' => $v->id,
            'start_date' => today()->toDateString(),
            'end_date' => today()->toDateString(),
            'address' => 'X', 'purpose' => 'Y',
        ]);

        $this->actingAs($pengurusPeminjam)
            ->post("/pegawai/returns/{$b->id}", ['complaint' => ''])
            ->assertSessionHasNoErrors();

        $this->assertSame('dikembalikan', $b->refresh()->status);
    }

    public function test_kondisi_perlu_diperiksa_hanya_manual_oleh_pengurus(): void
    {
        [$b, $v] = $this->bookingAktif();

        // Keluhan pegawai — kondisi tetap baik (dicek test lain);
        // tandai MANUAL oleh pengurus → unit tersisihkan
        $this->actingAs($this->pegawai)->post("/pegawai/returns/{$b->id}", ['complaint' => 'Mesin berat']);

        $this->actingAs($this->pengurus)
            ->patch("/pengurus/vehicles/{$v->id}/needs-inspection")
            ->assertSessionHasNoErrors();

        $this->assertSame('perlu_diperiksa', $v->refresh()->condition);
        $this->assertFalse(app(AvailabilityService::class)
            ->isAvailable($v, Carbon::parse(today()->addDays(5)), Carbon::parse(today()->addDays(6))));

        // Set kembali baik → tersedia lagi
        $this->actingAs($this->pengurus)
            ->patch("/pengurus/vehicles/{$v->id}/condition")
            ->assertSessionHasNoErrors();

        $this->assertTrue(app(AvailabilityService::class)
            ->isAvailable($v, Carbon::parse(today()->addDays(5)), Carbon::parse(today()->addDays(6))));
    }

    public function test_keluhan_tercatat_pada_mobil_pengganti(): void
    {
        $lama = Vehicle::factory()->create();
        $pengganti = Vehicle::factory()->create();

        $b = Booking::create([
            'user_id' => $this->pegawai->id,
            'vehicle_id' => $pengganti->id,
            'original_vehicle_id' => $lama->id,
            'start_date' => today()->toDateString(),
            'end_date' => today()->toDateString(),
            'address' => 'X', 'purpose' => 'Y',
        ]);

        $this->actingAs($this->pegawai)
            ->post("/pegawai/returns/{$b->id}", ['complaint' => 'Klakson mati']);

        $this->assertSame($pengganti->id, Complaint::first()->booking->vehicle_id);
    }

    public function test_pegawai_ditolak_mengakses_daftar_keluhan(): void
    {
        $this->actingAs($this->pegawai)->get('/pengurus/complaints')->assertForbidden();
    }
}
