<?php

namespace App\Http\Controllers\Admin;

use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VerifyTransactionRequest;
use App\Models\Transaction;
use App\Services\Admin\AdminVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TransactionVerificationController extends Controller
{
    public function __construct(
        private readonly AdminVerificationService $verificationService
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Transaction::class);

        $filters = $request->validate([
            'verification_status' => ['nullable', Rule::in([...VerificationStatus::values(), 'all'])],
            'date_from'           => ['nullable', 'date'],
            'date_to'             => ['nullable', 'date', 'after_or_equal:date_from'],
            'cashier_id'          => ['nullable', 'integer'],
        ]);

        $data = $this->verificationService->getVerificationOverview($filters, 20);

        return view('admin.verifications.index', $data);
    }

    public function show(Transaction $transaction): View
    {
        Gate::authorize('view', $transaction);

        $transaction->load(['cashier', 'verifier', 'shift', 'items']);

        return view('admin.verifications.show', ['transaction' => $transaction]);
    }

    public function update(VerifyTransactionRequest $request, Transaction $transaction): RedirectResponse
    {
        $status = VerificationStatus::from($request->validated('verification_status'));

        $this->verificationService->updateVerification(
            $transaction,
            $status,
            $request->validated('note'),
            $request->user()->id
        );

        $message = $status === VerificationStatus::Verified
            ? 'Transaksi diverifikasi.'
            : 'Transaksi ditandai untuk ditindaklanjuti.';

        return redirect()->route('admin.verifications.show', $transaction)->with('success', $message);
    }
}
