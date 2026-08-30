<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Seksi extends Model
{
    // Nama tabel bentuk tunggal (bukan "seksis" hasil pluralisasi bawaan)
    protected $table = 'seksi';

    protected $fillable = ['bidang_id', 'name'];

    public function bidang(): BelongsTo
    {
        return $this->belongsTo(Bidang::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
