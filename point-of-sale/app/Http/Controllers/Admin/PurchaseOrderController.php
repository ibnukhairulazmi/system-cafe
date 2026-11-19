<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PurchaseOrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PurchaseOrderRequest;
use App\Models\PurchaseOrder;
use App\Services\PurchaseOrderService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PurchaseOrderController extends Controller
{
    public function __construct(
        private readonly PurchaseOrderService $service
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', PurchaseOrder::class);

        $filters = $request->validate([
            'status'      => ['nullable', Rule::in(PurchaseOrderStatus::values())],
            'supplier_id' => ['nullable', 'integer'],
            'date_from'   => ['nullable', 'date'],
            'date_to'     => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $data = $this->service->getPaginatedOrders($filters, 20);

        return view('admin.purchase-orders.index', $data);
    }

    public function create(): View
    {
        Gate::authorize('create', PurchaseOrder::class);

        return view('admin.purchase-orders.form', $this->service->getFormData(null));
    }

    public function store(PurchaseOrderRequest $request): RedirectResponse
    {
        $po = $this->service->create($request->validated(), $request->user());

        return redirect()->route('admin.purchase-orders.show', $po)
            ->with('success', "PO {$po->po_no} disimpan sebagai draft.");
    }

    public function show(PurchaseOrder $purchaseOrder): View
    {
        Gate::authorize('view', $purchaseOrder);

        $purchaseOrder->load(['supplier', 'creator', 'receiver', 'items.ingredient']);

        return view('admin.purchase-orders.show', ['order' => $purchaseOrder]);
    }

    public function edit(PurchaseOrder $purchaseOrder): View
    {
        Gate::authorize('update', $purchaseOrder);

        $purchaseOrder->load('items');

        return view('admin.purchase-orders.form', $this->service->getFormData($purchaseOrder));
    }

    public function update(PurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        return $this->run(
            fn () => $this->service->update($purchaseOrder, $request->validated(), $request->user()),
            route('admin.purchase-orders.show', $purchaseOrder),
            'PO diperbarui.'
        );
    }

    public function destroy(PurchaseOrder $purchaseOrder, Request $request): RedirectResponse
    {
        Gate::authorize('delete', $purchaseOrder);

        return $this->run(
            fn () => $this->service->delete($purchaseOrder, $request->user()),
            route('admin.purchase-orders.index'),
            'PO draft dihapus.'
        );
    }

    public function order(PurchaseOrder $purchaseOrder, Request $request): RedirectResponse
    {
        Gate::authorize('order', $purchaseOrder);

        return $this->run(
            fn () => $this->service->markOrdered($purchaseOrder, $request->user()),
            route('admin.purchase-orders.show', $purchaseOrder),
            'PO ditandai sebagai dipesan.'
        );
    }

    public function receive(PurchaseOrder $purchaseOrder, Request $request): RedirectResponse
    {
        Gate::authorize('receive', $purchaseOrder);

        $date = $request->validate(['received_at' => ['nullable', 'date', 'before_or_equal:today']])['received_at'] ?? null;

        return $this->run(
            fn () => $this->service->receive($purchaseOrder, $request->user(), $date),
            route('admin.purchase-orders.show', $purchaseOrder),
            'Barang diterima, stok bahan sudah bertambah.'
        );
    }

    public function cancel(PurchaseOrder $purchaseOrder, Request $request): RedirectResponse
    {
        Gate::authorize('cancel', $purchaseOrder);

        return $this->run(
            fn () => $this->service->cancel($purchaseOrder, $request->user()),
            route('admin.purchase-orders.show', $purchaseOrder),
            'PO dibatalkan.'
        );
    }

    private function run(\Closure $action, string $redirectTo, string $successMessage): RedirectResponse
    {
        try {
            $action();
        } catch (DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect($redirectTo)->with('success', $successMessage);
    }
}
