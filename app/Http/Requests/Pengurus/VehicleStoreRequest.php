<?php

namespace App\Http\Requests\Pengurus;

use App\Models\Vehicle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VehicleStoreRequest extends FormRequest
{
    /**
     * Aturan kendaraan (docs/feature/manajemen_kendaraan.md):
     * 5 field primer wajib; data sekunder (rangka/mesin/pajak)
     * OPSIONAL — dilengkapi kapan pun (UAT 03-A11).
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'plate_number' => ['required', 'string', 'max:15', Rule::unique(Vehicle::class)],
            'year' => ['required', 'integer', 'min:1980', 'max:'.(int) now()->year + 1],
            'capacity' => ['required', 'integer', 'min:1', 'max:20'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            // Data sekunder (opsional)
            'nomor_rangka' => ['nullable', 'string', 'max:50'],
            'nomor_mesin' => ['nullable', 'string', 'max:50'],
            'pajak_tahunan' => ['nullable', 'date'],
            'pajak_lima_tahunan' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        // Pesan manusiawi (UAT 03-A3): jangan "2048 kilobita"
        return ['photo.max' => 'Foto maksimal berukuran 2 MB.'];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nama kendaraan',
            'plate_number' => 'plat nomor',
            'year' => 'tahun pembuatan',
            'capacity' => 'kapasitas',
            'photo' => 'foto',
        ];
    }
}
