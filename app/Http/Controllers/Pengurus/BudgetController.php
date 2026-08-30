<?php

namespace App\Http\Controllers\Pengurus;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pengurus\BudgetSaveRequest;
use App\Models\Vehicle;
use App\Services\BudgetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BudgetController extends Controller
{
    /**
     * Anggaran maintenance 4 pos per kendaraan
     * (docs/feature/anggaran_maintenance.md).
     */
    public function __construct(
        private readonly BudgetService $budgets,
    ) {}

    public function edit(Request $request, Vehicle $vehicle): View
    {
        // Tahun anggaran dapat dipilih via ?year= — alokasi tiap tahun
        // INDEPENDEN (2026 ≠ 2027), docs/feature/anggaran_maintenance.md
        $year = (int) $request->query('year', now()->year);
        $year = max(2000, min(2100, $year));

        return view('pengurus.budgets.edit', [
            'vehicle' => $vehicle,
            'year' => $year,
            'summary' => $this->budgets->summary($vehicle, $year),
            'budgets' => \App\Models\VehicleBudget::where('vehicle_id', $vehicle->id)
                ->where('year', $year)
                ->get()->keyBy('post'),
        ]);
    }

    public function update(BudgetSaveRequest $request, Vehicle $vehicle): RedirectResponse
    {
        $year = (int) $request->input('year');

        $this->budgets->setBudgets($vehicle, $year, $request->input('amounts'));

        // Kembali ke halaman tahun yang baru disunting (bukan tahun berjalan)
        return redirect()
            ->route('pengurus.budgets.edit', ['vehicle' => $vehicle, 'year' => $year])
            ->with('success', 'Anggaran tahun '.$year.' disimpan.');
    }
}
