<?php

namespace App\Http\Controllers\Pengurus;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceCost;
use App\Models\VehicleBudget;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RealisasiBulananController extends Controller
{
    /**
     * Realisasi bulanan — default: LIST MOBIL yang maintenance pada
     * bulan terpilih; klik → rincian per pos (rev UAT 04 user).
     */
    public function index(Request $request): View|StreamedResponse
    {
        $bulan = $request->input('bulan', now()->format('Y-m'));
        $vehicleId = $request->input('vehicle');
        $awal = Carbon::parse($bulan.'-01')->startOfMonth();

        if ($request->has('export')) {
            return $this->export($awal, $vehicleId);
        }

        $costs = MaintenanceCost::with(['maintenance.vehicle', 'details'])
            ->whereHas('maintenance', fn ($q) => $q
                ->whereRaw('YEAR(COALESCE(nota_date, start_date)) = ? AND MONTH(COALESCE(nota_date, start_date)) = ?', [$awal->year, $awal->month]))
            ->where('raw_amount', '>', 0)
            ->when($vehicleId, fn ($q) => $q->whereHas('maintenance', fn ($m) => $m->where('vehicle_id', $vehicleId)))
            ->orderBy('maintenance_id')
            ->get();

        // LEVEL 1: group by vehicle — maintenance TERBARU di atas (UAT 04-D1)
        $perVehicle = $costs->groupBy(fn ($c) => $c->maintenance->vehicle_id)->map(function ($group) {
            $perPost = collect(VehicleBudget::POSTS)->mapWithKeys(function ($post) use ($group) {
                // Nota terbaru dulu dalam tiap pos
                $postCosts = $group->where('post', $post)
                    ->sortByDesc(fn ($c) => optional($c->maintenance->nota_date ?? $c->maintenance->start_date)->timestamp)
                    ->values();

                return [$post => [
                    'raw' => $postCosts->sum('raw_amount'),
                    'taxed' => $postCosts->sum('taxed_amount'),
                    'costs' => $postCosts,
                ]];
            });

            return [
                'vehicle' => $group->first()->maintenance->vehicle,
                'totalRaw' => $group->sum('raw_amount'),
                'totalTaxed' => $group->sum('taxed_amount'),
                'perPost' => $perPost,
                'maintenanceCount' => $group->groupBy('maintenance_id')->count(),
                'lastDate' => $group->max(fn ($c) => optional($c->maintenance->nota_date ?? $c->maintenance->start_date)->timestamp),
            ];
        })->sortByDesc('lastDate')->values();

        // Ringkasan 4 pos (seluruh armada)
        $ringkasan = collect(VehicleBudget::POSTS)->mapWithKeys(function ($post) use ($costs, $awal) {
            $realisasi = (float) $costs->where('post', $post)->sum('taxed_amount');
            $anggaran = (float) VehicleBudget::where('year', $awal->year)->where('post', $post)->sum('amount');
            return [$post => ['anggaran' => $anggaran, 'realisasi' => $realisasi, 'sisa' => $anggaran - $realisasi]];
        });

        $bulanPilihan = collect(range(0, 11))
            ->map(fn ($i) => now()->subMonths($i)->format('Y-m'))
            ->mapWithKeys(fn ($b) => [$b => Carbon::parse($b.'-01')->translatedFormat('F Y')]);

        $selectedVehicle = $vehicleId ? $perVehicle->firstWhere('vehicle.id', (int) $vehicleId) : null;

        return view('pengurus.realisasi-bulanan.index', [
            'perVehicle' => $perVehicle,
            'selectedVehicle' => $selectedVehicle,
            'ringkasan' => $ringkasan,
            'bulan' => $bulan,
            'bulanPilihan' => $bulanPilihan,
            'vehicleId' => $vehicleId,
        ]);
    }

    private function export(Carbon $awal, ?string $vehicleId): StreamedResponse
    {
        $costs = MaintenanceCost::with(['maintenance.vehicle', 'details'])
            ->whereHas('maintenance', fn ($q) => $q
                ->whereRaw('YEAR(COALESCE(nota_date, start_date)) = ? AND MONTH(COALESCE(nota_date, start_date)) = ?', [$awal->year, $awal->month]))
            ->where('raw_amount', '>', 0)
            ->when($vehicleId, fn ($q) => $q->whereHas('maintenance', fn ($m) => $m->where('vehicle_id', $vehicleId)))
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
                    $m->vehicle->name, $m->vehicle->plate_number,
                    $m->workshop_name ?? '-', $m->nota_number ?? '-',
                    $c->post, $rincian ?: '-',
                    $c->raw_amount, $c->koefisien_used ?? '-', $c->taxed_amount,
                ], separator: ';');
            }

            fclose($out);
        }, 'realisasi-bulanan-'.$awal->format('Y-m').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
