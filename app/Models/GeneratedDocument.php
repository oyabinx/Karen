<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeneratedDocument extends Model
{
    use LogsActivity;

    public const TYPE_BEND26 = 'bend26';
    public const TYPE_DRAFT_NOTA = 'draft_nota';
    public const TYPE_KARTU_PEMELIHARAAN = 'kartu_pemeliharaan';

    protected $fillable = [
        'maintenance_id', 'vehicle_id', 'type', 'post', 'period', 'file_path', 'version', 'regenerated_at',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'regenerated_at' => 'datetime',
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
