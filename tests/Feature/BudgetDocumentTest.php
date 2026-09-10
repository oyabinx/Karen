<?php

namespace Tests\Feature;

use App\Models\GeneratedDocument;
use App\Models\Maintenance;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleBudget;
use App\Services\BudgetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BudgetDocumentTest extends TestCase
{
    use RefreshDatabase;

    private User $pengurus;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pengurus = User::factory()->create(['role' => 'pengurus']);
    }

    public function test_simpan_anggaran_dan_hitung_sisa(): void
    {
        $v = Vehicle::factory()->create();

        $this->actingAs($this->pengurus)
            ->put("/pengurus/vehicles/{$v->id}/budgets", [
                'year' => 2026,
                'amounts' => ['servis' => 5000000, 'suku_cadang' => 8000000, 'ac' => 2000000, 'pelumas' => 1500000],
            ])
            ->assertSessionHasNoErrors();

        $summary = app(BudgetService::class)->summary($v, 2026);
        $this->assertSame(5000000.0, $summary['servis']['anggaran']);
        $this->assertSame(5000000.0, $summary['servis']['sisa']); // belum ada realisasi

        $this->assertDatabaseHas('vehicle_budgets', ['vehicle_id' => $v->id, 'post' => 'servis', 'year' => 2026, 'amount' => 5000000]);
    }

    public function test_anggaran_tiap_tahun_independen(): void
    {
        $v = Vehicle::factory()->create();

        // Alokasi 2026 dan 2027 BERBEDA — tidak saling menimpa
        app(BudgetService::class)->setBudgets($v, 2026, ['servis' => 5000000, 'suku_cadang' => 8000000, 'ac' => 2000000, 'pelumas' => 1500000]);
        app(BudgetService::class)->setBudgets($v, 2027, ['servis' => 7000000, 'suku_cadang' => 6000000, 'ac' => 2500000, 'pelumas' => 2000000]);

        $s2026 = app(BudgetService::class)->summary($v, 2026);
        $s2027 = app(BudgetService::class)->summary($v, 2027);

        $this->assertSame(5000000.0, $s2026['servis']['anggaran']);
        $this->assertSame(7000000.0, $s2027['servis']['anggaran']);
        $this->assertSame(1500000.0, $s2026['pelumas']['anggaran']);
        $this->assertSame(2000000.0, $s2027['pelumas']['anggaran']);

        // Halaman dengan ?year=2027 menampilkan alokasi 2027 (format ribuan)
        $this->actingAs($this->pengurus)
            ->get("/pengurus/vehicles/{$v->id}/budgets?year=2027")
            ->assertOk()
            ->assertSee('value="7.000.000', false)
            ->assertDontSee('value="5.000.000', false);

        // 8 baris anggaran tersimpan (4 pos × 2 tahun)
        $this->assertSame(8, \App\Models\VehicleBudget::where('vehicle_id', $v->id)->count());
    }

    public function test_halaman_anggaran_dapat_dilihat(): void
    {
        $v = Vehicle::factory()->create();

        $this->actingAs($this->pengurus)
            ->get("/pengurus/vehicles/{$v->id}/budgets")
            ->assertOk()
            ->assertSee('Atur Total Anggaran 4 Pos')
            ->assertSee('Realisasi', false);
    }

    public function test_input_nota_mengali_koefisien_113_dan_menandai_selesai(): void
    {
        Storage::fake('local');

        $v = Vehicle::factory()->create();
        $m = Maintenance::create(['vehicle_id' => $v->id, 'start_date' => '2026-09-10', 'end_date' => '2026-09-11']);

        $response = $this->actingAs($this->pengurus)
            ->put("/pengurus/maintenances/{$m->id}/costs", [
                'workshop_name' => 'Bengkel Jaya',
                'nota_number' => 'INV/2026/081',
                'nota_date' => '2026-09-11',
                'costs' => ['servis' => 500000, 'suku_cadang' => 0, 'ac' => 0, 'pelumas' => 300000],
            ]);

        $response->assertSessionHasNoErrors();

        $m->refresh();
        $this->assertSame('selesai', $m->status);
        $this->assertSame('Bengkel Jaya', $m->workshop_name);

        // ×1,13
        $this->assertEquals(565000.0, (float) $m->costs->firstWhere('post', 'servis')->taxed_amount);
        $this->assertEquals(339000.0, (float) $m->costs->firstWhere('post', 'pelumas')->taxed_amount);
        $this->assertSame(0.0, (float) $m->costs->firstWhere('post', 'ac')->raw_amount);
    }

    public function test_nota_tanpa_pos_bernilai_ditolak(): void
    {
        $v = Vehicle::factory()->create();
        $m = Maintenance::create(['vehicle_id' => $v->id, 'start_date' => '2026-09-10', 'end_date' => '2026-09-11']);

        $this->actingAs($this->pengurus)
            ->put("/pengurus/maintenances/{$m->id}/costs", [
                'workshop_name' => 'Bengkel Jaya',
                'costs' => ['servis' => 0, 'suku_cadang' => 0, 'ac' => 0, 'pelumas' => 0],
            ])
            ->assertSessionHasErrors('costs');
    }

    public function test_generate_dokumen_bend26_dan_draft_nota_hanya_pos_bernilai(): void
    {
        Storage::fake('local');

        $v = Vehicle::factory()->create();
        $m = Maintenance::create(['vehicle_id' => $v->id, 'start_date' => '2026-09-10', 'end_date' => '2026-09-11']);

        $this->actingAs($this->pengurus)
            ->put("/pengurus/maintenances/{$m->id}/costs", [
                'workshop_name' => 'Bengkel Jaya',
                'costs' => ['servis' => 500000, 'suku_cadang' => 0, 'ac' => 200000, 'pelumas' => 0],
            ]);

        // 1 bend26 + 2 draft nota (servis & ac saja)
        $docs = GeneratedDocument::where('maintenance_id', $m->id)->get();
        $this->assertSame(1, $docs->where('type', 'bend26')->count());
        $this->assertSame(2, $docs->where('type', 'draft_nota')->count());
        $this->assertSame(0, $docs->where('type', 'draft_nota')->where('post', 'pelumas')->count());

        // File PDF benar-benar ada
        foreach ($docs as $doc) {
            Storage::disk('local')->assertExists($doc->file_path);
        }
    }

    public function test_regenerate_menaikkan_versi_dan_mengarsipkan(): void
    {
        Storage::fake('local');

        $v = Vehicle::factory()->create();
        $m = Maintenance::create(['vehicle_id' => $v->id, 'start_date' => '2026-09-10', 'end_date' => '2026-09-11']);

        $this->actingAs($this->pengurus)->put("/pengurus/maintenances/{$m->id}/costs", [
            'workshop_name' => 'Bengkel Jaya',
            'costs' => ['servis' => 500000, 'suku_cadang' => 0, 'ac' => 0, 'pelumas' => 0],
        ]);

        $this->actingAs($this->pengurus)
            ->post("/pengurus/maintenances/{$m->id}/generate")
            ->assertSessionHasNoErrors();

        $bend = GeneratedDocument::where('maintenance_id', $m->id)->where('type', 'bend26')->get();
        $this->assertCount(2, $bend); // v1 arsip + v2 baru
        $this->assertSame(2, $bend->max('version'));
    }

    public function test_realisasi_mengurangi_sisa_dan_melebihi_anggaran_tidak_diblokir(): void
    {
        Storage::fake('local');

        $v = Vehicle::factory()->create();
        app(BudgetService::class)->setBudgets($v, 2026, ['servis' => 100000, 'suku_cadang' => 0, 'ac' => 0, 'pelumas' => 0]);

        $m = Maintenance::create(['vehicle_id' => $v->id, 'start_date' => '2026-09-10', 'end_date' => '2026-09-11']);

        $this->actingAs($this->pengurus)->put("/pengurus/maintenances/{$m->id}/costs", [
            'workshop_name' => 'Bengkel Jaya',
            'nota_date' => '2026-09-11',
            'costs' => ['servis' => 500000, 'suku_cadang' => 0, 'ac' => 0, 'pelumas' => 0],
        ]);

        $summary = app(BudgetService::class)->summary($v, 2026);
        $this->assertEquals(565000.0, $summary['servis']['realisasi']);
        $this->assertEquals(-465000.0, $summary['servis']['sisa']); // negatif = peringatan, bukan blokir
    }

    public function test_kartu_inventaris_digenerate_per_kendaraan(): void
    {
        Storage::fake('local');

        $v = Vehicle::factory()->create();
        app(BudgetService::class)->setBudgets($v, 2026, ['servis' => 5000000, 'suku_cadang' => 0, 'ac' => 0, 'pelumas' => 0]);

        $m = Maintenance::create(['vehicle_id' => $v->id, 'start_date' => '2026-09-10', 'end_date' => '2026-09-11', 'workshop_name' => 'Bengkel Jaya']);
        app(BudgetService::class)->inputNota($m, ['workshop_name' => 'Bengkel Jaya', 'costs' => ['servis' => 500000, 'suku_cadang' => 0, 'ac' => 0, 'pelumas' => 0]]);

        $this->actingAs($this->pengurus)
            ->post("/pengurus/vehicles/{$v->id}/generate-kartu-inventaris", ['year' => 2026])
            ->assertRedirect();

        $kartu = GeneratedDocument::where('vehicle_id', $v->id)->where('type', 'kartu_inventaris')->first();
        $this->assertNotNull($kartu);
        Storage::disk('local')->assertExists($kartu->file_path);
    }

    public function test_daftar_dokumen_dan_unduhan(): void
    {
        Storage::fake('local');

        $v = Vehicle::factory()->create();
        $doc = GeneratedDocument::create([
            'vehicle_id' => $v->id,
            'type' => 'kartu_inventaris',
            'file_path' => 'documents/kartu-test-v1.pdf',
            'version' => 1,
        ]);
        Storage::disk('local')->put($doc->file_path, '%PDF-1.4 test');

        $this->actingAs($this->pengurus)
            ->get('/pengurus/documents')
            ->assertOk()
            ->assertSee('Kartu Inventaris');

        $this->actingAs($this->pengurus)
            ->get("/pengurus/documents/{$doc->id}/download")
            ->assertOk();
    }

    public function test_pegawai_ditolak_admin_boleh(): void
    {
        $v = Vehicle::factory()->create();

        $pegawai = User::factory()->create(['role' => 'pegawai']);
        $this->actingAs($pegawai)->get("/pengurus/vehicles/{$v->id}/budgets")->assertForbidden();
        $this->actingAs($pegawai)->get('/pengurus/documents')->assertForbidden();

        // Pasca-UAT 03: admin diberi akses menu Anggaran & Dokumen
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get("/pengurus/vehicles/{$v->id}/budgets")->assertOk();
        $this->actingAs($admin)->get('/pengurus/documents')->assertOk();
    }
}
