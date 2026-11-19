<?php

namespace App\Http\Controllers\Kasir;

use App\Enums\DeliveryOrderStatus;
use App\Http\Controllers\Controller;
use App\Models\DeliveryOrder;
use App\Models\Shift;
use App\Services\Kasir\KasirDeliveryService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DeliveryOrderController extends Controller
{
    public function __construct(
        private readonly KasirDeliveryService $deliveryService
    ) {}

    public function index(Request $request): View
    {
        $status = DeliveryOrderStatus::tryFrom((string) $request->status);

        $orders = $this->deliveryService->getPaginatedOrders(
            $status,
            $request->input('search'),
            15
        );

        return view('kasir.delivery-orders.index', [
            'orders'   => $orders,
            'statuses' => DeliveryOrderStatus::cases(),
        ]);
    }

    public function show(DeliveryOrder $deliveryOrder): View
    {
        $deliveryOrder->load(['items', 'handler']);

        return view('kasir.delivery-orders.show', ['order' => $deliveryOrder]);
    }

    public function ship(Request $request, DeliveryOrder $deliveryOrder): RedirectResponse
    {
        try {
            $this->deliveryService->ship($deliveryOrder, $request->user());
        } catch (DomainException $e) {
            return back()->withErrors(['order' => $e->getMessage()]);
        }

        return back()->with('success', "Pesanan {$deliveryOrder->code} sedang dikirim.");
    }

    
    public function complete(Request $request, DeliveryOrder $deliveryOrder): RedirectResponse
    {
        try {
            $this->deliveryService->complete($deliveryOrder, $request->user());
        } catch (DomainException $e) {
            return back()->withErrors(['order' => $e->getMessage()]);
        }

        return back()->with('success', "Pesanan {$deliveryOrder->code} selesai diantar.");
    }

    
    public function receivePayment(Request $request, DeliveryOrder $deliveryOrder): RedirectResponse
    {
        $shift = Shift::open()->where('user_id', $request->user()->id)->first();

        if (! $shift) {
            return back()->withErrors(['order' => 'Buka shift terlebih dahulu sebelum menerima pembayaran.']);
        }

        try {
            $this->deliveryService->receiveCashPayment($deliveryOrder, $shift, $request->user());
        } catch (DomainException $e) {
            return back()->withErrors(['order' => $e->getMessage()]);
        }

        return back()->with('success', 'Pembayaran tunai berhasil dicatat.');
    }
}
