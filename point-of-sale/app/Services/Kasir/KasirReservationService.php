<?php

namespace App\Services\Kasir;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\ReservationPayments;
use App\Models\Shift;
use App\Models\User;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class KasirReservationService
{
    /**
     * Mengambil daftar reservasi berpaginasi berdasarkan filter tanggal, status, dan kata kunci.
     */
    public function getPaginatedReservations(string $date, ?ReservationStatus $status, ?string $search, int $perPage = 15): LengthAwarePaginator
    {
        return Reservation::query()
            ->with('table')
            ->whereDate('reserved_date', $date)
            ->when(
                $status,
                fn($q) => $q->where('status', $status->value),
                fn($q) => $q->whereIn('status', array_map(fn($s) => $s->value, ReservationStatus::active()))
            )
            ->when(filled($search), function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->whereLike('code', "%{$search}%")
                        ->orWhereLike('name', "%{$search}%")
                        ->orWhereLike('phone', "%{$search}%");
                });
            })
            ->orderBy('reserved_time')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Memproses tamu datang dan check-in: status berubah dari Confirmed -> Seated.
     * Otomatis menandai meja terisi jika reservasi dialokasikan ke suatu meja.
     */
    public function checkIn(Reservation $reservation, User $user): void
    {
        if (! $reservation->status->canBecome(ReservationStatus::Seated)) {
            throw new DomainException('Hanya reservasi yang sudah dikonfirmasi (DP masuk) yang bisa check-in.');
        }

        DB::transaction(function () use ($reservation, $user) {
            $reservation->update([
                'status'        => ReservationStatus::Seated,
                'checked_in_at' => now(),
                'handled_by'    => $user->id,
                'handled_at'    => now(),
            ]);

            if ($reservation->cafe_table_id) {
                $reservation->table?->update([
                    'is_occupied' => true,
                    'occupied_at' => now(),
                    'occupied_by' => $user->id,
                ]);
            }
        });
    }

    /**
     * Mencatat pelunasan sisa tagihan reservasi di kasir.
     */
    public function settle(Reservation $reservation, Shift $shift, User $user, string $method): void
    {
        DB::transaction(function () use ($reservation, $shift, $user, $method) {
            $locked = Reservation::lockForUpdate()->findOrFail($reservation->id);

            if (! in_array($locked->status, ReservationStatus::active(), true)) {
                throw new DomainException('Reservasi belum dikonfirmasi atau sudah tidak aktif.');
            }

            $remaining = $locked->remaining_amount;

            if ($remaining <= 0) {
                throw new DomainException('Reservasi ini sudah lunas.');
            }

            ReservationPayments::create([
                'reservation_id' => $locked->id,
                'type'           => 'pelunasan',
                'shift_id'       => $shift->id,
                'amount'         => $remaining,
                'method'         => $method,
                'received_by'    => $user->id,
                'paid_at'        => now(),
            ]);

            $locked->update([
                'paid_amount'    => $locked->total_amount,
                'payment_status' => 'paid',
                'handled_by'     => $user->id,
                'handled_at'     => now(),
            ]);
        });
    }

    /**
     * Menyelesaikan reservasi saat tamu telah keluar dan meja dikosongkan (Seated -> Completed).
     */
    public function complete(Reservation $reservation, User $user): void
    {
        if (! $reservation->status->canBecome(ReservationStatus::Completed)) {
            throw new DomainException('Hanya tamu yang sedang dilayani yang bisa diselesaikan.');
        }

        if ($reservation->remaining_amount > 0) {
            throw new DomainException('Terima pelunasan terlebih dahulu sebelum menyelesaikan reservasi.');
        }

        $reservation->update([
            'status'     => ReservationStatus::Completed,
            'handled_by' => $user->id,
            'handled_at' => now(),
        ]);
    }
}
