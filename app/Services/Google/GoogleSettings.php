<?php

namespace App\Services\Google;

use App\Models\IntegrationSetting;
use Illuminate\Support\Facades\Crypt;

/**
 * Akses konfigurasi integrasi Google (docs/feature/integrasi_google.md).
 *
 * Semua konfigurasi runtime tersimpan di database — tidak ada file
 * kredensial di server. Nilai sensitif (kunci service account)
 * disimpan terenkripsi (Laravel Crypt).
 */
class GoogleSettings
{
    public static function get(string $key, mixed $default = null): mixed
    {
        $row = IntegrationSetting::where('setting_key', $key)->first();

        return $row?->value ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        IntegrationSetting::updateOrCreate(
            ['setting_key' => $key],
            ['value' => $value],
        );
    }

    /**
     * Kunci service account JSON — terenkripsi saat disimpan.
     */
    public static function getServiceAccountKey(): ?array
    {
        $encrypted = self::get(IntegrationSetting::KEY_SERVICE_ACCOUNT_JSON);

        if (! $encrypted) {
            return null;
        }

        $json = Crypt::decryptString($encrypted);
        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : null;
    }

    public static function setServiceAccountKey(string $json): void
    {
        self::set(IntegrationSetting::KEY_SERVICE_ACCOUNT_JSON, Crypt::encryptString($json));
    }

    public static function clearServiceAccountKey(): void
    {
        IntegrationSetting::where('setting_key', IntegrationSetting::KEY_SERVICE_ACCOUNT_JSON)->delete();
    }

    public static function enabled(): bool
    {
        return self::get(IntegrationSetting::KEY_ENABLED) === '1'
            && self::getServiceAccountKey() !== null
            && self::get(IntegrationSetting::KEY_SPREADSHEET_ID);
    }

    public static function pollMinutes(): int
    {
        return (int) (self::get(IntegrationSetting::KEY_POLL_MINUTES, 15) ?: 15);
    }

    public static function spreadsheetUrl(): ?string
    {
        $id = self::get(IntegrationSetting::KEY_SPREADSHEET_ID);

        return $id ? 'https://docs.google.com/spreadsheets/d/'.$id : null;
    }

    /**
     * Ekstrak Spreadsheet ID dari URL spreadsheet Google
     * (input divalidasi berupa URL — docs/feature/integrasi_google.md).
     */
    public static function extractSpreadsheetId(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        return preg_match('#/spreadsheets/d/([a-zA-Z0-9_-]+)#', $url, $m) ? $m[1] : null;
    }

    /**
     * Ekstrak Folder ID dari URL folder Google Drive (URL saja).
     */
    public static function extractFolderId(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        return preg_match('#/folders/([a-zA-Z0-9_-]+)#', $url, $m) ? $m[1] : null;
    }
}
