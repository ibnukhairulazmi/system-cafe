<x-app-layout>
    <div class="py-6">
        <div class="w-full px-4 lg:px-6 space-y-4">
            <x-flash />

            <div class="flex flex-wrap gap-2 text-sm">
                @foreach (['pending' => 'Menunggu', 'flagged' => 'Ditandai', 'verified' => 'Terverifikasi', 'all' => 'Semua'] as $key => $label)
                    <a href="{{ route('admin.verifications.index', array_merge(request()->except('page'), ['verification_status' => $key])) }}"
                        class="rounded-full px-4 py-1.5 border text-xs sm:text-sm font-medium transition duration-150 {{ $selected === $key ? 'bg-amber-700 text-white border-amber-700 shadow-xs' : 'bg-white text-stone-600 border-stone-200 hover:bg-stone-50 hover:text-stone-900' }}">
                        {{ $label }}@if ($key !== 'all')
                            ({{ $counts[$key] ?? 0 }})
                        @endif
                    </a>
                @endforeach
            </div>

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
                    <x-select name="cashier_id" label="Kasir" :options="$cashiers" :selected="$filters['cashier_id'] ?? null"
                        blank="Semua Kasir" />
                </div>
                <div>
                    <a href="{{ route('admin.verifications.index') }}"
                        class="inline-flex items-center px-4 py-2.5 border border-stone-200/90 rounded-xl text-xs font-semibold text-stone-600 hover:text-stone-900 hover:bg-stone-50 transition">
                        Reset
                    </a>
                </div>
            </form>

            <div class="bg-white border border-stone-200/90 rounded-2xl shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-stone-50 text-left text-xs uppercase text-black font-semibold border-b border-stone-100">
                            <tr>
                                <th class="px-5 py-3 font-semibold text-black">Invoice</th>
                                <th class="px-5 py-3 font-semibold text-black">Waktu</th>
                                <th class="px-5 py-3 font-semibold text-black">Kasir</th>
                                <th class="px-5 py-3 font-semibold text-black">Metode</th>
                                <th class="px-5 py-3 text-right font-semibold text-black">Total</th>
                                <th class="px-5 py-3 font-semibold text-black">Verifikasi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100 text-black">
                            @forelse ($transactions as $t)
                                <tr class="hover:bg-stone-50/60 transition text-black">
                                    <td class="px-5 py-3.5 font-semibold text-black">
                                        <a href="{{ route('admin.verifications.show', $t) }}"
                                            class="text-amber-800 hover:text-amber-900 hover:underline">
                                            {{ $t->invoice_no }}
                                        </a>
                                    </td>
                                    <td class="px-5 py-3.5 text-black font-medium">
                                        {{ $t->created_at->translatedFormat('d M Y H:i') }}</td>
                                    <td class="px-5 py-3.5 text-black font-medium">{{ $t->cashier->name }}</td>
                                    <td class="px-5 py-3.5 text-black font-medium">{{ $t->payment_method->label() }}</td>
                                    <td class="px-5 py-3.5 text-right font-bold text-black">@rupiah($t->total)</td>
                                    <td class="px-5 py-3.5"><x-status :value="$t->verification_status" /></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-5 py-10 text-center text-black font-medium">Tidak ada transaksi
                                        ditemukan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            {{ $transactions->links() }}
        </div>
    </div>
</x-app-layout>
