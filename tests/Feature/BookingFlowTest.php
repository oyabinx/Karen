<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Seksi;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BookingFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $pegawai;

    protected function setUp(): void
    {
        parent::setUp();

        $seksi = Seksi::factory()->create();
        $this->pegawai = User::factory()->create(['role' => 'pegawai', 'seksi_id' => $seksi->id]);
    }

    private function anggotaBidang(int $count = 2): array
    {
        return User::factory()->count($count)->create(['role' => 'pegawai', 'seksi_id' => $this->pegawai->seksi_id])->all();
    }

    public function test_halaman_cari_menampilkan_mobil_tersedia_dan_menyembunyikan_yang_terblokir(): void
    {
        $tersedia = Vehicle::factory()->create();
        $dipakai = Vehicle::factory()->create();

        Booking::create([
            'user_id' => User::factory()->create()->id,
            'vehicle_id' => $dipakai->id,
            'start_date' => today()->toDateString(),
            'end_date' => today()->toDateString(),
            'address' => 'X', 'purpose' => 'Y',
        ]);

        $this->actingAs($this->pegawai)
            ->get('/pegawai/search?start_date='.today()->toDateString().'&end_date='.today()->toDateString())
            ->assertOk()
            ->assertSee($tersedia->name)
            ->assertDontSee($dipakai->name);
    }

    public function test_durasi_maksimal_3_hari_dan_mingguan_diperbolehkan(): void
    {
        $v = Vehicle::factory()->create();

        // 4 hari → ditolak
        $this->actingAs($this->pegawai)
            ->post('/pegawai/bookings', [
                'vehicle_id' => $v->id,
                'start_date' => today()->toDateString(),
                'end_date' => today()->addDays(3)->toDateString(),
                'address' => 'Kantor B',
                'purpose' => 'Rapat',
            ])
            ->assertSessionHasErrors('end_date');

        // Sabtu–Senin (3 hari, termasuk akhir pekan) → diterima
        // 5 Des 2026 = Sabtu, 7 Des = Senin
        $this->actingAs($this->pegawai)
            ->post('/pegawai/bookings', [
                'vehicle_id' => $v->id,
                'start_date' => today()->addDays(5)->toDateString(),
                'end_date' => today()->addDays(7)->toDateString(),
                'address' => 'Kantor B',
                'purpose' => 'Rapat',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('bookings', ['vehicle_id' => $v->id, 'status' => 'dipinjam']);
    }

    public function test_tanggal_mulai_masa_lalu_ditolak(): void
    {
        $v = Vehicle::factory()->create();

        $this->actingAs($this->pegawai)
            ->post('/pegawai/bookings', [
                'vehicle_id' => $v->id,
                'start_date' => today()->subDay()->toDateString(),
                'end_date' => today()->toDateString(),
                'address' => 'Kantor B',
                'purpose' => 'Rapat',
            ])
            ->assertSessionHasErrors('end_date');
    }

    public function test_booking_hari_h_tercatat_mulai_pukul_nol(): void
    {
        $v = Vehicle::factory()->create();

        $this->actingAs($this->pegawai)
            ->post('/pegawai/bookings', [
                'vehicle_id' => $v->id,
                'start_date' => today()->toDateString(),
                'end_date' => today()->toDateString(),
                'address' => 'Kantor B',
                'purpose' => 'Rapat',
            ])
            ->assertSessionHasNoErrors();

        $b = Booking::first();
        $this->assertSame(today()->toDateString(), $b->start_date->toDateString());
        $this->assertSame(today()->toDateString(), $b->end_date->toDateString());
    }

    public function test_satu_peminjaman_aktif_per_user(): void
    {
        $a = Vehicle::factory()->create();
        $b = Vehicle::factory()->create();

        $this->actingAs($this->pegawai)
            ->post('/pegawai/bookings', [
                'vehicle_id' => $a->id, 'start_date' => today()->toDateString(), 'end_date' => today()->toDateString(),
                'address' => 'X', 'purpose' => 'Y',
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($this->pegawai)
            ->post('/pegawai/bookings', [
                'vehicle_id' => $b->id, 'start_date' => today()->addDays(10)->toDateString(), 'end_date' => today()->addDays(11)->toDateString(),
                'address' => 'X', 'purpose' => 'Y',
            ])
            ->assertSessionHas('error') // pesan "masih memiliki peminjaman aktif"
            ->assertSessionDoesntHaveErrors();

        $this->assertSame(1, Booking::where('user_id', $this->pegawai->id)->count());
    }

    public function test_kuota_bidang_default_2_dan_lepas_setelah_dikembalikan(): void
    {
        $anggota = $this->anggotaBidang();
        $v = Vehicle::factory()->count(3)->create();

        // Dua anggota meminjam → kuota (2) penuh
        Booking::create(['user_id' => $anggota[0]->id, 'vehicle_id' => $v[0]->id, 'start_date' => today()->toDateString(), 'end_date' => today()->toDateString(), 'address' => 'X', 'purpose' => 'Y']);
        Booking::create(['user_id' => $anggota[1]->id, 'vehicle_id' => $v[1]->id, 'start_date' => today()->toDateString(), 'end_date' => today()->toDateString(), 'address' => 'X', 'purpose' => 'Y']);

        $this->actingAs($this->pegawai)
            ->post('/pegawai/bookings', [
                'vehicle_id' => $v[2]->id, 'start_date' => today()->toDateString(), 'end_date' => today()->toDateString(),
                'address' => 'X', 'purpose' => 'Y',
            ])
            ->assertSessionHas('error');

        // Salah satu dikembalikan → kuota lepas, pegawai bisa meminjam
        Booking::first()->update(['status' => 'dikembalikan', 'returned_at' => now()]);

        $this->actingAs($this->pegawai)
            ->post('/pegawai/bookings', [
                'vehicle_id' => $v[2]->id, 'start_date' => today()->toDateString(), 'end_date' => today()->toDateString(),
                'address' => 'X', 'purpose' => 'Y',
            ])
            ->assertSessionHasNoErrors();
    }

    public function test_kuota_bidang_khusus_3(): void
    {
        $bidang = $this->pegawai->seksi->bidang;
        $bidang->update(['max_active_bookings' => 3]);

        $anggota = User::factory()->count(2)->create(['role' => 'pegawai', 'seksi_id' => $this->pegawai->seksi_id]);
        $v = Vehicle::factory()->count(3)->create();

        Booking::create(['user_id' => $anggota[0]->id, 'vehicle_id' => $v[0]->id, 'start_date' => today()->toDateString(), 'end_date' => today()->toDateString(), 'address' => 'X', 'purpose' => 'Y']);
        Booking::create(['user_id' => $anggota[1]->id, 'vehicle_id' => $v[1]->id, 'start_date' => today()->toDateString(), 'end_date' => today()->toDateString(), 'address' => 'X', 'purpose' => 'Y']);

        // Anggota ketiga (kuota 3) → masih boleh
        $this->actingAs($this->pegawai)
            ->post('/pegawai/bookings', [
                'vehicle_id' => $v[2]->id, 'start_date' => today()->toDateString(), 'end_date' => today()->toDateString(),
                'address' => 'X', 'purpose' => 'Y',
            ])
            ->assertSessionHasNoErrors();
    }

    public function test_pengurus_juga_bisa_meminjam_dan_terkena_kuota(): void
    {
        $pengurus = User::factory()->create(['role' => 'pengurus', 'seksi_id' => $this->pegawai->seksi_id]);
        $v = Vehicle::factory()->create();

        // Pengurus bisa mengakses seluruh fitur peminjaman
        $this->actingAs($pengurus)->get('/pegawai/search?start_date='.today()->toDateString().'&end_date='.today()->toDateString())->assertOk();
        $this->actingAs($pengurus)->get('/pegawai/bookings')->assertOk();

        $this->actingAs($pengurus)
            ->post('/pegawai/bookings', [
                'vehicle_id' => $v->id, 'start_date' => today()->toDateString(), 'end_date' => today()->toDateString(),
                'address' => 'X', 'purpose' => 'Y',
            ])
            ->assertSessionHasNoErrors();

        // Kuota bidang (default 2): satu slot lagi terisi pegawai
        $pegawaiLain = User::factory()->create(['role' => 'pegawai', 'seksi_id' => $this->pegawai->seksi_id]);
        $this->actingAs($pegawaiLain)
            ->post('/pegawai/bookings', [
                'vehicle_id' => Vehicle::factory()->create()->id, 'start_date' => today()->toDateString(), 'end_date' => today()->toDateString(),
                'address' => 'X', 'purpose' => 'Y',
            ])
            ->assertSessionHasNoErrors();

        // Slot ketiga → penuh, pengurus kedua pun ditolak
        $pengurus2 = User::factory()->create(['role' => 'pengurus', 'seksi_id' => $this->pegawai->seksi_id]);
        $this->actingAs($pengurus2)
            ->post('/pegawai/bookings', [
                'vehicle_id' => Vehicle::factory()->create()->id, 'start_date' => today()->toDateString(), 'end_date' => today()->toDateString(),
                'address' => 'X', 'purpose' => 'Y',
            ])
            ->assertSessionHas('error');
    }

    public function test_double_booking_bersamaan_ditahan_oleh_transaksi(): void
    {
        $v = Vehicle::factory()->create();
        $service = app(BookingService::class);

        $pertama = $service->create($this->pegawai, $v, today()->toDateString(), today()->toDateString(), 'X', 'Y');

        $this->assertSame('dipinjam', $pertama->status);

        // User lain mencoba mobil sama rentang overlap → DomainException
        $lain = User::factory()->create(['seksi_id' => Seksi::factory()->create()->id]);

        try {
            $service->create($lain, $v, today()->toDateString(), today()->addDays(2)->toDateString(), 'X', 'Y');
            $this->fail('Seharusnya melempar DomainException (mobil tidak tersedia).');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('tidak lagi tersedia', $e->getMessage());
        }

        $this->assertSame(1, Booking::where('vehicle_id', $v->id)->count());
    }

    public function test_user_tanpa_seksi_ditolak_dengan_pesan_jelas(): void
    {
        $tanpaSeksi = User::factory()->create(['role' => 'pegawai', 'seksi_id' => null]);
        $v = Vehicle::factory()->create();

        $this->actingAs($tanpaSeksi)
            ->post('/pegawai/bookings', [
                'vehicle_id' => $v->id, 'start_date' => today()->toDateString(), 'end_date' => today()->toDateString(),
                'address' => 'X', 'purpose' => 'Y',
            ])
            ->assertSessionHas('error');
    }

    public function test_riwayat_menampilkan_badge_dan_penanda(): void
    {
        $lama = Vehicle::factory()->create();
        $baru = Vehicle::factory()->create();

        Booking::create([
            'user_id' => $this->pegawai->id,
            'vehicle_id' => $lama->id,
            'start_date' => today()->toDateString(),
            'end_date' => today()->toDateString(),
            'address' => 'X', 'purpose' => 'Y',
            'status' => 'dikembalikan',
            'returned_at' => now(),
            'auto_returned' => true,
        ]);
        Booking::create([
            'user_id' => $this->pegawai->id,
            'vehicle_id' => $baru->id,
            'original_vehicle_id' => $lama->id,
            'start_date' => today()->toDateString(),
            'end_date' => today()->toDateString(),
            'address' => 'X', 'purpose' => 'Y',
            'status' => 'dipinjam',
        ]);

        $this->actingAs($this->pegawai)
            ->get('/pegawai/bookings')
            ->assertOk()
            ->assertSee('Dikembalikan otomatis oleh sistem')
            ->assertSee('Diganti dari '.$lama->name)
            ->assertSee('Dipinjam');
    }

    public function test_admin_ditolak_dari_fitur_peminjaman(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/pegawai/search')->assertForbidden();
        $this->actingAs($admin)->get('/pegawai/bookings')->assertForbidden();
        $this->actingAs($admin)->post('/pegawai/bookings', [])->assertForbidden();
    }
}
