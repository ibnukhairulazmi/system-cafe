<?php

namespace App\Http\Controllers\Api;

use App\Enums\ReservationStatus;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Reservation;
use App\Services\MidtransService;
use App\Services\TableAvailabilityService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    public function availability(Request $request, TableAvailabilityService $svc): JsonResponse
    {
        $v = $request->validate([
            'date'       => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'party_size' => ['required', 'integer', 'min:1', 'max:50'],
        ]);

        return response()->json([
            'slots' => $svc->slots(Carbon::createFromFormat('Y-m-d', $v['date'])->startOfDay(), (int) $v['party_size']),
        ]);
    }

    public function store(Request $request, TableAvailabilityService $svc, MidtransService $midtrans): JsonResponse
    {
        $data = $request->validate([
            'name'               => ['required', 'string', 'max:100'],
            'phone'              => ['required', 'string', 'min:9', 'max:20'],
            'date'               => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'time'               => ['required', 'date_format:H:i'],
            'party_size'         => ['required', 'integer', 'min:1', 'max:50'],
            'table_id'           => ['required', 'exists:cafe_tables,id'],
            'payment_method'     => ['nullable', 'string', 'in:qris,transfer,cash'],
            'notes'              => ['nullable', 'string', 'max:500'],
            'items'              => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity'   => ['required', 'integer', 'min:1', 'max:50'],
            'items.*.note'       => ['nullable', 'string', 'max:200'],
        ]);
        $slot = Carbon::createFromFormat('Y-m-d H:i', $data['date'] . ' ' . $data['time']);
        if ($slot->isPast()) {
            return response()->json(['message' => 'Waktu reservasi sudah lewat.'], 422);
        }
        $productIds = collect($data['items'])->pluck('product_id')->unique()->values();
        $products   = Product::whereIn('id', $productIds)->get()->keyBy('id');

        if ($products->count() !== $productIds->count()) {
            return response()->json(['message' => 'Sebagian menu tidak tersedia.'], 422);
        }

        $items = [];
        $total = 0;
        foreach ($data['items'] as $i) {
            $p        = $products[$i['product_id']];
            $subtotal = $p->price * $i['quantity'];
            $total   += $subtotal;
            $items[]  = [
                'product_id'   => $p->id,
                'product_name' => $p->name,
                'price'        => $p->price,
                'quantity'     => $i['quantity'],
                'note'         => $i['note'] ?? null,
                'subtotal'     => $subtotal,
            ];
        }

        $minOrder = (int) config('cafe.reservation.min_order', 200000);
        if ($total < $minOrder) {
            return response()->json([
                'message' => 'Minimum pesanan Rp ' . number_format($minOrder, 0, ',', '.') . ' untuk reservasi meja.',
            ], 422);
        }

        $data['phone'] = $this->normalizePhone($data['phone']);

        try {
            $reservation = $svc->createHold($data, (int) $total, $items);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        if ($midtrans->isConfigured() && ($data['payment_method'] ?? 'qris') !== 'cash') {
            try {
                $reservation->update(['snap_token' => $midtrans->snapToken($reservation)]);
            } catch (\Throwable $e) {
                report($e);
                $reservation->update([
                    'staff_note' => 'Gagal membuat snap token: ' . $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'code'            => $reservation->code,
            'snap_token'      => $reservation->snap_token,
            'dp_amount'       => (int) $reservation->dp_amount,
            'total_amount'    => (int) $reservation->total_amount,
            'hold_expires_at' => $reservation->hold_expires_at?->toIso8601String(),
        ], 201);
    }
    
    public function show(string $code): JsonResponse
    {
        Reservation::expireOverdue();

        $r = Reservation::with(['table', 'items'])->where('code', $code)->firstOrFail();

        if ($r->status === ReservationStatus::Pending) {
            app(QrisPaymentController::class)->syncMidtransStatus($r, 'table');
            $r->refresh();
        }

        $canPay = $r->status === ReservationStatus::Pending
            && $r->hold_expires_at?->isFuture()
            && $r->snap_token;

        return response()->json([
            'code'            => $r->code,
            'status'          => $r->status->value,
            'payment_status'  => $r->payment_status,
            'customer_name'   => $r->name,
            'customer_phone'  => $r->phone,
            'party_size'      => (int) $r->party_size,
            'table'           => $r->table?->name,
            'date'            => $r->reserved_date->toDateString(),
            'time'            => substr((string) $r->reserved_time, 0, 5),
            'notes'           => $r->notes,
            'dp_amount'       => (int) $r->dp_amount,
            'paid_amount'     => (int) $r->paid_amount,
            'total_amount'    => (int) $r->total_amount,
            'snap_token'      => $canPay ? $r->snap_token : null,
            'hold_expires_at' => $canPay ? $r->hold_expires_at->toIso8601String() : null,
            'items'           => $r->items->map(fn($item) => [
                'name'     => $item->product_name,
                'price'    => (int) $item->price,
                'quantity' => (int) $item->quantity,
                'subtotal' => (int) $item->subtotal,
                'note'     => $item->note,
            ])->values()->all(),
        ]);
    }

    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\D+/', '', $phone);

        if (str_starts_with($phone, '0')) {
            return '62' . substr($phone, 1);
        }
        if (str_starts_with($phone, '8')) {
            return '62' . $phone;
        }

        return $phone;
    }
}