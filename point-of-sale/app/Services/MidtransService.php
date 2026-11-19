<?php
namespace App\Services;

use App\Models\Reservation;
use Midtrans\Config;
use Midtrans\Snap;

class MidtransService
{
    public function __construct()
    {
        Config::$serverKey    = config('services.midtrans.server_key');
        Config::$isProduction = (bool) config('services.midtrans.is_production');
        Config::$isSanitized  = true;
        Config::$is3ds        = true;
    }

    public function isConfigured(): bool
    {
        return !empty(config('services.midtrans.server_key'));
    }

    public function snapToken(Reservation $r): string
    {
        $dp = (int) $r->dp_amount;

        return Snap::getSnapToken([
            'transaction_details' => ['order_id' => $r->gateway_order_id, 'gross_amount' => $dp],
            'item_details'        => [['id' => $r->code, 'price' => $dp, 'quantity' => 1, 'name' => 'DP Reservasi ' . $r->code]],
            'customer_details'    => ['first_name' => $r->name, 'phone' => $r->phone],
            'expiry'              => ['unit' => 'minute', 'duration' => (int) config('cafe.reservation.hold_minutes')],
        ]);
    }
}