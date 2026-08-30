<?php

namespace App\Http\Requests\Pengurus;

use App\Models\VehicleBudget;
use Illuminate\Foundation\Http\FormRequest;

class BudgetSaveRequest extends FormRequest
{
    /**
     * Anggaran 4 pos per kendaraan per tahun (docs/feature/anggaran_maintenance.md).
     */
    public function rules(): array
    {
        return [
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            // Hanya menerima kunci pos yang dikenal
            'amounts' => ['required', 'array:'.implode(',', VehicleBudget::POSTS)],
            'amounts.*' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function attributes(): array
    {
        return [
            'year' => 'tahun anggaran',
            'amounts' => 'nominal anggaran',
            'amounts.servis' => 'anggaran servis',
            'amounts.suku_cadang' => 'anggaran suku cadang',
            'amounts.ac' => 'anggaran pemeliharaan AC',
            'amounts.pelumas' => 'anggaran pelumas',
        ];
    }
}
