<?php

namespace Tests\Feature;

use App\Models\Seksi;
use App\Models\User;
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
}
