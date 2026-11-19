<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DeliveryOrderStatus;
use App\Http\Controllers\Controller;
use App\Models\DeliveryOrder;
use App\Services\Admin\AdminDeliveryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DeliveryOrderController extends Controller
{
    public function __construct(
        private readonly AdminDeliveryService $deliveryService
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(AdminDeliveryService::FILTER_STATUSES)],
            'date'   => ['nullable', 'date'],
            'q'      => ['nullable', 'string', 'max:100'],
        ]);

        $data = $this->deliveryService->getDeliveryOverview($filters, 15);

        return view('admin.delivery-orders.index', $data);
    }

    public function update(Request $request, DeliveryOrder $deliveryOrder): RedirectResponse
    {
        $data = $request->validate([
            'status'         => ['required', Rule::enum(DeliveryOrderStatus::class)],
            'payment_status' => ['nullable', 'string', Rule::in(['unpaid', 'paid'])],
            'staff_note'     => ['nullable', 'string', 'max:500'],
        ]);

        $this->deliveryService->updateDelivery($deliveryOrder, $data, $request->user()->id);

        return back()->with('success', "Pesanan antar {$deliveryOrder->code} berhasil diperbarui.");
    }
}
