<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntegrationLog extends Model
{
    public const STATUS_SUKSES = 'sukses';
    public const STATUS_GAGAL = 'gagal';

    public const TASK_SHEETS_SYNC = 'sheets_sync';
    public const TASK_DRIVE_UPLOAD = 'drive_upload';

    protected $fillable = ['provider', 'task', 'status', 'message', 'duration_ms', 'ran_at'];

    protected function casts(): array
    {
        return [
            'ran_at' => 'datetime',
        ];
    }
}
