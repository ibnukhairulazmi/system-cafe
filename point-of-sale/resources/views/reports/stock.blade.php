<x-app-layout>
    <div class="py-6">
        <div class="w-full px-4 lg:px-6 space-y-4">
            @include('reports._tabs')
            <x-report-filters :action="route($prefix . '.stock')" :from="$from" :to="$to" type="stock" />

            <p class="text-xs text-stone-500">Kolom <strong>stok</strong> dan <strong>nilai</strong> adalah kondisi saat
                ini. Kolom mutasi mengikuti periode di atas.</p>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 lg:w-2/3">
                <div class="bg-white border border-stone-200/90 rounded-2xl p-5 shadow-xs">
                    <p class="text-xs font-semibold uppercase tracking-wider text-stone-400">Jumlah Bahan</p>
                    <p class="text-2xl font-bold text-stone-900 mt-1">{{ $report['summary']['ingredients'] }}</p>
                </div>
                <div class="bg-white border border-stone-200/90 rounded-2xl p-5 shadow-xs">
                    <p class="text-xs font-semibold uppercase tracking-wider text-stone-400">Stok Menipis</p>
                    <p
                        class="text-2xl font-bold mt-1 {{ $report['summary']['low'] ? 'text-rose-600' : 'text-stone-900' }}">
                        {{ $report['summary']['low'] }}
                    </p>
                </div>
                <div class="bg-white border border-stone-200/90 rounded-2xl p-5 shadow-xs">
                    <p class="text-xs font-semibold uppercase tracking-wider text-stone-400">Nilai Persediaan</p>
                    <p class="text-2xl font-bold text-stone-900 mt-1">@rupiah($report['summary']['stock_value'])</p>
                </div>
            </div>

            <div class="bg-white border border-stone-200/90 rounded-2xl shadow-xs overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead
                        class="bg-stone-50 text-left text-xs uppercase text-black font-semibold border-b border-stone-100">
                        <tr>
                            <th class="px-4 py-3.5 text-black font-semibold">Bahan</th>
                            <th class="px-4 py-3.5 text-right text-black font-semibold">Stok</th>
                            <th class="px-4 py-3.5 text-right text-black font-semibold">Min</th>
                            <th class="px-4 py-3.5 text-right text-black font-semibold">Nilai</th>
                            <th class="px-4 py-3.5 text-right text-black font-semibold">Masuk</th>
                            <th class="px-4 py-3.5 text-right text-black font-semibold">Keluar</th>
                            <th class="px-4 py-3.5 text-right text-black font-semibold">Terjual</th>
                            <th class="px-4 py-3.5 text-right text-black font-semibold">Kembali</th>
                            <th class="px-4 py-3.5 text-right text-black font-semibold">Opname</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100 text-black">
                        @forelse ($report['rows'] as $r)
                            <tr class="hover:bg-stone-50/60 transition text-black">
                                <td class="px-4 py-3.5 font-medium text-black">
                                    {{ $r['name'] }}
                                    @if ($r['low'])
                                        <span class="text-xs text-rose-600 font-semibold ms-1">(Menipis)</span>
                                    @endif
                                </td>
                                <td
                                    class="px-4 py-3.5 text-right {{ $r['low'] ? 'text-rose-600 font-semibold' : 'text-black font-medium' }}">
                                    @qty($r['stock']) {{ $r['unit'] }}
                                </td>
                                <td class="px-4 py-3.5 text-right text-black font-medium">@qty($r['min_stock'])</td>
                                <td class="px-4 py-3.5 text-right font-medium text-black">@rupiah($r['value'])</td>
                                <td class="px-4 py-3.5 text-right text-emerald-700 font-medium">@qty($r['in'])</td>
                                <td class="px-4 py-3.5 text-right text-black font-medium">@qty($r['out'])</td>
                                <td class="px-4 py-3.5 text-right text-black font-medium">@qty($r['sold'])</td>
                                <td class="px-4 py-3.5 text-right text-black font-medium">@qty($r['returned'] ?? 0)</td>
                                <td
                                    class="px-4 py-3.5 text-right font-medium {{ $r['opname'] < 0 ? 'text-rose-600' : 'text-black' }}">
                                    {{ $r['opname'] > 0 ? '+' : '' }}@qty($r['opname'])
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-10 text-center text-black font-medium">Belum ada bahan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
