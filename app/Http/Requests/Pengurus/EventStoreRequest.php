<?php

namespace App\Http\Requests\Pengurus;

use App\Models\Vehicle;
use App\Services\EventService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class EventStoreRequest extends FormRequest
{
    /**
     * Wizard event (docs/feature/event_bidang.md):
     * durasi FLEKSIBEL (boleh >3 hari — batas 3 hari hanya untuk
     * peminjaman biasa); jumlah mobil terpilih harus tepat N.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'bidang_id' => ['required', Rule::exists('bidang', 'id')],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'note' => ['nullable', 'string', 'max:255'],
            'jumlah_mobil' => ['required', 'integer', 'min:1'],
            'vehicles' => [
                'required', 'array', 'min:1',
                // Jumlah armada terpilih harus tepat N (docs langkah 2)
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (is_array($value) && count($value) !== (int) $this->input('jumlah_mobil')) {
                        $fail('Jumlah armada terpilih ('.count($value).') harus tepat '.$this->input('jumlah_mobil').' mobil.');
                    }
                },
            ],
            'vehicles.*' => [
                Rule::exists(Vehicle::class, 'id'),
                // Armada harus layak: bisa dipinjam, kondisi baik,
                // tanpa maintenance/event lain overlap
                function (string $attribute, mixed $value, \Closure $fail) {
                    $vehicle = Vehicle::find($value);

                    if (! $vehicle || ! app(EventService::class)->isEligible(
                        $vehicle,
                        Carbon::parse($this->input('start_date')),
                        Carbon::parse($this->input('end_date')),
                    )) {
                        $fail("Kendaraan '{$vehicle?->name}' tidak layak untuk event pada rentang ini (maintenance, event lain, atau status/kondisi).");
                    }
                },
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nama event',
            'bidang_id' => 'bidang penyelenggara',
            'start_date' => 'tanggal mulai',
            'end_date' => 'tanggal selesai',
            'jumlah_mobil' => 'jumlah mobil dibutuhkan',
            'vehicles' => 'armada',
        ];
    }
}
