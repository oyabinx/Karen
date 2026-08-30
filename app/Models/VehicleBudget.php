<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleBudget extends Model
{
    public const POST_SERVIS = 'servis';
    public const POST_SUKU_CADANG = 'suku_cadang';
    public const POST_AC = 'ac';
    public const POST_PELUMAS = 'pelumas';

    public const POSTS = [
        self::POST_SERVIS,
        self::POST_SUKU_CADANG,
        self::POST_AC,
        self::POST_PELUMAS,
    ];

    protected $fillable = ['vehicle_id', 'post', 'amount', 'year'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'year' => 'integer',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
