<x-app-layout>

    <div class="py-6">
        <div class="w-full px-4 lg:px-6 space-y-4">
            <x-flash />

            <form method="GET" x-data
                @change="if ($event.target.tagName === 'SELECT' || $event.target.type === 'date') $el.requestSubmit()"
                class="bg-white border border-stone-200/90 rounded-2xl p-4 shadow-xs flex flex-wrap gap-4 items-end">
                <div class="min-w-40">
                    <x-input-label for="date_from" value="Dari Tanggal" />
                    <x-text-input id="date_from" name="date_from" type="date" class="mt-1" :value="$filters['date_from'] ?? ''" />
                </div>
                <div class="min-w-40">
                    <x-input-label for="date_to" value="Sampai Tanggal" />
                    <x-text-input id="date_to" name="date_to" type="date" class="mt-1" :value="$filters['date_to'] ?? ''" />
                </div>
                <div class="min-w-48">
                    <x-select name="supplier_id" label="Supplier" :options="$suppliers" :selected="$filters['supplier_id'] ?? null"
                        blank="Semua Supplier" />
                </div>
                <div class="min-w-40">
                    <x-select name="status" label="Status" :options="$statuses" :selected="$filters['status'] ?? null" blank="Semua Status" />
                </div>
                <div>
                    <a href="{{ route('admin.purchase-orders.index') }}"
                        class="inline-flex items-center px-4 py-2.5 border border-stone-200/90 rounded-xl text-xs font-semibold text-stone-600 hover:text-stone-900 hover:bg-stone-50 transition">
                        Reset
                    </a>
                </div>
                <div class="ml-auto">
                    <a href="{{ route('admin.purchase-orders.create') }}">
                        <x-primary-button type="button">Buat PO</x-primary-button>
                    </a>
                </div>
            </form>

            <div class="bg-white border border-stone-200/90 rounded-2xl shadow-xs overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead
                        class="bg-stone-50 text-left text-xs uppercase text-black border-b border-stone-100 font-semibold">
                        <tr>
                            <th class="px-5 py-3.5 text-black font-semibold">No. PO</th>
                            <th class="px-5 py-3.5 text-black font-semibold">Supplier</th>
                            <th class="px-5 py-3.5 text-black font-semibold">Dibuat</th>
                            <th class="px-5 py-3.5 text-right text-black font-semibold">Total</th>
                            <th class="px-5 py-3.5 text-black font-semibold">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100 text-black">
                        @forelse ($orders as $o)
                            <tr class="hover:bg-stone-50/60 transition text-black">
                                <td class="px-5 py-3.5 font-medium">
                                    <a href="{{ route('admin.purchase-orders.show', $o) }}"
                                        class="text-amber-800 hover:text-amber-900 hover:underline font-semibold">{{ $o->po_no }}</a>
                                </td>
                                <td class="px-5 py-3.5 text-black font-medium">{{ $o->supplier->name }}</td>
                                <td class="px-5 py-3.5 text-black font-medium">{{ $o->created_at->translatedFormat('d M Y') }} &middot;
                                    {{ $o->creator->name }}</td>
                                <td class="px-5 py-3.5 text-right text-black font-bold">@rupiah($o->total)</td>
                                <td class="px-5 py-3.5"><x-status :value="$o->status" /></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-10 text-center text-black font-medium">Belum ada pemesanan
                                    bahan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $orders->links() }}
        </div>
</x-app-layout>
