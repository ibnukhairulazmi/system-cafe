<?php

namespace App\Services\Admin;

use App\Enums\Role;
use App\Enums\VerificationStatus;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ActivityLogger;

class AdminVerificationService
{
    /**
     * Mengambil daftar transaksi yang perlu diverifikasi admin berpaginasi beserta filter & counter.
     */
    public function getVerificationOverview(array $filters, int $perPage = 20): array
    {
        $selected = $filters['verification_status'] ?? VerificationStatus::Pending->value;

        $query = $filters;
        if ($selected === 'all') {
            unset($query['verification_status']);
        } else {
            $query['verification_status'] = $selected;
        }

        $transactions = Transaction::query()
            ->with(['cashier', 'verifier'])
            ->filter($query)
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $counts = Transaction::query()
            ->selectRaw('verification_status, COUNT(*) as c')
            ->groupBy('verification_status')
            ->pluck('c', 'verification_status');

        $cashiers = User::where('role', Role::Kasir->value)
            ->orderBy('name')
            ->pluck('name', 'id');

        return [
            'transactions' => $transactions,
            'filters'      => $filters,
            'selected'     => $selected,
            'counts'       => $counts,
            'cashiers'     => $cashiers,
            'statuses'     => VerificationStatus::options(),
        ];
    }

    public function updateVerification(Transaction $transaction, VerificationStatus $status, ?string $note, int $verifierId): void
    {
        $transaction->update([
            'verification_status' => $status,
            'verified_by'         => $verifierId,
            'verified_at'         => now(),
            'note'                => $note,
        ]);

        ActivityLogger::log('transaction.' . $status->value, $transaction, [
            'invoice' => $transaction->invoice_no,
            'note'    => $note,
        ]);
    }
}
