<x-app-layout>
    <div class="py-6">
        <div class="w-full px-4 lg:px-6 space-y-4">
            @include('reports._tabs')

            <x-report-filters :action="route($prefix . '.purchases')" :from="$from" :to="$to" type="purchases">
                <x-select name="status" label="Status" :options="$statuses" :selected="$selectedStatus" />
                <x-select name="supplier_id" label="Supplier" :options="$suppliers" :selected="$selectedSupplier" blank="Semua" />
            </x-report-filters>

            <p class="text-xs text-stone-500">Periode dihitung berdasarkan
                <strong>{{ $report['filters']['basis'] }}</strong>. Total tidak menghitung PO yang dibatalkan.</p>

            <div class="grid grid-cols-2 gap-4 lg:w-1/2">
                <div class="bg-white border border-stone-200/90 rounded-2xl p-5 shadow-xs">
                    <p class="text-xs font-semibold uppercase tracking-wider text-stone-400">Jumlah PO</p>
                    <p class="text-2xl font-bold text-stone-900 mt-1">{{ $report['summary']['count'] }}</p>
                </div>
                <div class="bg-white border border-stone-200/90 rounded-2xl p-5 shadow-xs">
                    <p class="text-xs font-semibold uppercase tracking-wider text-stone-400">Total Pembelian</p>
                    <p class="text-2xl font-bold text-stone-900 mt-1">@rupiah($report['summary']['total'])</p>
                </div>
            </div>

            <div class="bg-white border border-stone-200/90 rounded-2xl shadow-xs overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead
                        class="bg-stone-50 text-left text-xs uppercase text-black font-semibold border-b border-stone-100">
                        <tr>
                            <th class="px-5 py-3.5 text-black font-semibold">No. PO</th>
                            <th class="px-5 py-3.5 text-black font-semibold">Supplier</th>
                            <th class="px-5 py-3.5 text-black font-semibold">Status</th>
                            <th class="px-5 py-3.5 text-black font-semibold">Dibuat</th>
                            <th class="px-5 py-3.5 text-black font-semibold">Diterima</th>
                            <th class="px-5 py-3.5 text-right text-black font-semibold">Item</th>
                            <th class="px-5 py-3.5 text-right text-black font-semibold">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100 text-black">
                        @forelse ($report['orders'] as $o)
                            <tr class="hover:bg-stone-50/60 transition text-black">
                                <td class="px-5 py-3.5 font-medium">
                                    <a href="{{ route(($isOwner ? 'superadmin' : 'admin') . '.purchase-orders.show', $o['id']) }}"
                                        class="text-amber-800 hover:text-amber-900 hover:underline font-semibold">{{ $o['po_no'] }}</a>
                                </td>
                                <td class="px-5 py-3.5 text-black font-medium">{{ $o['supplier'] }}</td>
                                <td class="px-5 py-3.5 text-black font-medium">{{ $o['status'] }}</td>
                                <td class="px-5 py-3.5 text-black font-medium">{{ $o['created_at'] }}</td>
                                <td class="px-5 py-3.5 text-black font-medium">{{ $o['received_at'] ?? '-' }}</td>
                                <td class="px-5 py-3.5 text-right text-black font-medium">{{ $o['items'] }}</td>
                                <td class="px-5 py-3.5 text-right font-bold text-black">@rupiah($o['total'])</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-10 text-center text-black font-medium">Tidak ada PO pada
                                    periode ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                <div class="bg-white border border-stone-200/90 rounded-2xl p-5 shadow-xs">
                    <h3 class="font-bold text-base text-stone-900 pb-3 border-b border-stone-100">Per Supplier</h3>
                    <table class="mt-2 min-w-full text-sm">
                        <thead
                            class="text-left text-xs uppercase text-black font-semibold border-b border-stone-100">
                            <tr>
                                <th class="py-2.5 text-black font-semibold">Supplier</th>
                                <th class="py-2.5 text-right text-black font-semibold">PO</th>
                                <th class="py-2.5 text-right text-black font-semibold">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100 text-black">
                            @forelse ($report['by_supplier'] as $x)
                                <tr class="hover:bg-stone-50/60 transition text-black">
                                    <td class="py-2.5 font-medium text-black">{{ $x['name'] }}</td>
                                    <td class="py-2.5 text-right text-black font-medium">{{ $x['orders'] }}</td>
                                    <td class="py-2.5 text-right font-bold text-black">@rupiah($x['total'])</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="py-6 text-center text-black font-medium">-</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="bg-white border border-stone-200/90 rounded-2xl p-5 shadow-xs">
                    <h3 class="font-bold text-base text-stone-900 pb-3 border-b border-stone-100">Per Bahan</h3>
                    <table class="mt-2 min-w-full text-sm">
                        <thead
                            class="text-left text-xs uppercase text-black font-semibold border-b border-stone-100">
                            <tr>
                                <th class="py-2.5 text-black font-semibold">Bahan</th>
                                <th class="py-2.5 text-right text-black font-semibold">Jumlah</th>
                                <th class="py-2.5 text-right text-black font-semibold">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100 text-black">
                            @forelse ($report['by_ingredient'] as $x)
                                <tr class="hover:bg-stone-50/60 transition text-black">
                                    <td class="py-2.5 font-medium text-black">{{ $x['name'] }}</td>
                                    <td class="py-2.5 text-right text-black font-medium">@qty($x['quantity']) {{ $x['unit'] }}
                                    </td>
                                    <td class="py-2.5 text-right font-bold text-black">@rupiah($x['total'])</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="py-6 text-center text-black font-medium">-</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
