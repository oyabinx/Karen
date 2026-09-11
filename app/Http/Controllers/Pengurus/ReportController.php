<?php

namespace App\Http\Controllers\Pengurus;

use App\Http\Controllers\Controller;
use App\Models\Bidang;
use App\Models\Booking;
use App\Models\Complaint;
use App\Models\MaintenanceCost;
use App\Models\Vehicle;
use App\Models\VehicleBudget;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /**
     * Laporan penggunaan kendaraan + realisasi anggaran per pos
     * (docs/feature/laporan.md) — default bulan berjalan.
     */
    public function index(Request $request): View|StreamedResponse
    {
        $from = Carbon::parse($request->input('from', now()->startOfMonth()->toDateString()));
        $to = Carbon::parse($request->input('to', now()->endOfMonth()->toDateString()));
        $bidangId = $request->input('bidang') ?: null;

        if ($request->has('export')) {
            return $this->export($from, $to, $bidangId, (bool) $request->boolean('with_budget'));
        }

        // Rentang overlap: booking yang menyentuh [from..to]
        $base = fn () => Booking::query()
            ->when($bidangId, fn ($q) => $q->whereHas('user.seksi.bidang', fn ($b) => $b->where('id', $bidangId)))
            ->whereDate('start_date', '<=', $to)
            ->whereDate('end_date', '>=', $from);

        $semua = (clone $base())->with(['vehicle', 'user.seksi.bidang'])->get();
        $tahun = now()->year;

        return view('pengurus.reports.index', [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'bidangList' => Bidang::orderBy('id')->get(),
            'bidangId' => $bidangId,
            'vehicles' => Vehicle::orderBy('name')->get(),
            'rekap' => $this->rekapPerMobil($semua),
            'totals' => [
                'peminjaman' => $semua->count(),
                'hariPakai' => $semua->sum(fn ($b) => $b->start_date->diffInDays($b->end_date) + 1),
                'keluhan' => Complaint::whereHas('booking', fn ($q) => $q
                    ->whereDate('start_date', '<=', $to)->whereDate('end_date', '>=', $from))->count(),
                'keluhanBelum' => Complaint::where('resolved', false)->count(),
                'terlambatOtomatis' => (clone $base())->where('auto_returned', true)
                    ->where('status', 'dikembalikan')->count(),
                'dibatalkanSistem' => (clone $base())->where('status', 'dibatalkan')->where('auto_returned', true)->count(),
            ],
            'anggaran' => $this->rekapAnggaran($tahun),
            'tahun' => $tahun,
        ]);
    }

    /**
     * Hari pakai & jumlah peminjaman per mobil.
     */
    private function rekapPerMobil(Collection $semua): Collection
    {
        return $semua
            ->groupBy('vehicle_id')
            ->map(fn ($group, $vehicleId) => [
                'vehicle' => $group->first()->vehicle,
                'jumlah' => $group->count(),
                'hariPakai' => $group->sum(fn ($b) => $b->start_date->diffInDays($b->end_date) + 1),
            ])
            ->sortByDesc('hariPakai')
            ->values();
    }

    /**
     * Realisasi anggaran per pos tahun berjalan (semua unit).
     */
    private function rekapAnggaran(int $tahun): array
    {
        return collect(VehicleBudget::POSTS)->mapWithKeys(function ($post) use ($tahun) {
            $anggaran = (float) VehicleBudget::where('year', $tahun)->where('post', $post)->sum('amount');
            $realisasi = (float) MaintenanceCost::where('post', $post)
                ->whereHas('maintenance', fn ($q) => $q->whereRaw('YEAR(COALESCE(nota_date, start_date)) = ?', [$tahun]))
                ->sum('taxed_amount');

            return [$post => ['anggaran' => $anggaran, 'realisasi' => $realisasi, 'sisa' => $anggaran - $realisasi]];
        })->all();
    }

    /**
     * Export CSV sesuai filter aktif (pemisah ; — locale Excel Indonesia).
     */
    private function export(Carbon $from, Carbon $to, ?int $bidangId, bool $withBudget): StreamedResponse
    {
        $semua = Booking::query()
            ->with(['vehicle', 'user.seksi.bidang'])
            ->when($bidangId, fn ($q) => $q->whereHas('user.seksi.bidang', fn ($b) => $b->where('id', $bidangId)))
            ->whereDate('start_date', '<=', $to)
            ->whereDate('end_date', '>=', $from)
            ->orderBy('start_date')
            ->get();

        return response()->streamDownload(function () use ($semua, $withBudget) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8 agar Excel benar
            fputcsv($out, ['mulai', 'selesai', 'mobil', 'plat', 'peminjam', 'bidang', 'alamat', 'keperluan', 'status', 'dikembalikan_otomatis', 'diganti_dari_unit'], separator: ';');

            foreach ($semua as $b) {
                fputcsv($out, [
                    $b->start_date->format('Y-m-d'),
                    $b->end_date->format('Y-m-d'),
                    $b->vehicle->name,
                    $b->vehicle->plate_number,
                    $b->user->name,
                    $b->user->seksi?->bidang?->name ?? '-',
                    $b->address,
                    $b->purpose,
                    $b->status,
                    $b->auto_returned ? 'ya' : 'tidak',
                    $b->originalVehicle?->name ?? '-',
                ], separator: ';');
            }

            if ($withBudget) {
                fputcsv($out, [], separator: ';');
                fputcsv($out, ['pos', 'anggaran_tahun', 'realisasi_x1_13', 'sisa'], separator: ';');
                foreach ($this->rekapAnggaran(now()->year) as $post => $a) {
                    fputcsv($out, [$post, $a['anggaran'], $a['realisasi'], $a['sisa']], separator: ';');
                }
            }

            fclose($out);
        }, 'laporan-karen-'.$from->format('Ymd').'-'.$to->format('Ymd').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
