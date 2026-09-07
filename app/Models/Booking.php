<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
{
    use LogsActivity;

    public const STATUS_DIPINJAM = 'dipinjam';
    public const STATUS_MENUNGGU_PENGGANTIAN = 'menunggu_penggantian';
    public const STATUS_DIKEMBALIKAN = 'dikembalikan';
    public const STATUS_DIBATALKAN = 'dibatalkan';

    protected $fillable = [
        'user_id', 'vehicle_id', 'start_date', 'end_date', 'address', 'purpose',
        'status', 'returned_at', 'auto_returned', 'original_vehicle_id',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'returned_at' => 'datetime',
            'auto_returned' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function originalVehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'original_vehicle_id');
    }

    public function complaint(): HasOne
    {
        return $this->hasOne(Complaint::class);
    }
}
