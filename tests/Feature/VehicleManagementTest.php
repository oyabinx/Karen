<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Testing\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VehicleManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $pengurus;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pengurus = User::factory()->create(['role' => 'pengurus']);
    }

    public function test_pengurus_dapat_melihat_dan_menambah_kendaraan(): void
    {
        $this->actingAs($this->pengurus)->get('/pengurus/vehicles')->assertOk()->assertSee('Data Kendaraan');

        $response = $this->actingAs($this->pengurus)->post('/pengurus/vehicles', [
            'name' => 'Avanza B 1234 XYZ',
            'plate_number' => 'B 1234 XYZ',
            'year' => 2020,
            'capacity' => 7,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('vehicles', ['plate_number' => 'B 1234 XYZ', 'year' => 2020]);
    }

    public function test_validasi_tahun_dan_plat(): void
    {
        // Tahun di luar rentang → ditolak
        $this->actingAs($this->pengurus)
            ->post('/pengurus/vehicles', [
                'name' => 'X', 'plate_number' => 'B 1 A', 'year' => 1975, 'capacity' => 4,
            ])
            ->assertSessionHasErrors('year');

        // Plat duplikat → ditolak
        Vehicle::factory()->create(['plate_number' => 'B 9999 ZZ']);

        $this->actingAs($this->pengurus)
            ->post('/pengurus/vehicles', [
                'name' => 'X', 'plate_number' => 'B 9999 ZZ', 'year' => 2020, 'capacity' => 4,
            ])
            ->assertSessionHasErrors('plate_number');
    }

    public function test_upload_foto_tersimpan_dan_mengganti_foto_lama(): void
    {
        Storage::fake('public');

        $v = Vehicle::factory()->create(['photo_path' => 'vehicles/lama.jpg']);
        Storage::disk('public')->put('vehicles/lama.jpg', 'x');

        $foto = File::image('mobil.jpg', 640, 360);

        $this->actingAs($this->pengurus)
            ->put("/pengurus/vehicles/{$v->id}", [
                'name' => $v->name,
                'plate_number' => $v->plate_number,
                'year' => $v->year,
                'capacity' => $v->capacity,
                'photo' => $foto,
            ])
            ->assertSessionHasNoErrors();

        $v->refresh();
        $this->assertNotSame('vehicles/lama.jpg', $v->photo_path);
        Storage::disk('public')->assertExists($v->photo_path);
        Storage::disk('public')->assertMissing('vehicles/lama.jpg');
    }

    public function test_toggle_status_dan_set_kondisi_baik(): void
    {
        $v = Vehicle::factory()->unavailable()->needsInspection()->create();

        $this->actingAs($this->pengurus)
            ->patch("/pengurus/vehicles/{$v->id}/status")
            ->assertSessionHasNoErrors();
        $this->assertSame('bisa_dipinjam', $v->refresh()->status);

        $this->actingAs($this->pengurus)
            ->patch("/pengurus/vehicles/{$v->id}/condition")
            ->assertSessionHasNoErrors();
        $this->assertSame('baik', $v->refresh()->condition);
    }

    public function test_hapus_kendaraan_soft_delete_riwayat_utuh(): void
    {
        $v = Vehicle::factory()->create();

        $this->actingAs($this->pengurus)
            ->delete("/pengurus/vehicles/{$v->id}")
            ->assertSessionHasNoErrors();

        $this->assertSoftDeleted($v);
    }

    public function test_admin_dan_pegawai_ditolak(): void
    {
        foreach (['admin', 'pegawai'] as $role) {
            $u = User::factory()->create(['role' => $role]);

            $this->actingAs($u)->get('/pengurus/vehicles')->assertForbidden();
            $this->actingAs($u)->post('/pengurus/vehicles', [])->assertForbidden();
        }
    }
}
