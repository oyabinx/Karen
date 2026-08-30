<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceCost extends Model
{
    protected $fillable = ['maintenance_id', 'post', 'raw_amount', 'taxed_amount'];

    protected function casts(): array
    {
        return [
            'raw_amount' => 'decimal:2',
            'taxed_amount' => 'decimal:2',
        ];
    }

    public function maintenance(): BelongsTo
    {
        return $this->belongsTo(Maintenance::class);
    }
}
