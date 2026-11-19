<?php

namespace App\Services;

use App\Enums\DeliveryOrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PurchaseOrderStatus;
use App\Enums\ReservationStatus;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Models\Ingredient;
use App\Models\PurchaseOrder;
use App\Models\Reservation;
use App\Models\ReservationItem;
use App\Models\ReservationPayments;
use App\Models\Shift;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use App\Models\Product;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class ReportService
{
    /**
     * Query dasar delivery yang dihitung: semua pesanan kecuali yang dibatalkan.
     */
    private function activeDelivery()
    {
        return DeliveryOrder::query()
            ->whereNotIn('status', [DeliveryOrderStatus::Cancelled, 'cancelled']);
    }

    /**
     * Hitung penjualan untuk periode tertentu, menggabungkan Kasir POS, Reservasi Meja, dan Delivery Order.
     *
     * @return array{transactions:int, net_sales:float, discount:float, tax:float, delivery_fee:float, gross_total:float, channels:array}
     */
    public function sales(CarbonInterface $from, CarbonInterface $to, ?string $channel = 'all'): array
    {
        $channel ??= 'all';
        $to = $to->copy()->endOfDay();

        $posRow = Transaction::query()
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('COUNT(*) as transactions,
                COALESCE(SUM(subtotal - discount), 0) as net_sales,
                COALESCE(SUM(discount), 0) as discount,
                COALESCE(SUM(tax), 0) as tax,
                COALESCE(SUM(total), 0) as gross_total')
            ->first();

        $pos = [
            'transactions' => (int) ($posRow->transactions ?? 0),
            'net_sales'    => (float) ($posRow->net_sales ?? 0),
            'discount'     => (float) ($posRow->discount ?? 0),
            'tax'          => (float) ($posRow->tax ?? 0),
            'delivery_fee' => 0.0,
            'gross_total'  => (float) ($posRow->gross_total ?? 0),
            'label'        => 'Kasir POS (Langsung)',
        ];

        $resPaymentSum = (float) ReservationPayments::query()
            ->whereIn('type', ['dp', 'settlement'])
            ->whereBetween('paid_at', [$from, $to])
            ->sum('amount');

        $resPaymentTrxIds = ReservationPayments::query()
            ->whereIn('type', ['dp', 'settlement'])
            ->whereBetween('paid_at', [$from, $to])
            ->pluck('reservation_id')
            ->unique();

        $directRes = Reservation::query()
            ->whereIn('status', [ReservationStatus::Confirmed, 'confirmed'])
            ->where('paid_amount', '>', 0)
            ->whereNotIn('id', $resPaymentTrxIds)
            ->whereBetween('created_at', [$from, $to]);

        $directResCount = $directRes->count();
        $directResSum = (float) $directRes->sum('paid_amount');

        $resTrxCount = $resPaymentTrxIds->count() + $directResCount;
        $resGross = round($resPaymentSum + $directResSum, 2);

        $res = [
            'transactions' => $resTrxCount,
            'net_sales'    => $resGross,
            'discount'     => 0.0,
            'tax'          => 0.0,
            'delivery_fee' => 0.0,
            'gross_total'  => $resGross,
            'label'        => 'Reservasi Meja',
        ];

        $delivRow = $this->activeDelivery()
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('COUNT(*) as transactions,
                COALESCE(SUM(subtotal_amount), 0) as net_sales,
                COALESCE(SUM(delivery_fee), 0) as delivery_fee,
                COALESCE(SUM(CASE WHEN paid_amount > 0 THEN paid_amount ELSE total_amount END), 0) as gross_total')
            ->first();

        $deliv = [
            'transactions' => (int) ($delivRow->transactions ?? 0),
            'net_sales'    => (float) ($delivRow->net_sales ?? 0),
            'discount'     => 0.0,
            'tax'          => 0.0,
            'delivery_fee' => (float) ($delivRow->delivery_fee ?? 0),
            'gross_total'  => (float) ($delivRow->gross_total ?? 0),
            'label'        => 'Pesan Antar (Delivery)',
        ];

        $totalGross = max(0.0, $pos['gross_total'] + $res['gross_total'] + $deliv['gross_total']);
        $pos['percentage'] = $totalGross > 0 ? round(($pos['gross_total'] / $totalGross) * 100, 1) : 0.0;
        $res['percentage'] = $totalGross > 0 ? round(($res['gross_total'] / $totalGross) * 100, 1) : 0.0;
        $deliv['percentage'] = $totalGross > 0 ? round(($deliv['gross_total'] / $totalGross) * 100, 1) : 0.0;

        $channels = [
            'pos'         => $pos,
            'reservation' => $res,
            'delivery'    => $deliv,
        ];

        if ($channel === 'pos') {
            return $pos + ['channels' => $channels];
        }

        if ($channel === 'reservation') {
            return $res + ['channels' => $channels];
        }

        if ($channel === 'delivery') {
            return $deliv + ['channels' => $channels];
        }

        return [
            'transactions' => $pos['transactions'] + $res['transactions'] + $deliv['transactions'],
            'net_sales'    => round($pos['net_sales'] + $res['net_sales'] + $deliv['net_sales'], 2),
            'discount'     => $pos['discount'],
            'tax'          => $pos['tax'],
            'delivery_fee' => $deliv['delivery_fee'],
            'gross_total'  => round($pos['gross_total'] + $res['gross_total'] + $deliv['gross_total'], 2),
            'channels'     => $channels,
        ];
    }

    public function purchases(CarbonInterface $from, CarbonInterface $to): float
    {
        return (float) PurchaseOrder::query()
            ->where('status', PurchaseOrderStatus::Received->value)
            ->whereBetween('received_at', [$from->toDateString(), $to->toDateString()])
            ->sum('total');
    }

    public function summary(CarbonInterface $from, CarbonInterface $to, ?string $channel = 'all'): array
    {
        $sales = $this->sales($from, $to, $channel);
        $purchases = $this->purchases($from, $to);

        return $sales + [
            'purchases'    => $purchases,
            'gross_profit' => round($sales['net_sales'] - $purchases, 2),
        ];
    }

    public function topProducts(CarbonInterface $from, CarbonInterface $to, int $limit = 5, ?string $channel = 'all'): Collection
    {
        $channel ??= 'all';
        $to = $to->copy()->endOfDay();
        $items = collect();

        if ($channel === 'all' || $channel === 'pos') {
            $posItems = TransactionItem::query()
                ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
                ->whereNull('transactions.deleted_at')
                ->whereBetween('transactions.created_at', [$from, $to])
                ->groupBy('transaction_items.product_id')
                ->selectRaw('transaction_items.product_id,
                    MAX(transaction_items.product_name) as name,
                    SUM(transaction_items.quantity) as qty,
                    SUM(transaction_items.subtotal) as revenue')
                ->get()
                ->map(fn($r) => [
                    'product_id' => $r->product_id,
                    'name'       => $r->name,
                    'qty'        => (int) $r->qty,
                    'revenue'    => (float) $r->revenue,
                    'pos_qty'    => (int) $r->qty,
                    'res_qty'    => 0,
                    'deliv_qty'  => 0,
                ]);
            $items = $items->concat($posItems);
        }

        if ($channel === 'all' || $channel === 'reservation') {
            $resItems = ReservationItem::query()
                ->join('reservations', 'reservations.id', '=', 'reservation_items.reservation_id')
                ->whereIn('reservations.status', [ReservationStatus::Confirmed, 'confirmed'])
                ->whereBetween('reservations.created_at', [$from, $to])
                ->groupBy('reservation_items.product_id')
                ->selectRaw('reservation_items.product_id,
                    MAX(reservation_items.product_name) as name,
                    SUM(reservation_items.quantity) as qty,
                    SUM(reservation_items.subtotal) as revenue')
                ->get()
                ->map(fn($r) => [
                    'product_id' => $r->product_id,
                    'name'       => $r->name,
                    'qty'        => (int) $r->qty,
                    'revenue'    => (float) $r->revenue,
                    'pos_qty'    => 0,
                    'res_qty'    => (int) $r->qty,
                    'deliv_qty'  => 0,
                ]);
            $items = $items->concat($resItems);
        }

        if ($channel === 'all' || $channel === 'delivery') {
            $delivItems = DeliveryOrderItem::query()
                ->join('delivery_orders', 'delivery_orders.id', '=', 'delivery_order_items.delivery_order_id')
                ->whereNotIn('delivery_orders.status', [DeliveryOrderStatus::Cancelled, 'cancelled'])
                ->whereBetween('delivery_orders.created_at', [$from, $to])
                ->groupBy('delivery_order_items.product_id')
                ->selectRaw('delivery_order_items.product_id,
                    MAX(delivery_order_items.product_name) as name,
                    SUM(delivery_order_items.quantity) as qty,
                    SUM(delivery_order_items.subtotal) as revenue')
                ->get()
                ->map(fn($r) => [
                    'product_id' => $r->product_id,
                    'name'       => $r->name,
                    'qty'        => (int) $r->qty,
                    'revenue'    => (float) $r->revenue,
                    'pos_qty'    => 0,
                    'res_qty'    => 0,
                    'deliv_qty'  => (int) $r->qty,
                ]);
            $items = $items->concat($delivItems);
        }

        return $items->groupBy('product_id')->map(function ($group) {
            return (object) [
                'product_id' => $group->first()['product_id'],
                'name'       => $group->first()['name'],
                'qty'        => (int) $group->sum('qty'),
                'revenue'    => (float) $group->sum('revenue'),
                'pos_qty'    => (int) $group->sum('pos_qty'),
                'res_qty'    => (int) $group->sum('res_qty'),
                'deliv_qty'  => (int) $group->sum('deliv_qty'),
            ];
        })->sortByDesc('qty')->values()->take($limit);
    }

    
    public function dailySales(int $days = 7, ?string $channel = 'all'): Collection
    {
        $channel ??= 'all';
        $end = now()->endOfDay();
        $start = now()->subDays($days - 1)->startOfDay();

        $rows = [];

        if ($channel === 'all' || $channel === 'pos') {
            $posDays = Transaction::query()
                ->whereBetween('created_at', [$start, $end])
                ->selectRaw('DATE(created_at) as day, COALESCE(SUM(subtotal - discount), 0) as net')
                ->groupBy('day')
                ->pluck('net', 'day');
            foreach ($posDays as $day => $val) {
                $rows[$day] = ($rows[$day] ?? 0) + (float) $val;
            }
        }

        if ($channel === 'all' || $channel === 'reservation') {
            $resDays = ReservationPayments::query()
                ->whereIn('type', ['dp', 'settlement'])
                ->whereBetween('paid_at', [$start, $end])
                ->selectRaw('DATE(paid_at) as day, COALESCE(SUM(amount), 0) as net')
                ->groupBy('day')
                ->pluck('net', 'day');
            foreach ($resDays as $day => $val) {
                $rows[$day] = ($rows[$day] ?? 0) + (float) $val;
            }
        }

        if ($channel === 'all' || $channel === 'delivery') {
            $delivDays = $this->activeDelivery()
                ->whereBetween('created_at', [$start, $end])
                ->selectRaw('DATE(created_at) as day, COALESCE(SUM(subtotal_amount), 0) as net')
                ->groupBy('day')
                ->pluck('net', 'day');
            foreach ($delivDays as $day => $val) {
                $rows[$day] = ($rows[$day] ?? 0) + (float) $val;
            }
        }

        return collect(range(0, $days - 1))->map(function (int $i) use ($start, $rows) {
            $date = $start->copy()->addDays($i);

            return [
                'date' => $date,
                'net'  => (float) ($rows[$date->toDateString()] ?? 0),
            ];
        });
    }

    
    public function dailyChartSeries(int $days, string $dateFormat, ?string $channel = 'all'): array
    {
        $channel ??= 'all';
        $end = now()->endOfDay();
        $start = now()->subDays($days - 1)->startOfDay();

        $salesByDay = [];

        if ($channel === 'all' || $channel === 'pos') {
            $posDays = Transaction::query()
                ->whereBetween('created_at', [$start, $end])
                ->selectRaw('DATE(created_at) as day, COALESCE(SUM(subtotal - discount), 0) as net')
                ->groupBy('day')
                ->pluck('net', 'day');
            foreach ($posDays as $day => $val) {
                $salesByDay[$day] = ($salesByDay[$day] ?? 0) + (float) $val;
            }
        }

        if ($channel === 'all' || $channel === 'reservation') {
            $resDays = ReservationPayments::query()
                ->whereIn('type', ['dp', 'settlement'])
                ->whereBetween('paid_at', [$start, $end])
                ->selectRaw('DATE(paid_at) as day, COALESCE(SUM(amount), 0) as net')
                ->groupBy('day')
                ->pluck('net', 'day');
            foreach ($resDays as $day => $val) {
                $salesByDay[$day] = ($salesByDay[$day] ?? 0) + (float) $val;
            }
        }

        if ($channel === 'all' || $channel === 'delivery') {
            $delivDays = $this->activeDelivery()
                ->whereBetween('created_at', [$start, $end])
                ->selectRaw('DATE(created_at) as day, COALESCE(SUM(subtotal_amount), 0) as net')
                ->groupBy('day')
                ->pluck('net', 'day');
            foreach ($delivDays as $day => $val) {
                $salesByDay[$day] = ($salesByDay[$day] ?? 0) + (float) $val;
            }
        }

        $purchasesByDay = PurchaseOrder::query()
            ->where('status', PurchaseOrderStatus::Received->value)
            ->whereBetween('received_at', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('DATE(received_at) as day, COALESCE(SUM(total), 0) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $labels = [];
        $netSales = [];
        $grossProfit = [];

        foreach (range(0, $days - 1) as $i) {
            $date = $start->copy()->addDays($i);
            $dayKey = $date->toDateString();

            $net = (float) ($salesByDay[$dayKey] ?? 0);
            $purchase = (float) ($purchasesByDay[$dayKey] ?? 0);

            $labels[] = $date->translatedFormat($dateFormat);
            $netSales[] = $net;
            $grossProfit[] = $net - $purchase;
        }

        return [
            'labels'       => $labels,
            'net_sales'    => $netSales,
            'gross_profit' => $grossProfit,
        ];
    }

    public function productSales(CarbonInterface $from, CarbonInterface $to, int $edge = 10): Collection
    {
        $sold = $this->topProducts($from, $to, PHP_INT_MAX)->keyBy('product_id');

        $all = Product::query()
            ->where('is_active', true)
            ->get(['id', 'name'])
            ->map(fn($p) => (object) [
                'product_id' => $p->id,
                'name'       => $p->name,
                'qty'        => (int) ($sold[$p->id]->qty ?? 0),
                'revenue'    => (float) ($sold[$p->id]->revenue ?? 0),
                'pos_qty'    => (int) ($sold[$p->id]->pos_qty ?? 0),
                'res_qty'    => (int) ($sold[$p->id]->res_qty ?? 0),
                'deliv_qty'  => (int) ($sold[$p->id]->deliv_qty ?? 0),
                'group'      => 'all',
            ])
            ->sortBy([['qty', 'asc'], ['name', 'asc']])
            ->values();

        if ($all->count() <= $edge * 2) {
            return $all;
        }

        $low = $all->take($edge)->map(function ($r) {
            $r->group = 'low';
            return $r;
        });

        $top = $all->take(-$edge)->map(function ($r) {
            $r->group = 'top';
            return $r;
        });

        return $low->concat($top)->values();
    }

    public function dashboard(): array
    {
        $now = now();

        $periods = [
            'today' => [
                'label'     => 'Hari ini',
                'from'      => $now->copy()->startOfDay(),
                'to'        => $now->copy()->endOfDay(),
                'prev_from' => $now->copy()->subDay()->startOfDay(),
                'prev_to'   => $now->copy()->subDay()->endOfDay(),
            ],
            'week' => [
                'label'     => '7 Hari Terakhir',
                'from'      => $now->copy()->subDays(6)->startOfDay(),
                'to'        => $now->copy()->endOfDay(),
                'prev_from' => $now->copy()->subDays(13)->startOfDay(),
                'prev_to'   => $now->copy()->subDays(7)->endOfDay(),
            ],
            'month' => [
                'label'     => 'Bulan ini',
                'from'      => $now->copy()->startOfMonth(),
                'to'        => $now->copy()->endOfMonth(),
                'prev_from' => $now->copy()->subMonthNoOverflow()->startOfMonth(),
                'prev_to'   => $now->copy()->subMonthNoOverflow()->endOfMonth(),
            ],
        ];

        $summaries = [];
        foreach ($periods as $key => $p) {
            $current = $this->summary($p['from'], $p['to']);
            $prevSales = $this->sales($p['prev_from'], $p['prev_to'])['net_sales'];

            $growth = $prevSales > 0
                ? (($current['net_sales'] - $prevSales) / $prevSales) * 100
                : ($current['net_sales'] > 0 ? 100.0 : 0.0);

            $summaries[$key] = ['label' => $p['label'], 'growth' => round($growth, 1)] + $current;
        }

        return [
            'periods'              => $summaries,
            'chart_data'           => [
                '7d'  => $this->dailyChartSeries(7, 'D d'),
                '30d' => $this->dailyChartSeries(30, 'd M'),
                '3m'  => $this->dailyChartSeries(90, 'd M'),
            ],
            'product_sales'        => $this->productSales($periods['month']['from'], $periods['month']['to']),
            'product_total'        => Product::where('is_active', true)->count(),
            'daily'                => $this->dailySales(7),
            'flagged'              => Transaction::where('verification_status', 'flagged')->count(),
            'pending_verification' => Transaction::where('verification_status', 'pending')->count(),
            'open_shifts'          => Shift::open()->count(),
            'low_stock'            => Ingredient::lowStock()->count(),
        ];
    }

    /**
     * Laporan penjualan komprehensif mencakup Kasir POS, Reservasi Meja, dan Delivery Order.
     *
     * @param bool $owner true: sertakan data per kasir, pengeluaran bahan, dan laba kotor
     */
    public function salesReport(CarbonInterface $from, CarbonInterface $to, bool $owner = false, ?string $channel = 'all'): array
    {
        $channel ??= 'all';
        $to = $to->copy()->endOfDay();
        $sales = $this->sales($from, $to, $channel);

        $summary = $sales + [
            'average'      => $sales['transactions'] > 0 ? round($sales['gross_total'] / $sales['transactions'], 2) : 0.0,
            'purchases'    => null,
            'gross_profit' => null,
        ];

        if ($owner) {
            $purchases = $this->purchases($from, $to);
            $summary['purchases'] = $purchases;
            $summary['gross_profit'] = round($sales['net_sales'] - $purchases, 2);
        }

        $totalGross = (float) ($sales['gross_total'] ?? 0);
        $totalTrx = max(1, (int) ($sales['transactions'] ?? 0));
        $channelsData = [];

        foreach ($sales['channels'] ?? [] as $cKey => $cVal) {
            $pct = $totalGross > 0 ? round(($cVal['gross_total'] / $totalGross) * 100, 1) : 0.0;
            $channelsData[$cKey] = $cVal + [
                'percentage'      => $pct,
                'revenue_percent' => $pct,
                'trx_percent'     => round((($cVal['transactions'] ?? 0) / $totalTrx) * 100, 1),
            ];
        }

        $dailyMap = [];

        if ($channel === 'all' || $channel === 'pos') {
            $posDaily = Transaction::query()
                ->whereBetween('created_at', [$from, $to])
                ->selectRaw('DATE(created_at) as day,
                    COUNT(*) as transactions,
                    SUM(subtotal - discount) as net_sales,
                    SUM(discount) as discount,
                    SUM(tax) as tax,
                    SUM(total) as gross_total')
                ->groupBy('day')
                ->get();

            foreach ($posDaily as $r) {
                $d = (string) $r->day;
                $dailyMap[$d] ??= $this->emptyDailyRow($d);
                $dailyMap[$d]['pos_transactions'] += (int) $r->transactions;
                $dailyMap[$d]['pos_sales']        += (float) $r->gross_total;
                $dailyMap[$d]['transactions']     += (int) $r->transactions;
                $dailyMap[$d]['net_sales']        += (float) $r->net_sales;
                $dailyMap[$d]['discount']         += (float) $r->discount;
                $dailyMap[$d]['tax']              += (float) $r->tax;
                $dailyMap[$d]['gross_total']      += (float) $r->gross_total;
            }
        }

        if ($channel === 'all' || $channel === 'reservation') {
            $resPaymentsDaily = ReservationPayments::query()
                ->whereIn('type', ['dp', 'settlement'])
                ->whereBetween('paid_at', [$from, $to])
                ->selectRaw('DATE(paid_at) as day,
                    COUNT(DISTINCT reservation_id) as transactions,
                    SUM(amount) as amount')
                ->groupBy('day')
                ->get();

            foreach ($resPaymentsDaily as $r) {
                $d = (string) $r->day;
                $dailyMap[$d] ??= $this->emptyDailyRow($d);
                $dailyMap[$d]['res_transactions'] += (int) $r->transactions;
                $dailyMap[$d]['res_sales']        += (float) $r->amount;
                $dailyMap[$d]['transactions']     += (int) $r->transactions;
                $dailyMap[$d]['net_sales']        += (float) $r->amount;
                $dailyMap[$d]['gross_total']      += (float) $r->amount;
            }
        }

        if ($channel === 'all' || $channel === 'delivery') {
            $delivDaily = $this->activeDelivery()
                ->whereBetween('created_at', [$from, $to])
                ->selectRaw('DATE(created_at) as day,
                    COUNT(*) as transactions,
                    SUM(subtotal_amount) as net_sales,
                    SUM(delivery_fee) as delivery_fee,
                    SUM(CASE WHEN paid_amount > 0 THEN paid_amount ELSE total_amount END) as gross_total')
                ->groupBy('day')
                ->get();

            foreach ($delivDaily as $r) {
                $d = (string) $r->day;
                $dailyMap[$d] ??= $this->emptyDailyRow($d);
                $dailyMap[$d]['deliv_transactions'] += (int) $r->transactions;
                $dailyMap[$d]['deliv_sales']        += (float) $r->gross_total;
                $dailyMap[$d]['transactions']       += (int) $r->transactions;
                $dailyMap[$d]['net_sales']          += (float) $r->net_sales;
                $dailyMap[$d]['delivery_fee']       += (float) $r->delivery_fee;
                $dailyMap[$d]['gross_total']        += (float) $r->gross_total;
            }
        }

        ksort($dailyMap);
        $daily = array_values($dailyMap);

        $methodsData = [
            'cash' => [
                'label'        => 'Tunai (Kasir / COD)',
                'transactions' => 0,
                'total'        => 0.0,
                'pos'          => 0.0,
                'reservation'  => 0.0,
                'delivery'     => 0.0,
            ],
            'qris' => [
                'label'        => 'QRIS Instan',
                'transactions' => 0,
                'total'        => 0.0,
                'pos'          => 0.0,
                'reservation'  => 0.0,
                'delivery'     => 0.0,
            ],
            'transfer' => [
                'label'        => 'Transfer Bank',
                'transactions' => 0,
                'total'        => 0.0,
                'pos'          => 0.0,
                'reservation'  => 0.0,
                'delivery'     => 0.0,
            ],
        ];

        if ($channel === 'all' || $channel === 'pos') {
            $posMethods = Transaction::query()
                ->whereBetween('created_at', [$from, $to])
                ->selectRaw('payment_method, COUNT(*) as transactions, SUM(total) as total')
                ->groupBy('payment_method')
                ->get();

            foreach ($posMethods as $pm) {
                $key = $pm->payment_method instanceof PaymentMethod ? $pm->payment_method->value : (string) $pm->payment_method;
                if (isset($methodsData[$key])) {
                    $methodsData[$key]['transactions'] += (int) $pm->transactions;
                    $methodsData[$key]['total']        += (float) $pm->total;
                    $methodsData[$key]['pos']          += (float) $pm->total;
                }
            }
        }

        if ($channel === 'all' || $channel === 'reservation') {
            $resMethods = ReservationPayments::query()
                ->whereIn('type', ['dp', 'settlement'])
                ->whereBetween('paid_at', [$from, $to])
                ->selectRaw('method, COUNT(*) as transactions, SUM(amount) as total')
                ->groupBy('method')
                ->get();

            foreach ($resMethods as $rm) {
                $key = (string) $rm->method;
                if (!isset($methodsData[$key])) {
                    $key = str_contains($key, 'transfer') ? 'transfer' : ($key === 'qris' ? 'qris' : 'cash');
                }
                $methodsData[$key]['transactions'] += (int) $rm->transactions;
                $methodsData[$key]['total']        += (float) $rm->total;
                $methodsData[$key]['reservation']  += (float) $rm->total;
            }
        }

        if ($channel === 'all' || $channel === 'delivery') {
            $delivMethods = $this->activeDelivery()
                ->whereBetween('created_at', [$from, $to])
                ->selectRaw('payment_method, COUNT(*) as transactions, SUM(CASE WHEN paid_amount > 0 THEN paid_amount ELSE total_amount END) as total')
                ->groupBy('payment_method')
                ->get();

            foreach ($delivMethods as $dm) {
                $key = (string) $dm->payment_method;
                $key = in_array($key, ['qris', 'transfer', 'cash'], true) ? $key : 'cash';
                $methodsData[$key]['transactions'] += (int) $dm->transactions;
                $methodsData[$key]['total']        += (float) $dm->total;
                $methodsData[$key]['delivery']     += (float) $dm->total;
            }
        }

        $byMethod = [];
        foreach ($methodsData as $k => $m) {
            $byMethod[] = [
                'key'          => $k,
                'label'        => $m['label'],
                'transactions' => $m['transactions'],
                'total'        => round($m['total'], 2),
                'percentage'   => $totalGross > 0 ? round(($m['total'] / $totalGross) * 100, 1) : 0.0,
                'pos'          => round($m['pos'], 2),
                'pos_total'    => round($m['pos'], 2),
                'reservation'  => round($m['reservation'], 2),
                'res_total'    => round($m['reservation'], 2),
                'delivery'     => round($m['delivery'], 2),
                'deliv_total'  => round($m['delivery'], 2),
            ];
        }

        $byCashier = [];
        if ($owner) {
            $staffMap = [];

            $addStaff = function (int $id, int $trx, float $net, float $gross) use (&$staffMap) {
                $staffMap[$id] ??= ['id' => $id, 'transactions' => 0, 'net_sales' => 0.0, 'gross_total' => 0.0];
                $staffMap[$id]['transactions'] += $trx;
                $staffMap[$id]['net_sales']    += $net;
                $staffMap[$id]['gross_total']  += $gross;
            };

            if ($channel === 'all' || $channel === 'pos') {
                $posCashiers = Transaction::query()
                    ->whereBetween('created_at', [$from, $to])
                    ->selectRaw('cashier_id, COUNT(*) as transactions, SUM(subtotal - discount) as net_sales, SUM(total) as gross_total')
                    ->groupBy('cashier_id')
                    ->get();

                foreach ($posCashiers as $c) {
                    $addStaff((int) $c->cashier_id, (int) $c->transactions, (float) $c->net_sales, (float) $c->gross_total);
                }
            }

            if ($channel === 'all' || $channel === 'reservation') {
                $resPayments = ReservationPayments::query()
                    ->whereIn('type', ['dp', 'settlement'])
                    ->whereBetween('paid_at', [$from, $to])
                    ->get(['reservation_id', 'amount']);

                $paidResIds = $resPayments->pluck('reservation_id')->unique();

                $resHandlers = Reservation::query()
                    ->whereIn('id', $paidResIds)
                    ->pluck('handled_by', 'id');

                foreach ($resPayments->groupBy(fn($p) => (int) ($resHandlers[$p->reservation_id] ?? 0)) as $id => $group) {
                    $amount = (float) $group->sum('amount');
                    $addStaff((int) $id, $group->pluck('reservation_id')->unique()->count(), $amount, $amount);
                }

                $directResStaff = Reservation::query()
                    ->whereIn('status', [ReservationStatus::Confirmed, 'confirmed'])
                    ->where('paid_amount', '>', 0)
                    ->whereNotIn('id', $paidResIds)
                    ->whereBetween('created_at', [$from, $to])
                    ->selectRaw('handled_by, COUNT(*) as transactions, SUM(paid_amount) as gross_total')
                    ->groupBy('handled_by')
                    ->get();

                foreach ($directResStaff as $c) {
                    $addStaff((int) $c->handled_by, (int) $c->transactions, (float) $c->gross_total, (float) $c->gross_total);
                }
            }

            if ($channel === 'all' || $channel === 'delivery') {
                $delivCashiers = $this->activeDelivery()
                    ->whereBetween('created_at', [$from, $to])
                    ->selectRaw('handled_by, COUNT(*) as transactions, SUM(subtotal_amount) as net_sales, SUM(CASE WHEN paid_amount > 0 THEN paid_amount ELSE total_amount END) as gross_total')
                    ->groupBy('handled_by')
                    ->get();

                foreach ($delivCashiers as $c) {
                    $addStaff((int) $c->handled_by, (int) $c->transactions, (float) $c->net_sales, (float) $c->gross_total);
                }
            }

            $names = User::withTrashed()
                ->whereIn('id', array_filter(array_keys($staffMap)))
                ->pluck('name', 'id');

            $byCashier = collect($staffMap)
                ->map(fn($s) => [
                    'name'         => $s['id'] > 0 ? ($names[$s['id']] ?? 'Kasir') : 'Kasir (tidak tercatat)',
                    'role'         => 'Kasir',
                    'transactions' => $s['transactions'],
                    'net_sales'    => $s['net_sales'],
                    'gross_total'  => $s['gross_total'],
                ])
                ->sortByDesc('net_sales')
                ->values()
                ->all();
        }

        $top = $this->topProducts($from, $to, 10, $channel)->map(fn($r) => [
            'name'       => $r->name,
            'qty'        => (int) $r->qty,
            'revenue'    => (float) $r->revenue,
            'pos_qty'    => (int) ($r->pos_qty ?? 0),
            'res_qty'    => (int) ($r->res_qty ?? 0),
            'deliv_qty'  => (int) ($r->deliv_qty ?? 0),
        ])->all();

        return [
            'period'              => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'channel'             => $channel,
            'summary'             => $summary,
            'channels'            => $channelsData,
            'daily'               => $daily,
            'by_method'           => $byMethod,
            'by_cashier'          => $byCashier,
            'top_products'        => $top,
        ];
    }

    private function emptyDailyRow(string $day): array
    {
        return [
            'date'               => $day,
            'pos_transactions'   => 0,
            'pos_sales'          => 0.0,
            'res_transactions'   => 0,
            'res_sales'          => 0.0,
            'deliv_transactions' => 0,
            'deliv_sales'        => 0.0,
            'transactions'       => 0,
            'net_sales'          => 0.0,
            'discount'           => 0.0,
            'tax'                => 0.0,
            'delivery_fee'       => 0.0,
            'gross_total'        => 0.0,
        ];
    }

    /**
     * Tanggal acuan: status "received" memakai tanggal diterima; status lain / "all" memakai tanggal dibuat.
     * Total tidak menghitung PO yang dibatalkan.
     */
    public function purchaseReport(CarbonInterface $from, CarbonInterface $to, ?string $status = null, ?int $supplierId = null): array
    {
        $status ??= PurchaseOrderStatus::Received->value;
        $byReceived = $status === PurchaseOrderStatus::Received->value;
        $toEnd = $to->copy()->endOfDay();

        $orders = PurchaseOrder::query()
            ->with(['supplier', 'items.ingredient'])
            ->when($status !== 'all', fn($q) => $q->where('status', $status))
            ->when($supplierId, fn($q, $v) => $q->where('supplier_id', $v))
            ->when(
                $byReceived,
                fn($q) => $q->whereBetween('received_at', [$from->toDateString(), $to->toDateString()]),
                fn($q) => $q->whereBetween('created_at', [$from, $toEnd]),
            )
            ->orderBy($byReceived ? 'received_at' : 'created_at')
            ->orderBy('id')
            ->get();

        $active = $orders->reject(fn($o) => $o->status === PurchaseOrderStatus::Cancelled);

        $rows = $orders->map(fn($o) => [
            'id'          => $o->id,
            'po_no'       => $o->po_no,
            'supplier'    => $o->supplier->name,
            'status'      => $o->status->label(),
            'created_at'  => $o->created_at->format('Y-m-d'),
            'ordered_at'  => $o->ordered_at?->format('Y-m-d'),
            'received_at' => $o->received_at?->format('Y-m-d'),
            'items'       => $o->items->count(),
            'total'       => (float) $o->total,
        ])->all();

        $bySupplier = $active->groupBy('supplier_id')->map(fn($g) => [
            'name'   => $g->first()->supplier->name,
            'orders' => $g->count(),
            'total'  => (float) $g->sum('total'),
        ])->sortByDesc('total')->values()->all();

        $byIngredient = $active->flatMap(fn($o) => $o->items)->groupBy('ingredient_id')->map(fn($g) => [
            'name'     => $g->first()->ingredient->name,
            'unit'     => $g->first()->ingredient->unit,
            'quantity' => (float) $g->sum('quantity'),
            'total'    => (float) $g->sum('subtotal'),
        ])->sortByDesc('total')->values()->all();

        return [
            'period'        => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'filters'       => [
                'status'   => $status,
                'supplier' => $supplierId ? Supplier::whereKey($supplierId)->value('name') : null,
                'basis'    => $byReceived ? 'tanggal diterima' : 'tanggal dibuat',
            ],
            'summary'       => ['count' => $active->count(), 'total' => round((float) $active->sum('total'), 2)],
            'orders'        => $rows,
            'by_supplier'   => $bySupplier,
            'by_ingredient' => $byIngredient,
        ];
    }

    
    public function stockReport(CarbonInterface $from, CarbonInterface $to): array
    {
        $toEnd = $to->copy()->endOfDay();

        $moves = StockMovement::query()
            ->whereBetween('created_at', [$from, $toEnd])
            ->selectRaw('ingredient_id, type, SUM(quantity) as qty')
            ->groupBy('ingredient_id', 'type')
            ->get()
            ->groupBy('ingredient_id');

        $rows = [];
        $value = 0.0;
        $low = 0;

        foreach (Ingredient::orderBy('name')->get() as $ingredient) {
            $m = $moves->get($ingredient->id, collect())
                ->mapWithKeys(fn($r) => [$r->type->value => (float) $r->qty]);

            $isLow = (float) $ingredient->stock <= (float) $ingredient->min_stock;
            $rowValue = round((float) $ingredient->stock * (float) $ingredient->last_cost, 2);

            $value += $rowValue;
            $low += $isLow ? 1 : 0;

            $rows[] = [
                'name'        => $ingredient->name,
                'unit'        => $ingredient->unit,
                'stock'       => (float) $ingredient->stock,
                'min_stock'   => (float) $ingredient->min_stock,
                'low'         => $isLow,
                'last_cost'   => (float) $ingredient->last_cost,
                'value'       => $rowValue,
                'in'          => abs($m['in'] ?? 0),
                'out'         => abs($m['out'] ?? 0),
                'sold'        => abs($m['sale'] ?? 0),
                'void_return' => abs($m['void_return'] ?? 0),
                'opname'      => $m['opname'] ?? 0.0,
            ];
        }

        return [
            'period'  => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'summary' => ['ingredients' => count($rows), 'low' => $low, 'stock_value' => round($value, 2)],
            'rows'    => $rows,
        ];
    }
}
