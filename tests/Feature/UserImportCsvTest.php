<?php

namespace Tests\Feature;

use App\Models\Bidang;
use App\Models\Seksi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Testing\File;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class UserImportCsvTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Bidang $bidangUmum;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->bidangUmum = Bidang::factory()->create(['name' => 'Bidang Umum']);
    }

    private function csv(array $rows): File
    {
        $lines = ['nama;email;password;bidang;seksi;role', ...$rows];

        return File::createWithContent('impor.csv', implode("\n", array_map(
            fn ($r) => is_array($r) ? implode(';', $r) : $r,
            $lines
        )));
    }

    public function test_template_csv_dapat_diunduh(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/users/template');

        $response->assertOk();
        $this->assertSame('text/csv; charset=UTF-8', $response->headers->get('Content-Type'));
    }

    public function test_pratinjau_memvalidasi_baris_valid_dan_invalid(): void
    {
        Seksi::factory()->create(['bidang_id' => $this->bidangUmum->id, 'name' => 'Seksi Kepegawaian']);

        $file = $this->csv([
            ['Budi Santoso', 'budi@karen.test', 'Password123', 'Bidang Umum', 'Seksi Kepegawaian', 'pegawai'], // valid
            ['Salah Seksi', 'salah@karen.test', 'Password123', 'Bidang Umum', 'Seksi Tidak Ada', 'pegawai'],  // seksi tidak cocok
            ['Pass Pendek', 'pendek@karen.test', 'abc', 'Bidang Umum', 'Seksi Kepegawaian', 'pegawai'],       // password < 8
        ]);

        $response = $this->actingAs($this->admin)
            ->post('/admin/users/import', ['file' => $file])
            ->assertRedirect(route('admin.users.import'));

        $preview = session('user_import_preview');
        $this->assertSame(3, $preview['total']);
        $this->assertSame(1, $preview['valid_count']);
        $this->assertSame(2, $preview['invalid_count']);

        // Tidak ada user yang dibuat pada tahap pratinjau (dry-run)
        $this->assertDatabaseMissing('users', ['email' => 'budi@karen.test']);

        // Halaman pratinjau menampilkan hasil per baris
        $this->actingAs($this->admin)
            ->get('/admin/users/import')
            ->assertOk()
            ->assertSee('Baris 2')
            ->assertSee('tidak terdaftar di bawah bidang');
    }

    public function test_header_salah_ditolak_dengan_pesan_jelas(): void
    {
        $file = File::createWithContent('salah.csv', "nama;email\nBudi;budi@x.test");

        $this->actingAs($this->admin)
            ->post('/admin/users/import', ['file' => $file]);

        $preview = session('user_import_preview');
        $this->assertSame(0, $preview['valid_count']);
        $this->assertStringContainsString('Header CSV tidak sesuai', $preview['rows'][0]['errors'][0]);
    }

    public function test_commit_hanya_membuat_baris_valid(): void
    {
        $seksi = Seksi::factory()->create(['bidang_id' => $this->bidangUmum->id, 'name' => 'Seksi Kepegawaian']);

        $file = $this->csv([
            ['Budi Santoso', 'budi@karen.test', 'Password123', 'Bidang Umum', 'Seksi Kepegawaian', ''],
            ['Salah Seksi', 'salah@karen.test', 'Password123', 'Bidang Umum', 'Seksi Kokoh', 'pegawai'],
        ]);

        $this->actingAs($this->admin)->post('/admin/users/import', ['file' => $file]);

        $response = $this->actingAs($this->admin)
            ->post('/admin/users/import/commit')
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'email' => 'budi@karen.test',
            'role' => 'pegawai', // role kosong → default pegawai
            'seksi_id' => $seksi->id,
        ]);
        $this->assertDatabaseMissing('users', ['email' => 'salah@karen.test']);

        // Session pratinjau dibersihkan setelah commit
        $this->assertNull(session('user_import_preview'));
    }

    public function test_impor_ulang_file_sama_semua_baris_gagal_karena_email_duplikat(): void
    {
        Seksi::factory()->create(['bidang_id' => $this->bidangUmum->id, 'name' => 'Seksi Kepegawaian']);

        $file = $this->csv([
            ['Budi Santoso', 'budi@karen.test', 'Password123', 'Bidang Umum', 'Seksi Kepegawaian', 'pegawai'],
        ]);

        $this->actingAs($this->admin)->post('/admin/users/import', ['file' => $file]);
        $this->actingAs($this->admin)->post('/admin/users/import/commit');

        // Unggah ulang file yang sama
        $file2 = $this->csv([
            ['Budi Santoso', 'budi@karen.test', 'Password123', 'Bidang Umum', 'Seksi Kepegawaian', 'pegawai'],
        ]);
        $this->actingAs($this->admin)->post('/admin/users/import', ['file' => $file2]);

        $preview = session('user_import_preview');
        $this->assertSame(0, $preview['valid_count']);
        $this->assertSame(1, $preview['invalid_count']);
        $this->assertStringContainsString('sudah terdaftar', implode(' ', $preview['rows'][0]['errors']));
    }

    public function test_email_duplikat_dalam_file_satu_file_ditandai(): void
    {
        Seksi::factory()->create(['bidang_id' => $this->bidangUmum->id, 'name' => 'Seksi Kepegawaian']);

        $file = $this->csv([
            ['Budi Satu', 'sama@karen.test', 'Password123', 'Bidang Umum', 'Seksi Kepegawaian', 'pegawai'],
            ['Budi Dua', 'sama@karen.test', 'Password123', 'Bidang Umum', 'Seksi Kepegawaian', 'pegawai'],
        ]);

        $this->actingAs($this->admin)->post('/admin/users/import', ['file' => $file]);

        $preview = session('user_import_preview');
        $this->assertSame(1, $preview['valid_count']);
        $this->assertSame(1, $preview['invalid_count']);
        $this->assertStringContainsString('duplikat di dalam file', implode(' ', $preview['rows'][1]['errors']));
    }

    public function test_laporan_baris_gagal_dapat_diunduh(): void
    {
        Seksi::factory()->create(['bidang_id' => $this->bidangUmum->id, 'name' => 'Seksi Kepegawaian']);

        $file = $this->csv([
            ['Budi Santoso', 'budi@karen.test', 'Password123', 'Bidang Umum', 'Seksi Kepegawaian', 'pegawai'],
            ['Salah Seksi', 'salah@karen.test', 'Password123', 'Bidang Umum', 'Seksi Kokoh', 'pegawai'],
        ]);

        $this->actingAs($this->admin)->post('/admin/users/import', ['file' => $file]);

        $response = $this->actingAs($this->admin)
            ->get('/admin/users/import/failed');

        $response->assertOk();
        $content = $response->streamedContent();
        $this->assertStringContainsString('alasan_gagal', $content);
        $this->assertStringContainsString('Salah Seksi', $content);
        $this->assertStringNotContainsString('budi@karen.test', $content);
    }

    public function test_non_admin_ditolak(): void
    {
        $user = User::factory()->create(['role' => 'pengurus']);

        $this->actingAs($user)->get('/admin/users/import')->assertForbidden();
        $this->actingAs($user)->post('/admin/users/import', [])->assertForbidden();
    }
}
