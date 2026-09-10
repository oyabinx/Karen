<?php

namespace App\Http\Requests\Pengurus;

use Illuminate\Foundation\Http\FormRequest;

class NotaRequest extends FormRequest
{
    /**
     * Validasi nota (rev UAT 04):
     * - Bengkel wajib
     * - Rincian per baris opsional (description + amount)
     * - Total per pos dihitung otomatis dari jumlah rincian
     * - Minimal satu baris rincian dengan nominal > 0 di seluruh nota
     */
    public function rules(): array
    {
        return [
            'workshop_name' => ['required', 'string', 'max:100'],
            'nota_number' => ['nullable', 'string', 'max:50'],
            'nota_date' => ['nullable', 'date'],
            'details' => ['nullable', 'array'],
            'details.*' => ['nullable', 'array'],
            'details.*.*' => ['nullable', 'string', 'max:255'],
            'detail_amounts' => ['nullable', 'array'],
            'detail_amounts.*' => ['nullable', 'array'],
            // Bisa string ("500.000" dari form) maupun angka (API/test)
            'detail_amounts.*.*' => ['nullable'],
        ];
    }

    public function attributes(): array
    {
        return [
            'workshop_name' => 'nama bengkel',
            'nota_number' => 'nomor nota',
            'nota_date' => 'tanggal nota',
        ];
    }

    /**
     * Minimal satu baris rincian bernilai > 0 di seluruh nota
     * (nota kosong tidak tersimpan — total dihitung dari rincian).
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $total = 0;
            foreach ((array) $this->input('detail_amounts', []) as $list) {
                foreach ((array) $list as $value) {
                    $total += (float) str_replace('.', '', (string) $value);
                }
            }

            if ($total <= 0) {
                $validator->errors()->add('details', 'Nota harus memiliki minimal satu rincian bernilai (lebih dari 0).');
            }
        });
    }
}
