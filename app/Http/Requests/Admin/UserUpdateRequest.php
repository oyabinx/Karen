<?php

namespace App\Http\Requests\Admin;

use App\Models\Seksi;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserUpdateRequest extends FormRequest
{
    /**
     * Nomor HP dinormalisasi ke bentuk baku sebelum validasi (UAT B4).
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('phone')) {
            $this->merge(['phone' => User::canonicalPhone($this->input('phone'))]);
        }
    }

    public function rules(): array
    {
        $user = $this->route('user');
        $seksiWajib = in_array($this->input('role'), ['pegawai', 'pengurus'], true);

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
            // Kosong = password tidak diganti; tanpa spasi (UAT B3)
            'password' => ['nullable', 'string', 'min:8', 'not_regex:/[\s]/'],
            'role' => ['required', Rule::in(['admin', 'pengurus', 'pegawai'])],
            'phone' => [
                'nullable',
                'regex:/^62[8][1-9][0-9]{6,10}$/',
                Rule::unique(User::class)->ignore($user->id),
            ],
            'seksi_id' => [
                $seksiWajib ? 'required' : 'nullable',
                Rule::exists(Seksi::class, 'id'),
            ],
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'nama', 'phone' => 'nomor HP', 'seksi_id' => 'seksi', 'password' => 'kata sandi'];
    }

    public function messages(): array
    {
        return ['password.not_regex' => 'Kata sandi tidak boleh mengandung spasi.'];
    }
}
