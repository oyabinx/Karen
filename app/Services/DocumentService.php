<?php

namespace App\Services;

use App\Models\GeneratedDocument;
use App\Models\Maintenance;
use App\Models\MaintenanceCost;
use App\Models\Vehicle;
use App\Models\VehicleBudget;
use App\Support\AppSettings;
use App\Support\Bend26Identity;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * Generate dokumen PDF dari master draft di resources/draft_documents/
 * (docs/feature/anggaran_maintenance.md §4 & docs/structure.md).
 *
 * Jenis (UAT 04 rev-2):
 * - draft_nota per pos (per maintenance — otomatis saat simpan/edit nota)
 * - bend26 BULANAN per pos (agregat seluruh nota bulan tsb — on demand
 *   dari Realisasi Bulanan; format mengikuti docs/Bend26/*.pdf)
 * - kartu_pemeliharaan (rekap per kendaraan — dari menu Laporan)
 *
 * Dokumen yang sudah ada DITIMPA DI TEMPAT saat digenerate ulang
 * (bukan versi baru) — badge "Diperbarui" memakai regenerated_at.
 */
class DocumentService
{
    public function __construct(
        private readonly BudgetService $budgets,
    ) {}

    /**
     * Generate draft nota (rincian baris) per pos bernilai untuk satu
     * maintenance — dipanggil setelah input/edit nota.
     *
     * @return GeneratedDocument[] dokumen yang dihasilkan
     */
    public function generateForMaintenance(Maintenance $maintenance): array
    {
        $maintenance->load(['vehicle', 'costs.details']);

        $documents = [];
        foreach ($this->budgets->filledCosts($maintenance) as $cost) {
            $documents[] = $this->draftNota($maintenance, $cost->post);
        }

        return $documents;
    }

    /**
     * bend26 BUKTI KAS PENGELUARAN — dokumen BULANAN per pos:
     * agregasi seluruh maintenance ber-nota pada bulan tsb
     * (atribusi bulan = tanggal nota, fallback tanggal mulai —
     * konsisten dengan Realisasi Bulanan).
     *
     * @param  string|null  $post  null = semua pos bernilai
     * @return GeneratedDocument[]
     */
    public function bend26Bulanan(int $year, int $month, ?string $post = null): array
    {
        $posts = $post !== null ? [$post] : VehicleBudget::POSTS;
        $awal = Carbon::create($year, $month, 1)->startOfMonth();
        $akhir = $awal->copy()->endOfMonth();

        $documents = [];
        foreach ($posts as $p) {
            $costs = MaintenanceCost::query()
                ->with(['maintenance.vehicle', 'details'])
                ->where('post', $p)
                ->where('raw_amount', '>', 0)
                ->whereHas('maintenance', fn ($q) => $q
                    ->whereRaw('YEAR(COALESCE(nota_date, start_date)) = ? AND MONTH(COALESCE(nota_date, start_date)) = ?', [$year, $month]))
                ->get();

            if ($costs->isEmpty()) {
                continue;
            }

            // Distinct kendaraan (urut plat) untuk baris "Yaitu untuk pembayaran"
            $vehicles = $costs->map(fn ($c) => $c->maintenance->vehicle)->unique('id')->sortBy('plate_number')->values();
            $total = (float) $costs->sum('taxed_amount');
            $identity = AppSettings::bend26Identity();

            $documents[] = $this->store(
                view: 'drafts::bend26',
                data: [
                    'year' => $year,
                    'month' => $month,
                    'post' => $p,
                    'posLabel' => Bend26Identity::posLabel($p),
                    'vehicles' => $vehicles,
                    'total' => $total,
                    'terbilang' => Bend26Identity::terbilang($total),
                    'pajak' => Bend26Identity::pajak($total, $p, $identity),
                    'identity' => $identity,
                    'costs' => $costs->sortBy(fn ($c) => optional($c->maintenance->nota_date ?? $c->maintenance->start_date)->timestamp)->values(),
                ],
                type: GeneratedDocument::TYPE_BEND26,
                post: $p,
                period: $awal->format('Y-m'),
                filename: "bend26-{$p}-{$awal->format('Y-m')}",
            );
        }

        return $documents;
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
            ],
            type: GeneratedDocument::TYPE_DRAFT_NOTA,
            maintenance: $maintenance,
            vehicle: $maintenance->vehicle,
            post: $post,
            filename: "nota-{$post}-m{$maintenance->id}",
        );
    }

    /**
     * Kartu Pemeliharaan Kendaraan (eks Kartu Inventaris — UAT 04-B10):
     * per maintenance per pos — jenis perbaikan + rincian (satu cell)
     * + biaya (setelah koefisien). Tahun anggaran = tahun parameter.
     */
    public function kartuPemeliharaan(Vehicle $vehicle, ?int $year = null): GeneratedDocument
    {
        $year ??= now()->year;

        $maintenances = Maintenance::with(['costs.details'])
            ->where('vehicle_id', $vehicle->id)
            ->whereRaw('YEAR(COALESCE(nota_date, start_date)) = ?', [$year])
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get();

        return $this->store(
            view: 'drafts::kartu_pemeliharaan',
            data: [
                'vehicle' => $vehicle,
                'year' => $year,
                'maintenances' => $maintenances,
            ],
            type: GeneratedDocument::TYPE_KARTU_PEMELIHARAAN,
            vehicle: $vehicle,
            filename: "kartu-pemeliharaan-{$vehicle->id}-{$year}",
        );
    }

    /**
     * Simpan PDF + catat baris generated_documents. Dokumen dengan kunci
     * sama (type + maintenance/vehicle/period + post) DITIMPA DI TEMPAT:
     * file lama dihapus, record lama dipakai ulang, regenerated_at diisi.
     */
    private function store(
        string $view,
        array $data,
        string $type,
        ?Maintenance $maintenance = null,
        ?Vehicle $vehicle = null,
        ?string $post = null,
        string $filename = 'dokumen',
        ?string $period = null,
    ): GeneratedDocument {
        $existing = GeneratedDocument::query()
            ->where('type', $type)
            ->when($maintenance, fn ($q) => $q->where('maintenance_id', $maintenance->id))
            ->when(! $maintenance, fn ($q) => $q->whereNull('maintenance_id'))
            ->when($vehicle, fn ($q) => $q->where('vehicle_id', $vehicle->id))
            ->when($post !== null, fn ($q) => $q->where('post', $post))
            ->when($period !== null, fn ($q) => $q->where('period', $period))
            ->first();

        $regenerate = $existing !== null;
        $path = "documents/{$filename}.pdf";

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

        if ($existing) {
            $existing->update([
                'file_path' => $path,
                'regenerated_at' => now(),
            ]);

            return $existing->refresh();
        }

        return GeneratedDocument::create([
            'maintenance_id' => $maintenance?->id,
            'vehicle_id' => $vehicle?->id,
            'type' => $type,
            'post' => $post,
            'period' => $period,
            'file_path' => $path,
            'version' => 1,
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
     * Daftar dokumen terbaru (dokumen unik per kunci — timpa-di-tempat
     * berarti satu record per kunci, tidak perlu dedup versi lagi).
     *
     * @return Collection<int, GeneratedDocument>
     */
    public function latestDocuments(?Vehicle $vehicle = null): Collection
    {
        return GeneratedDocument::with(['maintenance', 'vehicle'])
            ->when($vehicle, fn ($q) => $q->where('vehicle_id', $vehicle->id))
            ->orderByDesc('updated_at')
            ->get()
            ->values();
    }
}
