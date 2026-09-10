<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceCostDetail extends Model
{
    use LogsActivity;

    protected $fillable = ['maintenance_cost_id', 'description', 'amount'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function maintenanceCost(): BelongsTo
    {
        return $this->belongsTo(MaintenanceCost::class);
    }
}
