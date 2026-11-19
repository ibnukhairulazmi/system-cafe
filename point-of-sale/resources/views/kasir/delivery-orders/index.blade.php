<x-app-layout>
    @php
        $selectClass = 'mt-1 w-full rounded-xl border border-stone-300 bg-white px-3 py-2 text-sm text-stone-900 shadow-xs focus:border-amber-600 focus:ring-2 focus:ring-amber-500/20 focus:outline-none';
        $btnBase = 'inline-flex items-center rounded-lg px-3 py-1.5 text-xs font-semibold transition';
        $btnPrimary = $btnBase . ' bg-amber-700 text-white hover:bg-amber-800';
        $btnGhost = $btnBase . ' border border-stone-300 text-stone-700 hover:bg-stone-50';
        $Confirmed = \App\Enums\DeliveryOrderStatus::Confirmed;
        $OnDelivery = \App\Enums\DeliveryOrderStatus::OnDelivery;
        $Delivered = \App\Enums\DeliveryOrderStatus::Delivered;
    @endphp

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            <x-flash />
            <x-errors-box />

            <form method="GET" x-data
                @change="if ($event.target.tagName === 'SELECT') $el.requestSubmit()"
                @input.debounce.300ms="if ($event.target.name === 'search') $el.requestSubmit()"
                class="bg-white border border-stone-200/90 rounded-2xl p-4 shadow-xs flex flex-wrap gap-4 items-end">
                <div class="min-w-200">
                    <x-input-label for="search" value="Cari" />
                    <x-text-input id="search" name="search" type="text" class="mt-1 w-full"
                        placeholder="Kode / nama / telepon" :value="request('search')"
                        x-init="if ($el.value) { $el.focus(); $el.setSelectionRange($el.value.length, $el.value.length) }" />
                </div>
                <div>
                    <a href="{{ route('kasir.delivery-orders.index') }}"
                        class="inline-flex items-center justify-center px-4 py-2.5 border border-stone-200/90 rounded-xl text-xs font-semibold text-stone-600 hover:text-stone-900 hover:bg-stone-50 transition w-full">
                        Reset
                    </a>
                </div>
            </form>

            <div class="bg-white border border-stone-200/90 rounded-2xl shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-stone-50 text-left text-xs uppercase text-stone-400 border-b border-stone-100">
                            <tr>
                                <th class="px-5 py-3 font-semibold">Jadwal</th>
                                <th class="px-5 py-3 font-semibold">Kode</th>
                                <th class="px-5 py-3 font-semibold">Pelanggan &amp; Alamat</th>
                                <th class="px-5 py-3 font-semibold">Status</th>
                                <th class="px-5 py-3 font-semibold">Pembayaran</th>
                                <th class="px-5 py-3 text-right font-semibold">Total</th>
                                <th class="px-5 py-3 text-right font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            @forelse ($orders as $o)
                                @php
                                    $isCash = $o->payment_method === 'cash';
                                    $unpaid = $o->payment_status !== 'paid';
                                    $canDispatch = $o->status === $Confirmed;
                                    $canReceive = $isCash && $unpaid && in_array($o->status, [$OnDelivery, $Delivered], true);
                                    $canComplete = $o->status === $OnDelivery && ! ($isCash && $unpaid);
                                @endphp
                                <tr class="hover:bg-stone-50/60 transition">
                                    <td class="px-5 py-3.5 text-stone-700">
                                        <span class="font-bold text-stone-900">{{ $o->is_asap ? 'ASAP' : substr((string) $o->delivery_time, 0, 5) }}</span>
                                        <span class="block text-xs text-stone-400">{{ $o->delivery_date->translatedFormat('d M Y') }}</span>
                                    </td>
                                    <td class="px-5 py-3.5 font-semibold">
                                        <a href="{{ route('kasir.delivery-orders.show', $o) }}"
                                            class="text-amber-800 hover:text-amber-900 hover:underline">{{ $o->code }}</a>
                                        <span class="block text-xs font-normal text-stone-400">{{ $o->deliveryTierLabel() }}</span>
                                    </td>
                                    <td class="px-5 py-3.5 text-stone-700 max-w-xs">
                                        {{ $o->customer_name }} · <span class="text-xs text-stone-400">{{ $o->customer_phone }}</span>
                                        <span class="block text-xs text-stone-500 truncate">{{ $o->delivery_address }}</span>
                                    </td>
                                    <td class="px-5 py-3.5"><x-status :value="$o->status" /></td>
                                    <td class="px-5 py-3.5 text-stone-700">
                                        {{ $o->paymentMethodLabel() }}
                                        <span class="block text-xs font-semibold {{ $unpaid ? 'text-rose-600' : 'text-emerald-700' }}">{{ $o->paymentStatusLabel() }}</span>
                                    </td>
                                    <td class="px-5 py-3.5 text-right font-bold text-stone-900">@rupiah($o->total_amount)</td>
                                    <td class="px-5 py-3.5">
                                        <div class="flex flex-wrap justify-end items-center gap-2">
                                            @if ($canDispatch)
                                                <form method="POST" action="{{ route('kasir.delivery-orders.ship', $o) }}"
                                                    onsubmit="return confirm('Tandai pesanan {{ $o->code }} sedang dikirim?')">
                                                    @csrf @method('PATCH')
                                                    <button class="{{ $btnPrimary }}">Kirim</button>
                                                </form>
                                            @endif

                                            @if ($canReceive)
                                                <form method="POST" action="{{ route('kasir.delivery-orders.receive-payment', $o) }}"
                                                    onsubmit="return confirm('Terima uang tunai Rp {{ number_format((float) $o->total_amount, 0, ',', '.') }} untuk {{ $o->code }}?')">
                                                    @csrf
                                                    <button class="{{ $btnPrimary }}">Terima Tunai</button>
                                                </form>
                                            @endif

                                            @if ($canComplete)
                                                <form method="POST" action="{{ route('kasir.delivery-orders.complete', $o) }}"
                                                    onsubmit="return confirm('Tandai pesanan {{ $o->code }} sudah diterima pelanggan?')">
                                                    @csrf @method('PATCH')
                                                    <button class="{{ $btnPrimary }}">Selesai</button>
                                                </form>
                                            @endif

                                            <a href="{{ route('kasir.delivery-orders.show', $o) }}" class="{{ $btnGhost }}">Detail</a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-5 py-10 text-center text-stone-400">Tidak ada pesanan antar yang perlu ditangani.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{ $orders->links() }}
        </div>
    </div>
</x-app-layout>