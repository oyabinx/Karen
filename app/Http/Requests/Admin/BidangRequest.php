<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class BidangRequest extends FormRequest
{
    /**
     * Bidang + kuota peminjaman (docs/feature/organisasi.md &
     * kuota_bidang.md): kuota 1–9 (direvisi dari 1–5 — UAT C2),
     * default 2.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'max_active_bookings' => ['required', 'integer', 'min:1', 'max:9'],
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'nama bidang', 'max_active_bookings' => 'kuota peminjaman'];
    }
}
