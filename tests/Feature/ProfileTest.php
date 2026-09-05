<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_profil_dapat_ditampilkan(): void
    {
        $user = User::factory()->create(['phone' => '081234567890']);

        $response = $this->actingAs($user)->get('/profile');

        $response->assertOk();
        $response->assertSee('Informasi Profil');
        $response->assertSee('Nomor HP (wajib)');
    }

    public function test_profil_dapat_diperbarui_dengan_nomor_hp_valid(): void
    {
        $user = User::factory()->create(['phone' => '081234567890']);

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Nama Baru',
                'email' => 'baru@karen.test',
                'phone' => '081298765432',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Nama Baru', $user->name);
        $this->assertSame('baru@karen.test', $user->email);
        // Nomor HP tersimpan dalam bentuk baku 62… (normalisasi UAT B4)
        $this->assertSame('6281298765432', $user->phone);
        $this->assertNull($user->email_verified_at);
    }

    public function test_profil_tidak_bisa_disimpan_tanpa_nomor_hp(): void
    {
        $user = User::factory()->create(['phone' => '081234567890']);

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => '',
            ]);

        $response->assertSessionHasErrors('phone');
        $this->assertSame('6281234567890', $user->refresh()->phone); // baku 62…
    }

    public function test_nomor_hp_format_tidak_valid_ditolak(): void
    {
        $user = User::factory()->create(['phone' => '081234567890']);

        foreach (['12345', '0812345', 'halo', '081234567890123456'] as $invalid) {
            $this
                ->actingAs($user)
                ->patch('/profile', [
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $invalid,
                ])
                ->assertSessionHasErrors('phone');
        }
    }

    public function test_nomor_hp_tidak_boleh_duplikat_antar_user(): void
    {
        User::factory()->create(['phone' => '081211122233']);
        $user = User::factory()->create(['phone' => '081244455566']);

        $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => '081211122233',
            ])
            ->assertSessionHasErrors('phone');
    }

    public function test_nomor_hp_boleh_mempertahankan_nilai_sendiri(): void
    {
        $user = User::factory()->create(['phone' => '081233344455']);

        $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Nama Diubah',
                'email' => $user->email,
                'phone' => '081233344455',
            ])
            ->assertSessionHasNoErrors();
    }

    public function test_email_tidak_boleh_duplikat(): void
    {
        $lain = User::factory()->create();
        $user = User::factory()->create(['phone' => '081255566677']);

        $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $lain->email,
                'phone' => $user->phone,
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_kata_sandi_dapat_diubah(): void
    {
        $user = User::factory()->create(['phone' => '081277788899']);

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->put('/password', [
                'current_password' => 'password',
                'password' => 'kata-sandi-baru',
                'password_confirmation' => 'kata-sandi-baru',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertTrue(
            $this->post('/login', [
                'email' => $user->email,
                'password' => 'kata-sandi-baru',
            ])->assertRedirect()->isRedirection()
        );
    }

    public function test_tidak_ada_fitur_hapus_akun_mandiri(): void
    {
        $user = User::factory()->create(['phone' => '081299900011']);

        // Route DELETE /profile dihapus — akun hanya dinonaktifkan admin
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('profile.destroy'));

        $this
            ->actingAs($user)
            ->delete('/profile', ['password' => 'password'])
            ->assertStatus(405);

        $this->assertNotNull($user->fresh());
    }
}
