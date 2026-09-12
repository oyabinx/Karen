<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Event;
use App\Models\Maintenance;
use App\Models\Seksi;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_tamu_dialihkan_ke_halaman_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_admin_melihat_dashboard_admin(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Administrator', false)
            ->assertSee('manajemen pengguna', false);
    }

    public function test_pengurus_melihat_dashboard_pengurus(): void
    {
        $user = User::factory()->create(['role' => 'pengurus']);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Pengurus', false)
            ->assertSee('monitoring & anggaran', false);
    }

    public function test_pegawai_melihat_dashboard_pegawai_dengan_penempatan(): void
    {
        $seksi = Seksi::factory()->create();
        $user = User::factory()->create(['role' => 'pegawai', 'seksi_id' => $seksi->id]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Pegawai', false)
            ->assertSee($seksi->name);
    }

    public function test_banner_pengingat_nomor_hp_muncul_bila_kosong(): void
    {
        $user = User::factory()->create(['role' => 'pegawai', 'phone' => null]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Nomor HP belum diisi', false);
    }

    public function test_banner_pengingat_tidak_muncul_bila_nomor_hp_terisi(): void
    {
        $user = User::factory()->create(['role' => 'pegawai', 'phone' => '081234567890']);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertDontSee('Nomor HP belum diisi', false);
    }

    /**
     * Panel "Armada Hari Ini" tampil di dashboard SEMUA role: menjawab
     * "mobil X hari ini siapa yang pakai?" lengkap dengan kontak
     * peminjam, plus unit di bengkel / armada event (dashboard.md).
     */
    public function test_dashboard_semua_role_menampilkan_armada_hari_ini(): void
    {
        $peminjam = User::factory()->create([
            'seksi_id' => Seksi::factory()->create()->id,
            'phone' => '08123456789',
        ]);
        Booking::create([
            'user_id' => $peminjam->id,
            'vehicle_id' => Vehicle::factory()->create(['name' => 'Avanza Hari Ini'])->id,
            'start_date' => today()->toDateString(),
            'end_date' => today()->toDateString(),
            'address' => 'Kantor B', 'purpose' => 'Rapat',
        ]);
        Maintenance::create([
            'vehicle_id' => Vehicle::factory()->create(['name' => 'Hiace Bengkel'])->id,
            'start_date' => today()->toDateString(),
            'end_date' => today()->toDateString(),
            'note' => 'Servis rutin',
        ]);
        $event = Event::create([
            'name' => 'Rapat Koordinasi',
            'bidang_id' => \App\Models\Bidang::factory()->create()->id,
            'start_date' => today()->toDateString(),
            'end_date' => today()->toDateString(),
            'created_by' => User::factory()->create(['role' => 'pengurus'])->id,
        ]);
        $event->vehicles()->attach(Vehicle::factory()->create(['name' => 'Brio Event']));

        foreach (['admin', 'pengurus', 'pegawai'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->get('/dashboard')
                ->assertOk()
                ->assertSee('Armada Hari Ini')
                ->assertSee('Avanza Hari Ini')
                ->assertSee($peminjam->name)
                // Nomor HP tampil dalam bentuk baku (62…) sebagai kontak langsung
                ->assertSee('628123456789')
                ->assertSee('Hiace Bengkel')
                ->assertSee('di bengkel')
                ->assertSee('Brio Event')
                ->assertSee('Rapat Koordinasi');
        }
    }

    public function test_dashboard_armada_hari_ini_kosong_bila_tidak_ada_aktivitas(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'pegawai']))
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Tidak ada armada yang dipakai hari ini', false);
    }
}
