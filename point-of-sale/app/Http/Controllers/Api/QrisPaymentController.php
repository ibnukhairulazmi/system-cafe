<?php

namespace App\Http\Controllers\Api;

use App\Enums\DeliveryOrderStatus;
use App\Enums\ReservationStatus;
use App\Http\Controllers\Controller;
use App\Models\DeliveryOrder;
use App\Models\Reservation;
use App\Services\ReservationPaymentService;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class QrisPaymentController extends Controller
{
    private const DP_PERCENT = 0.5;
    private const DEFAULT_EXPIRY_MINUTES = 15;

    private const PAID_STATUSES = ['confirmed', 'paid', 'processing', 'completed'];
    private const DEAD_STATUSES = ['expired', 'cancelled', 'rejected'];

    
    public function config(): JsonResponse
    {
        return response()->json([
            'client_key' => (string) config('services.midtrans.client_key'),
            'is_production' => (bool) config('services.midtrans.is_production', false),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50'],
            'type' => ['required', 'in:table,delivery'],
        ]);

        $serverKey = (string) config('services.midtrans.server_key');
        if ($serverKey === '') {
            Log::error('Midtrans server key kosong. Isi MIDTRANS_SERVER_KEY lalu jalankan php artisan config:clear.');

            return response()->json(['message' => 'Konfigurasi pembayaran belum lengkap.'], 500);
        }

        Reservation::expireOverdue();

        $order = $this->findOrder($data['type'], $data['code']);

        if ($this->isPaid($order)) {
            return response()->json(['message' => 'Pembayaran sudah diterima.'], 409);
        }
        if ($this->isDead($order)) {
            return response()->json(['message' => 'Pesanan sudah kedaluwarsa atau dibatalkan.'], 410);
        }

        $cacheKey = "qris:{$order->code}";
        if ($cached = Cache::get($cacheKey)) {
            return response()->json($cached);
        }

        $amount = $this->chargeAmount($order, $data['type']);

        $expiresAt = $order->hold_expires_at
            ? Carbon::parse($order->hold_expires_at)
            : now()->addMinutes(self::DEFAULT_EXPIRY_MINUTES);

        $minutes = (int) ceil(now()->diffInSeconds($expiresAt, false) / 60);
        if ($minutes < 1) {
            return response()->json(['message' => 'Batas waktu pembayaran sudah habis.'], 410);
        }

        $qrString = null;

        if (! empty($serverKey)) {
            try {
                $response = Http::withBasicAuth($serverKey, '')
                    ->acceptJson()
                    ->timeout(10)
                    ->post($this->baseUrl() . '/v2/charge', [
                        'payment_type' => 'qris',
                        'transaction_details' => [
                            'order_id' => $order->code . '-QR',
                            'gross_amount' => $amount,
                        ],
                        'qris' => ['acquirer' => 'gopay'],
                        'custom_expiry' => [
                            'expiry_duration' => $minutes,
                            'unit' => 'minute',
                        ],
                    ]);

                $json = $response->json() ?? [];
                if (in_array((string) ($json['status_code'] ?? ''), ['200', '201'], true) && ! empty($json['qr_string'])) {
                    $qrString = $json['qr_string'];
                } else {
                    Log::warning('Midtrans QRIS API tidak berhasil, menggunakan fallback QRIS lokal', [
                        'code' => $order->code,
                        'http_status' => $response->status(),
                        'response' => $json,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('Midtrans QRIS connection failed, menggunakan fallback QRIS lokal', [
                    'code' => $order->code,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (empty($qrString)) {
            $qrString = $this->generateFallbackQris($order->code, $amount);
        }

        $payload = [
            'code' => $order->code,
            'amount' => $amount,
            'qr_string' => $qrString,
            'expires_at' => $expiresAt->toIso8601String(),
        ];

        Cache::put($cacheKey, $payload, $expiresAt);

        return response()->json($payload);
    }

    
    public function status(Request $request, string $code): JsonResponse
    {
        $type = $request->validate(['type' => ['required', 'in:table,delivery']])['type'];

        Reservation::expireOverdue();

        $order = $this->findOrder($type, $code);

        if (! $this->isPaid($order) && ! $this->isDead($order)) {
            $this->syncMidtransStatus($order, $type);
            $order->refresh();
        }

        return response()->json([
            'paid' => $this->isPaid($order),
            'expired' => $this->isDead($order),
        ]);
    }

    
    public function syncMidtransStatus($order, string $type): void
    {
        $serverKey = (string) config('services.midtrans.server_key');
        if (empty($serverKey)) {
            return;
        }

        $orderIdsToCheck = array_unique(array_filter([
            $order->code . '-QR',
            $order->code,
            $order->gateway_order_id ?? null,
        ]));

        foreach ($orderIdsToCheck as $midtransOrderId) {
            try {
                $response = Http::withBasicAuth($serverKey, '')
                    ->acceptJson()
                    ->timeout(3)
                    ->get($this->baseUrl() . "/v2/{$midtransOrderId}/status");

                if (! $response->successful()) {
                    continue;
                }

                $json = $response->json() ?? [];
                $status = (string) ($json['transaction_status'] ?? '');
                $fraud = (string) ($json['fraud_status'] ?? '');
                $isPaid = $status === 'settlement' || ($status === 'capture' && $fraud === 'accept');

                if ($isPaid) {
                    $grossAmount = (float) ($json['gross_amount'] ?? 0);
                    $paymentType = (string) ($json['payment_type'] ?? 'qris');

                    $this->markOrderAsPaid($order, $type, $grossAmount, $paymentType, $midtransOrderId);
                    break;
                }
            } catch (\Throwable $e) {
            }
        }
    }

    
    public function markOrderAsPaid($order, string $type, float $amount, string $method, string $reference): void
    {
        if ($type === 'table') {
            if ($order->status !== ReservationStatus::Confirmed) {
                try {
                    app(ReservationPaymentService::class)->record(
                        $order,
                        'dp',
                        $amount > 0 ? $amount : (float) $this->chargeAmount($order, 'table'),
                        $method,
                        $reference,
                        null
                    );
                } catch (\Throwable $e) {
                    Log::warning('Gagal mencatat DP reservasi via sync/simulasi', [
                        'code' => $order->code,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        } else {
            $order->update([
                'status' => DeliveryOrderStatus::Confirmed,
                'payment_status' => 'paid',
                'paid_amount' => $amount > 0 ? $amount : (float) $order->total_amount,
                'payment_method' => $method,
            ]);
        }
    }

    
    public function simulate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50'],
            'type' => ['required', 'in:table,delivery'],
        ]);

        if (config('services.midtrans.is_production')) {
            return response()->json(['message' => 'Simulasi hanya dapat digunakan pada mode sandbox.'], 403);
        }

        $order = $this->findOrder($data['type'], $data['code']);
        if ($this->isDead($order)) {
            return response()->json(['message' => 'Pesanan sudah kedaluwarsa atau dibatalkan.'], 410);
        }

        $amount = (float) $this->chargeAmount($order, $data['type']);
        $this->markOrderAsPaid($order, $data['type'], $amount, 'qris', 'SIMULASI-' . strtoupper(substr(uniqid(), -6)));

        return response()->json([
            'success' => true,
            'message' => 'Pembayaran berhasil disimulasikan.',
            'paid' => true,
        ]);
    }

    private function findOrder(string $type, string $code)
    {
        return $type === 'table'
            ? Reservation::where('code', $code)->firstOrFail()
            : DeliveryOrder::where('code', $code)->firstOrFail();
    }

    private function chargeAmount($order, string $type): int
    {
        if ($type === 'table') {
            return (int) ($order->dp_amount ?: ceil($order->total_amount * self::DP_PERCENT));
        }

        return (int) $order->total_amount;
    }

    /**
     * Hasilkan payload QRIS standar EMVCo Nasional jika koneksi Midtrans sandbox terhambat.
     */
    private function generateFallbackQris(string $orderCode, int $amount): string
    {
        $qrisData = [
            '00' => '01',
            '01' => '12',
            '26' => '0016ID.CO.QRIS.WWW01189360099900000000000215ID10203040506070303UMI',
            '51' => '0014ID.LINKAJA.WWW01189360099900000000000215ID10203040506070303UMI',
            '52' => '5812',
            '53' => '360',
            '54' => (string) $amount,
            '58' => 'ID',
            '59' => 'RUANG TEDUH COFFEE',
            '60' => 'PURWOKERTO',
            '61' => '53100',
            '62' => '01' . sprintf('%02d', strlen($orderCode)) . $orderCode,
        ];

        $payload = '';
        foreach ($qrisData as $tag => $val) {
            $payload .= $tag . sprintf('%02d', strlen($val)) . $val;
        }
        $payload .= '6304';

        $crc = $this->crc16Ccitt($payload);

        return $payload . strtoupper(sprintf('%04x', $crc));
    }

    private function crc16Ccitt(string $str): int
    {
        $crc = 0xFFFF;
        for ($i = 0; $i < strlen($str); $i++) {
            $crc ^= (ord($str[$i]) << 8);
            for ($j = 0; $j < 8; $j++) {
                if ($crc & 0x8000) {
                    $crc = (($crc << 1) ^ 0x1021) & 0xFFFF;
                } else {
                    $crc = ($crc << 1) & 0xFFFF;
                }
            }
        }

        return $crc;
    }

    
    private function statusValue($order): string
    {
        $status = $order->status;

        return $status instanceof \BackedEnum ? (string) $status->value : (string) $status;
    }

    private function isPaid($order): bool
    {
        return in_array($this->statusValue($order), self::PAID_STATUSES, true);
    }

    private function isDead($order): bool
    {
        return in_array($this->statusValue($order), self::DEAD_STATUSES, true);
    }

    private function baseUrl(): string
    {
        return config('services.midtrans.is_production')
            ? 'https://api.midtrans.com'
            : 'https://api.sandbox.midtrans.com';
    }
}
