<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Services\Admin\AdminReservationService;
use InvalidArgumentException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReservationController extends Controller
{
    public function __construct(
        private readonly AdminReservationService $reservationService
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(AdminReservationService::FILTER_STATUSES)],
            'date'   => ['nullable', 'date'],
            'q'      => ['nullable', 'string', 'max:100'],
        ]);

        $data = $this->reservationService->getReservationOverview($filters, 15);

        return view('admin.reservations.index', $data);
    }

    public function update(Request $request, Reservation $reservation): RedirectResponse
    {
        $data = $request->validate([
            'staff_note' => ['nullable', 'string', 'max:500'],
        ]);

        $this->reservationService->updateStaffNote($reservation, $data['staff_note'] ?? null);

        return back()->with('success', "Catatan reservasi {$reservation->code} diperbarui.");
    }

    public function storePayment(Request $request, Reservation $reservation): RedirectResponse
    {
        $data = $request->validate([
            'type'      => ['required', 'in:dp,settlement'],
            'amount'    => ['required', 'numeric', 'min:1'],
            'method'    => ['required', 'in:cash,transfer,qris'],
            'reference' => ['nullable', 'string', 'max:100'],
        ]);

        try {
            $this->reservationService->recordPayment(
                $reservation,
                $data['type'],
                (float) $data['amount'],
                $data['method'],
                $data['reference'] ?? null,
                $request->user()->id
            );
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }

        return back()->with('success', $data['type'] === 'dp'
            ? 'DP dicatat, reservasi dikonfirmasi.'
            : 'Pelunasan dicatat.');
    }
}
