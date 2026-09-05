<?php

namespace App\Http\Requests\Admin;

use App\Models\Seksi;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserStoreRequest extends FormRequest
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

    /**
     * Aturan manajemen user (docs/feature/manajemen_user.md):
     * seksi WAJIB untuk pegawai & pengurus (dasar kuota bidang),
     * opsional hanya untuk admin.
     */
    public function rules(): array
    {
        $seksiWajib = in_array($this->input('role'), ['pegawai', 'pengurus'], true);

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)],
            // Password awal — user ganti sendiri saat login pertama
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::in(['admin', 'pengurus', 'pegawai'])],
            'phone' => [
                'nullable',
                'regex:/^62[8][1-9][0-9]{6,10}$/',
                Rule::unique(User::class),
            ],
            'seksi_id' => [
                $seksiWajib ? 'required' : 'nullable',
                Rule::exists(Seksi::class, 'id'),
            ],
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'nama', 'phone' => 'nomor HP', 'seksi_id' => 'seksi'];
    }
}
