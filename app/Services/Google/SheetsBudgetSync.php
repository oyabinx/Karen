<?php

namespace App\Services\Google;

use App\Models\IntegrationLog;
use App\Models\Maintenance;
use App\Models\Vehicle;
use App\Models\VehicleBudget;
use Google\Client as GoogleClient;
use Google\Service\Sheets as GoogleSheets;
use Google\Service\Sheets\ClearValuesRequest;
use Google\Service\Sheets\ValueRange;
use Illuminate\Support\Collection;

/**
 * Sinkronisasi anggaran dengan Google Sheets (outbound — Mode A,
 * docs/feature/anggaran_maintenance.md §5 & integrasi_google.md).
 *
 * Pull: tab Anggaran (plat; pos; nominal; tahun) → vehicle_budgets.
 * Push: tab Realisasi (data nota ×1,13) ditulis ulang dari database.
 * Log tiap eksekusi ke integration_logs (tanpa mencatat kredensial).
 */
class SheetsBudgetSync
{
    public function __construct(
        private readonly GoogleSettings $settings,
    ) {}

    /**
     * Jalankan bila sudah jatuh tempo sesuai interval (menit).
     */
    public function runIfDue(): void
    {
        if (! $this->settings->enabled()) {
            return; // nonaktif — dilewati tanpa log (tenang)
        }

        $lastRun = $this->settings->get('google_last_run');
        $interval = $this->settings->pollMinutes();

        if ($lastRun && now()->diffInMinutes(\Illuminate\Support\Carbon::parse($lastRun)) < $interval) {
            return;
        }

        $this->run();
    }

    /**
     * Sinkron penuh: pull anggaran + push realisasi + catat log.
     */
    public function run(): array
    {
        $start = microtime(true);

        try {
            $sheets = $this->sheets();

            $pulled = $this->pullBudgets($sheets);
            $pushed = $this->pushRealizations($sheets);

            $result = ['pulled' => $pulled, 'pushed' => $pushed];
            $status = IntegrationLog::STATUS_SUKSES;
            $message = "Tarik {$pulled} baris anggaran; dorong {$pushed} baris realisasi.";
        } catch (\Throwable $e) {
            $result = ['error' => true];
            $status = IntegrationLog::STATUS_GAGAL;
            $message = $e->getMessage();
        }

        $duration = (int) ((microtime(true) - $start) * 1000);

        IntegrationLog::create([
            'provider' => 'google',
            'task' => IntegrationLog::TASK_SHEETS_SYNC,
            'status' => $status,
            'message' => $message,
            'duration_ms' => $duration,
            'ran_at' => now(),
        ]);

        $this->settings->set('google_last_run', now()->toDateTimeString());

        return $result + ['status' => $status, 'message' => $message];
    }

    /**
     * Pull anggaran dari tab Anggaran → upsert vehicle_budgets.
     */
    public function pullBudgets(GoogleSheets $sheets): int
    {
        $spreadsheetId = $this->settings->get(\App\Models\IntegrationSetting::KEY_SPREADSHEET_ID);
        $tab = $this->settings->get(\App\Models\IntegrationSetting::KEY_SHEET_ANGGARAN, 'Anggaran');

        $values = $sheets->spreadsheets_values->get($spreadsheetId, "'{$tab}'!A2:D")->getValues() ?? [];

        $count = 0;
        foreach ($values as $row) {
            [$plate, $post, $amount, $year] = array_pad($row, 4, null);

            $vehicle = Vehicle::where('plate_number', trim((string) $plate))->first();
            if (! $vehicle || ! in_array($post, VehicleBudget::POSTS, true)) {
                continue;
            }

            VehicleBudget::updateOrCreate(
                ['vehicle_id' => $vehicle->id, 'post' => $post, 'year' => (int) $year],
                ['amount' => (float) str_replace(['.', ','], ['', '.'], (string) $amount)],
            );
            $count++;
        }

        return $count;
    }

    /**
     * Push realisasi (payload kontrak JSON, kolom selaras) ke tab Realisasi.
     */
    public function pushRealizations(GoogleSheets $sheets): int
    {
        $spreadsheetId = $this->settings->get(\App\Models\IntegrationSetting::KEY_SPREADSHEET_ID);
        $tab = $this->settings->get(\App\Models\IntegrationSetting::KEY_SHEET_REALISASI, 'Realisasi');

        $rows = $this->realizationRows();

        // Tulis ulang penuh: bersihkan isi lama lalu append
        $sheets->spreadsheets_values->clear($spreadsheetId, "'{$tab}'!A2:Z", new ClearValuesRequest());

        if ($rows->isNotEmpty()) {
            $body = new ValueRange(['values' => $rows->all()]);
            $sheets->spreadsheets_values->append($spreadsheetId, "'{$tab}'!A1", $body, ['valueInputOption' => 'RAW']);
        }

        return $rows->count();
    }

    /**
     * Baris realisasi (kontrak JSON anggaran_maintenance.md) — murni,
     * mudah diuji tanpa jaringan.
     */
    public function realizationRows(): Collection
    {
        return Maintenance::with(['vehicle', 'costs'])
            ->whereNotNull('workshop_name')
            ->orderBy('id')
            ->get()
            ->flatMap(fn (Maintenance $m) => $m->costs
                ->filter(fn ($c) => $c->raw_amount > 0)
                ->map(fn ($c) => [
                    $m->vehicle->plate_number,
                    $m->id,
                    $m->workshop_name,
                    $m->nota_number ?? '-',
                    optional($m->nota_date ?? $m->start_date)->format('Y-m-d'),
                    $c->post,
                    (float) $c->raw_amount,
                    (float) $c->taxed_amount,
                ]));
    }

    /**
     * Klien Sheets siap pakai (kredensial terenkripsi dari database).
     */
    public function sheets(): GoogleSheets
    {
        $key = $this->settings->getServiceAccountKey();

        if (! $key) {
            throw new \RuntimeException('Kredensial Google belum dikonfigurasi (kunci service account kosong).');
        }

        $client = new GoogleClient();
        $client->setAuthConfig($key);
        $client->addScope(GoogleSheets::SPREADSHEETS);

        return new GoogleSheets($client);
    }

    /**
     * Test koneksi bertahap (docs integrasi_google.md — 5 langkah).
     *
     * @return array<int, array{langkah: string, ok: bool, pesan: string}>
     */
    public function testConnection(): array
    {
        $steps = [];

        // 1. Kunci terbaca & valid — aman terhadap kunci NULL (UAT D4:
        // akses $key['type'] pada null melempar error sebelumnya).
        // Pesan dipisah per kondisi agar tidak kontradiktif (UAT D7).
        $key = $this->settings->getServiceAccountKey();

        if (! $key) {
            $steps[] = [
                'langkah' => 'Kunci service account',
                'ok' => false,
                'pesan' => '❌ Belum diunggah — silakan unggah file JSON terlebih dahulu.',
            ];
        } elseif (($key['type'] ?? null) !== 'service_account') {
            $steps[] = [
                'langkah' => 'Kunci service account',
                'ok' => false,
                'pesan' => '❌ Kunci terunggah tetapi TIDAK VALID — format bukan service account.',
            ];
        } else {
            $steps[] = [
                'langkah' => 'Kunci service account',
                'ok' => true,
                'pesan' => '✅ Valid: '.$key['client_email'],
            ];
        }

        if (! $key) {
            return $this->finishTest($steps);
        }

        // 2. Kredensial diterima Google (token)
        try {
            $client = new GoogleClient();
            $client->setAuthConfig($key);
            $client->addScope(GoogleSheets::SPREADSHEETS);
            $client->fetchAccessTokenWithAssertion();
            $steps[] = ['langkah' => 'Autentikasi Google (token)', 'ok' => true, 'pesan' => 'token diterima'];
        } catch (\Throwable $e) {
            $steps[] = ['langkah' => 'Autentikasi Google (token)', 'ok' => false, 'pesan' => $e->getMessage()];

            return $this->finishTest($steps);
        }

        // 3 & 4. Spreadsheet & tab
        $spreadsheetId = $this->settings->get(\App\Models\IntegrationSetting::KEY_SPREADSHEET_ID);
        if (! $spreadsheetId) {
            $steps[] = ['langkah' => 'Spreadsheet dapat diakses', 'ok' => false, 'pesan' => 'URL spreadsheet belum diisi'];

            return $this->finishTest($steps);
        }

        try {
            $sheets = new GoogleSheets($client);
            $meta = $sheets->spreadsheets->get($spreadsheetId);
            $titles = collect($meta->getSheets())->map(fn ($s) => $s->getProperties()->getTitle());

            $steps[] = ['langkah' => 'Spreadsheet dapat diakses', 'ok' => true, 'pesan' => $meta->getProperties()->getTitle()];

            $tabA = $this->settings->get(\App\Models\IntegrationSetting::KEY_SHEET_ANGGARAN, 'Anggaran');
            $tabR = $this->settings->get(\App\Models\IntegrationSetting::KEY_SHEET_REALISASI, 'Realisasi');
            $okTab = $titles->contains($tabA) && $titles->contains($tabR);
            $steps[] = ['langkah' => "Tab '{$tabA}' & '{$tabR}' ditemukan", 'ok' => $okTab, 'pesan' => $okTab ? 'kedua tab ada' : 'tab tersedia: '.$titles->implode(', ')];
        } catch (\Throwable $e) {
            $steps[] = ['langkah' => 'Spreadsheet dapat diakses', 'ok' => false, 'pesan' => $e->getMessage()];

            return $this->finishTest($steps);
        }

        // 5. Folder Drive (opsional)
        $driveEnabled = $this->settings->get(\App\Models\IntegrationSetting::KEY_DRIVE_ENABLED) === '1';
        if ($driveEnabled) {
            try {
                (new DriveUploader($this->settings))->assertFolderAccessible();
                $steps[] = ['langkah' => 'Folder Drive dapat diakses', 'ok' => true, 'pesan' => 'folder OK'];
            } catch (\Throwable $e) {
                $steps[] = ['langkah' => 'Folder Drive dapat diakses', 'ok' => false, 'pesan' => $e->getMessage()];
            }
        }

        return $this->finishTest($steps);
    }

    private function finishTest(array $steps): array
    {
        IntegrationLog::create([
            'provider' => 'google',
            'task' => 'test_connection',
            'status' => collect($steps)->every(fn ($s) => $s['ok']) ? IntegrationLog::STATUS_SUKSES : IntegrationLog::STATUS_GAGAL,
            'message' => collect($steps)->map(fn ($s) => $s['langkah'].': '.($s['ok'] ? 'OK' : $s['pesan']))->implode(' | '),
            'ran_at' => now(),
        ]);

        return $steps;
    }
}
