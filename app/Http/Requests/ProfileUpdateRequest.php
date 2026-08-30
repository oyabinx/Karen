<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
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
            // Format nomor HP Indonesia: 08xx / 62xxx / +62xxx
            'phone' => [
                'required',
                'string',
                'regex:/^(\+62|62|0)8[1-9][0-9]{6,10}$/',
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
