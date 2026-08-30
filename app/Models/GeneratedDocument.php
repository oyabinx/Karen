<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeneratedDocument extends Model
{
    public const TYPE_BEND26 = 'bend26';
    public const TYPE_DRAFT_NOTA = 'draft_nota';
    public const TYPE_KARTU_INVENTARIS = 'kartu_inventaris';

    protected $fillable = [
        'maintenance_id', 'vehicle_id', 'type', 'post', 'file_path', 'version',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
        ];
    }

    public function maintenance(): BelongsTo
    {
        return $this->belongsTo(Maintenance::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
