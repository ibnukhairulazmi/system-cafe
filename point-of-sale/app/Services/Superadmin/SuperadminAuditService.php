<?php

namespace App\Services\Superadmin;

use App\Enums\DeliveryOrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\ReservationStatus;
use App\Enums\Role;
use App\Enums\ShiftStatus;
use App\Enums\StockMovementType;
use App\Enums\VerificationStatus;
use App\Models\ActivityLog;
use App\Models\DeliveryOrder;
use App\Models\Ingredient;
use App\Models\Reservation;
use App\Models\Shift;
use App\Models\StockMovement;
use App\Models\Transaction;
use App\Models\User;

class SuperadminAuditService
{
    /**
     * Mengambil daftar log aktivitas sistem berpaginasi beserta opsi filter.
     */
    public function getActivityLogs(array $filters, int $perPage = 30): array
    {
        $logs = ActivityLog::query()
            ->with('user')
            ->when($filters['user_id'] ?? null, fn($q, $v) => $q->where('user_id', $v))
            ->when($filters['action'] ?? null, fn($q, $v) => $q->where('action', $v))
            ->when($filters['date_from'] ?? null, fn($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filters['date_to'] ?? null, fn($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        return [
            'logs'    => $logs,
            'filters' => $filters,
            'users'   => User::whereIn('role', [Role::Admin->value, Role::Kasir->value])->orderBy('name')->get(['id', 'name']),
            'actions' => ActivityLog::query()->distinct()->orderBy('action')->pluck('action'),
        ];
    }

    /**
     * Mengambil daftar transaksi dan ringkasan nilai transaksi untuk pemantauan superadmin.
     */
    public function getTransactions(array $filters, int $perPage = 20): array
    {
        $transactions = Transaction::query()
            ->with(['cashier', 'verifier'])
            ->filter($filters)
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $summary = Transaction::query()
            ->filter($filters)
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(total), 0) as total')
            ->first();

        return [
            'transactions'  => $transactions,
            'summary'       => $summary,
            'filters'       => $filters,
            'cashiers'      => User::where('role', Role::Kasir->value)->orderBy('name')->get(['id', 'name']),
            'methods'       => PaymentMethod::options(),
            'verifications' => VerificationStatus::options(),
        ];
    }

    /**
     * Mengambil riwayat shift kasir dan total selisih kas untuk audit.
     */
    public function getShifts(array $filters, int $perPage = 15): array
    {
        $status = isset($filters['status']) ? ShiftStatus::tryFrom((string) $filters['status']) : null;

        $shifts = Shift::query()
            ->with('user')
            ->withCount('transactions')
            ->when(! empty($filters['user_id']), fn($q) => $q->where('user_id', $filters['user_id']))
            ->when($status, fn($q) => $q->where('status', $status->value))
            ->when(! empty($filters['from']), fn($q) => $q->whereDate('opened_at', '>=', $filters['from']))
            ->when(! empty($filters['to']), fn($q) => $q->whereDate('opened_at', '<=', $filters['to']))
            ->when(! empty($filters['only_difference']), fn($q) => $q
                ->whereNotNull('closed_at')
                ->where('difference', '!=', 0))
            ->latest('opened_at')
            ->paginate($perPage)
            ->withQueryString();

        $cashiers = User::withTrashed()
            ->whereIn('id', Shift::query()->select('user_id'))
            ->orderBy('name')
            ->get(['id', 'name']);

        $totalDifference = (float) Shift::query()
            ->whereNotNull('closed_at')
            ->when(! empty($filters['user_id']), fn($q) => $q->where('user_id', $filters['user_id']))
            ->when(! empty($filters['from']), fn($q) => $q->whereDate('opened_at', '>=', $filters['from']))
            ->when(! empty($filters['to']), fn($q) => $q->whereDate('opened_at', '<=', $filters['to']))
            ->sum('difference');

        return [
            'shifts'          => $shifts,
            'cashiers'        => $cashiers,
            'statuses'        => ShiftStatus::cases(),
            'totalDifference' => $totalDifference,
        ];
    }

    /**
     * Audit perbandingan stok fisik menurut master vs stok akhir mutasi ledger.
     */
    public function getStockAudit(array $filters, int $ingredientsPerPage = 15, int $movementsPerPage = 20): array
    {
        $ledger = StockMovement::query()
            ->select('stock_after')
            ->whereColumn('ingredient_id', 'ingredients.id')
            ->orderByDesc('id')
            ->limit(1);

        $ledgerSql      = $ledger->toSql();
        $ledgerBindings = $ledger->getBindings();

        $ingredients = Ingredient::query()
            ->addSelect(['ledger_stock' => $ledger])
            ->when(! empty($filters['search']), fn($q) => $q->whereLike('name', "%{$filters['search']}%"))
            ->when(! empty($filters['low_only']), fn($q) => $q->whereColumn('stock', '<=', 'min_stock'))
            ->when(! empty($filters['mismatch_only']), fn($q) => $q->whereRaw(
                "({$ledgerSql}) IS NOT NULL AND ({$ledgerSql}) <> ingredients.stock",
                array_merge($ledgerBindings, $ledgerBindings)
            ))
            ->orderBy('name')
            ->paginate($ingredientsPerPage, ['ingredients.*'], 'ingredients_page')
            ->withQueryString();

        $type = isset($filters['type']) ? StockMovementType::tryFrom((string) $filters['type']) : null;

        $movements = StockMovement::query()
            ->with(['ingredient', 'user', 'reference'])
            ->when(! empty($filters['ingredient_id']), fn($q) => $q->where('ingredient_id', $filters['ingredient_id']))
            ->when($type, fn($q) => $q->where('type', $type->value))
            ->when(! empty($filters['user_id']), fn($q) => $q->where('user_id', $filters['user_id']))
            ->when(! empty($filters['from']), fn($q) => $q->whereDate('created_at', '>=', $filters['from']))
            ->when(! empty($filters['to']), fn($q) => $q->whereDate('created_at', '<=', $filters['to']))
            ->latest('id')
            ->paginate($movementsPerPage, ['*'], 'movements_page')
            ->withQueryString();

        return [
            'ingredients' => $ingredients,
            'movements'   => $movements,
            'types'       => StockMovementType::cases(),
        ];
    }
    public function getReservations(array $filters, int $perPage = 15): array
    {
        $status = isset($filters['status']) ? ReservationStatus::tryFrom((string) $filters['status']) : null;

        $reservations = Reservation::query()
            ->with(['handler', 'table'])
            ->when($status, fn($q) => $q->where('status', $status->value))
            ->when(! empty($filters['from']), fn($q) => $q->whereDate('reserved_date', '>=', $filters['from']))
            ->when(! empty($filters['to']), fn($q) => $q->whereDate('reserved_date', '<=', $filters['to']))
            ->when(! empty($filters['search']), function ($q) use ($filters) {
                $q->where(function ($sub) use ($filters) {
                    $search = $filters['search'];
                    $sub->whereLike('code', "%{$search}%")
                        ->orWhereLike('name', "%{$search}%")
                        ->orWhereLike('phone', "%{$search}%");
                });
            })
            ->when(! empty($filters['anomaly']), function ($q) {
                $q->where(function ($sub) {
                    $sub->whereColumn('paid_amount', '>', 'total_amount')
                        ->orWhere(function ($nested) {
                            $nested->where('payment_status', 'paid')
                                ->whereColumn('paid_amount', '<', 'total_amount');
                        });
                });
            })
            ->latest('reserved_date')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        $summary = Reservation::query()
            ->selectRaw('status, COUNT(*) as total, COALESCE(SUM(total_amount), 0) as amount')
            ->groupBy('status')
            ->get()
            ->keyBy(fn($row) => $row->getRawOriginal('status'));

        return [
            'reservations' => $reservations,
            'summary'      => $summary,
            'statuses'     => ReservationStatus::cases(),
        ];
    }

    public function getDeliveryOrders(array $filters, int $perPage = 15): array
    {
        $status = isset($filters['status']) ? DeliveryOrderStatus::tryFrom((string) $filters['status']) : null;

        $orders = DeliveryOrder::query()
            ->with('handler')
            ->when($status, fn($q) => $q->where('status', $status->value))
            ->when(! empty($filters['from']), fn($q) => $q->whereDate('delivery_date', '>=', $filters['from']))
            ->when(! empty($filters['to']), fn($q) => $q->whereDate('delivery_date', '<=', $filters['to']))
            ->when(! empty($filters['search']), function ($q) use ($filters) {
                $q->where(function ($sub) use ($filters) {
                    $search = $filters['search'];
                    $sub->whereLike('code', "%{$search}%")
                        ->orWhereLike('customer_name', "%{$search}%")
                        ->orWhereLike('customer_phone', "%{$search}%");
                });
            })
            ->when(! empty($filters['anomaly']), function ($q) {
                $q->where(function ($sub) {
                    $sub->where(function ($d1) {
                        $d1->where('status', DeliveryOrderStatus::Delivered->value)
                            ->where('payment_status', '!=', 'paid');
                    })->orWhere(function ($d2) {
                        $d2->where('status', DeliveryOrderStatus::Cancelled->value)
                            ->where('paid_amount', '>', 0);
                    })->orWhere(function ($d3) {
                        $d3->where('payment_status', 'paid')
                            ->whereColumn('paid_amount', '<', 'total_amount');
                    });
                });
            })
            ->latest('delivery_date')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        $summary = DeliveryOrder::query()
            ->selectRaw('status, COUNT(*) as total, COALESCE(SUM(total_amount), 0) as amount')
            ->groupBy('status')
            ->get()
            ->keyBy(fn($row) => $row->getRawOriginal('status'));

        return [
            'orders'   => $orders,
            'summary'  => $summary,
            'statuses' => DeliveryOrderStatus::cases(),
        ];
    }
}
