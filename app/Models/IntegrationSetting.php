<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;

use Illuminate\Database\Eloquent\Model;

class IntegrationSetting extends Model
{
    use LogsActivity;

    public const KEY_ENABLED = 'google_enabled';
    public const KEY_SERVICE_ACCOUNT_JSON = 'google_service_account_json';
    public const KEY_SPREADSHEET_ID = 'google_spreadsheet_id';
    public const KEY_SHEET_ANGGARAN = 'google_sheet_anggaran';
    public const KEY_SHEET_REALISASI = 'google_sheet_realisasi';
    public const KEY_DRIVE_ENABLED = 'google_drive_enabled';
    public const KEY_DRIVE_FOLDER_ID = 'google_drive_folder_id';
    public const KEY_POLL_MINUTES = 'google_poll_minutes';

    // Kolom `key` tidak dipakai — reserved word MySQL; lihat migrasi.
    protected $fillable = ['setting_key', 'value'];
}
