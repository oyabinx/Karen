<?php

namespace App\Services;

use App\Models\GeneratedDocument;
use App\Models\Maintenance;
use App\Models\Vehicle;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * Generate dokumen PDF dari master draft di resources/draft_documents/
 * (docs/feature/anggaran_maintenance.md §4 & docs/structure.md).
 *
 * Jenis: bend26 (bukti pengeluaran bendahara), draft_nota per pos,
 * kartu_inventaris (rekap per kendaraan).
 * Hasil tersimpan di storage/app/documents/ — versi lama diarsipkan
 * (version bertambah, file lama tidak dihapus).
 */
class DocumentService
{
    public function __construct(
        private readonly BudgetService $budgets,
    ) {}

    /**
     * Generate bend26 + draft nota untuk satu maintenance
     * (dipanggil setelah input nota / regenerasi).
     *
     * @return GeneratedDocument[] dokumen yang dihasilkan
     */
    public function generateForMaintenance(Maintenance $maintenance): array
    {
        $maintenance->load(['vehicle', 'costs']);
        $documents = [$this->bend26($maintenance)];

        foreach ($this->budgets->filledCosts($maintenance) as $cost) {
            $documents[] = $this->draftNota($maintenance, $cost->post);
        }

        return $documents;
    }

    public function bend26(Maintenance $maintenance): GeneratedDocument
    {
        return $this->store(
            view: 'drafts::bend26',
            data: [
                'maintenance' => $maintenance,
                'vehicle' => $maintenance->vehicle,
                'costs' => $maintenance->costs,
                'totalRaw' => $maintenance->costs->sum('raw_amount'),
                'totalTaxed' => $maintenance->costs->sum('taxed_amount'),
                'koefisien' => BudgetService::KOEFISIEN_PAJAK,
            ],
            type: GeneratedDocument::TYPE_BEND26,
            maintenance: $maintenance,
            vehicle: $maintenance->vehicle,
            filename: "bend26-m{$maintenance->id}",
        );
    }

    public function draftNota(Maintenance $maintenance, string $post): GeneratedDocument
    {
        $cost = $maintenance->costs->firstWhere('post', $post);

        return $this->store(
            view: 'drafts::draft_nota',
            data: [
                'maintenance' => $maintenance,
                'vehicle' => $maintenance->vehicle,
                'cost' => $cost,
                'koefisien' => BudgetService::KOEFISIEN_PAJAK,
            ],
            type: GeneratedDocument::TYPE_DRAFT_NOTA,
            maintenance: $maintenance,
            vehicle: $maintenance->vehicle,
            post: $post,
            filename: "nota-{$post}-m{$maintenance->id}",
        );
    }

    /**
     * Kartu inventaris pemeliharaan: seluruh riwayat maintenance mobil
     * + akumulasi per pos vs anggaran + sisa.
     */
    public function kartuInventaris(Vehicle $vehicle, ?int $year = null): GeneratedDocument
    {
        $year ??= now()->year;

        $maintenances = Maintenance::with('costs')
            ->where('vehicle_id', $vehicle->id)
            ->whereRaw('YEAR(COALESCE(nota_date, start_date)) = ?', [$year])
            ->orderBy('start_date')
            ->get();

        return $this->store(
            view: 'drafts::kartu_inventaris',
            data: [
                'vehicle' => $vehicle,
                'year' => $year,
                'maintenances' => $maintenances,
                'summary' => $this->budgets->summary($vehicle, $year),
            ],
            type: GeneratedDocument::TYPE_KARTU_INVENTARIS,
            vehicle: $vehicle,
            filename: "kartu-{$vehicle->id}-{$year}",
        );
    }

    /**
     * Simpan PDF + catat baris generated_documents (version naik,
     * file versi lama tetap sebagai arsip).
     */
    private function store(
        string $view,
        array $data,
        string $type,
        ?Maintenance $maintenance = null,
        ?Vehicle $vehicle = null,
        ?string $post = null,
        string $filename = 'dokumen',
    ): GeneratedDocument {
        $previous = GeneratedDocument::query()
            ->where('type', $type)
            ->when($maintenance, fn ($q) => $q->where('maintenance_id', $maintenance->id))
            ->when(! $maintenance, fn ($q) => $q->whereNull('maintenance_id'))
            ->when($vehicle, fn ($q) => $q->where('vehicle_id', $vehicle->id))
            ->when($post !== null, fn ($q) => $q->where('post', $post))
            ->max('version');

        $version = ($previous ?? 0) + 1;
        $path = "documents/{$filename}-v{$version}.pdf";

        Storage::disk('local')->put($path, Pdf::loadView($view, $data)->output());

        // Arsip sekunder opsional ke Google Drive (gagal tidak mengganggu)
        try {
            app(\App\Services\Google\DriveUploader::class)->upload(
                Storage::disk('local')->path($path),
                basename($path),
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Arsip Drive gagal: '.$e->getMessage());
        }

        return GeneratedDocument::create([
            'maintenance_id' => $maintenance?->id,
            'vehicle_id' => $vehicle?->id,
            'type' => $type,
            'post' => $post,
            'file_path' => $path,
            'version' => $version,
        ]);
    }

    /**
     * Unduhan dokumen (stream dari storage lokal).
     */
    public function download(GeneratedDocument $document)
    {
        return Storage::disk('local')->download(
            $document->file_path,
            basename($document->file_path),
        );
    }

    /**
     * Daftar dokumen terbaru per (type, maintenance/vehicle, post)
     * — versi lama disembunyikan dari daftar utama.
     *
     * @return Collection<int, GeneratedDocument>
     */
    public function latestDocuments(?Vehicle $vehicle = null): Collection
    {
        return GeneratedDocument::with(['maintenance', 'vehicle'])
            ->when($vehicle, fn ($q) => $q->where('vehicle_id', $vehicle->id))
            ->orderByDesc('created_at')
            ->get()
            ->unique(fn (GeneratedDocument $d) => $d->type.'|'.$d->maintenance_id.'|'.$d->vehicle_id.'|'.$d->post)
            ->values();
    }
}
