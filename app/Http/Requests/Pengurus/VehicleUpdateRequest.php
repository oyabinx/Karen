<?php

namespace App\Http\Requests\Pengurus;

use App\Models\Vehicle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VehicleUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        $vehicle = $this->route('vehicle');

        return [
            'name' => ['required', 'string', 'max:100'],
            'plate_number' => ['required', 'string', 'max:15', Rule::unique(Vehicle::class)->ignore($vehicle->id)],
            'year' => ['required', 'integer', 'min:1980', 'max:'.(int) now()->year + 1],
            'capacity' => ['required', 'integer', 'min:1', 'max:20'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
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
