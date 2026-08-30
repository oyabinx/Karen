<?php

use App\Http\Controllers\Admin\BidangController;
use App\Http\Controllers\Admin\IntegrationController;
use App\Http\Controllers\Admin\SeksiController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Pengurus\BudgetController;
use App\Http\Controllers\Pengurus\DocumentController;
use App\Http\Controllers\Pengurus\MaintenanceController;
use App\Http\Controllers\Pengurus\ReplacementController;
use App\Http\Controllers\Pengurus\VehicleController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

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
    });

// ── PENGURUS: kendaraan, maintenance, penggantian mobil ──
Route::middleware(['auth', 'role:pengurus'])
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
        Route::patch('/replacements/{booking}/cancel', [ReplacementController::class, 'cancel'])->name('replacements.cancel');

        // Anggaran 4 pos per kendaraan
        Route::get('/vehicles/{vehicle}/budgets', [BudgetController::class, 'edit'])->name('budgets.edit');
        Route::put('/vehicles/{vehicle}/budgets', [BudgetController::class, 'update'])->name('budgets.update');

        // Dokumen hasil generate
        Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
        Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
        Route::post('/vehicles/{vehicle}/generate-kartu-inventaris', [DocumentController::class, 'kartuInventaris'])->name('documents.kartu');
    });

require __DIR__.'/auth.php';
