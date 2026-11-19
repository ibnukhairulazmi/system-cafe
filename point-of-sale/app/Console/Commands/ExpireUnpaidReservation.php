<?php

namespace App\Console\Commands;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Services\ActivityLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExpireUnpaidReservation extends Command
{
    protected $signature = 'reservations:expire-unpaid';

    protected $description = 'Menghanguskan reservasi berstatus Menunggu yang DP-nya tidak dibayar sampai batas waktu.';

    public function handle(): int
    {
        $ids = Reservation::query()
            ->where('status', ReservationStatus::Pending)
            ->whereNotNull('hold_expires_at')
            ->where('hold_expires_at', '<=', now())
            ->pluck('id');

        $expired = 0;

        foreach ($ids as $id) {
            DB::transaction(function () use ($id, &$expired) {
                $r = Reservation::lockForUpdate()->find($id);

                if (! $r
                    || $r->status !== ReservationStatus::Pending
                    || (float) $r->paid_amount > 0
                    || ! $r->hold_expires_at?->isPast()) {
                    return;
                }

                $note = 'Hangus otomatis: DP tidak dibayar sampai batas waktu.';

                $r->update([
                    'status'     => ReservationStatus::Expired,
                    'staff_note' => $r->staff_note ? $r->staff_note . ' | ' . $note : $note,
                ]);

                rescue(fn() => ActivityLogger::log('reservation.expired', $r, ['code' => $r->code]));

                $expired++;
            });
        }

        $this->info("{$expired} reservasi dihanguskan.");

        return self::SUCCESS;
    }
}