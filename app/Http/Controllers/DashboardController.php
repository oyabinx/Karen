<?php

namespace App\Http\Controllers;

use App\Models\Bidang;
use App\Models\Booking;
use App\Models\Event;
use App\Models\IntegrationSetting;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleBudget;
use App\Services\BudgetService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Router dashboard — menampilkan view sesuai role user
     * (docs/feature/dashboard.md). Kerangka dibuat Fase 2; Fase 8
     * menambah rincian admin (per role/bidang + kesehatan scheduler),
     * daftar pengurus (peminjaman hari ini, anggaran, event), dan
     * riwayat singkat pegawai.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('dashboard.'.$user->role, [
            'user' => $user,
            'data' => match ($user->role) {
                'admin' => $this->adminData(),
                'pengurus' => $this->pengurusData($user),
                'pegawai' => $this->pegawaiData($user),
            },
        ]);
    }

    private function adminData(): array
    {
        // Rincian user per role & per bidang (taskplan Fase 8)
        $perRole = User::query()->selectRaw('role, COUNT(*) AS total')->groupBy('role')->pluck('total', 'role');
        $perBidang = Bidang::query()
            ->withCount(['seksi as jumlah_seksi'])
            ->withCount(['users as jumlah_user' => fn ($q) => $q->whereNull('users.deleted_at')])
            ->orderBy('id')
            ->get();

        return [
            'totals' => [
                'user' => User::count(),
                'bidang' => Bidang::count(),
                'seksi' => \App\Models\Seksi::count(),
                'armada' => Vehicle::count(),
            ],
            'perRole' => collect(['admin', 'pengurus', 'pegawai'])->mapWithKeys(fn ($r) => [$r => (int) ($perRole[$r] ?? 0)]),
            'perBidang' => $perBidang,
            'scheduler' => $this->schedulerHealth(),
        ];
    }

    private function pengurusData(User $user): array
    {
        $year = now()->year;

        // Notifikasi pajak seluruh armada (UAT 03-A11)
        $pajakNotifs = Vehicle::query()->orderBy('name')->get()
            ->flatMap(fn ($v) => collect($v->pajakWarnings())->map(fn ($w) => ['v' => $v] + $w))
            ->values();

        $summaryAnggaran = collect(VehicleBudget::POSTS)->mapWithKeys(function ($post) use ($year) {
            $rows = VehicleBudget::where('year', $year)->where('post', $post)->get();

            return [$post => [
                'anggaran' => (float) $rows->sum('amount'),
                'realisasi' => (float) \App\Models\MaintenanceCost::where('post', $post)
                    ->whereHas('maintenance', fn ($q) => $q->whereRaw('YEAR(COALESCE(nota_date, start_date)) = ?', [$year]))
                    ->sum('taxed_amount'),
            ]];
        });

        return [
            'cards' => [
                'armada' => Vehicle::count(),
                'bisaDipinjam' => Vehicle::where('status', 'bisa_dipinjam')->where('condition', 'baik')->count(),
                'maintenanceAktif' => \App\Models\Maintenance::where('status', 'terjadwal')->count(),
                'menungguPengganti' => Booking::where('status', Booking::STATUS_MENUNGGU_PENGGANTIAN)->count(),
                'keluhanBelum' => \App\Models\Complaint::where('resolved', false)->count(),
            ],
            'peminjamanHariIni' => Booking::with(['user.seksi.bidang', 'vehicle'])
                ->where('status', Booking::STATUS_DIPINJAM)
                ->whereDate('start_date', '<=', today())
                ->whereDate('end_date', '>=', today())
                ->orderBy('end_date')
                ->limit(6)
                ->get(),
            'eventBerjalan' => Event::with('bidang')->withCount('vehicles as armada_count')
                ->where('status', 'terjadwal')->orderBy('start_date')->limit(3)->get(),
            'anggaran' => $summaryAnggaran,
            'tahunAnggaran' => $year,
            // Pengurus juga peminjam — kartu kuota bidangnya sendiri
            // (docs/feature/kuota_bidang.md Transparansi UI)
            'kuota' => app(\App\Services\BookingService::class)->quotaInfo($user),
            'pajakNotifs' => $pajakNotifs,
        ];
    }

    private function pegawaiData(User $user): array
    {
        return [
            // Riwayat singkat 5 terakhir (docs/feature/dashboard.md)
            'riwayatSingkat' => Booking::with('vehicle')
                ->where('user_id', $user->id)
                ->orderByDesc('start_date')
                ->limit(5)
                ->get(),
        ];
    }

    /**
     * Kesehatan pemicu scheduler (taskplan Fase 10.3): tugas harian
     * (auto-return/auto-finish) wajib meninggalkan jejak maksimal
     * ~36 jam; sinkron Sheets mengikuti setting google_last_run.
     * Peringatan merah hanya di produksi (dev kerap tanpa cron).
     */
    private function schedulerHealth(): array
    {
        $baca = fn (string $k) => IntegrationSetting::where('setting_key', $k)->value('value');

        $autoReturn = $baca('system_last_auto_return');
        $autoFinish = $baca('system_last_auto_finish');
        $sync = $baca('google_last_run');

        $stale = fn (?string $v, int $jam) => $v === null || now()->diffInHours(\Illuminate\Support\Carbon::parse($v)) > $jam;
        $produksi = app()->isProduction();

        return [
            'autoReturn' => ['waktu' => $autoReturn, 'buruk' => $produksi && $stale($autoReturn, 36)],
            'autoFinish' => ['waktu' => $autoFinish, 'buruk' => $produksi && $stale($autoFinish, 36)],
            'sync' => ['waktu' => $sync, 'buruk' => false], // interval bervariasi & bisa nonaktif — info saja
            'produksi' => $produksi,
        ];
    }
}
