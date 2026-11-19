<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use App\Models\Transaction;
use App\Services\Kasir\KasirTransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function __construct(
        private readonly KasirTransactionService $transactionService
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $shift = Shift::open()->where('user_id', $user->id)->latest('opened_at')->first();

        $data = $this->transactionService->getShiftTransactionsOverview($user, $shift);

        return view('kasir.transactions', $data);
    }

    public function receipt(Request $request, Transaction $transaction): View
    {
        Gate::authorize('view', $transaction);

        $data = $this->transactionService->getReceiptData(
            $transaction,
            $request->boolean('autoprint')
        );

        return view('kasir.receipt', $data);
    }
}
