<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReservationStatus;
use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Services\ActivityLogger;
use App\Support\Format;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReservationController extends Controller
{
    public function index(Request $request): View
    {
        $f = $request->validate([
            'status' => ['nullable', Rule::in(['pending', 'confirmed', 'rejected', 'cancelled', 'all'])],
            'date' => ['nullable', 'date'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $selected = $f['status'] ?? 'pending';

        $reservations = Reservation::query()
            ->with('handler:id,name')
            ->when($selected !== 'all', fn($q) => $q->where('status', $selected))
            ->when($f['date'] ?? null, fn($q, $v) => $q->whereDate('reserved_date', $v))
            ->when($f['q'] ?? null, fn($q, $v) => $q->where(
                fn($w) => $w->whereLike('name', Format::like($v))
                    ->orWhereLike('phone', Format::like($v))
                    ->orWhereLike('code', Format::like($v))
            ))
            ->when(
                $selected === 'pending',
                fn($q) => $q->orderBy('reserved_date')->orderBy('reserved_time'),
                fn($q) => $q->orderByDesc('reserved_date')->orderByDesc('reserved_time'),
            )
            ->paginate(15)
            ->withQueryString();

        $counts = Reservation::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.reservations.index', [
            'reservations' => $reservations,
            'filters' => $f,
            'selected' => $selected,
            'counts' => $counts,
        ]);
    }

    public function update(Request $request, Reservation $reservation): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(ReservationStatus::class)],
            'staff_note' => ['nullable', 'string', 'max:500'],
        ]);

        $reservation->update($data + [
            'handled_by' => $request->user()->id,
            'handled_at' => now(),
        ]);

        ActivityLogger::log('reservation.' . $data['status'], $reservation, ['code' => $reservation->code]);

        return back()->with('success', "Reservasi {$reservation->code} diperbarui.");
    }
}
