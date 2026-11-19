<?php

namespace App\Services\Kasir;

use App\Enums\ReservationStatus;
use App\Models\CafeTable;
use App\Models\Reservation;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Collection;

class KasirTableService
{
    /**
     * Mengambil daftar meja aktif beserta status reservasi yang mendekati waktu datang (dalam 60 menit).
     */
    public function getTablesOverview(): Collection
    {
        $upcoming = Reservation::where('status', ReservationStatus::Confirmed)
            ->whereDate('reserved_date', today())
            ->whereNotNull('cafe_table_id')
            ->whereNull('checked_in_at')
            ->get()
            ->keyBy('cafe_table_id');

        return CafeTable::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function (CafeTable $t) use ($upcoming) {
                $next = $upcoming->get($t->id);

                $t->next_reservation = $next;
                $t->reserved_soon    = $next
                    && Carbon::parse(today()->toDateString() . ' ' . $next->reserved_time)->lte(now()->addMinutes(60));

                return $t;
            });
    }

    /**
     * Menandai meja terisi oleh pelanggan walk-in.
     * Memeriksa apakah ada konflik dengan reservasi terkonfirmasi dalam 60 menit ke depan.
     */
    public function occupy(CafeTable $table, int $userId, bool $force = false): void
    {
        $conflict = Reservation::where('cafe_table_id', $table->id)
            ->where('status', ReservationStatus::Confirmed)
            ->whereDate('reserved_date', today())
            ->whereNull('checked_in_at')
            ->where('reserved_time', '<=', now()->addMinutes(60)->format('H:i:s'))
            ->first();

        if ($conflict && ! $force) {
            throw new DomainException("{$table->name} direservasi pukul " . substr($conflict->reserved_time, 0, 5) . '.');
        }

        $table->update([
            'is_occupied' => true,
            'occupied_at' => now(),
            'occupied_by' => $userId,
        ]);
    }

    /**
     * Mengosongkan meja setelah pelanggan selesai.
     */
    public function release(CafeTable $table): void
    {
        $table->update([
            'is_occupied' => false,
            'occupied_at' => null,
            'occupied_by' => null,
        ]);
    }
}
