<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Complaint;
use App\Models\Maintenance;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Rev user: Keluhan Unit dikelompokkan per kendaraan; tombol
 * "Jadwalkan Maintenance" membuka modal tanggal dengan catatan
 * GABUNGAN seluruh keluhan aktif unit (pegawai A: setir tidak
 * center → hari berikutnya pegawai B: rem bermasalah → keduanya
 * masuk catatan otomatis).
 */
class KeluhanJadwalkanMaintenanceTest extends TestCase
{
    use RefreshDatabase;

    private User $pengurus;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pengurus = User::factory()->create(['role' => 'pengurus']);
    }

    private function bookingDenganKeluhan(Vehicle $v, string $namaPegawai, string $keluhan): Booking
    {
        $pegawai = User::factory()->create(['name' => $namaPegawai, 'role' => 'pegawai']);
        $b = Booking::create([
            'user_id' => $pegawai->id,
            'vehicle_id' => $v->id,
            'start_date' => today()->subDay()->toDateString(),
            'end_date' => today()->toDateString(),
            'address' => 'Kantor B',
            'purpose' => 'Rapat',
            'status' => Booking::STATUS_DIKEMBALIKAN,
            'returned_at' => now(),
        ]);
        Complaint::create(['booking_id' => $b->id, 'message' => $keluhan, 'resolved' => false]);

        return $b;
    }

    public function test_keluhan_dikelompokkan_per_kendaraan_dengan_catatan_gabungan(): void
    {
        $mobilA = Vehicle::factory()->create(['name' => 'Mobil A', 'plate_number' => 'AB 1000 UH']);
        $mobilB = Vehicle::factory()->create(['name' => 'Mobil B', 'plate_number' => 'AB 2000 UH']);

        // Skenario user: A keluhan "setir tidak center", hari berikutnya B keluhan "rem bermasalah"
        $this->bookingDenganKeluhan($mobilA, 'Pegawai A', 'setir tidak center');
        $this->bookingDenganKeluhan($mobilA, 'Pegawai B', 'rem bermasalah');
        $this->bookingDenganKeluhan($mobilB, 'Pegawai C', 'lampu mati');

        $res = $this->actingAs($this->pengurus)->get('/pengurus/complaints');

        // Kartu per kendaraan: jumlah keluhan + kedua pesan tampil
        $res->assertOk()
            ->assertSee('Mobil A')
            ->assertSee('Mobil B')
            ->assertSee('2 keluhan aktif')
            ->assertSee('setir tidak center')
            ->assertSee('rem bermasalah');

        // Modal jadwalkan: catatan terisi GABUNGAN (urut terbaru dulu) + user masing-masing
        $res->assertSee('Jadwalkan Maintenance')
            ->assertSee('showModal')
            ->assertSee('Keluhan: rem bermasalah (Pegawai B', false)
            ->assertSee('setir tidak center (Pegawai A', false)
            ->assertSee('value="'.$mobilA->id.'"', false)
            ->assertSee('value="'.$mobilB->id.'"', false);

        // Tiap modal hanya memuat keluhan unitnya sendiri: keluhan mobil B
        // ("lampu mati") hanya muncul di kartu+modal mobil B (2x: daftar & catatan)
        $html = $res->getContent();
        $this->assertSame(2, substr_count($html, 'lampu mati'), 'keluhan mobil B tidak ikut ke catatan mobil A');
        $this->assertSame(2, substr_count($html, 'rem bermasalah'), 'keluhan mobil A muncul di daftar + catatan gabungannya saja');
    }

    public function test_jadwalkan_dari_modal_keluhan_langsung_membuat_maintenance(): void
    {
        $mobilA = Vehicle::factory()->create(['name' => 'Mobil A', 'plate_number' => 'AB 1000 UH']);
        $this->bookingDenganKeluhan($mobilA, 'Pegawai A', 'setir tidak center');
        $this->bookingDenganKeluhan($mobilA, 'Pegawai B', 'rem bermasalah');

        $this->actingAs($this->pengurus)
            ->post('/pengurus/maintenances', [
                'vehicle_id' => $mobilA->id,
                'start_date' => today()->addDays(5)->toDateString(),
                'end_date' => today()->addDays(6)->toDateString(),
                'note' => 'Keluhan: rem bermasalah (Pegawai B, '.today()->translatedFormat('d M').'); setir tidak center (Pegawai A, '.today()->translatedFormat('d M').')',
            ])
            ->assertSessionHasNoErrors();

        $m = Maintenance::where('vehicle_id', $mobilA->id)->first();
        $this->assertNotNull($m);
        $this->assertSame($mobilA->id, $m->vehicle_id);
        $this->assertStringContainsString('rem bermasalah', $m->note);
        $this->assertStringContainsString('setir tidak center', $m->note);
        $this->assertSame('terjadwal', $m->status);

        // Keluhan TIDAK otomatis selesai (aturan existing: manual oleh pengurus)
        $this->assertSame(2, Complaint::where('resolved', false)->count());
    }

    public function test_keluhan_selesai_ditandai_per_baris_dari_kartu(): void
    {
        $mobilA = Vehicle::factory()->create();
        $this->bookingDenganKeluhan($mobilA, 'Pegawai A', 'setir tidak center');
        $b2 = $this->bookingDenganKeluhan($mobilA, 'Pegawai B', 'rem bermasalah');

        $this->actingAs($this->pengurus)
            ->patch('/pengurus/complaints/'.Complaint::where('booking_id', $b2->id)->first()->id.'/resolve')
            ->assertSessionHasNoErrors();

        // Kartu kini hanya 1 keluhan aktif; catatan gabungan tinggal sisa aktif
        $res = $this->actingAs($this->pengurus)->get('/pengurus/complaints');
        $res->assertOk()->assertSee('1 keluhan aktif');
        $this->assertStringNotContainsString('rem bermasalah (Pegawai B', $res->getContent());
        $res->assertSee('setir tidak center (Pegawai A', false);
    }

    public function test_unit_terjadwal_tombol_berubah_sedang_maintenance(): void
    {
        $mobilA = Vehicle::factory()->create(['name' => 'Mobil A']);
        $mobilB = Vehicle::factory()->create(['name' => 'Mobil B']);
        $this->bookingDenganKeluhan($mobilA, 'Pegawai A', 'setir tidak center');
        $this->bookingDenganKeluhan($mobilB, 'Pegawai C', 'lampu mati');

        // Mobil A dijadwalkan; mobil B belum
        $mulai = today()->addDays(5);
        Maintenance::create([
            'vehicle_id' => $mobilA->id,
            'start_date' => $mulai->toDateString(),
            'end_date' => $mulai->copy()->addDays(2)->toDateString(),
            'status' => 'terjadwal',
            'note' => 'Keluhan: setir tidak center',
        ]);

        $res = $this->actingAs($this->pengurus)->get('/pengurus/complaints');

        // Mobil A: tombol SEDANG MAINTENANCE (+rentang) menggantikan tombol jadwalkan
        $html = $res->getContent();
        $this->assertStringContainsString('Sedang Maintenance', $html);
        $this->assertStringContainsString($mulai->translatedFormat('d M'), $html);
        $this->assertStringContainsString(route('pengurus.maintenances.index'), $html);
        // Hanya mobil B yang masih punya tombol + modal jadwalkan
        // (per unit ber-modal: tombol + judul modal = 2 kemunculan)
        $this->assertSame(2, substr_count($html, 'Jadwalkan Maintenance'), 'tombol jadwalkan hanya untuk unit tanpa jadwal');
        $this->assertSame(1, substr_count($html, '<dialog'), 'modal hanya untuk unit tanpa jadwal');

        // Jadwal selesai → tombol jadwalkan kembali
        Maintenance::where('vehicle_id', $mobilA->id)->update(['status' => 'selesai']);
        $res2 = $this->actingAs($this->pengurus)->get('/pengurus/complaints');
        $this->assertSame(4, substr_count($res2->getContent(), 'Jadwalkan Maintenance'));
        $this->assertStringNotContainsString('Sedang Maintenance', $res2->getContent());
    }
}
