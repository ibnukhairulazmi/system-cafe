<x-app-layout>
    @php
        $Confirmed = \App\Enums\DeliveryOrderStatus::Confirmed;
        $OnDelivery = \App\Enums\DeliveryOrderStatus::OnDelivery;
        $Delivered = \App\Enums\DeliveryOrderStatus::Delivered;

        $isCash = $order->payment_method === 'cash';
        $unpaid = $order->payment_status !== 'paid';
        $canDispatch = $order->status === $Confirmed;
        $canReceive = $isCash && $unpaid && in_array($order->status, [$OnDelivery, $Delivered], true);
        $canComplete = $order->status === $OnDelivery && ! ($isCash && $unpaid);
    @endphp

    <div class="py-6">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            <x-flash />
            <x-errors-box />

            <div class="flex items-center justify-between">
                <h1 class="text-lg font-bold text-stone-900">Pesanan Antar {{ $order->code }}</h1>
                <a href="{{ route('kasir.delivery-orders.index') }}"
                    class="text-xs font-semibold text-stone-600 hover:text-stone-900">&larr; Kembali</a>
            </div>

            <div class="bg-white border border-stone-200/90 rounded-2xl p-6 shadow-xs">
                <dl class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                    <div><dt class="text-xs text-stone-500">Status</dt><dd class="mt-0.5"><x-status :value="$order->status" /></dd></div>
                    <div><dt class="text-xs text-stone-500">Pelanggan</dt><dd class="font-semibold text-stone-900">{{ $order->customer_name }}</dd></div>
                    <div><dt class="text-xs text-stone-500">Telepon</dt><dd class="font-semibold text-stone-900">{{ $order->customer_phone }}</dd></div>
                    <div><dt class="text-xs text-stone-500">Paket Antar</dt><dd class="font-semibold text-stone-900">{{ $order->deliveryTierLabel() }}</dd></div>

                    <div class="col-span-2 md:col-span-3">
                        <dt class="text-xs text-stone-500">Alamat</dt>
                        <dd class="font-semibold text-stone-900">{{ $order->delivery_address }}
                            @if ($order->landmark)
                                <span class="block text-xs font-normal text-stone-500">Patokan: {{ $order->landmark }}</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs text-stone-500">Jadwal</dt>
                        <dd class="font-semibold text-stone-900">
                            {{ $order->delivery_date->translatedFormat('d M Y') }},
                            {{ $order->is_asap ? 'ASAP' : substr((string) $order->delivery_time, 0, 5) }}
                        </dd>
                    </div>
                </dl>

                @if ($order->customer_note)
                    <div class="mt-4 text-sm border-t border-stone-100 pt-4">
                        <dt class="text-xs text-stone-500">Catatan Pelanggan</dt>
                        <dd class="text-stone-700">{{ $order->customer_note }}</dd>
                    </div>
                @endif

                <div class="mt-4 border-t border-stone-100 pt-4">
                    <a href="{{ $order->whatsappUrl() }}" target="_blank" rel="noopener"
                        class="inline-flex items-center rounded-xl px-4 py-2.5 text-xs font-semibold border border-stone-300 text-stone-700 hover:bg-stone-50 transition">
                        Hubungi via WhatsApp
                    </a>
                </div>
            </div>

            
            <div class="bg-white border border-stone-200/90 rounded-2xl p-6 shadow-xs space-y-4">
                <h3 class="font-bold text-base text-stone-900 pb-3 border-b border-stone-100">Pembayaran &amp; Pengiriman</h3>

                <dl class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm bg-stone-50/70 p-4 rounded-xl border border-stone-100">
                    <div><dt class="text-xs text-stone-500">Subtotal</dt><dd class="font-bold text-stone-900 mt-0.5">@rupiah($order->subtotal_amount)</dd></div>
                    <div><dt class="text-xs text-stone-500">Ongkir</dt><dd class="font-bold text-stone-900 mt-0.5">@rupiah($order->delivery_fee)</dd></div>
                    <div><dt class="text-xs text-stone-500">Total Tagihan</dt><dd class="font-bold text-amber-800 mt-0.5">@rupiah($order->total_amount)</dd></div>
                    <div>
                        <dt class="text-xs text-stone-500">Metode &amp; Status</dt>
                        <dd class="font-bold mt-0.5 {{ $unpaid ? 'text-rose-600' : 'text-emerald-700' }}">
                            {{ $order->paymentMethodLabel() }} · {{ $order->paymentStatusLabel() }}
                        </dd>
                    </div>
                </dl>

                @if ($canDispatch || $canReceive || $canComplete)
                    <div class="flex flex-wrap gap-3">
                        @if ($canDispatch)
                            <form method="POST" action="{{ route('kasir.delivery-orders.dispatch', $order) }}"
                                onsubmit="return confirm('Tandai pesanan {{ $order->code }} sedang dikirim?')">
                                @csrf @method('PATCH')
                                <x-primary-button>Tandai Sedang Dikirim</x-primary-button>
                            </form>
                        @endif

                        @if ($canReceive)
                            <form method="POST" action="{{ route('kasir.delivery-orders.receive-payment', $order) }}"
                                onsubmit="return confirm('Terima uang tunai Rp {{ number_format((float) $order->total_amount, 0, ',', '.') }}?')">
                                @csrf
                                <x-primary-button>Terima Tunai Rp {{ number_format((float) $order->total_amount, 0, ',', '.') }}</x-primary-button>
                            </form>
                        @endif

                        @if ($canComplete)
                            <form method="POST" action="{{ route('kasir.delivery-orders.complete', $order) }}"
                                onsubmit="return confirm('Tandai pesanan {{ $order->code }} sudah diterima pelanggan?')">
                                @csrf @method('PATCH')
                                <x-primary-button>Tandai Sudah Diterima Pelanggan</x-primary-button>
                            </form>
                        @endif
                    </div>
                @endif

                @if ($order->status === $OnDelivery && $isCash && $unpaid)
                    <p class="text-xs text-stone-500">Pesanan tunai baru bisa diselesaikan setelah uang diterima dan dicatat.</p>
                @elseif ($order->status === $Delivered && ! $unpaid)
                    <p class="text-sm font-semibold text-emerald-700">Pesanan sudah diantar dan lunas.</p>
                @endif
            </div>

            
            <div class="bg-white border border-stone-200/90 rounded-2xl shadow-xs overflow-hidden">
                <div class="px-5 py-3 text-sm font-semibold text-stone-900 border-b border-stone-100">Item Pesanan</div>
                <table class="min-w-full text-sm">
                    <thead class="bg-stone-50 text-left text-xs uppercase text-stone-400 border-b border-stone-100">
                        <tr>
                            <th class="px-5 py-3 font-semibold">Menu</th>
                            <th class="px-5 py-3 text-right font-semibold">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        @forelse ($order->items as $item)
                            <tr>
                                <td class="px-5 py-3.5 text-stone-700">{{ $item->product_name }}</td>
                                <td class="px-5 py-3.5 text-right text-stone-900">{{ $item->quantity }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="px-5 py-8 text-center text-stone-400">Tidak ada item.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>