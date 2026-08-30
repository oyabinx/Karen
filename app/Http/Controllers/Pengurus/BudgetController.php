<?php

namespace App\Http\Controllers\Pengurus;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pengurus\BudgetSaveRequest;
use App\Models\Vehicle;
use App\Services\BudgetService;
use Illuminate\Http\RedirectResponse;
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

    public function edit(Vehicle $vehicle, ?int $year = null): View
    {
        $year ??= now()->year;

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
        $this->budgets->setBudgets($vehicle, (int) $request->input('year'), $request->input('amounts'));

        return back()->with('success', 'Anggaran tahun '.$request->input('year').' disimpan.');
    }
}
