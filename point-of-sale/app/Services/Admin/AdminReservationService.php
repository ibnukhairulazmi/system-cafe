<?php

namespace App\Services\Admin;

use App\Models\Reservation;
use App\Services\ActivityLogger;
use App\Services\ReservationPaymentService;
use App\Support\Format;
use InvalidArgumentException;

class AdminReservationService
{
    public const FILTER_STATUSES = ['awaiting_payment', 'confirmed', 'expired', 'all'];

    public function __construct(
        private readonly ReservationPaymentService $paymentService
    ) {}

    /**
     * Mengambil daftar reservasi berpaginasi untuk admin beserta data filter dan counter status.
     */
    public function getReservationOverview(array $filters, int $perPage = 15): array
    {
        Reservation::expireOverdue();

        $selected = $filters['status'] ?? 'all';

        $reservations = Reservation::query()
            ->with(['handler:id,name', 'items', 'table:id,name', 'payments.receiver:id,name'])
            ->when($selected !== 'all', fn($q) => $q->where('status', $selected))
            ->when($filters['date'] ?? null, fn($q, $v) => $q->whereDate('reserved_date', $v))
            ->when($filters['q'] ?? null, fn($q, $v) => $q->where(
                fn($w) => $w->whereLike('name', Format::like($v))
                    ->orWhereLike('phone', Format::like($v))
                    ->orWhereLike('code', Format::like($v))
            ))
            ->when(
                in_array($selected, ['awaiting_payment', 'confirmed'], true),
                fn($q) => $q->orderBy('reserved_date')->orderBy('reserved_time'),
                fn($q) => $q->orderByDesc('reserved_date')->orderByDesc('reserved_time'),
            )
            ->paginate($perPage)
            ->withQueryString();

        $counts = Reservation::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'reservations' => $reservations,
            'filters'      => $filters,
            'selected'     => $selected,
            'counts'       => $counts,
        ];
    }

    public function updateStaffNote(Reservation $reservation, ?string $note): void
    {
        $reservation->update(['staff_note' => $note]);
        ActivityLogger::log('reservation.note_updated', $reservation, ['code' => $reservation->code]);
    }

    public function recordPayment(Reservation $reservation, string $type, float $amount, string $method, ?string $reference, int $userId): void
    {
        $this->paymentService->record(
            $reservation,
            $type,
            $amount,
            $method,
            $reference,
            $userId
        );

        ActivityLogger::log('reservation.payment.' . $type, $reservation, ['code' => $reservation->code]);
    }
}
