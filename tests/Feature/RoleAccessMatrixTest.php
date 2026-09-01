<?php

namespace Tests\Feature;

use App\Models\Seksi;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Matriks akses lintas role — otomatisasi "uji manual" taskplan
 * Fase 9: setiap role × perwakilan rute tiap kelompok fitur.
 */
class RoleAccessMatrixTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('ruteProvider')]
    public function test_matriks_akses(string $path, array $harapan): void
    {
        foreach (['admin', 'pengurus', 'pegawai'] as $role) {
            $user = User::factory()->create([
                'role' => $role,
                'seksi_id' => Seksi::factory()->create()->id,
            ]);

            $respon = $this->actingAs($user)->get($path);

            $diharapkan = $harapan[$role];
            $diharapkan === 200
                ? $respon->assertOk()
                : $respon->assertForbidden();

            $this->post('/logout'); // isolasi antar iterasi
        }
    }

    public static function ruteProvider(): array
    {
        return [
            // [path, [admin => kode, pengurus => kode, pegawai => kode]]
            'dashboard umum' => ['/dashboard', ['admin' => 200, 'pengurus' => 200, 'pegawai' => 200]],
            'profil umum' => ['/profile', ['admin' => 200, 'pengurus' => 200, 'pegawai' => 200]],

            'admin users' => ['/admin/users', ['admin' => 200, 'pengurus' => 403, 'pegawai' => 403]],
            'admin organisasi' => ['/admin/bidang', ['admin' => 200, 'pengurus' => 403, 'pegawai' => 403]],
            'admin integrasi google' => ['/admin/integrasi/google', ['admin' => 200, 'pengurus' => 403, 'pegawai' => 403]],

            'pengurus kendaraan' => ['/pengurus/vehicles', ['admin' => 403, 'pengurus' => 200, 'pegawai' => 403]],
            'pengurus maintenance' => ['/pengurus/maintenances', ['admin' => 403, 'pengurus' => 200, 'pegawai' => 403]],
            'pengurus penggantian' => ['/pengurus/replacements', ['admin' => 403, 'pengurus' => 200, 'pegawai' => 403]],
            'pengurus dokumen' => ['/pengurus/documents', ['admin' => 403, 'pengurus' => 200, 'pegawai' => 403]],
            'pengurus keluhan' => ['/pengurus/complaints', ['admin' => 403, 'pengurus' => 200, 'pegawai' => 403]],
            'pengurus monitoring' => ['/pengurus/bookings', ['admin' => 403, 'pengurus' => 200, 'pegawai' => 403]],
            'pengurus laporan' => ['/pengurus/reports', ['admin' => 403, 'pengurus' => 200, 'pegawai' => 403]],

            'event armada (admin+pengurus)' => ['/pengurus/events', ['admin' => 200, 'pengurus' => 200, 'pegawai' => 403]],

            'peminjaman cari (pegawai+pengurus)' => ['/pegawai/search', ['admin' => 403, 'pengurus' => 200, 'pegawai' => 200]],
            'peminjaman riwayat' => ['/pegawai/bookings', ['admin' => 403, 'pengurus' => 200, 'pegawai' => 200]],
        ];
    }

    /**
     * GET dengan ID dinamis (butuh data) — diperiksa terpisah.
     */
    public function test_matriks_rute_berparameter(): void
    {
        $vehicle = Vehicle::factory()->create();

        $kasus = [
            ["/pengurus/vehicles/{$vehicle->id}/budgets", ['admin' => 403, 'pengurus' => 200, 'pegawai' => 403]],
            ["/pegawai/bookings/create?vehicle_id={$vehicle->id}&start_date=".today()->toDateString()."&end_date=".today()->toDateString(), ['admin' => 403, 'pengurus' => 200, 'pegawai' => 200]],
        ];

        foreach ($kasus as [$path, $harapan]) {
            foreach (['admin', 'pengurus', 'pegawai'] as $role) {
                $user = User::factory()->create(['role' => $role, 'seksi_id' => Seksi::factory()->create()->id]);
                $respon = $this->actingAs($user)->get($path);
                $harapan[$role] === 200 ? $respon->assertOk() : $respon->assertForbidden();
                $this->post('/logout');
            }
        }
    }
}
