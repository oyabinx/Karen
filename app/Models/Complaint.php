<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Complaint extends Model
{
    use LogsActivity;

    protected $fillable = ['booking_id', 'message', 'resolved'];

    protected function casts(): array
    {
        return [
            'resolved' => 'boolean',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
