<?php

namespace App\Http\Controllers;

use App\Enums\DeliveryOrderStatus;
use App\Enums\ReservationStatus;
use App\Models\DeliveryOrder;
use App\Models\Reservation;

class NotificationCountController extends Controller
{
    private const DELIVERY_STATUSES = ['pending'];

    public function __invoke()
    {
        return response()->json(self::counts());
    }

    public static function counts(): array
    {
        $role = auth()->user()?->role?->value;

        if (! in_array($role, ['admin', 'kasir'], true)) {
            return ['reservations' => 0, 'delivery_orders' => 0];
        }

        

        return [
            'reservations' => Reservation::where('status', ReservationStatus::Confirmed)
                ->whereDate('reserved_date', today())
                ->whereNull('checked_in_at')
                ->count(),

            'delivery_orders' => DeliveryOrder::where('status', DeliveryOrderStatus::Confirmed)->count(),
        ];
    }
}
