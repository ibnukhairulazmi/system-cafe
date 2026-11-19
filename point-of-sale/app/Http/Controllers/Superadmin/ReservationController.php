<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Services\Superadmin\SuperadminAuditService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReservationController extends Controller
{
    public function __construct(
        private readonly SuperadminAuditService $auditService
    ) {}

    public function index(Request $request): View
    {
        $data = $this->auditService->getReservations($request->all(), 15);

        return view('superadmin.reservations.index', $data);
    }

    public function show(Reservation $reservation): View
    {
        $reservation->load(['handler', 'table', 'items', 'payments']);

        return view('superadmin.reservations.show', compact('reservation'));
    }
}
