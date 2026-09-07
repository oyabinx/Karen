<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    public const ACTION_CREATED = 'created';
    public const ACTION_UPDATED = 'updated';
    public const ACTION_DELETED = 'deleted';
    public const ACTION_RESTORED = 'restored';
    public const ACTION_SYSTEM = 'system';

    protected $fillable = [
        'user_id', 'action', 'model_type', 'model_id', 'model_label', 'description', 'changes',
    ];

    protected function casts(): array
    {
        return [
            'changes' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pelaku(): string
    {
        return $this->user?->name ?? 'Sistem (otomatis)';
    }

    public function aksiLabel(): string
    {
        return match ($this->action) {
            self::ACTION_CREATED => 'Menambah',
            self::ACTION_UPDATED => 'Mengubah',
            self::ACTION_DELETED => 'Menghapus',
            self::ACTION_RESTORED => 'Mengaktifkan kembali',
            self::ACTION_SYSTEM => 'Sistem',
            default => ucfirst($this->action),
        };
    }
}
