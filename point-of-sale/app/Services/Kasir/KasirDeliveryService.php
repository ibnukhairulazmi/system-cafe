<?php

namespace App\Services\Kasir;

use App\Enums\DeliveryOrderStatus;
use App\Models\DeliveryOrder;
use App\Models\Shift;
use App\Models\User;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class KasirDeliveryService
{
    /**
     * Mengambil daftar pesanan antar (delivery) berpaginasi untuk kasir.
     */
    public function getPaginatedOrders(?DeliveryOrderStatus $status, ?string $search, int $perPage = 15): LengthAwarePaginator
    {
        return DeliveryOrder::query()
            ->when(
                $status,
                fn($q) => $q->where('status', $status->value),
                fn($q) => $q->whereIn('status', [
                    DeliveryOrderStatus::Confirmed->value,
                    DeliveryOrderStatus::OnDelivery->value,
                ])
            )
            ->when(filled($search), function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->whereLike('code', "%{$search}%")
                        ->orWhereLike('customer_name', "%{$search}%")
                        ->orWhereLike('customer_phone', "%{$search}%");
                });
            })
            ->orderBy('delivery_date')
            ->orderByDesc('is_asap')
            ->orderBy('delivery_time')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Mengubah status pesanan menjadi sedang diantar oleh kurir/petugas (Confirmed -> OnDelivery).
     */
    public function ship(DeliveryOrder $order, User $user): void
    {
        if (! $order->status->canBecome(DeliveryOrderStatus::OnDelivery)) {
            throw new DomainException('Hanya pesanan yang siap dikirim yang bisa dikirim.');
        }

        $order->update([
            'status'     => DeliveryOrderStatus::OnDelivery,
            'handled_by' => $user->id,
            'handled_at' => now(),
        ]);
    }

    /**
     * Menyelesaikan pesanan setelah sampai ke tangan pelanggan (OnDelivery -> Delivered).
     */
    public function complete(DeliveryOrder $order, User $user): void
    {
        if (! $order->status->canBecome(DeliveryOrderStatus::Delivered)) {
            throw new DomainException('Hanya pesanan yang sedang dikirim yang bisa diselesaikan.');
        }

        if ($order->payment_method === 'cash' && $order->payment_status !== 'paid') {
            throw new DomainException('Terima pembayaran tunai (COD) terlebih dahulu sebelum menandai pesanan selesai.');
        }

        $order->update([
            'status'     => DeliveryOrderStatus::Delivered,
            'handled_by' => $user->id,
            'handled_at' => now(),
        ]);
    }

    /**
     * Menerima dan mencatat pembayaran tunai (COD) dari kurir/pelanggan ke kasir yang sedang bertugas.
     */
    public function receiveCashPayment(DeliveryOrder $order, Shift $shift, User $user): void
    {
        DB::transaction(function () use ($order, $shift, $user) {
            $locked = DeliveryOrder::lockForUpdate()->findOrFail($order->id);

            if ($locked->payment_method !== 'cash') {
                throw new DomainException('Hanya pesanan dengan metode pembayaran tunai (COD) yang uangnya diterima oleh kasir.');
            }

            if ($locked->payment_status === 'paid') {
                throw new DomainException('Pesanan ini sudah lunas.');
            }

            if (! in_array($locked->status, [DeliveryOrderStatus::OnDelivery, DeliveryOrderStatus::Delivered], true)) {
                throw new DomainException('Uang tunai baru bisa diterima setelah pesanan dikirim atau diterima.');
            }

            $locked->update([
                'paid_amount'    => $locked->total_amount,
                'payment_status' => 'paid',
                'shift_id'       => $shift->id,
                'paid_at'        => now(),
                'handled_by'     => $user->id,
                'handled_at'     => now(),
            ]);
        });
    }
}
