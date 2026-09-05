<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Seksi;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Revisi hasil UAT Skenario 01 (file UAT/01-inti-login-profil-peminjaman.md).
 */
class UatSkenario01RevisiTest extends TestCase
{
    use RefreshDatabase;

    /** A1 — root mengarahkan tamu ke login & user aktif ke dashboard. */
    public function test_a1_root_redirect_tamu_ke_login_user_ke_dashboard(): void
    {
        $this->get('/')->assertRedirect('/login');

        $user = User::factory()->create(['role' => 'pegawai']);
        $this->actingAs($user)->get('/')->assertRedirect('/dashboard');
    }

    /** A5 — halaman terproteksi dikirim tanpa-cache: back pasca-logout → login. */
    public function test_a5_header_no_store_pada_halaman_terproteksi(): void
    {
        $user = User::factory()->create(['role' => 'pegawai']);

        $this->actingAs($user)->get('/dashboard')->assertOk();

        $this->assertStringContainsString(
            'no-store',
            $this->actingAs($user)->get('/dashboard')->headers->get('Cache-Control'),
        );
    }

    /**
     * B4 — duplikat nomor HP lintas format ditolak:
     * 0812…, 62812…, +62812… adalah nomor yang sama.
     */
    public function test_b4_nomor_hp_duplikat_lintas_format_ditolak(): void
    {
        User::factory()->create(['phone' => '081233344455']);

        $pengurus = User::factory()->create(['role' => 'pengurus', 'phone' => '087711122233']);

        foreach (['6281233344455', '+6281233344455', '081233344455'] as $duplikat) {
            $this->actingAs($pengurus)
                ->patch('/profile', [
                    'name' => $pengurus->name,
                    'email' => $pengurus->email,
                    'phone' => $duplikat,
                ])
                ->assertSessionHasErrors('phone');
        }

        // Nomor sendiri (format beda) tetap boleh
        $this->actingAs($pengurus)
            ->patch('/profile', [
                'name' => $pengurus->name,
                'email' => $pengurus->email,
                'phone' => '+6287711122233',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('6287711122233', $pengurus->refresh()->phone); // tersimpan baku
    }

    /** B4 — admin membuat user dengan HP duplikat (format beda) juga ditolak. */
    public function test_b4_admin_tambah_user_hp_duplikat_ditolak(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create(['phone' => '081200011122']);

        $this->actingAs($admin)
            ->post('/admin/users', [
                'name' => 'Duplikat',
                'email' => 'dup@karen.test',
                'password' => 'rahasia123',
                'role' => 'pegawai',
                'phone' => '+6281200011122',
                'seksi_id' => Seksi::factory()->create()->id,
            ])
            ->assertSessionHasErrors('phone');
    }

    /**
     * F2 — membuat jadwal maintenance yang menabrak booking aktif
     * LANGSUNG mengarahkan ke halaman penggantian dengan peringatan.
     */
    public function test_f2_maintenance_menabrak_booking_langsung_ke_penggantian(): void
    {
        $pengurus = User::factory()->create(['role' => 'pengurus']);
        $v = Vehicle::factory()->create();

        Booking::create([
            'user_id' => User::factory()->create(['role' => 'pegawai'])->id,
            'vehicle_id' => $v->id,
            'start_date' => today()->toDateString(),
            'end_date' => today()->addDay()->toDateString(),
            'address' => 'Kantor B', 'purpose' => 'Rapat',
        ]);

        $this->actingAs($pengurus)
            ->post('/pengurus/maintenances', [
                'vehicle_id' => $v->id,
                'start_date' => today()->toDateString(),
                'end_date' => today()->addDay()->toDateString(),
            ])
            ->assertRedirect(route('pengurus.replacements.index'))
            ->assertSessionHas('warning');

        // Booking ter-flag menunggu penggantian (alur F2 inti)
        $this->assertSame(Booking::STATUS_MENUNGGU_PENGGANTIAN, Booking::first()->status);

        // Banner menunggu tampil di index maintenance
        $this->actingAs($pengurus)
            ->get('/pengurus/maintenances')
            ->assertOk()
            ->assertSee('menunggu mobil pengganti');
    }

    /** F1 — timezone aplikasi Asia/Jakarta (basis waktu tampilan konsisten). */
    public function test_f1_timezone_aplikasi_asia_jakarta(): void
    {
        $this->assertSame('Asia/Jakarta', config('app.timezone'));
        $this->assertSame('Asia/Jakarta', now()->getTimezone()->getName());
        $this->assertSame(420, now()->utcOffset()); // WIB = UTC+7 jam (420 menit)
    }
}
