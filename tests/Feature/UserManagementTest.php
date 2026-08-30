<?php

namespace Tests\Feature;

use App\Models\Seksi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_admin_dapat_melihat_daftar_user(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/users')
            ->assertOk()
            ->assertSee('Manajemen User');
    }

    public function test_admin_dapat_membuat_user_pegawai_dengan_seksi(): void
    {
        $seksi = Seksi::factory()->create();

        $response = $this->actingAs($this->admin)->post('/admin/users', [
            'name' => 'Pegawai Baru',
            'email' => 'baru@karen.test',
            'password' => 'rahasia123',
            'role' => 'pegawai',
            'seksi_id' => $seksi->id,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['email' => 'baru@karen.test', 'role' => 'pegawai', 'seksi_id' => $seksi->id]);
    }

    public function test_seksi_wajib_untuk_pegawai(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/users', [
                'name' => 'Tanpa Seksi',
                'email' => 'tanpaseksi@karen.test',
                'password' => 'rahasia123',
                'role' => 'pegawai',
                'seksi_id' => null,
            ])
            ->assertSessionHasErrors('seksi_id');
    }

    public function test_seksi_wajib_untuk_pengurus_opsional_untuk_admin(): void
    {
        // pengurus tanpa seksi → ditolak
        $this->actingAs($this->admin)
            ->post('/admin/users', [
                'name' => 'Pengurus X',
                'email' => 'pengurusx@karen.test',
                'password' => 'rahasia123',
                'role' => 'pengurus',
                'seksi_id' => null,
            ])
            ->assertSessionHasErrors('seksi_id');

        // admin tanpa seksi → diterima
        $this->actingAs($this->admin)
            ->post('/admin/users', [
                'name' => 'Admin Kedua',
                'email' => 'admin2@karen.test',
                'password' => 'rahasia123',
                'role' => 'admin',
                'seksi_id' => null,
            ])
            ->assertSessionHasNoErrors();
    }

    public function test_email_duplikat_ditolak(): void
    {
        $existing = User::factory()->create();

        $this->actingAs($this->admin)
            ->post('/admin/users', [
                'name' => 'Duplikat',
                'email' => $existing->email,
                'password' => 'rahasia123',
                'role' => 'pegawai',
                'seksi_id' => Seksi::factory()->create()->id,
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_admin_dapat_mengubah_user_termasuk_role_dan_seksi(): void
    {
        $seksi = Seksi::factory()->create();
        $user = User::factory()->create(['role' => 'pegawai', 'seksi_id' => $seksi->id]);

        $this->actingAs($this->admin)
            ->put("/admin/users/{$user->id}", [
                'name' => $user->name,
                'email' => $user->email,
                'password' => '',
                'role' => 'pengurus',
                'seksi_id' => $seksi->id,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('pengurus', $user->refresh()->role);
        // Password kosong = tidak berubah
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('password', $user->refresh()->password));
    }

    public function test_nonaktifkan_user_tidak_menghapus_riwayat_dan_bisa_diatifkan_kembali(): void
    {
        $user = User::factory()->create(['role' => 'pegawai']);

        $this->actingAs($this->admin)
            ->delete("/admin/users/{$user->id}")
            ->assertSessionHasNoErrors();

        $this->assertSoftDeleted($user);
        $this->assertNotNull(User::withTrashed()->find($user->id));

        $this->actingAs($this->admin)
            ->patch("/admin/users/{$user->id}/restore")
            ->assertSessionHasNoErrors();

        $this->assertNotSoftDeleted($user->refresh());
    }

    public function test_admin_tidak_bisa_menonaktifkan_akun_sendiri(): void
    {
        $this->actingAs($this->admin)
            ->delete('/admin/users/'.$this->admin->id)
            ->assertSessionHas('error');

        $this->assertNotSoftDeleted($this->admin->refresh());
    }

    public function test_pengurus_dan_pegawai_ditolak_mengakses_manajemen_user(): void
    {
        foreach (['pengurus', 'pegawai'] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)->get('/admin/users')->assertForbidden();
            $this->actingAs($user)->post('/admin/users', [])->assertForbidden();
        }
    }

    public function test_pencarian_dan_filter_berfungsi(): void
    {
        $bidang = \App\Models\Bidang::factory()->create();
        $seksi = Seksi::factory()->create(['bidang_id' => $bidang->id]);
        User::factory()->create(['name' => 'Zainal Abidin', 'role' => 'pegawai', 'seksi_id' => $seksi->id]);
        User::factory()->create(['name' => 'Maria Ulfa', 'role' => 'pengurus', 'seksi_id' => $seksi->id]);

        $this->actingAs($this->admin)
            ->get('/admin/users?q=zainal')
            ->assertOk()
            ->assertSee('Zainal Abidin')
            ->assertDontSee('Maria Ulfa');

        $this->actingAs($this->admin)
            ->get('/admin/users?role=pengurus')
            ->assertOk()
            ->assertSee('Maria Ulfa')
            ->assertDontSee('Zainal Abidin');
    }
}
