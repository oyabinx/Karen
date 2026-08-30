<?php

namespace App\Http\Requests\Admin;

use App\Models\Bidang;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SeksiRequest extends FormRequest
{
    /**
     * Seksi milik satu bidang; nama unik dalam bidang tersebut
     * (docs/feature/organisasi.md).
     */
    public function rules(): array
    {
        // Sumber bidang: pindah-bidang (input) > edit seksi (route seksi)
        // > tambah seksi (route bidang)
        $bidangId = $this->input('bidang_id')
            ?? $this->route('seksi')?->bidang_id
            ?? $this->bidangIdFromRoute();

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('seksi', 'name')
                    ->where('bidang_id', $bidangId)
                    ->ignore($this->route('seksi')?->id),
            ],
            'bidang_id' => ['sometimes', 'exists:bidang,id'],
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'nama seksi', 'bidang_id' => 'bidang'];
    }

    private function bidangIdFromRoute(): mixed
    {
        $bidang = $this->route('bidang');

        return $bidang instanceof Bidang ? $bidang->id : $bidang;
    }
}
