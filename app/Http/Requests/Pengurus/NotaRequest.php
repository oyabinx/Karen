<?php

namespace App\Http\Requests\Pengurus;

use App\Models\VehicleBudget;
use Illuminate\Foundation\Http\FormRequest;

class NotaRequest extends FormRequest
{
    /**
     * Input nota bengkel: minimal satu pos bernilai > 0
     * (docs/feature/anggaran_maintenance.md).
     */
    public function rules(): array
    {
        return [
            'workshop_name' => ['required', 'string', 'max:100'],
            'nota_number' => ['nullable', 'string', 'max:50'],
            'nota_date' => ['nullable', 'date'],
            'costs' => ['required', 'array'],
            'costs.servis' => ['required', 'numeric', 'min:0'],
            'costs.suku_cadang' => ['required', 'numeric', 'min:0'],
            'costs.ac' => ['required', 'numeric', 'min:0'],
            'costs.pelumas' => ['required', 'numeric', 'min:0'],
            'costs' => [
                function (string $attribute, mixed $value, \Closure $fail) {
                    $total = collect($value)->filter(fn ($v, $k) => in_array($k, VehicleBudget::POSTS, true))->sum();
                    if ($total <= 0) {
                        $fail('Minimal satu pos harus bernilai lebih dari 0.');
                    }
                },
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'workshop_name' => 'nama bengkel',
            'nota_number' => 'nomor nota',
            'nota_date' => 'tanggal nota',
            'costs' => 'rincian nota',
        ];
    }
}
