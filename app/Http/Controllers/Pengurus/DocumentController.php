<?php

namespace App\Http\Controllers\Pengurus;

use App\Http\Controllers\Controller;
use App\Models\GeneratedDocument;
use App\Models\Vehicle;
use App\Services\DocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DocumentController extends Controller
{
    /**
     * Hasil generate dokumen (bend26, draft nota, kartu inventaris)
     * — unduh & regenerasi (docs/feature/anggaran_maintenance.md).
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

    public function kartuInventaris(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $data = $request->validate(['year' => ['nullable', 'integer', 'min:2000', 'max:2100']]);

        $doc = $this->documents->kartuInventaris($vehicle, isset($data['year']) ? (int) $data['year'] : null);

        return redirect()
            ->route('pengurus.documents.download', $doc)
            ->with('success', 'Kartu inventaris digenerate — mengunduh versi v'.$doc->version.'.');
    }
}
