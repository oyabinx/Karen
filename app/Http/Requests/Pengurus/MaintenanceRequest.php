<?php

namespace App\Http\Requests\Pengurus;

use App\Models\Maintenance;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MaintenanceRequest extends FormRequest
{
    /**
     * Jadwal maintenance (docs/feature/manajemen_kendaraan.md):
     * rentang valid, dan SATU kendaraan tidak boleh memiliki dua
     * jadwal maintenance yang overlap (ditambahkan agar implementasi
     * tidak ambigu — lihat build_logs/fase4.log).
     */
    public function rules(): array
    {
        return [
            'vehicle_id' => ['required', Rule::exists('vehicles', 'id')],
            // Jadwal tidak boleh dimulai di masa lalu (UAT 03-B4)
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => [
                'required',
                'date',
                'after_or_equal:start_date',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $query = Maintenance::query()
                        ->where('vehicle_id', $this->input('vehicle_id'))
                        ->where('status', 'terjadwal')
                        ->whereDate('start_date', '<=', $value)
                        ->whereDate('end_date', '>=', $this->input('start_date'));

                    if ($maintenance = $this->route('maintenance')) {
                        $query->where('id', '!=', $maintenance->id);
                    }

                    if ($query->exists()) {
                        $fail('Kendaraan ini sudah memiliki jadwal maintenance yang bertumpuk pada rentang tanggal tersebut.');
                    }
                },
                // Aturan dua arah: maintenance juga tidak boleh menabrak
                // armada event terjadwal (mobil tidak bisa di bengkel dan
                // dipakai event sekaligus) — docs/feature/manajemen_kendaraan.md
                function (string $attribute, mixed $value, \Closure $fail) {
                    $overlap = \App\Models\Event::query()
                        ->whereHas('vehicles', fn ($q) => $q->where('vehicles.id', $this->input('vehicle_id')))
                        ->where('status', 'terjadwal')
                        ->whereDate('start_date', '<=', $value)
                        ->whereDate('end_date', '>=', $this->input('start_date'))
                        ->exists();

                    if ($overlap) {
                        $fail('Kendaraan ini menjadi armada event terjadwal pada rentang tanggal tersebut — atur jadwal maintenance di luar rentang event.');
                    }
                },
            ],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'vehicle_id' => 'kendaraan',
            'start_date' => 'tanggal mulai',
            'end_date' => 'tanggal selesai',
            'note' => 'catatan',
        ];
    }
}
