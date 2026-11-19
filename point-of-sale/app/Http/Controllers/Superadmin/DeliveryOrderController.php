<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryOrder;
use App\Services\Superadmin\SuperadminAuditService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DeliveryOrderController extends Controller
{
    public function __construct(
        private readonly SuperadminAuditService $auditService
    ) {}

    public function index(Request $request): View
    {
        $data = $this->auditService->getDeliveryOrders($request->all(), 15);

        return view('superadmin.delivery-orders.index', $data);
    }

    public function show(DeliveryOrder $deliveryOrder): View
    {
        $deliveryOrder->load(['handler', 'items']);

        return view('superadmin.delivery-orders.show', ['order' => $deliveryOrder]);
    }
}
