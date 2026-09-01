<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Bidang extends Model
{
    use HasFactory;

    // Nama tabel bentuk tunggal (bukan "bidangs" hasil pluralisasi bawaan)
    protected $table = 'bidang';

    protected $fillable = ['name', 'max_active_bookings'];

    protected function casts(): array
    {
        return [
            'max_active_bookings' => 'integer',
        ];
    }

    public function seksi(): HasMany
    {
        return $this->hasMany(Seksi::class);
    }

    /**
     * Anggota bidang = user pada seluruh sekinya (dipakai rincian
     * dashboard admin & perhitungan kuota).
     */
    public function users(): HasManyThrough
    {
        return $this->hasManyThrough(User::class, Seksi::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }
}
