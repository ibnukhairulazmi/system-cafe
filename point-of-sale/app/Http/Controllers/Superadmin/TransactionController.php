<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Superadmin\TransactionFilterRequest;
use App\Models\Transaction;
use App\Services\Superadmin\SuperadminAuditService;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function __construct(
        private readonly SuperadminAuditService $auditService
    ) {}

    public function index(TransactionFilterRequest $request): View
    {
        $data = $this->auditService->getTransactions($request->validated(), 20);

        return view('superadmin.transactions.index', $data);
    }

    public function show(Transaction $transaction): View
    {
        Gate::authorize('view', $transaction);

        $transaction->load(['cashier', 'verifier', 'shift', 'items']);

        return view('superadmin.transactions.show', ['transaction' => $transaction]);
    }
}
