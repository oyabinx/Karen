<?php

namespace App\Http\Requests\Pegawai;

use App\Services\BookingService;
use Illuminate\Foundation\Http\FormRequest;

class BookingStoreRequest extends FormRequest
{
    /**
     * Validasi form booking (docs/feature/peminjaman.md) —
     * aturan rentang (durasi ≤3 hari, start >= today) divalidasi
     * oleh BookingService::rangeErrors agar tunggal sumbernya.
     */
    public function rules(): array
    {
        return [
            'vehicle_id' => ['required', 'exists:vehicles,id'],
            'start_date' => ['required', 'date'],
            'end_date' => [
                'required',
                'date',
                function (string $attribute, mixed $value, \Closure $fail) {
                    foreach (app(BookingService::class)->rangeErrors($this->input('start_date'), $value) as $error) {
                        $fail($error);
                    }
                },
            ],
            'address' => ['required', 'string', 'max:255'],
            'purpose' => ['required', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'start_date' => 'tanggal mulai',
            'end_date' => 'tanggal selesai',
            'address' => 'alamat tujuan',
            'purpose' => 'keperluan',
        ];
    }
}
