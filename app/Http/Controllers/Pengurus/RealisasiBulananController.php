<?php

namespace App\Http\Controllers\Pengurus;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceCost;
use App\Models\VehicleBudget;
use App\Services\BudgetService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RealisasiBulananController extends Controller
{
    /**
     * Realisasi bulanan — semua nota per bulan + rincian per baris
     * (skema baru UAT 04). Pengurus dapat mengecek apa saja yang
     * dikerjakan / diganti per bulan.
     */
    public function index(Request $request): View|StreamedResponse
    {
        $bulan = $request->input('bulan', now()->format('Y-m'));
        $awal = Carbon::parse($bulan.'-01')->startOfMonth();
        $akhir = $awal->copy()->endOfMonth();

        if ($request->has('export')) {
            return $this->export($awal, $akhir);
        }

        // Semua maintenance_cost pada bulan tsb (via nota_date/start_date)
        $costs = MaintenanceCost::with(['maintenance.vehicle', 'details', 'maintenance'])
            ->whereHas('maintenance', fn ($q) => $q
                ->whereRaw('YEAR(COALESCE(nota_date, start_date)) = ? AND MONTH(COALESCE(nota_date, start_date)) = ?', [$awal->year, $awal->month]))
            ->where('raw_amount', '>', 0)
            ->orderBy('maintenance_id')
            ->get();

        // Ringkasan per pos (bulan ini)
        $ringkasan = collect(VehicleBudget::POSTS)->mapWithKeys(function ($post) use ($costs, $awal) {
            $realisasi = (float) $costs->where('post', $post)->sum('taxed_amount');
            $anggaran = (float) \App\Models\VehicleBudget::where('year', $awal->year)->where('post', $post)->sum('amount');

            return [$post => ['anggaran' => $anggaran, 'realisasi' => $realisasi, 'sisa' => $anggaran - $realisasi]];
        });

        // Month picker (12 bulan ke belakang)
        $bulanPilihan = collect(range(0, 11))
            ->map(fn ($i) => now()->subMonths($i)->format('Y-m'))
            ->mapWithKeys(fn ($b) => [$b => Carbon::parse($b.'-01')->translatedFormat('F Y')]);

        return view('pengurus.realisasi-bulanan.index', [
            'costs' => $costs,
            'ringkasan' => $ringkasan,
            'bulan' => $bulan,
            'bulanPilihan' => $bulanPilihan,
            'koefisien' => BudgetService::koefisienPajak(),
        ]);
    }

    private function export(Carbon $awal, Carbon $akhir): StreamedResponse
    {
        $costs = MaintenanceCost::with(['maintenance.vehicle', 'details'])
            ->whereHas('maintenance', fn ($q) => $q
                ->whereRaw('YEAR(COALESCE(nota_date, start_date)) = ? AND MONTH(COALESCE(nota_date, start_date)) = ?', [$awal->year, $awal->month]))
            ->where('raw_amount', '>', 0)
            ->get();

        return response()->streamDownload(function () use ($costs) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, ['tanggal', 'mobil', 'plat', 'bengkel', 'nota', 'pos', 'rincian', 'nilai', 'koefisien', 'total'], separator: ';');

            foreach ($costs as $c) {
                $m = $c->maintenance;
                $rincian = $c->details->map(fn ($d) => $d->description.($d->amount > 0 ? ' ('.number_format($d->amount, 0, ',', '.').')' : ''))->implode(' | ');
                fputcsv($out, [
                    optional($m->nota_date ?? $m->start_date)->format('Y-m-d'),
                    $m->vehicle->name,
                    $m->vehicle->plate_number,
                    $m->workshop_name ?? '-',
                    $m->nota_number ?? '-',
                    $c->post,
                    $rincian ?: '-',
                    $c->raw_amount,
                    $c->koefisien_used ?? '-',
                    $c->taxed_amount,
                ], separator: ';');
            }

            fclose($out);
        }, 'realisasi-bulanan-'.$awal->format('Y-m').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
