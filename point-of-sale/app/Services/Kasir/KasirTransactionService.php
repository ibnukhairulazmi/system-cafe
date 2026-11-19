<?php

namespace App\Services\Kasir;

use App\Models\Setting;
use App\Models\Shift;
use App\Models\Transaction;
use App\Models\User;

class KasirTransactionService
{
    /**
     * Mengambil daftar transaksi dan ringkasan penjualan kasir pada shift yang aktif.
     */
    public function getShiftTransactionsOverview(User $user, ?Shift $shift, int $perPage = 25): array
    {
        $query = Transaction::query()
            ->where('cashier_id', $user->id)
            ->where('shift_id', $shift?->id ?? 0);

        $transactions = (clone $query)
            ->latest()
            ->paginate($perPage);

        $completedQuery = (clone $query);

        return [
            'shift'          => $shift,
            'transactions'   => $transactions,
            'completedCount' => $completedQuery->count(),
            'completedTotal' => (float) $completedQuery->sum('total'),
        ];
    }

    /**
     * Menyiapkan data struk transaksi kasir lengkap beserta metadata cafe.
     */
    public function getReceiptData(Transaction $transaction, bool $autoprint = false): array
    {
        $transaction->load(['items', 'cashier']);

        return [
            'transaction' => $transaction,
            'cafeName'    => Setting::get('cafe_name', 'Cafe'),
            'cafeAddress' => Setting::get('cafe_address', ''),
            'autoprint'   => $autoprint,
        ];
    }
}
