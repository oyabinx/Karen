<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Nomor HP dinormalisasi dulu ke bentuk baku (62…) agar cek
     * unique membandingkan apel dengan apel (UAT B4).
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('phone')) {
            $this->merge(['phone' => User::canonicalPhone($this->input('phone'))]);
        }
    }

    /**
     * Aturan profil (docs/feature/profil.md):
     * nomor HP WAJIB diisi setiap kali profil disimpan.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            // Format nomor HP Indonesia (input bebas 08xx/62xxx/+62xxx —
            // divalidasi SETELAH normalisasi ke 62xxx)
            'phone' => [
                'required',
                'string',
                'regex:/^62[8][1-9][0-9]{6,10}$/',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nama',
            'phone' => 'nomor HP',
        ];
    }
}
