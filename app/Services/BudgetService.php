<?php

namespace App\Services;

use App\Models\Maintenance;
use App\Models\MaintenanceCost;
use App\Models\Vehicle;
use App\Models\VehicleBudget;
use Illuminate\Support\Collection;

/**
 * Anggaran maintenance 4 pos + input nota (docs/feature/anggaran_maintenance.md).
 *
 * Pos: servis, suku_cadang, ac, pelumas.
 * Realisasi = nilai nota × koefisien pajak (dapat diatur admin via
 * Pengaturan Aplikasi — skema baru UAT 04; nota lama menyimpan
 * koefisien saat input di kolom koefisien_used).
 */
class BudgetService
{
    /**
     * Koefisien pajak — TIDAK LAGI konstanta 1.13. Dibaca dinamis
     * dari AppSettings. Nota lama tetap pakai koefisien saat input.
     */
    public static function koefisienPajak(): float
    {
        return \App\Support\AppSettings::koefisienPajak();
    }

    /**
     * Simpan total anggaran 4 pos untuk satu mobil & tahun anggaran (upsert).
     *
     * @param  array<string, numeric>  $amounts  [post => nominal]
     */
    public function setBudgets(Vehicle $vehicle, int $year, array $amounts): void
    {
        foreach (VehicleBudget::POSTS as $post) {
            VehicleBudget::updateOrCreate(
                ['vehicle_id' => $vehicle->id, 'post' => $post, 'year' => $year],
                ['amount' => (float) ($amounts[$post] ?? 0)],
            );
        }
    }

    /**
     * Ringkasan per pos: anggaran, realisasi (×1,13), sisa.
     *
     * @return array<string, array{anggaran: float, realisasi: float, sisa: float}>
     */
    public function summary(Vehicle $vehicle, ?int $year = null): array
    {
        $year ??= now()->year;

        $anggaran = VehicleBudget::where('vehicle_id', $vehicle->id)
            ->where('year', $year)
            ->get()
            ->keyBy('post');

        $realisasi = $this->realizationPerPost($vehicle, $year);

        $summary = [];
        foreach (VehicleBudget::POSTS as $post) {
            $a = (float) ($anggaran[$post]->amount ?? 0);
            $r = (float) ($realisasi[$post] ?? 0);
            $summary[$post] = ['anggaran' => $a, 'realisasi' => $r, 'sisa' => $a - $r];
        }

        return $summary;
    }

    /**
     * Total realisasi per pos untuk satu mobil pada tahun anggaran
     * (tahun dihitung dari nota_date, fallback start_date maintenance).
     *
     * @return array<string, float>
     */
    public function realizationPerPost(Vehicle $vehicle, int $year): array
    {
        return MaintenanceCost::query()
            ->join('maintenances', 'maintenances.id', '=', 'maintenance_costs.maintenance_id')
            ->where('maintenances.vehicle_id', $vehicle->id)
            ->whereRaw('YEAR(COALESCE(maintenances.nota_date, maintenances.start_date)) = ?', [$year])
            ->selectRaw('maintenance_costs.post, SUM(maintenance_costs.taxed_amount) AS total')
            ->groupBy('maintenance_costs.post')
            ->pluck('total', 'post')
            ->map(fn ($v) => (float) $v)
            ->all();
    }

    /**
     * Input nota bengkel: identitas nota + rincian nilai per pos
     * + rincian baris (description + amount, opsional — skema baru UAT 04).
     * Nilai × koefisien dinamis = realisasi. Maintenance otomatis selesai.
     *
     * @param  array{workshop_name?: string, nota_number?: ?string, nota_date?: ?string, costs?: array<string, numeric>, details?: array<string, array<int, array{description?: string, amount?: numeric}>>}  $data
     * @return array<string, MaintenanceCost> pos => cost tersimpan
     */
    public function inputNota(Maintenance $maintenance, array $data): array
    {
        // Upsert identitas nota — field yang tidak dikirim MEMPERTAHANKAN
        // nilai lama (mis. nomor nota sudah tersimpan sebelumnya)
        $payload = ['status' => 'selesai'];
        foreach (['workshop_name', 'nota_number', 'nota_date'] as $field) {
            if (array_key_exists($field, $data)) {
                $payload[$field] = $data[$field];
            }
        }
        $maintenance->update($payload);

        $koefisien = self::koefisienPajak();
        $saved = [];

        foreach (VehicleBudget::POSTS as $post) {
            $raw = (float) ($data['costs'][$post] ?? 0);

            $saved[$post] = MaintenanceCost::updateOrCreate(
                ['maintenance_id' => $maintenance->id, 'post' => $post],
                [
                    'raw_amount' => $raw,
                    'taxed_amount' => round($raw * $koefisien, 2),
                    'koefisien_used' => $koefisien,
                ],
            );

            // Rincian baris per pos (replace: hapus lama, buat baru)
            $saved[$post]->details()->delete();

            $details = $data['details'][$post] ?? [];
            foreach ($details as $detail) {
                $desc = trim((string) ($detail['description'] ?? ''));
                $amount = (float) ($detail['amount'] ?? 0);

                if ($desc !== '' || $amount > 0) {
                    $saved[$post]->details()->create([
                        'description' => $desc !== '' ? $desc : '(tanpa deskripsi)',
                        'amount' => $amount,
                    ]);
                }
            }
        }

        return $saved;
    }

    /**
     * Rincian pos bernilai (untuk generate draft nota).
     *
     * @return Collection<int, MaintenanceCost>
     */
    public function filledCosts(Maintenance $maintenance): Collection
    {
        return $maintenance->costs->filter(fn (MaintenanceCost $c) => $c->raw_amount > 0)->values();
    }
}
