<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\ReturnService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutoReturnTest extends TestCase
{
    use RefreshDatabase;

    private User $pegawai;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pegawai = User::factory()->create([
            'role' => 'pegawai',
            'seksi_id' => \App\Models\Seksi::factory()->create()->id,
        ]);
    }

    private function booking(array $over = []): Booking
    {
        return Booking::create(array_merge([
            'user_id' => $this->pegawai->id,
            'vehicle_id' => Vehicle::factory()->create()->id,
            'start_date' => today()->subDays(3)->toDateString(),
            'end_date' => today()->subDay()->toDateString(), // kemarin = lewat tempo
            'address' => 'Kantor B',
            'purpose' => 'Rapat',
        ], $over));
    }

    public function test_booking_lewat_tempo_dikembalikan_otomatis(): void
    {
        $b = $this->booking();

        $result = app(ReturnService::class)->autoReturn();

        $this->assertSame(1, $result['dikembalikan']);
        $b->refresh();
        $this->assertSame('dikembalikan', $b->status);
        $this->assertTrue($b->auto_returned);
        $this->assertNotNull($b->returned_at);
    }

    public function test_booking_end_date_hari_ini_tidak_disentuh(): void
    {
        $b = $this->booking(['end_date' => today()->toDateString()]);

        app(ReturnService::class)->autoReturn();

        $this->assertSame('dipinjam', $b->refresh()->status); // masih berlaku sampai 24:00
    }

    public function test_booking_sudah_dikembalikan_manual_tidak_tersentuh(): void
    {
        $b = $this->booking(['status' => 'dikembalikan', 'returned_at' => now(), 'auto_returned' => false]);

        $result = app(ReturnService::class)->autoReturn();

        $this->assertSame(0, $result['dikembalikan']);
        $this->assertFalse($b->refresh()->auto_returned); // penanda manual tetap false
    }

    public function test_idempoten_dua_eksekusi(): void
    {
        $this->booking();

        $service = app(ReturnService::class);
        $service->autoReturn();
        $kedua = $service->autoReturn();

        $this->assertSame(0, $kedua['dikembalikan']);
        $this->assertSame(0, $kedua['dibatalkan']);
    }

    public function test_menunggu_penggantian_lewat_tempo_dibatalkan_dan_kuota_lepas(): void
    {
        $b = $this->booking(['status' => Booking::STATUS_MENUNGGU_PENGGANTIAN]);

        $result = app(ReturnService::class)->autoReturn();

        $this->assertSame(1, $result['dibatalkan']);
        $b->refresh();
        $this->assertSame('dibatalkan', $b->status);
        $this->assertTrue($b->auto_returned);

        // Kuota bidang lepas — user bisa meminjam lagi
        $baru = app(\App\Services\BookingService::class)->create(
            $this->pegawai,
            Vehicle::factory()->create(),
            today()->addDays(2)->toDateString(),
            today()->addDays(3)->toDateString(),
            'Kantor C',
            'Dinas',
        );
        $this->assertSame('dipinjam', $baru->status);
    }

    public function test_tidak_ada_keluhan_dicatat_pada_jalur_otomatis(): void
    {
        $this->booking();

        app(ReturnService::class)->autoReturn();

        $this->assertSame(0, \App\Models\Complaint::count());
    }

    public function test_riwayat_pegawai_menampilkan_penanda_otomatis(): void
    {
        $this->booking();
        app(ReturnService::class)->autoReturn();

        $this->actingAs($this->pegawai)
            ->get('/pegawai/bookings')
            ->assertOk()
            ->assertSee('Dikembalikan otomatis oleh sistem');
    }
}
