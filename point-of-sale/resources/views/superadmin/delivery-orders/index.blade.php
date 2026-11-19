<x-app-layout>
    @php
        $selectClass = 'mt-1 w-full rounded-xl border border-stone-300 bg-white px-3 py-2 text-sm text-stone-900 shadow-xs focus:border-amber-600 focus:ring-2 focus:ring-amber-500/20 focus:outline-none';
    @endphp

    <div class="py-6">
        <div class="w-full px-4 lg:px-6 space-y-5">
            <form method="GET" x-data
                @change="if (['SELECT'].includes($event.target.tagName) || ['date','checkbox'].includes($event.target.type)) $el.requestSubmit()"
                class="bg-white border border-stone-200/90 rounded-2xl p-4 shadow-xs flex flex-wrap items-end gap-3">
                <div class="w-full lg:flex-1 lg:min-w-[220px]">
                    <x-input-label for="search" value="Cari" />
                    <x-text-input id="search" name="search" type="text" class="mt-1 w-full"
                        placeholder="Kode / nama / telepon" :value="request('search')" />
                </div>
                <div class="w-full sm:w-[170px]">
                    <x-input-label for="from" value="Dari" />
                    <x-text-input id="from" name="from" type="date" class="mt-1 w-full" :value="request('from')" />
                </div>
                <div class="w-full sm:w-[170px]">
                    <x-input-label for="to" value="Sampai" />
                    <x-text-input id="to" name="to" type="date" class="mt-1 w-full" :value="request('to')" />
                </div>
                <div class="w-full sm:w-[180px]">
                    <x-input-label for="status" value="Status" />
                    <select id="status" name="status" class="{{ $selectClass }}">
                        <option value="">Semua</option>
                        @foreach ($statuses as $s)
                            <option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ $s->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-full sm:w-[100px]">
                    <a href="{{ route('superadmin.delivery-orders.index') }}"
                        class="inline-flex items-center justify-center px-4 py-2.5 border border-stone-200/90 rounded-xl text-xs font-semibold text-stone-600 hover:text-stone-900 hover:bg-stone-50 transition w-full">
                        Reset
                    </a>
                </div>
            </form>


            <div class="bg-white border border-stone-200/90 rounded-2xl p-4 text-sm flex flex-wrap gap-8 shadow-xs">
                @foreach ($statuses as $s)
                    @php
                        $row = $summary[$s->value] ?? null;
                        $count = $row->total ?? 0;
                        $amount = $row->amount ?? 0;
                    @endphp
                    <div>
                        <span class="text-xs text-stone-500 block">{{ $s->label() }}</span>
                        <strong class="text-base text-stone-900">{{ $count }}</strong>
                        <span class="text-xs text-stone-500">· @rupiah($amount)</span>
                    </div>
                @endforeach
            </div>

            <div class="bg-white border border-stone-200/90 rounded-2xl shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-stone-50 text-left text-xs uppercase text-stone-400 border-b border-stone-100">
                            <tr>
                                <th class="px-5 py-3 font-semibold">Kode</th>
                                <th class="px-5 py-3 font-semibold">Jadwal</th>
                                <th class="px-5 py-3 font-semibold">Pelanggan</th>
                                <th class="px-5 py-3 font-semibold">Paket</th>
                                <th class="px-5 py-3 font-semibold">Status</th>
                                <th class="px-5 py-3 font-semibold">Pembayaran</th>
                                <th class="px-5 py-3 text-right font-semibold">Total</th>
                                <th class="px-5 py-3 font-semibold">Ditangani</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            @forelse ($orders as $o)
                                @php
                                    $paid = (float) $o->paid_amount;
                                    $total = (float) $o->total_amount;
                                    $flag = ($o->status === \App\Enums\DeliveryOrderStatus::Delivered && $o->payment_status !== 'paid')
                                        || ($o->status === \App\Enums\DeliveryOrderStatus::Cancelled && $paid > 0)
                                        || ($o->payment_status === 'paid' && $paid < $total);
                                @endphp
                                <tr class="hover:bg-stone-50/60 transition {{ $flag ? 'bg-red-50/40' : '' }}">
                                    <td class="px-5 py-3.5 font-semibold">
                                        <a href="{{ route('superadmin.delivery-orders.show', $o) }}"
                                            class="text-amber-800 hover:text-amber-900 hover:underline">{{ $o->code }}</a>
                                    </td>
                                    <td class="px-5 py-3.5 text-stone-600">
                                        {{ $o->delivery_date->translatedFormat('d M Y') }}
                                        {{ $o->is_asap ? 'ASAP' : substr((string) $o->delivery_time, 0, 5) }}
                                    </td>
                                    <td class="px-5 py-3.5 text-stone-700">
                                        {{ $o->customer_name }}
                                        <span class="block text-xs text-stone-400">{{ $o->customer_phone }}</span>
                                    </td>
                                    <td class="px-5 py-3.5 text-stone-700">{{ $o->deliveryTierLabel() }}</td>
                                    <td class="px-5 py-3.5"><x-status :value="$o->status" /></td>
                                    <td class="px-5 py-3.5 text-stone-700">
                                        {{ $o->paymentMethodLabel() }} · {{ $o->paymentStatusLabel() }}
                                        <span class="block text-xs text-stone-400">Terbayar @rupiah($o->paid_amount)</span>
                                        @if ($flag)
                                            <span class="inline-block mt-1 rounded-md bg-red-100 px-1.5 py-0.5 text-[11px] font-semibold text-red-700">Janggal</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5 text-right font-bold text-stone-900">@rupiah($o->total_amount)</td>
                                    <td class="px-5 py-3.5 text-stone-700">{{ $o->handler?->name ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-5 py-10 text-center text-stone-400">Tidak ada pesanan antar ditemukan.</td>
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