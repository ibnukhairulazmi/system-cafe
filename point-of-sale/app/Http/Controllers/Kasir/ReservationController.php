<?php

namespace App\Http\Controllers\Kasir;

use App\Enums\ReservationStatus;
use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Shift;
use App\Services\Kasir\KasirReservationService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReservationController extends Controller
{
    public function __construct(
        private readonly KasirReservationService $reservationService
    ) {}

    public function index(Request $request): View
    {
        $date   = $request->input('date', today()->toDateString());
        $status = ReservationStatus::tryFrom((string) $request->status);

        $reservations = $this->reservationService->getPaginatedReservations(
            $date,
            $status,
            $request->input('search'),
            15
        );

        return view('kasir.reservations.index', [
            'reservations' => $reservations,
            'statuses'     => ReservationStatus::cases(),
            'date'         => $date,
        ]);
    }

    public function show(Reservation $reservation): View
    {
        $reservation->load(['table', 'items', 'payments', 'handler']);

        return view('kasir.reservations.show', compact('reservation'));
    }

    
    public function checkIn(Request $request, Reservation $reservation): RedirectResponse
    {
        try {
            $this->reservationService->checkIn($reservation, $request->user());
        } catch (DomainException $e) {
            return back()->withErrors(['reservation' => $e->getMessage()]);
        }

        return back()->with('success', "Tamu {$reservation->name} berhasil check-in. Status: Sedang Dilayani.");
    }

    
    public function settle(Request $request, Reservation $reservation): RedirectResponse
    {
        $data = $request->validate([
            'method' => ['required', 'in:cash,qris,transfer'],
        ]);

        $shift = Shift::open()->where('user_id', $request->user()->id)->first();

        if (! $shift) {
            return back()->withErrors(['reservation' => 'Buka shift terlebih dahulu sebelum menerima pembayaran.']);
        }

        try {
            $this->reservationService->settle($reservation, $shift, $request->user(), $data['method']);
        } catch (DomainException $e) {
            return back()->withErrors(['reservation' => $e->getMessage()]);
        }

        return back()->with('success', 'Pelunasan reservasi berhasil dicatat.');
    }

    
    public function complete(Request $request, Reservation $reservation): RedirectResponse
    {
        try {
            $this->reservationService->complete($reservation, $request->user());
        } catch (DomainException $e) {
            return back()->withErrors(['reservation' => $e->getMessage()]);
        }

        return back()->with('success', "Reservasi {$reservation->code} selesai.");
    }
}
