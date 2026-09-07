<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Seksi;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\Google\GoogleSettings;
use App\Services\ReturnService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Keputusan user pasca-UAT 03 (tindak lanjut):
 * (1) admin membuka Keluhan/Monitoring/Laporan;
 * (2) LOG AKTIVITAS — "siapa mengubah apa, kapan" (pengelolaan
 *     kendaraan dipegang lebih dari satu akun pengurus).
 */
class AdminAccessDanLogAktivitasTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_membuka_keluhan_monitoring_dan_laporan(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/pengurus/complaints')->assertOk();
        $this->actingAs($admin)->get('/pengurus/bookings')->assertOk();
        $this->actingAs($admin)->get('/pengurus/reports')->assertOk();

        $pegawai = User::factory()->create(['role' => 'pegawai']);
        $this->actingAs($pegawai)->get('/pengurus/complaints')->assertForbidden();
    }

    public function test_log_mencatat_siapa_mengubah_apa_kapan(): void
    {
        $pengurusA = User::factory()->create(['role' => 'pengurus', 'name' => 'Pengurus A']);
        $admin = User::factory()->create(['role' => 'admin', 'name' => 'Si Admin']);

        // Pengurus A menambah kendaraan
        $this->actingAs($pengurusA)
            ->post('/pengurus/vehicles', [
                'name' => 'Log Tester', 'plate_number' => 'B 777 LOG', 'year' => 2024, 'capacity' => 5,
            ])->assertSessionHasNoErrors();

        $created = ActivityLog::where('action', 'created')->where('model_type', Vehicle::class)->first();
        $this->assertNotNull($created);
        $this->assertSame($pengurusA->id, $created->user_id);
        $this->assertStringContainsString('Menambah Kendaraan', $created->description);
        $this->assertStringContainsString('Log Tester', $created->model_label);

        // Admin mengubah status kendaraan itu
        $v = Vehicle::where('plate_number', 'B 777 LOG')->first();
        $this->actingAs($admin)
            ->patch("/pengurus/vehicles/{$v->id}/status")
            ->assertSessionHasNoErrors();

        $updated = ActivityLog::where('action', 'updated')->where('model_type', Vehicle::class)->latest('id')->first();
        $this->assertSame($admin->id, $updated->user_id);
        $this->assertArrayHasKey('status', $updated->changes);
        $this->assertSame('bisa_dipinjam', $updated->changes['status']['lama']);
        $this->assertSame('tidak_bisa_dipinjam', $updated->changes['status']['baru']);

        // Hapus → log deleted (pelaku tetap tercatat)
        $this->actingAs($pengurusA)->delete("/pengurus/vehicles/{$v->id}");
        $this->assertSame('deleted', ActivityLog::where('model_type', Vehicle::class)->latest('id')->first()->action);

        // Halaman log (admin) menampilkan kedua pelaku; pegawai 403
        $this->actingAs($admin)
            ->get('/admin/activity-logs')
            ->assertOk()
            ->assertSee('Pengurus A')
            ->assertSee('Si Admin')
            ->assertSee('Menambah Kendaraan');

        $this->actingAs(User::factory()->create(['role' => 'pegawai']))
            ->get('/admin/activity-logs')->assertForbidden();
    }

    public function test_filter_log_berfungsi(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'pengurus']);

        $this->actingAs($user)->post('/pengurus/vehicles', [
            'name' => 'Filter Alpha', 'plate_number' => 'B 101 AAA', 'year' => 2024, 'capacity' => 5,
        ]);
        $this->actingAs($admin)->post('/pengurus/vehicles', [
            'name' => 'Filter Beta', 'plate_number' => 'B 202 BBB', 'year' => 2023, 'capacity' => 7,
        ]);

        // Filter pelaku = admin saja
        $this->actingAs($admin)
            ->get('/admin/activity-logs?user='.$admin->id)
            ->assertOk()
            ->assertSee('Filter Beta')
            ->assertDontSee('Filter Alpha');

        // Filter aksi created
        $this->actingAs($admin)
            ->get('/admin/activity-logs?action=created&q=Filter Alpha')
            ->assertOk()
            ->assertSee('Filter Alpha');
    }

    public function test_field_sensitif_tidak_pernah_tercatat(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Password user — ubah nama+password bersamaan: log tercatat
        // untuk nama, TANPA memuat password
        $target = User::factory()->create(['role' => 'pegawai', 'seksi_id' => Seksi::factory()->create()->id]);
        $this->actingAs($admin)
            ->put("/admin/users/{$target->id}", [
                'name' => 'Nama Baru Sekali', 'email' => $target->email,
                'password' => 'rahasiabaru', 'role' => 'pegawai', 'seksi_id' => $target->seksi_id,
            ])->assertSessionHasNoErrors();

        $log = ActivityLog::where('model_type', User::class)->where('model_id', $target->id)->where('action', 'updated')->first();
        $this->assertNotNull($log);
        $this->assertArrayHasKey('name', $log->changes);
        $this->assertArrayNotHasKey('password', $log->changes);
        $this->assertStringNotContainsString('rahasiabaru', json_encode($log->changes));

        // Kunci service account Google (kolom value IntegrationSetting)
        GoogleSettings::setServiceAccountKey(json_encode(['type' => 'service_account', 'client_email' => 'x@y.iam', 'private_key' => 'RAHASIA-KUNCI']));
        $this->actingAs($admin)->post('/admin/integrasi/google', [
            'enabled' => '1', 'sheet_anggaran' => 'Anggaran', 'sheet_realisasi' => 'Realisasi', 'poll_minutes' => 15,
        ]);

        foreach (ActivityLog::where('model_type', \App\Models\IntegrationSetting::class)->get() as $logSetting) {
            $this->assertStringNotContainsString('RAHASIA-KUNCI', json_encode($logSetting->changes));
            $this->assertStringNotContainsString('RAHASIA-KUNCI', $logSetting->description);
        }
    }

    public function test_scheduler_membuat_log_sistem(): void
    {
        app(ReturnService::class)->autoReturn();

        $log = ActivityLog::where('action', ActivityLog::ACTION_SYSTEM)->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertNull($log->user_id);
        $this->assertStringContainsString('pengembalian otomatis', $log->description);

        // Halaman menampilkan "Sistem (otomatis)"
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get('/admin/activity-logs')
            ->assertOk()
            ->assertSee('Sistem (otomatis)');
    }

    public function test_log_tidak_merekursi(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post('/pengurus/vehicles', [
            'name' => 'Rekursi', 'plate_number' => 'B 303 CCC', 'year' => 2024, 'capacity' => 5,
        ]);

        // Hanya log untuk Vehicle — ActivityLog sendiri tidak tercatat
        $this->assertSame(0, ActivityLog::where('model_type', ActivityLog::class)->count());
        $this->assertGreaterThan(0, ActivityLog::count());
    }
}
