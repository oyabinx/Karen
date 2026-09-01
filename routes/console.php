<?php

use App\Models\IntegrationLog;
use App\Services\EventService;
use App\Services\Google\SheetsBudgetSync;
use App\Services\ReturnService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ── Scheduler (docs/tech.md §4.3 & §7) ────────────────────────────
// Pemicu eksternal (cron/systemd/Task Scheduler) menjalankan
// `php artisan schedule:run` tiap menit — pasang SEKALI oleh IT.

// Pengembalian otomatis + pembatalan booking menunggu_penggantian
// yang lewat jatuh tempo (idempoten)
Schedule::call(fn () => app(ReturnService::class)->autoReturn())
    ->dailyAt('00:01')
    ->name('bookings:auto-return');

// Event armada lewat end_date → selesai otomatis (idemponen)
Schedule::call(fn () => app(EventService::class)->autoFinish())
    ->dailyAt('00:02')
    ->name('events:auto-finish');

// Sinkronisasi Google Sheets anggaran — cek interval di dalam task
// (5/15/30/60 menit, diatur admin) agar interval bisa diubah tanpa
// menyentuh cron
Schedule::call(fn () => app(SheetsBudgetSync::class)->runIfDue())
    ->everyFiveMinutes()
    ->name('budgets:sync-sheets');

// Retensi log integrasi 90 hari (docs/feature/integrasi_google.md)
Schedule::call(fn () => IntegrationLog::where('ran_at', '<', now()->subDays(90))->delete())
    ->weekly()
    ->name('integration-logs:purge');
