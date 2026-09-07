<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Complaint;
use App\Models\Seksi;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\BudgetService;
use App\Services\ReturnService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitoringReportTest extends TestCase
{
    use RefreshDatabase;

    private User $pengurus;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pengurus = User::factory()->create(['role' => 'pengurus']);
    }

    public function test_monitoring_dengan_filter_dan_penanda(): void
    {
        $seksiA = Seksi::factory()->create();
        $seksiB = Seksi::factory()->create();

        $b1 = Booking::create([
            'user_id' => User::factory()->create(['seksi_id' => $seksiA->id])->id,
            'vehicle_id' => Vehicle::factory()->create(['name' => 'Avanza Uji'])->id,
            'start_date' => '2026-09-01', 'end_date' => '2026-09-02',
            'address' => 'A', 'purpose' => 'X',
        ]);
        Booking::create([
            'user_id' => User::factory()->create(['seksi_id' => $seksiB->id])->id,
            'vehicle_id' => Vehicle::factory()->create()->id,
            'start_date' => '2026-09-10', 'end_date' => '2026-09-11',
            'address' => 'B', 'purpose' => 'Y',
            'status' => 'dibatalkan', 'auto_returned' => true,
        ]);

        $response = $this->actingAs($this->pengurus)
            ->get('/pengurus/bookings?q=&status=&vehicle=&bidang='.$seksiA->bidang_id.'&from=2026-09-01&to=2026-09-05')
            ->assertOk();

        $response->assertSee('Avanza Uji')->assertDontSee('Dibatalkan sistem');

        // Filter bidang B → hanya booking kedua
        $this->actingAs($this->pengurus)
            ->get('/pengurus/bookings?bidang='.$seksiB->bidang_id)
            ->assertOk()
            ->assertSee('Ditutup otomatis');
    }

    public function test_laporan_rekap_angka_benar(): void
    {
        $v = Vehicle::factory()->create(['name' => 'Innova Rekap']);
        Booking::create([
            'user_id' => User::factory()->create(['seksi_id' => Seksi::factory()->create()->id])->id,
            'vehicle_id' => $v->id,
            'start_date' => '2026-09-01', 'end_date' => '2026-09-03', // 3 hari pakai
            'address' => 'A', 'purpose' => 'X',
            'status' => 'dikembalikan', 'returned_at' => now(), 'auto_returned' => true,
        ]);

        $response = $this->actingAs($this->pengurus)
            ->get('/pengurus/reports?from=2026-09-01&to=2026-09-30')
            ->assertOk();

        $response->assertSee('Rekap per Mobil')
            ->assertSee('Innova Rekap')
            ->assertSee('Total Hari Pakai');

        // Baris rekap mobil memuat 3 hari pakai & total keseluruhan 3 hari
        $this->assertStringContainsString('>3</td>', $response->getContent());
    }

    public function test_export_csv_berisi_kolom_dan_baris(): void
    {
        $v = Vehicle::factory()->create(['name' => 'Brio Ekspor']);

        Booking::create([
            'user_id' => User::factory()->create(['seksi_id' => Seksi::factory()->create()->id])->id,
            'vehicle_id' => $v->id,
            'start_date' => '2026-09-02', 'end_date' => '2026-09-03',
            'address' => 'Kantor Z', 'purpose' => 'Ekspor',
        ]);

        $response = $this->actingAs($this->pengurus)
            ->get('/pengurus/reports?from=2026-09-01&to=2026-09-30&export=1&with_budget=1');

        $response->assertOk();
        $content = $response->streamedContent();

        $this->assertStringContainsString('mulai;selesai;mobil;plat', $content);
        $this->assertStringContainsString('Brio Ekspor', $content);
        $this->assertStringContainsString('Ekspor', $content);
        $this->assertStringContainsString('pos;anggaran_tahun;realisasi_x1_13;sisa', $content);
        $this->assertStringContainsString('servis', $content);
    }

    public function test_laporan_menyertakan_rekap_anggaran_per_pos(): void
    {
        $v = Vehicle::factory()->create();
        app(BudgetService::class)->setBudgets($v, now()->year, ['servis' => 1000000, 'suku_cadang' => 0, 'ac' => 0, 'pelumas' => 0]);

        $m = \App\Models\Maintenance::create([
            'vehicle_id' => $v->id,
            'start_date' => now()->startOfYear()->toDateString(),
            'end_date' => now()->startOfYear()->addDay()->toDateString(),
            'workshop_name' => 'Bengkel Laporan',
        ]);
        app(BudgetService::class)->inputNota($m, ['workshop_name' => 'Bengkel Laporan', 'costs' => ['servis' => 500000, 'suku_cadang' => 0, 'ac' => 0, 'pelumas' => 0]]);

        $this->actingAs($this->pengurus)
            ->get('/pengurus/reports')
            ->assertOk()
            ->assertSee('Rp '.number_format(1000000, 0, ',', '.')) // anggaran servis
            ->assertSee('Rp '.number_format(565000, 0, ',', '.'))  // realisasi ×1,13
            ->assertSee('Rp '.number_format(435000, 0, ',', '.')); // sisa
    }

    public function test_dashboard_admin_rincian_dan_kesehatan_scheduler(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Seksi::factory()->count(2)->create(); // + bidang otomatis

        // autoReturn menulis jejak kesehatan
        app(ReturnService::class)->autoReturn();

        $response = $this->actingAs($admin)->get('/dashboard')->assertOk();

        $response->assertSee('Struktur Organisasi', false)
            ->assertSee('Kesehatan Scheduler')
            ->assertSee('terakhir:', false);
        $this->assertNotNull(\App\Models\IntegrationSetting::where('setting_key', 'system_last_auto_return')->first());
    }

    public function test_dashboard_pengurus_daftar_dan_anggaran(): void
    {
        Booking::create([
            'user_id' => User::factory()->create(['seksi_id' => Seksi::factory()->create()->id])->id,
            'vehicle_id' => Vehicle::factory()->create(['name' => 'Hiace Daftar'])->id,
            'start_date' => today()->toDateString(),
            'end_date' => today()->toDateString(),
            'address' => 'A', 'purpose' => 'X',
        ]);

        $this->actingAs($this->pengurus)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Peminjaman Berjalan Hari Ini')
            ->assertSee('Hiace Daftar')
            ->assertSee('Anggaran Maintenance');
    }

    public function test_dashboard_pegawai_riwayat_singkat(): void
    {
        $pegawai = User::factory()->create(['role' => 'pegawai', 'seksi_id' => Seksi::factory()->create()->id]);
        Booking::create([
            'user_id' => $pegawai->id,
            'vehicle_id' => Vehicle::factory()->create(['name' => 'Brio Riwayat'])->id,
            'start_date' => today()->subDays(5)->toDateString(),
            'end_date' => today()->subDays(4)->toDateString(),
            'address' => 'A', 'purpose' => 'X',
            'status' => 'dikembalikan', 'returned_at' => now(),
        ]);

        $this->actingAs($pegawai)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Riwayat Terakhir')
            ->assertSee('Brio Riwayat');
    }

    public function test_pegawai_ditolak_admin_boleh(): void
    {
        $pegawai = User::factory()->create(['role' => 'pegawai']);
        $this->actingAs($pegawai)->get('/pengurus/bookings')->assertForbidden();
        $this->actingAs($pegawai)->get('/pengurus/reports')->assertForbidden();

        // Pasca-UAT 03 (tindak lanjut): admin diberi akses monitoring & laporan
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get('/pengurus/bookings')->assertOk();
        $this->actingAs($admin)->get('/pengurus/reports')->assertOk();
    }
}
