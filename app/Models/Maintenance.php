<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Maintenance extends Model
{
    use LogsActivity;

    protected $fillable = [
        'vehicle_id', 'start_date', 'end_date', 'note', 'status',
        'workshop_name', 'nota_number', 'nota_date',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'nota_date' => 'date',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function costs(): HasMany
    {
        return $this->hasMany(MaintenanceCost::class);
    }
}
