<?php

namespace App\Http\Controllers\Pengurus;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pengurus\MaintenanceRequest;
use App\Http\Requests\Pengurus\NotaRequest;
use App\Models\Maintenance;
use App\Models\Vehicle;
use App\Services\BudgetService;
use App\Services\DocumentService;
use App\Services\ReplacementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MaintenanceController extends Controller
{
    /**
     * Jadwal maintenance (docs/feature/manajemen_kendaraan.md &
     * penggantian_mobil.md) — kendaraan maintenance tidak tersedia
     * pada rentangnya; booking yang menabrak → menunggu_penggantian.
     * Setelah perawatan selesai: input nota 4 pos (×1,13) → generate
     * bend26 + draft nota (docs/feature/anggaran_maintenance.md).
     */
    public function __construct(
        private readonly ReplacementService $replacements,
        private readonly BudgetService $budgets,
        private readonly DocumentService $documents,
    ) {}

    public function index(Request $request): View
    {
        $maintenances = Maintenance::with('vehicle')
            ->when($request->status === 'selesai', fn ($q) => $q->where('status', 'selesai'))
            ->when(! $request->filled('status') || $request->status === 'terjadwal', fn ($q) => $q->where('status', 'terjadwal'))
            ->orderBy('start_date')
            ->get();

        return view('pengurus.maintenances.index', [
            'maintenances' => $maintenances,
            'vehicles' => Vehicle::orderBy('name')->get(),
            'status' => $request->input('status', 'terjadwal'),
        ]);
    }

    public function store(MaintenanceRequest $request): RedirectResponse
    {
        $maintenance = Maintenance::create($request->validated());

        $terdampak = $this->replacements->flagConflictingBookings($maintenance);

        $pesan = 'Jadwal maintenance tersimpan.';
        if (count($terdampak) > 0) {
            $pesan .= ' '.count($terdampak).' peminjaman terdampak — menunggu pengaturan mobil pengganti.';
        }

        return back()->with('success', $pesan);
    }

    public function update(MaintenanceRequest $request, Maintenance $maintenance): RedirectResponse
    {
        $maintenance->update($request->validated());

        // Dua arah: booking yang TIDAK lagi tertabrak dan belum diganti
        // kembali ke dipinjam; yang masih tertabrak ditandai ulang.
        $this->replacements->revertPendingForVehicle($maintenance->vehicle);
        $terdampak = $this->replacements->flagConflictingBookings($maintenance);

        $pesan = 'Jadwal maintenance diperbarui.';
        if (count($terdampak) > 0) {
            $pesan .= ' '.count($terdampak).' peminjaman terdampak.';
        }

        return back()->with('success', $pesan);
    }

    /**
     * Tandai perawatan selesai (input nota & anggaran menyusul di Fase 4b).
     */
    public function finish(Maintenance $maintenance): RedirectResponse
    {
        $maintenance->update(['status' => 'selesai']);

        return back()->with('success', 'Maintenance ditandai selesai — jadwal ini tidak lagi memblokir ketersediaan.');
    }

    /**
     * Hapus jadwal — booking yang masih menunggu pengganti pada mobil
     * ini dikembalikan ke status dipinjam (mobil semula).
     */
    public function destroy(Maintenance $maintenance): RedirectResponse
    {
        $vehicle = $maintenance->vehicle;
        $maintenance->delete();

        $dikembalikan = $this->replacements->revertPendingForVehicle($vehicle);

        $pesan = 'Jadwal maintenance dihapus.';
        if ($dikembalikan > 0) {
            $pesan .= " {$dikembalikan} peminjaman dikembalikan ke mobil semula.";
        }

        return back()->with('success', $pesan);
    }

    // ── Input nota & dokumen (Fase 4b) ────────────────────────────

    /**
     * Form input nota bengkel (4 pos) untuk maintenance selesai.
     */
    public function costs(Maintenance $maintenance): View
    {
        return view('pengurus.maintenances.costs', [
            'maintenance' => $maintenance->load('vehicle'),
            'costs' => $maintenance->costs->keyBy('post'),
        ]);
    }

    /**
     * Simpan nota: nilai per pos ×1,13 = realisasi; maintenance
     * berstatus selesai; bend26 + draft nota per pos digenerate.
     */
    public function inputNota(NotaRequest $request, Maintenance $maintenance): RedirectResponse
    {
        $this->budgets->inputNota($maintenance, [
            'workshop_name' => $request->input('workshop_name'),
            'nota_number' => $request->input('nota_number'),
            'nota_date' => $request->input('nota_date'),
            'costs' => $request->input('costs'),
        ]);

        $documents = $this->documents->generateForMaintenance($maintenance->refresh());

        $nota = collect($documents)->filter(fn ($d) => $d->type === 'draft_nota')->count();

        return redirect()
            ->route('pengurus.maintenances.index', ['status' => 'selesai'])
            ->with('success', 'Nota tersimpan (×1,13). bend26 + '.$nota.' draft nota digenerate — lihat menu Dokumen.');
    }

    /**
     * Generate ulang dokumen (bila nota direvisi — versi lama diarsipkan).
     */
    public function generate(Maintenance $maintenance): RedirectResponse
    {
        $documents = $this->documents->generateForMaintenance($maintenance->load('costs'));

        return back()->with('success', count($documents).' dokumen digenerate ulang (versi baru).');
    }
}
