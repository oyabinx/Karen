<?php

namespace App\Http\Controllers\Pengurus;

use App\Http\Controllers\Controller;
use App\Models\Bidang;
use App\Models\Booking;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingMonitorController extends Controller
{
    /**
     * Monitoring seluruh peminjaman (docs/feature/laporan.md).
     */
    public function index(Request $request): View
    {
        $filters = $request->only(['q', 'vehicle', 'bidang', 'status', 'from', 'to']);

        $bookings = Booking::query()
            ->with(['user.seksi.bidang', 'vehicle', 'originalVehicle', 'complaint'])
            ->when($filters['q'] ?? null, fn ($qq) => $qq->whereHas('user', fn ($u) => $u
                ->where('name', 'like', '%'.$filters['q'].'%')))
            ->when($filters['vehicle'] ?? null, fn ($qq) => $qq->where('vehicle_id', $filters['vehicle']))
            ->when($filters['bidang'] ?? null, fn ($qq) => $qq->whereHas('user.seksi.bidang', fn ($b) => $b->where('id', $filters['bidang'])))
            ->when($filters['status'] ?? null, fn ($qq) => $qq->where('status', $filters['status']))
            ->when(($filters['from'] ?? null) && ($filters['to'] ?? null), fn ($qq) => $qq
                ->whereDate('start_date', '<=', $filters['to'])
                ->whereDate('end_date', '>=', $filters['from']))
            ->orderByDesc('start_date')
            ->paginate(15)
            ->withQueryString();

        return view('pengurus.bookings.index', [
            'bookings' => $bookings,
            'vehicles' => Vehicle::orderBy('name')->get(),
            'bidangList' => Bidang::orderBy('id')->get(),
            'filters' => $filters,
        ]);
    }
}
