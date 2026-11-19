<x-app-layout>
    <div class="py-6">
        <div class="w-full px-4 lg:px-6 space-y-5">
            <div class="flex items-center justify-between">
                <h1 class="text-lg font-bold text-stone-900">Pesanan Antar {{ $order->code }}</h1>
                <a href="{{ route('superadmin.delivery-orders.index') }}"
                    class="text-xs font-semibold text-stone-600 hover:text-stone-900">&larr; Kembali</a>
            </div>

            <div class="bg-white border border-stone-200/90 rounded-2xl p-5 shadow-xs">
                <dl class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                    <div><dt class="text-xs text-stone-500">Status</dt><dd class="mt-0.5"><x-status :value="$order->status" /></dd></div>
                    <div><dt class="text-xs text-stone-500">Pelanggan</dt><dd class="font-semibold text-stone-900">{{ $order->customer_name }}</dd></div>
                    <div><dt class="text-xs text-stone-500">Telepon</dt><dd class="font-semibold text-stone-900">{{ $order->customer_phone }}</dd></div>
                    <div><dt class="text-xs text-stone-500">Paket Antar</dt><dd class="font-semibold text-stone-900">{{ $order->deliveryTierLabel() }}</dd></div>

                    <div class="col-span-2"><dt class="text-xs text-stone-500">Alamat</dt><dd class="font-semibold text-stone-900">{{ $order->delivery_address }}@if ($order->landmark) <span class="block text-xs font-normal text-stone-500">Patokan: {{ $order->landmark }}</span>@endif</dd></div>
                    <div><dt class="text-xs text-stone-500">Tanggal Antar</dt><dd class="font-semibold text-stone-900">{{ $order->delivery_date->translatedFormat('d F Y') }}</dd></div>
                    <div><dt class="text-xs text-stone-500">Jam</dt><dd class="font-semibold text-stone-900">{{ $order->is_asap ? 'ASAP' : substr((string) $order->delivery_time, 0, 5) }}</dd></div>

                    <div><dt class="text-xs text-stone-500">Metode Bayar</dt><dd class="font-semibold text-stone-900">{{ $order->paymentMethodLabel() }}</dd></div>
                    <div><dt class="text-xs text-stone-500">Status Bayar</dt><dd class="font-semibold text-stone-900">{{ $order->paymentStatusLabel() }}</dd></div>
                    <div><dt class="text-xs text-stone-500">Subtotal</dt><dd class="font-semibold text-stone-900">@rupiah($order->subtotal_amount)</dd></div>
                    <div><dt class="text-xs text-stone-500">Ongkir</dt><dd class="font-semibold text-stone-900">@rupiah($order->delivery_fee)</dd></div>

                    <div><dt class="text-xs text-stone-500">Total</dt><dd class="font-bold text-stone-900">@rupiah($order->total_amount)</dd></div>
                    <div><dt class="text-xs text-stone-500">Terbayar</dt><dd class="font-bold text-amber-800">@rupiah($order->paid_amount)</dd></div>
                    <div><dt class="text-xs text-stone-500">Ditangani Oleh</dt><dd class="font-semibold text-stone-900">{{ $order->handler?->name ?? '-' }}</dd></div>
                    <div><dt class="text-xs text-stone-500">Waktu Ditangani</dt><dd class="font-semibold text-stone-900">{{ $order->handled_at?->translatedFormat('d M Y H:i') ?? '-' }}</dd></div>
                </dl>

                @if ($order->customer_note || $order->staff_note)
                    <div class="mt-4 grid md:grid-cols-2 gap-4 text-sm border-t border-stone-100 pt-4">
                        <div><dt class="text-xs text-stone-500">Catatan Pelanggan</dt><dd class="text-stone-700">{{ $order->customer_note ?: '-' }}</dd></div>
                        <div><dt class="text-xs text-stone-500">Catatan Staf</dt><dd class="text-stone-700">{{ $order->staff_note ?: '-' }}</dd></div>
                    </div>
                @endif
            </div>

            <div class="bg-white border border-stone-200/90 rounded-2xl shadow-xs overflow-hidden">
                <div class="px-5 py-3 text-sm font-semibold text-stone-900 border-b border-stone-100">Item Pesanan</div>
                <div class="overflow-x-auto">
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
    </div>
</x-app-layout>