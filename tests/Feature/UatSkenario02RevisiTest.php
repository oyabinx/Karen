<?php

namespace Tests\Feature;

use App\Models\Bidang;
use App\Models\Seksi;
use App\Models\User;
use App\Models\VehicleBudget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Testing\File;
use Tests\TestCase;

/**
 * Revisi hasil UAT Skenario 02 (file UAT/02-admin-manajemen.md).
 */
class UatSkenario02RevisiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    /**
     * B3 — password mengandung spasi ditolak di SEMUA jalur:
     * tambah user (admin), ubah user, ganti sandi profil, dan impor CSV.
     */
    public function test_b3_password_spasi_ditolak_di_semua_jalur(): void
    {
        $seksi = Seksi::factory()->create();

        // Jalur 1: admin tambah user
        $this->actingAs($this->admin)
            ->post('/admin/users', [
                'name' => 'Spasi',
                'email' => 'spasi@karen.test',
                'password' => 'rahasia 123', // ada spasi
                'role' => 'pegawai',
                'seksi_id' => $seksi->id,
            ])
            ->assertSessionHasErrors('password');

        // Jalur 2: admin ubah user
        $target = User::factory()->create(['role' => 'pegawai', 'seksi_id' => $seksi->id]);
        $this->actingAs($this->admin)
            ->put("/admin/users/{$target->id}", [
                'name' => $target->name,
                'email' => $target->email,
                'password' => 'abc def gh', // ada spasi
                'role' => 'pegawai',
                'seksi_id' => $seksi->id,
            ])
            ->assertSessionHasErrors('password');

        // Jalur 3: ganti sandi sendiri (profil)
        $user = User::factory()->create(['role' => 'pegawai', 'phone' => '081233344455']);
        $this->actingAs($user)
            ->from('/profile')
            ->put('/password', [
                'current_password' => 'password',
                'password' => 'baru banget', // ada spasi
                'password_confirmation' => 'baru banget',
            ])
            ->assertSessionHasErrorsIn('updatePassword', 'password');

        // Jalur 4: impor CSV — baris ber-password spasi ditandai gagal
        Bidang::factory()->create(['name' => 'Bidang Umum']);
        Seksi::factory()->create(['bidang_id' => Bidang::where('name', 'Bidang Umum')->first()->id, 'name' => 'Seksi Kepegawaian']);

        $file = File::createWithContent('impor.csv', "nama;email;password;bidang;seksi;role\nBudi;budi@karen.test;rahasia 123;Bidang Umum;Seksi Kepegawaian;pegawai");
        $this->actingAs($this->admin)
            ->post('/admin/users/import', ['file' => $file]);

        $preview = session('user_import_preview');
        $this->assertSame(0, $preview['valid_count']);
        $this->assertStringContainsString('tidak boleh mengandung spasi', implode(' ', $preview['rows'][0]['errors']));

        // Kontrol positif: tanpa spasi tetap diterima
        $this->actingAs($this->admin)
            ->post('/admin/users', [
                'name' => 'Aman',
                'email' => 'aman@karen.test',
                'password' => 'rahasia123',
                'role' => 'pegawai',
                'seksi_id' => $seksi->id,
            ])
            ->assertSessionHasNoErrors();
    }

    /**
     * C2 — kuota bidang: rentang baru 1–9 (0 & 10 ditolak, 9 diterima).
     */
    public function test_c2_kuota_bidang_rentang_1_sampai_9(): void
    {
        foreach ([0, 10] as $invalid) {
            $this->actingAs($this->admin)
                ->post('/admin/bidang', ['name' => 'Bidang X'.$invalid, 'max_active_bookings' => $invalid])
                ->assertSessionHasErrors('max_active_bookings');
        }

        $this->actingAs($this->admin)
            ->post('/admin/bidang', ['name' => 'Bidang Sembilan', 'max_active_bookings' => 9])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('bidang', ['name' => 'Bidang Sembilan', 'max_active_bookings' => 9]);
    }

    /**
     * D4 — Test Koneksi TANPA kunci tidak boleh 500; langkah 1 gagal
     * dengan pesan "belum diunggah" dan hasil per langkah tampil.
     */
    public function test_d4_test_koneksi_tanpa_kunci_tidak_error(): void
    {
        $response = $this->actingAs($this->admin)
            ->post('/admin/integrasi/google/test');

        $response->assertRedirect()->assertSessionHasNoErrors(); // bukan 500

        $steps = session('integration_test_steps');
        $this->assertNotNull($steps);
        $this->assertFalse($steps[0]['ok']);
        $this->assertStringContainsString('Belum diunggah', $steps[0]['pesan']);
    }

    /** B1 — halaman impor memuat tombol pilih file & area nama file baru. */
    public function test_b1_halaman_impor_memuat_tombol_dan_area_nama_file(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/users/import')
            ->assertOk()
            ->assertSee('Pilih File CSV')
            ->assertSee('belum memilih file…');
    }

    /** C1 — halaman bidang: semua kartu collapse (tidak ada yang open). */
    public function test_c1_halaman_bidang_semua_collapse(): void
    {
        Bidang::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)
            ->get('/admin/bidang')
            ->assertOk();

        $html = $response->getContent();
        $this->assertStringNotContainsString('<details class="bg-white rounded-xl border border-gray-200 overflow-hidden" open', $html);
        $this->assertSame(3, substr_count($html, '<details'));
    }
}
