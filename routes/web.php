<?php

use App\Http\Controllers\Admin\BidangController;
use App\Http\Controllers\Admin\SeksiController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
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

require __DIR__.'/auth.php';
