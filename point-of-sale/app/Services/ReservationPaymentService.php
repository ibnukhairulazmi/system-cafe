<?php

namespace App\Services;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ReservationPaymentService
{
    private const EPSILON = 0.009;

    public function record(
        Reservation $reservation,
        string $type,
        float $amount,
        string $method,
        ?string $reference = null,
        ?int $userId = null
    ): Reservation {
        return DB::transaction(function () use ($reservation, $type, $amount, $method, $reference, $userId) {
            $reservation = Reservation::lockForUpdate()->findOrFail($reservation->id);

            if (! in_array($type, ['dp', 'settlement'], true)) {
                throw new InvalidArgumentException('Jenis pembayaran tidak valid.');
            }

            if ($amount <= 0) {
                throw new InvalidArgumentException('Nominal harus lebih dari 0.');
            }

            if ($reservation->status === ReservationStatus::Expired) {
                throw new InvalidArgumentException('Reservasi sudah hangus karena DP tidak dibayar tepat waktu.');
            }

            $alreadyPaid = (float) $reservation->paid_amount;
            $remaining   = $reservation->remaining_amount;

            if ($type === 'dp') {
                if ($alreadyPaid > 0) {
                    throw new InvalidArgumentException('DP sudah pernah dibayar.');
                }
                if ($amount + self::EPSILON < $reservation->minimum_dp) {
                    throw new InvalidArgumentException('Nominal DP kurang dari minimum.');
                }
            }

            if ($type === 'settlement') {
                if ($alreadyPaid <= 0) {
                    throw new InvalidArgumentException('Catat DP terlebih dahulu sebelum pelunasan.');
                }
                if (abs($amount - $remaining) > self::EPSILON) {
                    throw new InvalidArgumentException('Nominal pelunasan harus sama dengan sisa tagihan.');
                }
            }

            if ($amount - $remaining > self::EPSILON) {
                throw new InvalidArgumentException('Nominal melebihi sisa tagihan.');
            }

            $reservation->payments()->create([
                'type'        => $type,
                'amount'      => $amount,
                'method'      => $method,
                'reference'   => $reference,
                'paid_at'     => now(),
                'received_by' => $userId,
            ]);

            $paid = (float) $reservation->payments()->whereIn('type', ['dp', 'settlement'])->sum('amount')
                  - (float) $reservation->payments()->where('type', 'refund')->sum('amount');

            $total = (float) $reservation->total_amount;

            $reservation->paid_amount    = $paid;
            $reservation->payment_method = $method;
            $reservation->payment_status = $paid <= 0
                ? 'unpaid'
                : (($paid + self::EPSILON) >= $total ? 'paid' : 'dp');

            if ($type === 'dp' && $reservation->status === ReservationStatus::Pending) {
                $reservation->status          = ReservationStatus::Confirmed;
                $reservation->hold_expires_at = null;
            }

            $reservation->handled_by = $userId ?? $reservation->handled_by;
            $reservation->handled_at = now();
            $reservation->save();

            return $reservation;
        });
    }
}