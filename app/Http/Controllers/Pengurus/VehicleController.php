<?php

namespace App\Http\Controllers\Pengurus;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pengurus\VehicleStoreRequest;
use App\Http\Requests\Pengurus\VehicleUpdateRequest;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class VehicleController extends Controller
{
    /**
     * Manajemen kendaraan — pengurus (docs/feature/manajemen_kendaraan.md).
     */
    public function index(Request $request): View
    {
        $vehicles = Vehicle::query()
            ->when($request->tab === 'tidak_bisa_dipinjam', fn ($q) => $q->where('status', 'tidak_bisa_dipinjam'))
            ->when($request->tab === 'perlu_diperiksa', fn ($q) => $q->where('condition', 'perlu_diperiksa'))
            ->when(! $request->filled('tab') || $request->tab === 'semua', fn ($q) => $q->withTrashed())
            ->when($request->tab === 'tersedia', fn ($q) => $q->where('status', 'bisa_dipinjam')->where('condition', 'baik'))
            ->orderBy('name')
            ->get();

        return view('pengurus.vehicles.index', [
            'vehicles' => $vehicles,
            'tab' => $request->input('tab', 'semua'),
        ]);
    }

    public function create(): View
    {
        return view('pengurus.vehicles.form', ['vehicle' => new Vehicle()]);
    }

    public function store(VehicleStoreRequest $request): RedirectResponse
    {
        Vehicle::create($this->payload($request));

        return redirect()->route('pengurus.vehicles.index')->with('success', 'Kendaraan berhasil ditambahkan.');
    }

    public function edit(Vehicle $vehicle): View
    {
        return view('pengurus.vehicles.form', ['vehicle' => $vehicle]);
    }

    public function update(VehicleUpdateRequest $request, Vehicle $vehicle): RedirectResponse
    {
        $vehicle->update($this->payload($request, $vehicle));

        return redirect()->route('pengurus.vehicles.index')->with('success', 'Kendaraan berhasil diperbarui.');
    }

    /**
     * Soft delete — riwayat booking tetap utuh.
     */
    public function destroy(Vehicle $vehicle): RedirectResponse
    {
        $vehicle->delete();

        return back()->with('success', 'Kendaraan dinonaktifkan dari daftar (riwayat tetap tersimpan).');
    }

    /**
     * Toggle bisa_dipinjam <-> tidak_bisa_dipinjam.
     */
    public function toggleStatus(Vehicle $vehicle): RedirectResponse
    {
        $vehicle->update([
            'status' => $vehicle->status === 'bisa_dipinjam' ? 'tidak_bisa_dipinjam' : 'bisa_dipinjam',
        ]);

        return back()->with('success', 'Status kendaraan kini: '.($vehicle->status === 'bisa_dipinjam' ? 'bisa dipinjam' : 'tidak bisa dipinjam').'.');
    }

    /**
     * Tandai kondisi perlu diperiksa — MANUAL saja oleh pengurus
     * (keluhan pengembalian TIDAK mengubah kondisi; kesepakatan
     * docs/feature/pengembalian.md).
     */
    public function needsInspection(Vehicle $vehicle): RedirectResponse
    {
        $vehicle->update(['condition' => 'perlu_diperiksa']);

        return back()->with('success', 'Unit ditandai perlu diperiksa — tidak bisa dipinjam sementara.');
    }

    /**
     * Set kondisi kembali baik setelah selesai diperiksa pengurus.
     */
    public function markGood(Vehicle $vehicle): RedirectResponse
    {
        $vehicle->update(['condition' => 'baik']);

        return back()->with('success', 'Kondisi kendaraan dikembalikan ke baik.');
    }

    private function payload(VehicleStoreRequest|VehicleUpdateRequest $request, ?Vehicle $vehicle = null): array
    {
        $data = collect($request->validated())->except('photo')->all();

        if ($request->hasFile('photo')) {
            // Ganti foto lama bila ada
            if ($vehicle?->photo_path) {
                Storage::disk('public')->delete($vehicle->photo_path);
            }
            $data['photo_path'] = $request->file('photo')->store('vehicles', 'public');
        }

        return $data;
    }
}
