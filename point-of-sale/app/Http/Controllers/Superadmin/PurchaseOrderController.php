<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Superadmin\PurchaseOrderFilterRequest;
use App\Models\PurchaseOrder;
use App\Services\PurchaseOrderService;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PurchaseOrderController extends Controller
{
    public function __construct(
        private readonly PurchaseOrderService $purchaseOrderService
    ) {}

    public function index(PurchaseOrderFilterRequest $request): View
    {
        $data = $this->purchaseOrderService->getPaginatedOrders($request->validated(), 20);

        return view('superadmin.purchase-orders.index', $data);
    }

    public function show(PurchaseOrder $purchaseOrder): View
    {
        Gate::authorize('view', $purchaseOrder);

        $purchaseOrder->load(['supplier', 'creator', 'receiver', 'items.ingredient']);

        return view('superadmin.purchase-orders.show', ['order' => $purchaseOrder]);
    }
}
