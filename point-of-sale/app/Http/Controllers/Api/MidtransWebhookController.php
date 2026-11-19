<?php

namespace App\Http\Controllers\Api;

use App\Enums\ReservationStatus;
use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Services\ReservationPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MidtransWebhookController extends Controller
{
    public function handle(Request $request, ReservationPaymentService $service): JsonResponse
    {
        $p = $request->all();

        $orderId = (string) ($p['order_id'] ?? '');

        $expected = hash(
            'sha512',
            $orderId . ($p['status_code'] ?? '') . ($p['gross_amount'] ?? '') . config('services.midtrans.server_key')
        );
        if (! hash_equals($expected, (string) ($p['signature_key'] ?? ''))) {
            abort(403);
        }

        $code = preg_replace('/-QR$/', '', $orderId);

        $r = Reservation::where('gateway_order_id', $code)->first();
        if (! $r) {
            return response()->json(['ok' => true]); 
        }

        $status = (string) ($p['transaction_status'] ?? '');
        $isPaid = $status === 'settlement'
            || ($status === 'capture' && ($p['fraud_status'] ?? '') === 'accept');

        if ($isPaid) {
            if ($r->payments()->where('reference', $orderId)->exists()) {
                return response()->json(['ok' => true]);
            }

            if ($r->status !== ReservationStatus::Pending) {
                Log::warning('Pembayaran masuk untuk reservasi bukan awaiting_payment (perlu cek/refund)', [
                    'code' => $r->code,
                    'status' => $r->status->value,
                    'order_id' => $orderId,
                ]);

                return response()->json(['ok' => true]);
            }

            $method = ($p['payment_type'] ?? '') === 'qris' ? 'qris' : 'transfer';

            try {
                $service->record($r, 'dp', (float) ($p['gross_amount'] ?? 0), $method, $orderId, null);
            } catch (\InvalidArgumentException $e) {
                Log::warning('Pembayaran DP tidak dapat dicatat (perlu cek/refund)', [
                    'code' => $r->code,
                    'order_id' => $orderId,
                    'error' => $e->getMessage(),
                ]);
            }
        } elseif (
            in_array($status, ['expire', 'cancel', 'deny'], true)
            && $r->status === ReservationStatus::Pending
        ) {
            $r->update([
                'status'          => ReservationStatus::Expired,
                'hold_expires_at' => null,
                'staff_note'      => 'Dibatalkan otomatis: pembayaran DP ' . $status . '.',
            ]);
        }

        return response()->json(['ok' => true]);
    }
}
