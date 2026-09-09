<?php

namespace App\Http\Controllers\Pengurus;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pengurus\EventStoreRequest;
use App\Models\Booking;
use App\Models\Event;
use App\Services\AvailabilityService;
use App\Services\EventService;
use App\Services\ReplacementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class EventController extends Controller
{
    /**
     * Event armada bidang — AKSES: pengurus DAN admin
     * (docs/feature/event_bidang.md).
     */
    public function __construct(
        private readonly EventService $events,
        private readonly ReplacementService $replacements,
        private readonly AvailabilityService $availability,
    ) {}

    public function index(): View
    {
        $events = Event::with(['bidang', 'vehicles', 'creator'])
            ->withCount('vehicles as armada_count')
            ->orderByDesc('start_date')
            ->get()
            ->map(function (Event $event) {
                $event->unresolved_count = $this->events->unresolvedConflicts($event)->count();

                return $event;
            });

        return view('pengurus.events.index', ['events' => $events]);
    }

    /**
     * Wizard langkah 1-2: info event + pemilihan armada
     * (pratinjau armada muncul bila tanggal lengkap via query GET).
     */
    public function create(Request $request): View
    {
        $armada = null;

        if ($request->filled(['start_date', 'end_date'])) {
            $start = Carbon::parse($request->query('start_date'));
            $end = Carbon::parse($request->query('end_date'));

            if ($end->gte($start)) {
                $armada = $this->events->armadaOptions($start, $end);
            }
        }

        return view('pengurus.events.create', [
            'bidangList' => \App\Models\Bidang::orderBy('id')->get(),
            'armada' => $armada,
            'input' => $request->only(['name', 'bidang_id', 'start_date', 'end_date', 'note', 'jumlah_mobil']),
            'selected' => old('vehicles', []),
        ]);
    }

    public function store(EventStoreRequest $request): RedirectResponse
    {
        $result = $this->events->createEvent(
            $request->safe()->only(['name', 'bidang_id', 'start_date', 'end_date', 'note']),
            array_map('intval', $request->input('vehicles')),
            $request->user()->id,
        );

        $event = $result['event'];
        $terdampak = count($result['terdampak']);

        if ($terdampak > 0) {
            return redirect()
                ->route('pengurus.events.conflicts', $event)
                ->with('success', "Event '{$event->name}' dibuat dengan {$event->vehicles->count()} armada. {$terdampak} peminjaman ditabrak — atur mobil penggantinya sebelum konfirmasi.");
        }

        return redirect()
            ->route('pengurus.events.index')
            ->with('success', "Event '{$event->name}' dibuat — armada terkunci (bebas konflik).");
    }

    /**
     * Langkah 3: booking tertabrak + kandidat pengganti.
     */
    public function conflicts(Event $event): View
    {
        // Kandidat per booking — otomatis mengecualikan seluruh armada
        // event (event terjadwal memblokir ketersediaan) + mobil lama.
        // Parsial (UAT 03-B7): bila event hanya menimpa tepi rentang
        // booking, tawarkan pengganti untuk bagian yang menabrak saja.
        $bookings = $this->events->unresolvedConflicts($event)
            ->map(function (Booking $booking) use ($event) {
                $booking->candidates = $this->replacements->candidates($booking);

                $bs = Carbon::parse($booking->start_date)->startOfDay();
                $be = Carbon::parse($booking->end_date)->startOfDay();
                $os = $bs->max(Carbon::parse($event->start_date)->startOfDay());
                $oe = $be->min(Carbon::parse($event->end_date)->startOfDay());

                $penuh = $os->equalTo($bs) && $oe->equalTo($be);
                $tepi = $os->equalTo($bs) || $oe->equalTo($be);

                $booking->partial = (! $penuh && $tepi) ? ['os' => $os, 'oe' => $oe] : null;
                $booking->partialCandidates = $booking->partial
                    ? $this->availability->availableBetween($booking->partial['os'], $booking->partial['oe'], $booking->vehicle_id)
                    : collect();

                return $booking;
            });

        return view('pengurus.events.conflicts', [
            'event' => $event->load(['bidang', 'vehicles']),
            'bookings' => $bookings,
        ]);
    }

    /**
     * Pilih mobil pengganti untuk satu booking tertabrak.
     */
    public function assignBooking(Request $request, Event $event, Booking $booking): RedirectResponse
    {
        $request->validate(['vehicle_id' => ['required', 'exists:vehicles,id']]);

        if ($booking->status !== Booking::STATUS_MENUNGGU_PENGGANTIAN) {
            return back()->with('error', 'Peminjaman ini tidak menunggu penggantian.');
        }

        try {
            $replacement = \App\Models\Vehicle::findOrFail($request->input('vehicle_id'));
            $this->replacements->assign($booking, $replacement);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Pengganti ditetapkan: {$replacement->name}.");
    }

    /**
     * Batalkan satu booking tertabrak (tanpa pengganti).
     */
    public function cancelBooking(Event $event, Booking $booking): RedirectResponse
    {
        if ($booking->status !== Booking::STATUS_MENUNGGU_PENGGANTIAN) {
            return back()->with('error', 'Peminjaman ini tidak menunggu penggantian.');
        }

        $this->replacements->cancel($booking);

        return back()->with('success', 'Peminjaman dibatalkan (tanpa pengganti).');
    }

    /**
     * Penggantian PARSIAL dari konflik event (UAT 03-B7): pengganti
     * hanya untuk tanggal yang ditabrak event; sisa tetap mobil lama.
     */
    public function assignPartial(Request $request, Event $event, Booking $booking): RedirectResponse
    {
        $request->validate(['vehicle_id' => ['required', 'exists:vehicles,id']]);

        if ($booking->status !== Booking::STATUS_MENUNGGU_PENGGANTIAN) {
            return back()->with('error', 'Peminjaman ini tidak menunggu penggantian.');
        }

        $bs = Carbon::parse($booking->start_date)->startOfDay();
        $be = Carbon::parse($booking->end_date)->startOfDay();
        $os = $bs->max(Carbon::parse($event->start_date)->startOfDay());
        $oe = $be->min(Carbon::parse($event->end_date)->startOfDay());

        try {
            $replacement = \App\Models\Vehicle::findOrFail($request->input('vehicle_id'));
            $result = $this->replacements->assignPartial($booking, $os, $oe, $replacement);
        } catch (\InvalidArgumentException|\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        $segmenInfo = collect($result['segments'])
            ->map(fn ($s) => "{$s->start_date->translatedFormat('d M')}–{$s->end_date->translatedFormat('d M Y')} {$s->vehicle->name}")
            ->implode('; ');

        return back()->with('success', "Pengganti parsial — peminjaman terpecah: {$segmenInfo}");
    }

    /**
     * Langkah 4: finalisasi — hanya boleh bila semua konflik selesai.
     */
    public function confirm(Event $event): RedirectResponse
    {
        if ($event->status !== 'terjadwal') {
            return redirect()->route('pengurus.events.index')->with('error', 'Event tidak dalam status terjadwal.');
        }

        $unresolved = $this->events->unresolvedConflicts($event)->count();

        if ($unresolved > 0) {
            return back()->with('error', "Masih ada {$unresolved} peminjaman tanpa keputusan pengganti — selesaikan dahulu.");
        }

        return redirect()
            ->route('pengurus.events.index')
            ->with('success', "Event '{$event->name}' dikonfirmasi — armada terkunci sampai {$event->end_date->translatedFormat('d M Y')}.");
    }

    /**
     * Batalkan event: armada lepas; booking belum diganti kembali ke
     * mobil semula; yang sudah diganti tetap di penggantinya.
     */
    public function cancel(Event $event): RedirectResponse
    {
        $dikembalikan = $this->events->cancelEvent($event);

        $pesan = "Event '{$event->name}' dibatalkan — armada dilepaskan.";
        if ($dikembalikan > 0) {
            $pesan .= " {$dikembalikan} peminjaman dikembalikan ke mobil semula.";
        }

        return redirect()->route('pengurus.events.index')->with('success', $pesan);
    }
}
