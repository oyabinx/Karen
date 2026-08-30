<?php

namespace App\Http\Controllers\Pengurus;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pengurus\MaintenanceRequest;
use App\Models\Maintenance;
use App\Models\Vehicle;
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
     */
    public function __construct(
        private readonly ReplacementService $replacements,
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
}
