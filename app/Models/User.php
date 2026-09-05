<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'phone', 'seksi_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    public function seksi(): BelongsTo
    {
        return $this->belongsTo(Seksi::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isPengurus(): bool
    {
        return $this->role === 'pengurus';
    }

    public function isPegawai(): bool
    {
        return $this->role === 'pegawai';
    }

    public function hasAnyRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    /**
     * Normalisasi nomor HP ke bentuk baku 62xxxxxxxxxx SEBELUM disimpan
     * (temuan UAT B4): 0812…, 62812…, +62812… adalah nomor yang sama —
     * tanpa normalisasi, duplikat lolos cek unique karena string berbeda.
     * Pengecekan unique di validasi tetap membandingkan nilai baku.
     */
    public function setPhoneAttribute(?string $value): void
    {
        $this->attributes['phone'] = self::canonicalPhone($value);
    }

    /**
     * Bentuk baku nomor HP: hanya digit, diawali 62
     * (0812… / 62812… / +62812… → 62812…).
     */
    public static function canonicalPhone(?string $value): ?string
    {
        $digit = preg_replace('/\D+/', '', (string) $value);

        if ($digit === '') {
            return null;
        }

        if (str_starts_with($digit, '0')) {
            $digit = '62'.substr($digit, 1);
        }

        return $digit;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
