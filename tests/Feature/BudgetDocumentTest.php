<?php

namespace Tests\Feature;

use App\Models\GeneratedDocument;
use App\Models\Maintenance;
use App\Models\MaintenanceCost;
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

    public function test_input_nota_total_dari_rincian_dikali_koefisien_dan_menandai_selesai(): void
    {
        Storage::fake('local');

        $v = Vehicle::factory()->create();
        $m = Maintenance::create(['vehicle_id' => $v->id, 'start_date' => '2026-09-10', 'end_date' => '2026-09-11']);

        $response = $this->actingAs($this->pengurus)
            ->put("/pengurus/maintenances/{$m->id}/costs", [
                'workshop_name' => 'Bengkel Jaya',
                'nota_number' => 'INV/2026/081',
                'nota_date' => '2026-09-11',
                // Rincian per baris — total pos = auto-sum rincian (rev UAT 04)
                'details' => ['servis' => ['Tune up', 'Ganti kampas'], 'pelumas' => ['Oli mesin']],
                'detail_amounts' => ['servis' => [300000, 200000], 'pelumas' => [300000]],
            ]);

        $response->assertSessionHasNoErrors();

        $m->refresh();
        $this->assertSame('selesai', $m->status);
        $this->assertSame('Bengkel Jaya', $m->workshop_name);

        // Auto-sum rincian: servis 300rb+200rb=500rb, ×1,13
        $servis = $m->costs->firstWhere('post', 'servis');
        $this->assertEquals(500000.0, (float) $servis->raw_amount);
        $this->assertEquals(565000.0, (float) $servis->taxed_amount);
        $this->assertEquals(1.13, (float) $servis->koefisien_used);
        $this->assertCount(2, $servis->details);
        $this->assertEquals(339000.0, (float) $m->costs->firstWhere('post', 'pelumas')->taxed_amount);
        $this->assertSame(0.0, (float) $m->costs->firstWhere('post', 'ac')->raw_amount);
        $this->assertCount(0, $m->costs->firstWhere('post', 'ac')->details);
    }

    public function test_nota_tanpa_rincian_bernilai_ditolak(): void
    {
        $v = Vehicle::factory()->create();
        $m = Maintenance::create(['vehicle_id' => $v->id, 'start_date' => '2026-09-10', 'end_date' => '2026-09-11']);

        // Semua rincian 0 / tanpa baris → ditolak
        $this->actingAs($this->pengurus)
            ->put("/pengurus/maintenances/{$m->id}/costs", [
                'workshop_name' => 'Bengkel Jaya',
                'details' => ['servis' => ['Servis rutin']],
                'detail_amounts' => ['servis' => [0]],
            ])
            ->assertSessionHasErrors('details');

        $m->refresh();
        $this->assertSame('terjadwal', $m->status);
    }

    public function test_simpan_nota_hanya_membuat_draft_nota_per_pos(): void
    {
        Storage::fake('local');

        $v = Vehicle::factory()->create();
        $m = Maintenance::create(['vehicle_id' => $v->id, 'start_date' => '2026-09-10', 'end_date' => '2026-09-11']);

        $this->actingAs($this->pengurus)
            ->put("/pengurus/maintenances/{$m->id}/costs", [
                'workshop_name' => 'Bengkel Jaya',
                'details' => ['servis' => ['Servis rutin'], 'ac' => ['Freon']],
                'detail_amounts' => ['servis' => [500000], 'ac' => [200000]],
            ]);

        // UAT 04 rev-2: bend26 TIDAK lagi otomatis (bulanan, on demand);
        // hanya draft nota untuk pos bernilai (servis & ac)
        $docs = GeneratedDocument::where('maintenance_id', $m->id)->get();
        $this->assertSame(0, $docs->where('type', 'bend26')->count());
        $this->assertSame(2, $docs->where('type', 'draft_nota')->count());
        $this->assertSame(0, $docs->where('type', 'draft_nota')->where('post', 'pelumas')->count());

        // File PDF benar-benar ada
        foreach ($docs as $doc) {
            Storage::disk('local')->assertExists($doc->file_path);
        }
    }

    public function test_edit_nota_menimpa_dokumen_di_tempat_tanpa_versi_baru(): void
    {
        Storage::fake('local');

        $v = Vehicle::factory()->create();
        $m = Maintenance::create(['vehicle_id' => $v->id, 'start_date' => '2026-09-10', 'end_date' => '2026-09-11']);

        $this->actingAs($this->pengurus)->put("/pengurus/maintenances/{$m->id}/costs", [
            'workshop_name' => 'Bengkel Jaya',
            'details' => ['servis' => ['Servis rutin']],
            'detail_amounts' => ['servis' => [500000]],
        ]);

        $before = GeneratedDocument::where('maintenance_id', $m->id)->where('type', 'draft_nota')->first();
        $this->assertNull($before->regenerated_at);

        // EDIT nota — nilai berubah
        $this->actingAs($this->pengurus)->put("/pengurus/maintenances/{$m->id}/costs", [
            'workshop_name' => 'Bengkel Jaya',
            'details' => ['servis' => ['Servis rutin + rem']],
            'detail_amounts' => ['servis' => [700000]],
        ]);

        // Timpa di tempat: record TIDAK bertambah, regenerated_at terisi (UAT 04-B4/B11)
        $this->assertSame(1, GeneratedDocument::where('maintenance_id', $m->id)->where('type', 'draft_nota')->count());
        $after = GeneratedDocument::where('maintenance_id', $m->id)->where('type', 'draft_nota')->first();
        $this->assertSame($before->id, $after->id);
        $this->assertNotNull($after->regenerated_at);
        Storage::disk('local')->assertExists($after->file_path);
    }

    public function test_bend26_bulanan_per_pos_dari_realisasi(): void
    {
        Storage::fake('local');

        $v1 = Vehicle::factory()->create(['plate_number' => 'AB 1001 UH']);
        $v2 = Vehicle::factory()->create(['plate_number' => 'AB 1002 UH']);

        foreach ([$v1, $v2] as $i => $v) {
            $m = Maintenance::create([
                'vehicle_id' => $v->id,
                'start_date' => '2026-09-0'.(5 + $i), 'end_date' => '2026-09-0'.(6 + $i),
                'workshop_name' => 'Bengkel Jaya', 'nota_number' => 'INV/'.$i, 'nota_date' => '2026-09-1'.(0 + $i),
            ]);
            app(BudgetService::class)->inputNota($m, [
                'workshop_name' => 'Bengkel Jaya',
                'details' => ['servis' => ['Servis rutin']],
                'detail_amounts' => ['servis' => [500000]],
            ]);
        }

        // Generate bend26 bulanan September 2026 (semua pos)
        $this->actingAs($this->pengurus)
            ->post('/pengurus/documents/bend26-bulanan', ['month' => '2026-09'])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        // Hanya pos servis bernilai → 1 bend26 period 2026-09 (bukan per maintenance)
        $bend = GeneratedDocument::where('type', 'bend26')->get();
        $this->assertCount(1, $bend);
        $this->assertSame('servis', $bend->first()->post);
        $this->assertSame('2026-09', $bend->first()->period);
        $this->assertNull($bend->first()->maintenance_id);
        Storage::disk('local')->assertExists($bend->first()->file_path);

        // Total = 2 nota × 565.000 (servis × koefisien 1,13)
        $this->assertSame(1130000.0, (float) MaintenanceCost::where('post', 'servis')->sum('taxed_amount'));

        // Regenerate bulan sama → TIDAK menambah record, menandai Diperbarui
        $this->actingAs($this->pengurus)
            ->post('/pengurus/documents/bend26-bulanan', ['month' => '2026-09'])
            ->assertRedirect();

        $this->assertCount(1, GeneratedDocument::where('type', 'bend26')->get());
        $this->assertNotNull($bend->first()->fresh()->regenerated_at);

        // Bulan tanpa realisasi → tidak membuat apa pun + peringatan
        $this->actingAs($this->pengurus)
            ->post('/pengurus/documents/bend26-bulanan', ['month' => '2026-01'])
            ->assertSessionHas('warning');
        $this->assertSame(0, GeneratedDocument::where('type', 'bend26')->where('period', '2026-01')->count());
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
            'details' => ['servis' => ['Servis rutin']],
            'detail_amounts' => ['servis' => [500000]],
        ]);

        $summary = app(BudgetService::class)->summary($v, 2026);
        $this->assertEquals(565000.0, $summary['servis']['realisasi']);
        $this->assertEquals(-465000.0, $summary['servis']['sisa']); // negatif = peringatan, bukan blokir
    }

    public function test_kartu_pemeliharaan_digenerate_dari_laporan(): void
    {
        Storage::fake('local');

        $v = Vehicle::factory()->create();
        app(BudgetService::class)->setBudgets($v, 2026, ['servis' => 5000000, 'suku_cadang' => 0, 'ac' => 0, 'pelumas' => 0]);

        $m = Maintenance::create(['vehicle_id' => $v->id, 'start_date' => '2026-09-10', 'end_date' => '2026-09-11', 'workshop_name' => 'Bengkel Jaya']);
        app(BudgetService::class)->inputNota($m, ['workshop_name' => 'Bengkel Jaya', 'details' => ['servis' => ['Servis rutin']], 'detail_amounts' => ['servis' => [500000]]]);

        // Tombol generate kini di menu Laporan (UAT 04-B10)
        $this->actingAs($this->pengurus)
            ->post("/pengurus/vehicles/{$v->id}/generate-kartu-pemeliharaan", ['year' => 2026])
            ->assertRedirect();

        $kartu = GeneratedDocument::where('vehicle_id', $v->id)->where('type', 'kartu_pemeliharaan')->first();
        $this->assertNotNull($kartu);
        Storage::disk('local')->assertExists($kartu->file_path);
    }

    public function test_daftar_dokumen_dan_unduhan(): void
    {
        Storage::fake('local');

        $v = Vehicle::factory()->create();
        $doc = GeneratedDocument::create([
            'vehicle_id' => $v->id,
            'type' => 'kartu_pemeliharaan',
            'file_path' => 'documents/kartu-test-v1.pdf',
            'version' => 1,
        ]);
        Storage::disk('local')->put($doc->file_path, '%PDF-1.4 test');

        $this->actingAs($this->pengurus)
            ->get('/pengurus/documents')
            ->assertOk()
            ->assertSee('Kartu Pemeliharaan');

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
