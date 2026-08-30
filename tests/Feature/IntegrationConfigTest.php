<?php

namespace Tests\Feature;

use App\Models\IntegrationLog;
use App\Models\IntegrationSetting;
use App\Models\Maintenance;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\Google\GoogleSettings;
use App\Services\Google\SheetsBudgetSync;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class IntegrationConfigTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    /** Kunci service account dummy yang valid strukturnya. */
    private function dummyKey(): string
    {
        return json_encode([
            'type' => 'service_account',
            'client_email' => 'karen-sa@test.iam.gserviceaccount.com',
            'private_key' => '-----BEGIN PRIVATE KEY-----\nX\n-----END PRIVATE KEY-----\n',
            'token_uri' => 'https://oauth2.googleapis.com/token',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_admin_dapat_melihat_halaman_konfigurasi(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/integrasi/google')
            ->assertOk()
            ->assertSee('Integrasi Google')
            ->assertSee('Log Sinkronisasi');
    }

    public function test_non_admin_ditolak(): void
    {
        foreach (['pengurus', 'pegawai'] as $role) {
            $u = User::factory()->create(['role' => $role]);

            $this->actingAs($u)->get('/admin/integrasi/google')->assertForbidden();
        }
    }

    public function test_simpan_konfigurasi_dengan_unggah_kunci(): void
    {
        $file = \Illuminate\Http\Testing\File::createWithContent('sa.json', $this->dummyKey());

        $response = $this->actingAs($this->admin)
            ->post('/admin/integrasi/google', [
                'enabled' => '1',
                'spreadsheet' => 'https://docs.google.com/spreadsheets/d/1AbC_dEf-123/edit',
                'sheet_anggaran' => 'Anggaran',
                'sheet_realisasi' => 'Realisasi',
                'drive_enabled' => null,
                'drive_folder' => null,
                'poll_minutes' => 15,
                'key_file' => $file,
            ]);

        $response->assertSessionHasNoErrors();

        $this->assertSame('1', GoogleSettings::get(IntegrationSetting::KEY_ENABLED));
        $this->assertSame('1AbC_dEf-123', GoogleSettings::get(IntegrationSetting::KEY_SPREADSHEET_ID));
        $this->assertSame('15', GoogleSettings::get(IntegrationSetting::KEY_POLL_MINUTES));
        $this->assertSame('karen-sa@test.iam.gserviceaccount.com', GoogleSettings::getServiceAccountKey()['client_email']);

        // Kunci disimpan TERENKRIPSI — nilai mentah tidak boleh ada di database
        $raw = IntegrationSetting::where('setting_key', IntegrationSetting::KEY_SERVICE_ACCOUNT_JSON)->first()->value;
        $this->assertStringNotContainsString('service_account', $raw);
        $this->assertSame('karen-sa@test.iam.gserviceaccount.com', json_decode(Crypt::decryptString($raw), true)['client_email']);
    }

    public function test_kunci_bukan_service_account_ditolak(): void
    {
        $file = \Illuminate\Http\Testing\File::createWithContent('salah.json', '{"type":"user"}');

        $this->actingAs($this->admin)
            ->post('/admin/integrasi/google', [
                'sheet_anggaran' => 'Anggaran',
                'sheet_realisasi' => 'Realisasi',
                'poll_minutes' => 15,
                'key_file' => $file,
            ])
            ->assertSessionHas('error');
    }

    public function test_url_spreadsheet_salah_format_ditandai(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/integrasi/google', [
                'spreadsheet' => 'bukan-url',
                'sheet_anggaran' => 'Anggaran',
                'sheet_realisasi' => 'Realisasi',
                'poll_minutes' => 15,
            ])
            ->assertSessionHas('error');
    }

    public function test_interval_di_luar_pilihan_ditolak(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/integrasi/google', [
                'sheet_anggaran' => 'Anggaran',
                'sheet_realisasi' => 'Realisasi',
                'poll_minutes' => 7,
            ])
            ->assertSessionHasErrors('poll_minutes');
    }

    public function test_hapus_kunci(): void
    {
        GoogleSettings::setServiceAccountKey($this->dummyKey());

        $this->actingAs($this->admin)
            ->delete('/admin/integrasi/google/key')
            ->assertSessionHasNoErrors();

        $this->assertNull(GoogleSettings::getServiceAccountKey());
    }

    public function test_baris_realisasi_sesuai_kontrak(): void
    {
        $v = Vehicle::factory()->create(['plate_number' => 'B 1234 XYZ']);
        $m = Maintenance::create([
            'vehicle_id' => $v->id,
            'start_date' => '2026-09-10',
            'end_date' => '2026-09-11',
            'workshop_name' => 'Bengkel Jaya',
            'nota_number' => 'INV/1',
            'nota_date' => '2026-09-11',
        ]);
        app(\App\Services\BudgetService::class)->inputNota($m, [
            'workshop_name' => 'Bengkel Jaya',
            'costs' => ['servis' => 500000, 'suku_cadang' => 0, 'ac' => 0, 'pelumas' => 300000],
        ]);

        $rows = app(SheetsBudgetSync::class)->realizationRows();

        $this->assertCount(2, $rows);
        $this->assertSame(['B 1234 XYZ', $m->id, 'Bengkel Jaya', 'INV/1', '2026-09-11', 'servis', 500000.0, 565000.0], $rows[0]);
        $this->assertSame('pelumas', $rows[1][5]);
    }

    public function test_sync_saat_nonaktif_tidak_melakukan_apapun(): void
    {
        // Tanpa konfigurasi apa pun — runIfDue harus diam (tidak throw, tidak log)
        $result = app(SheetsBudgetSync::class)->runIfDue();

        $this->assertNull($result);
        $this->assertSame(0, IntegrationLog::count());
    }

    public function test_sync_tanpa_kredensial_tercatat_gagal(): void
    {
        GoogleSettings::set(IntegrationSetting::KEY_ENABLED, '1');
        GoogleSettings::set(IntegrationSetting::KEY_SPREADSHEET_ID, 'abc123');

        app(SheetsBudgetSync::class)->run();

        $log = IntegrationLog::latest('id')->first();
        $this->assertSame('gagal', $log->status);
        $this->assertStringContainsString('Kredensial Google belum dikonfigurasi', $log->message);
    }
}
