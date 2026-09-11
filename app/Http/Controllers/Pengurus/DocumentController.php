<?php

namespace App\Http\Controllers\Pengurus;

use App\Http\Controllers\Controller;
use App\Models\GeneratedDocument;
use App\Models\Vehicle;
use App\Models\VehicleBudget;
use App\Services\DocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DocumentController extends Controller
{
    /**
     * Hasil generate dokumen — draft nota (per maintenance, otomatis),
     * bend26 BULANAN per pos (on demand), kartu pemeliharaan (dari
     * Laporan). Dokumen revisi ditimpa di tempat + badge Diperbarui.
     */
    public function __construct(
        private readonly DocumentService $documents,
    ) {}

    public function index(Request $request): View
    {
        $vehicle = $request->filled('vehicle')
            ? Vehicle::findOrFail($request->input('vehicle'))
            : null;

        return view('pengurus.documents.index', [
            'documents' => $this->documents->latestDocuments($vehicle),
            'vehicles' => Vehicle::orderBy('name')->get(),
            'vehicle' => $vehicle,
        ]);
    }

    public function download(GeneratedDocument $document)
    {
        return $this->documents->download($document);
    }

    /**
     * Generate bend26 (BKPN) bulanan — per pos atau semua pos bernilai.
     * Dipanggil dari menu Realisasi Bulanan (UAT 04 rev-2).
     */
    public function bend26Bulanan(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
            'post' => ['nullable', 'in:'.implode(',', VehicleBudget::POSTS)],
        ]);

        [$year, $month] = array_map('intval', explode('-', $data['month']));

        $docs = $this->documents->bend26Bulanan($year, $month, $data['post'] ?? null);

        if (empty($docs)) {
            return back()->with('warning', 'Tidak ada realisasi nota pada bulan tersebut — bend26 tidak dibuat.');
        }

        $diperbarui = collect($docs)->filter(fn ($d) => $d->regenerated_at !== null)->count();

        return redirect()
            ->route('pengurus.documents.index')
            ->with('success', count($docs).' bend26 bulanan '.($diperbarui > 0 ? 'diperbarui' : 'digenerate').' — lihat menu Dokumen.');
    }

    public function kartuPemeliharaan(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $data = $request->validate(['year' => ['nullable', 'integer', 'min:2000', 'max:2100']]);

        $doc = $this->documents->kartuPemeliharaan($vehicle, isset($data['year']) ? (int) $data['year'] : null);

        return redirect()
            ->route('pengurus.documents.download', $doc)
            ->with('success', 'Kartu Pemeliharaan Kendaraan '.($doc->regenerated_at ? 'diperbarui' : 'digenerate').' — mengunduh.');
    }
}
