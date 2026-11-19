<?php

namespace App\Services;

use App\Enums\ReservationStatus;
use App\Models\CafeTable;
use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class TableAvailabilityService
{
    
    public function slots(Carbon $date, int $partySize): array
    {
        $cfg      = config('cafe.reservation');
        $earliest = now()->addMinutes($cfg['min_lead_minutes']);

        $freeTables = CafeTable::where('is_active', true)
            ->where('capacity', '>=', $partySize)
            ->whereNotIn('id', $this->bookedTableIds($date))
            ->orderBy('capacity')->orderBy('name')
            ->get()
            ->map(fn($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'area' => $t->area,
                'capacity' => $t->capacity,
            ])
            ->values()
            ->all();

        $cursor = $date->copy()->setTimeFromTimeString($cfg['open']);
        $close  = $date->copy()->setTimeFromTimeString($cfg['close']);
        $slots  = [];

        while ($cursor->lt($close)) {
            $available = $cursor->gte($earliest) && ! empty($freeTables);

            $slots[] = [
                'time'      => $cursor->format('H:i'),
                'available' => $available,
                'tables'    => $available ? $freeTables : [],
            ];

            $cursor->addMinutes($cfg['slot_step_minutes']);
        }

        return $slots;
    }

    
    public function createHold(array $data, int $totalAmount, array $items): Reservation
    {
        $cfg = config('cafe.reservation');

        $start = Carbon::createFromFormat('Y-m-d H:i|', $data['date'] . ' ' . $data['time']);

        if ($start->lt(now()->addMinutes($cfg['min_lead_minutes']))) {
            throw new InvalidArgumentException('Reservasi minimal 1 jam sebelum jam kunjungan.');
        }
        if ($start->gt(now()->addDays($cfg['max_days_ahead'])->endOfDay())) {
            throw new InvalidArgumentException('Reservasi maksimal ' . $cfg['max_days_ahead'] . ' hari ke depan.');
        }
        if (
            $start->lt($start->copy()->setTimeFromTimeString($cfg['open']))
            || $start->gte($start->copy()->setTimeFromTimeString($cfg['close']))
        ) {
            throw new InvalidArgumentException('Jam di luar jam operasional.');
        }

        return DB::transaction(function () use ($data, $totalAmount, $items, $cfg, $start) {
            $table = CafeTable::whereKey($data['table_id'])->where('is_active', true)->lockForUpdate()->first();

            if (! $table) {
                throw new InvalidArgumentException('Meja tidak tersedia.');
            }
            if ($table->capacity < $data['party_size']) {
                throw new InvalidArgumentException('Kapasitas meja tidak cukup untuk jumlah tamu.');
            }
            if (in_array($table->id, $this->bookedTableIds($start), true)) {
                throw new InvalidArgumentException('Meja ini sudah dipesan pada tanggal tersebut. Silakan pilih meja lain.');
            }

            $code = 'RSV-' . strtoupper(Str::random(6));

            $reservation = Reservation::create([
                'code'             => $code,
                'name'             => $data['name'],
                'phone'            => $data['phone'],
                'reserved_date'    => $start->toDateString(),
                'reserved_time'    => $start->format('H:i:s'),
                'party_size'       => $data['party_size'],
                'cafe_table_id'    => $table->id,
                'notes'            => $data['notes'] ?? null,
                'total_amount'     => $totalAmount,
                'payment_status'   => 'unpaid',
                'status'           => ReservationStatus::Pending,
                'hold_expires_at'  => now()->addMinutes($cfg['hold_minutes']),
                'gateway_order_id' => $code,
            ]);

            $reservation->items()->createMany($items); // sesuaikan kolom reservation_items Anda

            return $reservation;
        });
    }

    
    private function bookedTableIds(Carbon $date): array
    {
        return Reservation::query()
            ->whereDate('reserved_date', $date->toDateString())
            ->whereNotNull('cafe_table_id')
            ->where(function ($q) {
                $q->where('status', ReservationStatus::Confirmed)
                    ->orWhere(fn($q2) => $q2
                        ->where('status', ReservationStatus::Pending)
                        ->where('hold_expires_at', '>', now()));
            })
            ->pluck('cafe_table_id')
            ->all();
    }
}
