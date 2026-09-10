<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AppSettingController;
use App\Http\Controllers\Admin\BidangController;
use App\Http\Controllers\Admin\IntegrationController;
use App\Http\Controllers\Admin\SeksiController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Pengurus\BudgetController;
use App\Http\Controllers\Pengurus\BudgetOverviewController;
use App\Http\Controllers\Pengurus\BookingMonitorController;
use App\Http\Controllers\Pengurus\DocumentController;
use App\Http\Controllers\Pengurus\EventController;
use App\Http\Controllers\Pegawai\BookingController as PegawaiBookingController;
use App\Http\Controllers\Pegawai\ReturnController;
use App\Http\Controllers\Pegawai\SearchController;
use App\Http\Controllers\Pengurus\ComplaintController;
use App\Http\Controllers\Pengurus\MaintenanceController;
use App\Http\Controllers\Pengurus\RealisasiBulananController;
use App\Http\Controllers\Pengurus\ReplacementController;
use App\Http\Controllers\Pengurus\ReportController;
use App\Http\Controllers\Pengurus\VehicleController;
use Illuminate\Support\Facades\Route;

// Root langsung mengarahkan: guest → login, sesi aktif → dashboard
// (temuan UAT A1 — tidak lagi menampilkan halaman sambutan Laravel)
Route::get('/', function (\Illuminate\Http\Request $request) {
    return redirect($request->user() ? 'dashboard' : 'login');
})->name('home');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    // Fitur hapus akun mandiri DIHAPUS — hanya admin yang menonaktifkan
    // user via Manajemen User (docs/feature/profil.md & manajemen_user.md).
});

// ── ADMIN: manajemen user (+impor CSV), bidang & seksi (kuota) ──
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::patch('/users/{id}/restore', [UserController::class, 'restore'])->name('users.restore');

        Route::get('/users/template', [UserController::class, 'template'])->name('users.template');
        Route::get('/users/import', [UserController::class, 'importPreview'])->name('users.import');
        Route::post('/users/import', [UserController::class, 'import'])->name('users.import.post');
        Route::post('/users/import/commit', [UserController::class, 'importCommit'])->name('users.import.commit');
        Route::get('/users/import/failed', [UserController::class, 'importFailed'])->name('users.import.failed');

        Route::get('/bidang', [BidangController::class, 'index'])->name('bidang.index');
        Route::post('/bidang', [BidangController::class, 'store'])->name('bidang.store');
        Route::put('/bidang/{bidang}', [BidangController::class, 'update'])->name('bidang.update');
        Route::delete('/bidang/{bidang}', [BidangController::class, 'destroy'])->name('bidang.destroy');

        Route::post('/bidang/{bidang}/seksi', [SeksiController::class, 'store'])->name('seksi.store');
        Route::put('/seksi/{seksi}', [SeksiController::class, 'update'])->name('seksi.update');
        Route::delete('/seksi/{seksi}', [SeksiController::class, 'destroy'])->name('seksi.destroy');

        // Konfigurasi integrasi Google (runtime di database)
        Route::get('/integrasi/google', [IntegrationController::class, 'show'])->name('integrasi.google');
        Route::post('/integrasi/google', [IntegrationController::class, 'update'])->name('integrasi.google.update');
        Route::delete('/integrasi/google/key', [IntegrationController::class, 'destroyKey'])->name('integrasi.google.key.destroy');
        Route::post('/integrasi/google/test', [IntegrationController::class, 'test'])->name('integrasi.google.test');
        Route::post('/integrasi/google/sync', [IntegrationController::class, 'syncNow'])->name('integrasi.google.sync');

        // Log aktivitas "siapa mengubah apa, kapan" (keputusan user pasca-UAT 03)
        Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');

        // Pengaturan aplikasi — durasi maksimal dll (skema baru UAT 03)
        Route::get('/settings', [AppSettingController::class, 'index'])->name('settings.index');
        Route::post('/settings', [AppSettingController::class, 'update'])->name('settings.update');
    });

// ── PENGURUS + ADMIN: kendaraan, maintenance, penggantian mobil,
//    anggaran & dokumen (permintaan user pasca-UAT 03) ──
Route::middleware(['auth', 'role:admin|pengurus'])
    ->prefix('pengurus')
    ->name('pengurus.')
    ->group(function () {
        Route::get('/vehicles', [VehicleController::class, 'index'])->name('vehicles.index');
        Route::get('/vehicles/create', [VehicleController::class, 'create'])->name('vehicles.create');
        Route::post('/vehicles', [VehicleController::class, 'store'])->name('vehicles.store');
        Route::get('/vehicles/{vehicle}/edit', [VehicleController::class, 'edit'])->name('vehicles.edit');
        Route::put('/vehicles/{vehicle}', [VehicleController::class, 'update'])->name('vehicles.update');
        Route::delete('/vehicles/{vehicle}', [VehicleController::class, 'destroy'])->name('vehicles.destroy');
        Route::patch('/vehicles/{vehicle}/status', [VehicleController::class, 'toggleStatus'])->name('vehicles.status');
        Route::patch('/vehicles/{vehicle}/condition', [VehicleController::class, 'markGood'])->name('vehicles.condition');
        Route::patch('/vehicles/{vehicle}/needs-inspection', [VehicleController::class, 'needsInspection'])->name('vehicles.needsInspection');

        Route::get('/maintenances', [MaintenanceController::class, 'index'])->name('maintenances.index');
        Route::post('/maintenances', [MaintenanceController::class, 'store'])->name('maintenances.store');
        Route::put('/maintenances/{maintenance}', [MaintenanceController::class, 'update'])->name('maintenances.update');
        Route::patch('/maintenances/{maintenance}/finish', [MaintenanceController::class, 'finish'])->name('maintenances.finish');
        Route::delete('/maintenances/{maintenance}', [MaintenanceController::class, 'destroy'])->name('maintenances.destroy');

        // Input nota bengkel (4 pos × 1,13) + generate dokumen
        Route::get('/maintenances/{maintenance}/costs', [MaintenanceController::class, 'costs'])->name('maintenances.costs');
        Route::put('/maintenances/{maintenance}/costs', [MaintenanceController::class, 'inputNota'])->name('maintenances.nota');
        Route::post('/maintenances/{maintenance}/generate', [MaintenanceController::class, 'generate'])->name('maintenances.generate');

        Route::get('/replacements', [ReplacementController::class, 'index'])->name('replacements.index');
        Route::get('/replacements/{booking}', [ReplacementController::class, 'show'])->name('replacements.show');
        Route::patch('/replacements/{booking}/assign', [ReplacementController::class, 'assign'])->name('replacements.assign');
        Route::patch('/replacements/{booking}/assign-partial', [ReplacementController::class, 'assignPartial'])->name('replacements.assignPartial');
        Route::patch('/replacements/{booking}/cancel', [ReplacementController::class, 'cancel'])->name('replacements.cancel');

        // Anggaran 4 pos per kendaraan (tombolnya ada di kartu kendaraan —
        // ikut dibuka untuk admin agar tidak mati)
        Route::get('/vehicles/{vehicle}/budgets', [BudgetController::class, 'edit'])->name('budgets.edit');
        Route::put('/vehicles/{vehicle}/budgets', [BudgetController::class, 'update'])->name('budgets.update');

        // Anggaran terpusat (semua kendaraan) — skema baru UAT 04
        Route::get('/anggaran', [BudgetOverviewController::class, 'index'])->name('anggaran.index');

        // Realisasi bulanan + rincian — skema baru UAT 04
        Route::get('/realisasi-bulanan', [RealisasiBulananController::class, 'index'])->name('realisasi-bulanan.index');

        // Dokumen hasil generate
        Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
        Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
        Route::post('/vehicles/{vehicle}/generate-kartu-inventaris', [DocumentController::class, 'kartuInventaris'])->name('documents.kartu');
    });

// ── PENGURUS + ADMIN: keluhan unit, monitoring & laporan ──
// (keputusan user: admin harus bisa melihat apa yang dilihat pengurus
//  saat ada komplain — seluruh menu sisi pengurus kini dibagi admin)
Route::middleware(['auth', 'role:admin|pengurus'])
    ->prefix('pengurus')
    ->name('pengurus.')
    ->group(function () {
        Route::get('/complaints', [ComplaintController::class, 'index'])->name('complaints.index');
        Route::patch('/complaints/{complaint}/resolve', [ComplaintController::class, 'resolve'])->name('complaints.resolve');
        Route::patch('/complaints/{complaint}/reopen', [ComplaintController::class, 'reopen'])->name('complaints.reopen');

        Route::get('/bookings', [BookingMonitorController::class, 'index'])->name('bookings.index');
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    });

// ── EVENT ARMADA BIDANG — pengurus DAN admin (docs/feature/event_bidang.md) ──
Route::middleware(['auth', 'role:admin|pengurus'])
    ->prefix('pengurus/events')
    ->name('pengurus.events.')
    ->group(function () {
        Route::get('/', [EventController::class, 'index'])->name('index');
        Route::get('/create', [EventController::class, 'create'])->name('create');
        Route::post('/', [EventController::class, 'store'])->name('store');
        Route::get('/{event}/conflicts', [EventController::class, 'conflicts'])->name('conflicts');
        Route::patch('/{event}/conflicts/{booking}', [EventController::class, 'assignBooking'])->name('conflicts.assign');
        Route::patch('/{event}/conflicts/{booking}/partial', [EventController::class, 'assignPartial'])->name('conflicts.assignPartial');
        Route::patch('/{event}/conflicts/{booking}/cancel', [EventController::class, 'cancelBooking'])->name('conflicts.cancel');
        Route::patch('/{event}/confirm', [EventController::class, 'confirm'])->name('confirm');
        Route::patch('/{event}/cancel', [EventController::class, 'cancel'])->name('cancel');
    });

// ── PEMINJAMAN (pegawai DAN pengurus) — docs/feature/peminjaman.md ──
Route::middleware(['auth', 'role:pegawai|pengurus'])
    ->prefix('pegawai')
    ->name('pegawai.')
    ->group(function () {
        Route::get('/search', [SearchController::class, 'index'])->name('search.index');
        Route::get('/bookings', [PegawaiBookingController::class, 'index'])->name('bookings.index');
        Route::get('/bookings/create', [PegawaiBookingController::class, 'create'])->name('bookings.create');
        Route::post('/bookings', [PegawaiBookingController::class, 'store'])->name('bookings.store');
        Route::post('/returns/{booking}', [ReturnController::class, 'store'])->name('returns.store');
        Route::post('/returns/{booking}/cancel', [ReturnController::class, 'cancel'])->name('returns.cancel');
    });

require __DIR__.'/auth.php';
