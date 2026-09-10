<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaintenanceCost extends Model
{
    use LogsActivity;

    protected $fillable = ['maintenance_id', 'post', 'raw_amount', 'taxed_amount', 'koefisien_used'];

    protected function casts(): array
    {
        return [
            'raw_amount' => 'decimal:2',
            'taxed_amount' => 'decimal:2',
            'koefisien_used' => 'decimal:4',
        ];
    }

    public function maintenance(): BelongsTo
    {
        return $this->belongsTo(Maintenance::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(MaintenanceCostDetail::class);
    }
}
