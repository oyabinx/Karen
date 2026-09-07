<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use LogsActivity, HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'plate_number', 'year', 'capacity', 'status', 'condition', 'photo_path',
        'nomor_rangka', 'nomor_mesin', 'pajak_tahunan', 'pajak_lima_tahunan',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'capacity' => 'integer',
            'pajak_tahunan' => 'date',
            'pajak_lima_tahunan' => 'date',
        ];
    }

    /**
     * Peringatan pajak untuk pengurus (UAT 03-A11): tanggal pajak
     * tahunan / 5 tahunan yang jatuh tempo ≤ 21 hari atau sudah lewat.
     *
     * @return array<int, array{jenis: string, tanggal: \Illuminate\Support\Carbon, hari: int, lewat: bool}>
     */
    public function pajakWarnings(): array
    {
        $warnings = [];

        foreach (['pajak_tahunan' => 'Pajak Tahunan', 'pajak_lima_tahunan' => 'Pajak 5 Tahunan'] as $field => $label) {
            $tanggal = $this->{$field};

            if (! $tanggal) {
                continue;
            }

            $hari = round(now()->startOfDay()->diffInDays($tanggal->copy()->startOfDay(), false));

            if ($hari <= 21) {
                $warnings[] = [
                    'jenis' => $label,
                    'tanggal' => $tanggal,
                    'hari' => abs((int) $hari),
                    'lewat' => $hari < 0,
                ];
            }
        }

        return $warnings;
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function maintenances(): HasMany
    {
        return $this->hasMany(Maintenance::class);
    }

    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'event_vehicles');
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(VehicleBudget::class);
    }
}
