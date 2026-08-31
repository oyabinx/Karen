<?php

namespace App\Http\Controllers\Pegawai;

use App\Http\Controllers\Controller;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class SearchController extends Controller
{
    /**
     * Cari mobil tersedia pada rentang tanggal — pegawai DAN pengurus
     * (docs/feature/peminjaman.md).
     */
    public function __construct(
        private readonly AvailabilityService $availability,
        private readonly BookingService $bookings,
    ) {}

    public function index(Request $request): View
    {
        $start = $request->query('start_date');
        $end = $request->query('end_date');

        $rangeErrors = ($start || $end) ? $this->bookings->rangeErrors($start, $end) : [];

        $vehicles = collect();
        $validRange = $start && $end && $rangeErrors === [];

        if ($validRange) {
            $vehicles = $this->availability->availableBetween(Carbon::parse($start), Carbon::parse($end));
        }

        return view('pegawai.search.index', [
            'vehicles' => $vehicles,
            'start' => $start,
            'end' => $end,
            'rangeErrors' => $rangeErrors,
            'validRange' => $validRange,
            'quota' => $this->bookings->quotaInfo($request->user()),
            'hasActive' => $request->user()->bookings()
                ->whereIn('status', ['dipinjam', 'menunggu_penggantian'])
                ->exists(),
        ]);
    }
}
