<?php

namespace App\Models\Concerns;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

/**
 * Trait LogsActivity — catat "siapa mengubah apa, kapan" untuk model
 * penting (keputusan user pasca-UAT 03: pengelolaan kendaraan dipegang
 * lebih dari satu akun pengurus).
 *
 * - Aksi: menambah / mengubah (kolom lama→baru) / menghapus /
 *   mengaktifkan kembali.
 * - Field sensitif TIDAK PERNAH dicatat isinya: password,
 *   remember_token, dan `value` IntegrationSetting (kunci Google
 *   terenkripsi).
 * - Eksekusi console/scheduler (Auth::id() null) → user_id NULL =
 *   tampil sebagai "Sistem (otomatis)".
 */
trait LogsActivity
{
    /** Kolom yang isinya tidak boleh masuk log. */
    protected function activityHiddenFields(): array
    {
        // deleted_at sengaja TIDAK disembunyikan agar aktivasi kembali
        // (restore) tercatat sebagai perubahan deleted_at: null
        return ['password', 'remember_token', 'value', 'created_at', 'updated_at', 'email_verified_at'];
    }

    /** Label enak dibaca — boleh dioverride di model. */
    public function activityLabel(): string
    {
        $judul = $this->activityTitle();

        return ($this->activityModelName() ?: static::class).' '.($judul ?: '#'.$this->getKey());
    }

    protected function activityTitle(): ?string
    {
        foreach (['name', 'plate_number', 'title', 'setting_key', 'nota_number'] as $kolom) {
            if (isset($this->attributes[$kolom]) && $this->attributes[$kolom] !== null && $this->attributes[$kolom] !== '') {
                return (string) $this->attributes[$kolom];
            }
        }

        return null;
    }

    protected function activityModelName(): string
    {
        return match (class_basename(static::class)) {
            'Vehicle' => 'Kendaraan',
            'Maintenance' => 'Jadwal Maintenance',
            'Booking' => 'Peminjaman',
            'Event' => 'Event Armada',
            'VehicleBudget' => 'Anggaran',
            'MaintenanceCost' => 'Biaya Maintenance',
            'Complaint' => 'Keluhan',
            'User' => 'Pengguna',
            'Bidang' => 'Bidang',
            'Seksi' => 'Seksi',
            'IntegrationSetting' => 'Konfigurasi Integrasi',
            'GeneratedDocument' => 'Dokumen',
            default => class_basename(static::class),
        };
    }

    public static function bootLogsActivity(): void
    {
        static::created(fn ($model) => $model->activityLog(ActivityLog::ACTION_CREATED));
        static::updated(fn ($model) => $model->activityLog(ActivityLog::ACTION_UPDATED));
        static::deleted(fn ($model) => $model->activityLog(ActivityLog::ACTION_DELETED));
        // CATATAN: jangan daftarkan static::restored(...) — bukan event
        // sihir di Laravel 13 (__callStatic meng-instansiasi model saat
        // boot → reentransi). Restore tercatat lewat event `updated`
        // karena deleted_at tidak masuk daftar tersembunyi.
    }

    public function activityLog(string $action): void
    {
        $changes = null;

        if ($action === ActivityLog::ACTION_UPDATED) {
            foreach ($this->getChanges() as $kolom => $baru) {
                if (in_array($kolom, $this->activityHiddenFields(), true)) {
                    continue;
                }
                $changes[$kolom] = ['lama' => $this->getOriginal($kolom), 'baru' => $baru];
            }

            if ($changes === null) {
                return; // hanya field sensitif/timestamps yang berubah — skip
            }
        }

        $label = $this->activityLabel();

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'model_type' => static::class,
            'model_id' => $this->getKey(),
            'model_label' => $label,
            'description' => $this->activityDescription($action, $label, $changes),
            'changes' => $changes,
        ]);
    }

    protected function activityDescription(string $action, string $label, ?array $changes): string
    {
        $kalimat = match ($action) {
            ActivityLog::ACTION_CREATED => "Menambah {$label}",
            ActivityLog::ACTION_UPDATED => "Mengubah {$label}",
            ActivityLog::ACTION_DELETED => "Menghapus {$label}",
            ActivityLog::ACTION_RESTORED => "Mengaktifkan kembali {$label}",
            default => "{$label}",
        };

        if ($changes !== null) {
            $kalimat .= ' (kolom: '.implode(', ', array_keys($changes)).')';
        }

        return $kalimat;
    }
}
