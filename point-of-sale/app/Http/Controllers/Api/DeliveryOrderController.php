<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeliveryOrder;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DeliveryOrderController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'wa' => ['required', 'string', 'max:25'],
            'alamat' => ['required', 'string', 'max:500'],
            'patokan' => ['nullable', 'string', 'max:255'],
            'delivery_tier' => ['required', 'string', 'in:saving,fast'],
            'tanggal' => ['nullable', 'date'],
            'jam' => ['nullable', 'string', 'max:10'],
            'jadwal_tipe' => ['nullable', 'string', 'in:asap,scheduled'],
            'payment_method' => ['required', 'string', 'in:qris,transfer,cash'],
            'catatan' => ['nullable', 'string', 'max:500'], 
            'website' => ['prohibited'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'integer'],
            'items.*.name' => ['required', 'string', 'max:150'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.note' => ['nullable', 'string', 'max:255'],
        ], [
            'items.required' => 'Pilih minimal 1 menu makanan atau minuman untuk diantar.',
            'items.min' => 'Pilih minimal 1 menu makanan atau minuman untuk diantar.',
        ]);

        $phone = preg_replace('/\D/', '', $data['wa']);
        $phone = match (true) {
            str_starts_with($phone, '0') => '62' . substr($phone, 1),
            str_starts_with($phone, '8') => '62' . $phone,
            default => $phone,
        };
        if (! preg_match('/^62\d{9,13}$/', $phone)) {
            throw ValidationException::withMessages([
                'wa' => 'Nomor WhatsApp belum valid. Gunakan nomor Indonesia, 10–15 digit.',
            ]);
        }

        do {
            $code = 'DEL-' . strtoupper(Str::random(6));
        } while (DeliveryOrder::where('code', $code)->exists());

        $subtotal = 0;
        $itemsToCreate = [];
        foreach ($data['items'] as $item) {
            $qty = (int) $item['quantity'];
            $price = (float) $item['price'];
            $itemSubtotal = $qty * $price;
            $subtotal += $itemSubtotal;

            $productId = $item['product_id'] ?? null;
            if (! $productId) {
                $p = Product::where('name', $item['name'])->first();
                if ($p) {
                    $productId = $p->id;
                }
            }

            $itemsToCreate[] = [
                'product_id' => $productId,
                'product_name' => trim($item['name']),
                'price' => $price,
                'quantity' => $qty,
                'subtotal' => $itemSubtotal,
                'note' => $item['note'] ?? null,
            ];
        }

        $tier = $data['delivery_tier'];
        $fee = $tier === 'fast' ? 18000 : 8000;
        $total = $subtotal + $fee;

        $isAsap = ($data['jadwal_tipe'] ?? 'asap') === 'asap';
        $deliveryDate = $isAsap ? now()->toDateString() : ($data['tanggal'] ?? now()->toDateString());
        $deliveryTime = $isAsap ? 'ASAP' : ($data['jam'] ?? now()->format('H:i'));

        $paymentMethod = $data['payment_method'];
        $paymentStatus = $paymentMethod === 'cash' ? 'unpaid' : 'paid';

        DB::transaction(function () use (
            $code,
            $data,
            $phone,
            $tier,
            $fee,
            $subtotal,
            $total,
            $deliveryDate,
            $deliveryTime,
            $isAsap,
            $paymentMethod,
            $paymentStatus,
            $itemsToCreate
        ) {
            $order = DeliveryOrder::create([
                'code' => $code,
                'customer_name' => trim($data['nama']),
                'customer_phone' => $phone,
                'delivery_address' => trim($data['alamat']),
                'landmark' => !empty($data['patokan']) ? trim($data['patokan']) : null,
                'delivery_tier' => $tier,
                'delivery_fee' => $fee,
                'subtotal_amount' => $subtotal,
                'total_amount' => $total,
                'delivery_date' => $deliveryDate,
                'delivery_time' => $deliveryTime,
                'is_asap' => $isAsap,
                'payment_method' => $paymentMethod,
                'payment_status' => $paymentStatus,
                'paid_amount' => $paymentStatus === 'paid' ? $total : 0,
                'customer_note' => !empty($data['catatan']) ? trim($data['catatan']) : null,
            ]);

            $order->items()->createMany($itemsToCreate);
        });

        return response()->json([
            'code' => $code,
            'message' => 'Pesanan antar berhasil diterima. Barista kami segera menyiapkan pesananmu.',
        ], 201);
    }
}

