<?php

namespace Tests\Feature;

use App\Models\Bidang;
use App\Models\Seksi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BidangSeksiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_admin_dapat_melihat_halaman_organisasi(): void
    {
        Bidang::factory()->create(['name' => 'Bidang Contoh']);

        $this->actingAs($this->admin)
            ->get('/admin/bidang')
            ->assertOk()
            ->assertSee('Bidang Contoh')
            ->assertSee('Kuota');
    }

    public function test_admin_dapat_membuat_bidang_dengan_kuota(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/bidang', ['name' => 'Bidang Baru', 'max_active_bookings' => 3])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('bidang', ['name' => 'Bidang Baru', 'max_active_bookings' => 3]);
    }

    public function test_kuota_bidang_validasi_1_sampai_5(): void
    {
        foreach ([0, 6] as $invalid) {
            $this->actingAs($this->admin)
                ->post('/admin/bidang', ['name' => 'Bidang X', 'max_active_bookings' => $invalid])
                ->assertSessionHasErrors('max_active_bookings');
        }
    }

    public function test_admin_dapat_mengubah_nama_dan_kuota_bidang(): void
    {
        $bidang = Bidang::factory()->create(['name' => 'Bidang Lama', 'max_active_bookings' => 2]);

        $this->actingAs($this->admin)
            ->put("/admin/bidang/{$bidang->id}", ['name' => 'Bidang BaruNama', 'max_active_bookings' => 3])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('bidang', ['id' => $bidang->id, 'name' => 'Bidang BaruNama', 'max_active_bookings' => 3]);
    }

    public function test_bidang_dengan_seksi_tidak_bisa_dihapus(): void
    {
        $bidang = Bidang::factory()->create();
        Seksi::factory()->create(['bidang_id' => $bidang->id]);

        $this->actingAs($this->admin)
            ->delete("/admin/bidang/{$bidang->id}")
            ->assertSessionHas('error');

        $this->assertDatabaseHas('bidang', ['id' => $bidang->id]);
    }

    public function test_admin_dapat_menambah_dan_mengubah_seksi(): void
    {
        $bidang = Bidang::factory()->create();

        $this->actingAs($this->admin)
            ->post("/admin/bidang/{$bidang->id}/seksi", ['name' => 'Seksi Baru'])
            ->assertSessionHasNoErrors();

        $seksi = Seksi::where('name', 'Seksi Baru')->first();
        $this->assertNotNull($seksi);

        $this->actingAs($this->admin)
            ->put("/admin/seksi/{$seksi->id}", ['name' => 'Seksi Renamed'])
            ->assertSessionHasNoErrors();

        $this->assertSame('Seksi Renamed', $seksi->refresh()->name);
    }

    public function test_nama_seksi_unik_dalam_satu_bidang(): void
    {
        $bidang = Bidang::factory()->create();
        Seksi::factory()->create(['bidang_id' => $bidang->id, 'name' => 'Seksi Sama']);

        $this->actingAs($this->admin)
            ->post("/admin/bidang/{$bidang->id}/seksi", ['name' => 'Seksi Sama'])
            ->assertSessionHasErrors('name');
    }

    public function test_seksi_dengan_anggota_tidak_bisa_dihapus(): void
    {
        $seksi = Seksi::factory()->create();
        User::factory()->create(['seksi_id' => $seksi->id]);

        $this->actingAs($this->admin)
            ->delete("/admin/seksi/{$seksi->id}")
            ->assertSessionHas('error');

        $this->assertDatabaseHas('seksi', ['id' => $seksi->id]);
    }

    public function test_non_admin_ditolak_mengakses_organisasi(): void
    {
        $user = User::factory()->create(['role' => 'pengurus']);

        $this->actingAs($user)->get('/admin/bidang')->assertForbidden();
    }
}
