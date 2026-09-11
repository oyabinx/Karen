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
        // Terbaru di atas (UAT 04-B4) + costs untuk label tombol Input/Edit Nota
        $maintenances = Maintenance::with(['vehicle', 'costs'])
            ->when($request->status === 'selesai', fn ($q) => $q->where('status', 'selesai'))
            ->when(! $request->filled('status') || $request->status === 'terjadwal', fn ($q) => $q->where('status', 'terjadwal'))
            ->orderByDesc('start_date')
            ->orderByDesc('id')
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

        $terdampak = count($this->replacements->flagConflictingBookings($maintenance));

        // PERINGATAN KUAT (UAT F2): bila jadwal menabrak peminjaman aktif,
        // LANGSUNG arahkan ke halaman penggantian mobil — tidak lagi
        // hanya pesan sukses kecil yang mudah terlewat.
        if ($terdampak > 0) {
            return redirect()
                ->route('pengurus.replacements.index')
                ->with('warning', "⚠ Jadwal menabrak {$terdampak} peminjaman aktif! Pilih mobil pengganti (atau batalkan peminjaman) sekarang.");
        }

        return back()->with('success', 'Jadwal maintenance tersimpan.');
    }

    public function update(MaintenanceRequest $request, Maintenance $maintenance): RedirectResponse
    {
        $maintenance->update($request->validated());

        // Dua arah: booking yang TIDAK lagi tertabrak dan belum diganti
        // kembali ke dipinjam; yang masih tertabrak ditandai ulang.
        $this->replacements->revertPendingForVehicle($maintenance->vehicle);
        $terdampak = count($this->replacements->flagConflictingBookings($maintenance));

        if ($terdampak > 0) {
            return redirect()
                ->route('pengurus.replacements.index')
                ->with('warning', "⚠ Perubahan jadwal menabrak {$terdampak} peminjaman aktif — atur mobil penggantinya.");
        }

        return back()->with('success', 'Jadwal maintenance diperbarui.');
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
     * Simpan nota: rincian per baris dijumlah otomatis per pos,
     * dikalikan koefisien pajak; maintenance berstatus selesai;
     * draft nota per pos digenerate (timpa di tempat bila revisi —
     * bend26 kini dokumen BULANAN, digenerate terpisah dari
     * Realisasi Bulanan, UAT 04 rev-2).
     */
    public function inputNota(NotaRequest $request, Maintenance $maintenance): RedirectResponse
    {
        // Sudah ada nota sebelumnya? → mode EDIT (dokumen ditimpa, bukan versi baru)
        $modeEdit = $maintenance->costs()->where('raw_amount', '>', 0)->exists();

        $this->budgets->inputNota($maintenance, [
            'workshop_name' => $request->input('workshop_name'),
            'nota_number' => $request->input('nota_number'),
            'nota_date' => $request->input('nota_date'),
            'details' => $request->input('details', []),
            'detail_amounts' => $request->input('detail_amounts', []),
        ]);

        $documents = $this->documents->generateForMaintenance($maintenance->refresh());

        $nota = count($documents);

        $pesan = $modeEdit
            ? "Nota diperbarui — {$nota} draft nota ditimpa dengan nilai terbaru (lihat badge Diperbarui di menu Dokumen)."
            : "Nota tersimpan (total dari rincian, dikalikan koefisien pajak). {$nota} draft nota digenerate — lihat menu Dokumen. bend26 dibuat bulanan dari menu Realisasi Bulanan.";

        return redirect()
            ->route('pengurus.maintenances.index', ['status' => 'selesai'])
            ->with('success', $pesan);
    }
}
