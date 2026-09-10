<?php

namespace App\Http\Controllers\Pengurus;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Models\VehicleBudget;
use App\Services\BudgetService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BudgetOverviewController extends Controller
{
    /**
     * Anggaran terpusat — semua kendaraan dalam satu halaman
     * (skema baru UAT 04: menu mandiri "Anggaran" untuk pengurus).
     */
    public function __construct(
        private readonly BudgetService $budgets,
    ) {}

    public function index(Request $request): View
    {
        $year = (int) $request->query('year', now()->year);
        $year = max(2000, min(2100, $year));

        $vehicles = Vehicle::orderBy('name')->get();

        // Ringkasan per kendaraan per pos
        $summaries = $vehicles->mapWithKeys(function ($v) use ($year) {
            return [$v->id => [
                'vehicle' => $v,
                'summary' => $this->budgets->summary($v, $year),
            ]];
        });

        // Total seluruh armada per pos
        $totals = collect(VehicleBudget::POSTS)->mapWithKeys(function ($post) use ($summaries) {
            $anggaran = $summaries->sum(fn ($s) => $s['summary'][$post]['anggaran']);
            $realisasi = $summaries->sum(fn ($s) => $s['summary'][$post]['realisasi']);

            return [$post => ['anggaran' => $anggaran, 'realisasi' => $realisasi, 'sisa' => $anggaran - $realisasi]];
        });

        return view('pengurus.anggaran.index', [
            'summaries' => $summaries,
            'totals' => $totals,
            'year' => $year,
            'koefisien' => BudgetService::koefisienPajak(),
        ]);
    }
}
