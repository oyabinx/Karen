<?php

use App\Http\Controllers\Admin\BidangController;
use App\Http\Controllers\Admin\SeksiController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
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

        Route::get('/replacements', [ReplacementController::class, 'index'])->name('replacements.index');
        Route::get('/replacements/{booking}', [ReplacementController::class, 'show'])->name('replacements.show');
        Route::patch('/replacements/{booking}/assign', [ReplacementController::class, 'assign'])->name('replacements.assign');
        Route::patch('/replacements/{booking}/cancel', [ReplacementController::class, 'cancel'])->name('replacements.cancel');
    });

require __DIR__.'/auth.php';
