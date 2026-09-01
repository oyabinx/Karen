<?php

namespace Tests\Feature;

use App\Models\Seksi;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Alur end-to-end SUNGGUHAN melalui HTTP: login form → dashboard →
 * cari mobil → form booking → simpan → tombol Selesai + keluhan →
 * verifikasi — untuk pegawai dan pengurus (taskplan Fase 9).
 */
class EndToEndFlowTest extends TestCase
{
    use RefreshDatabase;

    private function loginViaForm(User $user): void
    {
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect('/dashboard');
    }

    private function alurLengkap(User $peminjam): void
    {
        $vehicle = Vehicle::factory()->create(['name' => 'E2E Avanza']);

        // Login via form HTTP sungguhan
        $this->loginViaForm($peminjam);

        // Dashboard menampilkan kartu kuota
        $this->get('/dashboard')->assertOk()->assertSee('Kuota Peminjaman');

        // Cari mobil pada rentang valid
        $mulai = today()->addDays(2)->toDateString();
        $selesai = today()->addDays(4)->toDateString();

        $this->get("/pegawai/search?start_date={$mulai}&end_date={$selesai}")
            ->assertOk()
            ->assertSee('E2E Avanza')
            ->assertSee('Pinjam Mobil Ini');

        // Buka form booking lalu simpan
        $this->get("/pegawai/bookings/create?vehicle_id={$vehicle->id}&start_date={$mulai}&end_date={$selesai}")
            ->assertOk()
            ->assertSee('Form Peminjaman');

        $this->post('/pegawai/bookings', [
            'vehicle_id' => $vehicle->id,
            'start_date' => $mulai,
            'end_date' => $selesai,
            'address' => 'Gedung E2E',
            'purpose' => 'Rapat ujung-ke-ujung',
        ])->assertRedirect('/pegawai/bookings');

        // Riwayat menampilkan booking aktif + tombol Selesai
        $this->get('/pegawai/bookings')
            ->assertOk()
            ->assertSee('Rapat ujung-ke-ujung')
            ->assertSee('Selesai — Kembalikan Mobil');

        // Arahkan booking ke hari ini supaya bisa diselesaikan
        $booking = $peminjam->bookings()->first();
        $booking->update([
            'start_date' => today()->toDateString(),
            'end_date' => today()->toDateString(),
        ]);

        // Tombol Selesai + keluhan (sesi HTTP asli)
        $this->post("/pegawai/returns/{$booking->id}", [
            'complaint' => 'Spion kanan goyang',
        ])->assertSessionHasNoErrors();

        $booking->refresh();
        $this->assertSame('dikembalikan', $booking->status);
        $this->assertSame('Spion kanan goyang', $booking->complaint->message);

        // Kesepakatan Fase 6: mobil TETAP bisa dipinjam, kondisi baik
        $vehicle->refresh();
        $this->assertSame('baik', $vehicle->condition);
        $this->assertSame('bisa_dipinjam', $vehicle->status);

        // Logout via form
        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_alur_lengkap_pegawai(): void
    {
        $this->alurLengkap(User::factory()->create([
            'role' => 'pegawai',
            'seksi_id' => Seksi::factory()->create()->id,
        ]));
    }

    public function test_alur_lengkap_pengurus(): void
    {
        $this->alurLengkap(User::factory()->create([
            'role' => 'pengurus',
            'seksi_id' => Seksi::factory()->create()->id,
        ]));
    }

    /**
     * Pengecualian kuota oleh event — eksplisit (docs kuota_bidang.md):
     * event memakai banyak unit, jumlah booking aktif bidang TIDAK
     * bertambah → sisa kuota tidak berubah.
     */
    public function test_event_tidak_mengonsumsi_kuota_bidang(): void
    {
        $seksi = Seksi::factory()->create();
        $peminjam = User::factory()->create(['role' => 'pegawai', 'seksi_id' => $seksi->id]);
        $service = app(\App\Services\BookingService::class);

        $sebelum = $service->quotaInfo($peminjam);

        // Event 3 unit oleh bidang yang sama (di atas kuota sekalipun)
        $pengurus = User::factory()->create(['role' => 'pengurus', 'seksi_id' => $seksi->id]);
        $event = \App\Models\Event::create([
            'name' => 'Event Kuota',
            'bidang_id' => $seksi->bidang_id,
            'start_date' => today()->addDays(10)->toDateString(),
            'end_date' => today()->addDays(12)->toDateString(),
            'created_by' => $pengurus->id,
        ]);
        $event->vehicles()->attach(Vehicle::factory()->count(3)->create()->pluck('id'));

        $sesudah = $service->quotaInfo($peminjam);

        $this->assertSame($sebelum['used'], $sesudah['used'], 'Event tidak boleh menambah pemakaian kuota.');
        $this->assertSame($sebelum['remaining'], $sesudah['remaining']);
    }
}
