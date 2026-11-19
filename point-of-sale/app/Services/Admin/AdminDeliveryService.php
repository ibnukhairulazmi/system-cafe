<?php

namespace App\Services\Admin;

use App\Models\DeliveryOrder;
use App\Services\ActivityLogger;
use App\Support\Format;

class AdminDeliveryService
{
    public const FILTER_STATUSES = ['pending', 'confirmed', 'on_delivery', 'delivered', 'cancelled', 'all'];

    /**
     * Mengambil daftar pesanan antar (delivery order) berpaginasi untuk admin beserta counter status.
     */
    public function getDeliveryOverview(array $filters, int $perPage = 15): array
    {
        $selected = $filters['status'] ?? 'pending';

        $orders = DeliveryOrder::query()
            ->with(['handler:id,name', 'items'])
            ->when($selected !== 'all', fn($q) => $q->where('status', $selected))
            ->when($filters['date'] ?? null, fn($q, $v) => $q->whereDate('delivery_date', $v))
            ->when($filters['q'] ?? null, fn($q, $v) => $q->where(
                fn($w) => $w->whereLike('customer_name', Format::like($v))
                    ->orWhereLike('customer_phone', Format::like($v))
                    ->orWhereLike('code', Format::like($v))
                    ->orWhereLike('delivery_address', Format::like($v))
            ))
            ->when(
                $selected === 'pending',
                fn($q) => $q->orderBy('delivery_date')->orderByDesc('created_at'),
                fn($q) => $q->orderByDesc('delivery_date')->orderByDesc('created_at'),
            )
            ->paginate($perPage)
            ->withQueryString();

        $counts = DeliveryOrder::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'orders'   => $orders,
            'filters'  => $filters,
            'selected' => $selected,
            'counts'   => $counts,
        ];
    }

    public function updateDelivery(DeliveryOrder $order, array $data, int $userId): void
    {
        $order->update($data + [
            'handled_by' => $userId,
            'handled_at' => now(),
        ]);

        ActivityLogger::log('delivery_order.' . $data['status'], $order, ['code' => $order->code]);
    }
}
