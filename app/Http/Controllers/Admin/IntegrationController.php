<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IntegrationLog;
use App\Models\IntegrationSetting;
use App\Services\Google\GoogleSettings;
use App\Services\Google\SheetsBudgetSync;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IntegrationController extends Controller
{
    /**
     * Konfigurasi integrasi Google — hanya admin
     * (docs/feature/integrasi_google.md). Runtime config di database,
     * tanpa file kredensial di server.
     */
    public function show(): View
    {
        return view('admin.integrations.google', [
            'settings' => [
                'enabled' => GoogleSettings::get(IntegrationSetting::KEY_ENABLED) === '1',
                'keyEmail' => GoogleSettings::getServiceAccountKey()['client_email'] ?? null,
                'spreadsheetUrl' => GoogleSettings::spreadsheetUrl(),
                'sheetAnggaran' => GoogleSettings::get(IntegrationSetting::KEY_SHEET_ANGGARAN, 'Anggaran'),
                'sheetRealisasi' => GoogleSettings::get(IntegrationSetting::KEY_SHEET_REALISASI, 'Realisasi'),
                'driveEnabled' => GoogleSettings::get(IntegrationSetting::KEY_DRIVE_ENABLED) === '1',
                'driveFolderUrl' => ($id = GoogleSettings::get(IntegrationSetting::KEY_DRIVE_FOLDER_ID)) ? 'https://drive.google.com/drive/folders/'.$id : null,
                'pollMinutes' => GoogleSettings::pollMinutes(),
            ],
            'logs' => IntegrationLog::latest('ran_at')->limit(20)->get(),
            'testSteps' => session('integration_test_steps'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'spreadsheet' => ['nullable', 'string', 'max:255'],
            'sheet_anggaran' => ['required', 'string', 'max:100'],
            'sheet_realisasi' => ['required', 'string', 'max:100'],
            'drive_enabled' => ['nullable', 'boolean'],
            'drive_folder' => ['nullable', 'string', 'max:255'],
            'poll_minutes' => ['required', 'integer', 'in:5,15,30,60'],
            'key_file' => ['nullable', 'file', 'mimes:json,txt', 'max:50'],
        ], [
            'poll_minutes.in' => 'Interval harus 5/15/30/60 menit.',
            'key_file.max' => 'Ukuran kunci maksimal 50 KB.',
        ]);

        // Kunci service account — divalidasi isinya lalu disimpan terenkripsi
        if ($request->hasFile('key_file')) {
            $json = $request->file('key_file')->getContent();
            $decoded = json_decode($json, true);

            if (! is_array($decoded) || ($decoded['type'] ?? '') !== 'service_account' || empty($decoded['client_email'])) {
                return back()->with('error', 'File kunci bukan JSON service account yang valid (field type/client_email tidak ditemukan).');
            }

            GoogleSettings::setServiceAccountKey($json);
        }

        GoogleSettings::set(IntegrationSetting::KEY_ENABLED, $request->boolean('enabled') ? '1' : '0');

        if ($id = GoogleSettings::extractSpreadsheetId($validated['spreadsheet'] ?? null)) {
            GoogleSettings::set(IntegrationSetting::KEY_SPREADSHEET_ID, $id);
        } elseif (($validated['spreadsheet'] ?? '') !== '') {
            return back()->with('error', 'URL spreadsheet tidak dikenali — gunakan format https://docs.google.com/spreadsheets/d/...');
        }

        GoogleSettings::set(IntegrationSetting::KEY_SHEET_ANGGARAN, $validated['sheet_anggaran']);
        GoogleSettings::set(IntegrationSetting::KEY_SHEET_REALISASI, $validated['sheet_realisasi']);
        GoogleSettings::set(IntegrationSetting::KEY_DRIVE_ENABLED, $request->boolean('drive_enabled') ? '1' : '0');

        if ($folder = GoogleSettings::extractFolderId($validated['drive_folder'] ?? null)) {
            GoogleSettings::set(IntegrationSetting::KEY_DRIVE_FOLDER_ID, $folder);
        }

        GoogleSettings::set(IntegrationSetting::KEY_POLL_MINUTES, (string) $validated['poll_minutes']);

        return back()->with('success', 'Konfigurasi integrasi disimpan. Jalankan Test Koneksi untuk memverifikasi.');
    }

    public function destroyKey(): RedirectResponse
    {
        GoogleSettings::clearServiceAccountKey();

        return back()->with('success', 'Kunci service account dihapus dari database.');
    }

    public function test(): RedirectResponse
    {
        $steps = app(SheetsBudgetSync::class)->testConnection();

        return back()->with([
            'integration_test_steps' => $steps,
            'success' => collect($steps)->every(fn ($s) => $s['ok'])
                ? 'Test koneksi: semua langkah lolos ✅'
                : 'Test koneksi selesai — ada langkah yang gagal, periksa rincian di bawah.',
        ]);
    }

    public function syncNow(): RedirectResponse
    {
        $result = app(SheetsBudgetSync::class)->run();

        return back()->with(
            $result['status'] === 'sukses' ? 'success' : 'error',
            'Sinkronisasi manual: '.$result['message'],
        );
    }
}
