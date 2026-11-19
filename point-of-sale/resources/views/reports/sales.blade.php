<x-app-layout>
    @php
        $s = $report['summary'];
        $ch = $report['channels'];
    @endphp

    <div class="py-6">
        <div class="w-full px-4 lg:px-6 space-y-5">
            @include('reports._tabs')
            <x-report-filters :action="route($prefix . '.sales')" :from="$from" :to="$to" type="sales" :channel="$selectedChannel ?? 'all'" />

            
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white border border-stone-200/90 rounded-2xl p-5 shadow-xs">
                    <p class="text-xs font-semibold uppercase tracking-wider text-stone-400">Omzet Bersih</p>
                    <p class="text-2xl font-bold text-stone-900 mt-1">@rupiah($s['net_sales'])</p>
                    <p class="text-xs text-stone-400 mt-1">Semua saluran sebelum pajak</p>
                </div>
                <div class="bg-white border border-stone-200/90 rounded-2xl p-5 shadow-xs">
                    <p class="text-xs font-semibold uppercase tracking-wider text-stone-400">Total Transaksi</p>
                    <p class="text-2xl font-bold text-stone-900 mt-1">{{ $s['transactions'] }}</p>
                    <p class="text-xs text-stone-400 mt-1">Rata-rata @rupiah($s['average']) / pesanan</p>
                </div>
                <div class="bg-white border border-stone-200/90 rounded-2xl p-5 shadow-xs">
                    <p class="text-xs font-semibold uppercase tracking-wider text-stone-400">Diskon Diberikan</p>
                    <p class="text-2xl font-bold text-stone-900 mt-1">@rupiah($s['discount'])</p>
                    <p class="text-xs text-stone-400 mt-1">Potongan harga promo</p>
                </div>
                <div class="bg-white border border-stone-200/90 rounded-2xl p-5 shadow-xs">
                    <p class="text-xs font-semibold uppercase tracking-wider text-stone-400">Total Diterima (Kotor)</p>
                    <p class="text-2xl font-bold text-stone-900 mt-1">@rupiah($s['gross_total'])</p>
                    <p class="text-xs text-stone-400 mt-1">Termasuk pajak @rupiah($s['tax'])</p>
                </div>
                @if ($isOwner)
                    <div class="bg-white border border-stone-200/90 rounded-2xl p-5 shadow-xs">
                        <p class="text-xs font-semibold uppercase tracking-wider text-stone-400">Pengeluaran Bahan</p>
                        <p class="text-2xl font-bold text-stone-900 mt-1">@rupiah($s['purchases'])</p>
                        <p class="text-xs text-stone-400 mt-1">PO diterima & diverifikasi</p>
                    </div>
                    <div class="bg-white border border-stone-200/90 rounded-2xl p-5 shadow-xs">
                        <p class="text-xs font-semibold uppercase tracking-wider text-stone-400">Laba Kotor</p>
                        <p
                            class="text-2xl font-bold mt-1 {{ $s['gross_profit'] < 0 ? 'text-rose-600' : 'text-emerald-700' }}">
                            @rupiah($s['gross_profit'])
                        </p>
                        <p class="text-xs text-stone-400 mt-1">Omzet bersih dikurangi belanja bahan</p>
                    </div>
                @endif
            </div>

            
            <div class="bg-white border border-stone-200/90 rounded-2xl p-5 shadow-xs">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-3 border-b border-stone-100">
                    <div>
                        <h3 class="font-bold text-base text-stone-900">Performa Saluran Penjualan</h3>
                        <p class="text-xs text-stone-500">Distribusi pendapatan dan pesanan dari Kasir Langsung, Reservasi Online, dan Pesan Antar</p>
                    </div>
                    @if (($selectedChannel ?? 'all') !== 'all')
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-900 border border-amber-200 w-fit">
                            Difilter: {{ $ch[$selectedChannel]['label'] ?? $selectedChannel }}
                        </span>
                    @endif
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                    
                    <div class="p-4 rounded-xl border border-stone-200 bg-stone-50/50 hover:bg-stone-50 transition">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-stone-500">Kasir POS (Langsung)</span>
                            <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-stone-200 text-stone-700">{{ $ch['pos']['percentage'] ?? ($ch['pos']['revenue_percent'] ?? 0) }}% Omzet</span>
                        </div>
                        <div class="mt-3 flex items-baseline justify-between">
                            <p class="text-xl font-bold text-stone-900">@rupiah($ch['pos']['gross_total'] ?? 0)</p>
                            <span class="text-xs font-medium text-stone-600">{{ $ch['pos']['transactions'] ?? 0 }} transaksi</span>
                        </div>
                        <div class="mt-2 text-[11px] text-stone-500 flex justify-between">
                            <span>Omzet Bersih:</span>
                            <span class="font-medium text-stone-700">@rupiah($ch['pos']['net_sales'] ?? 0)</span>
                        </div>
                    </div>

                    
                    <div class="p-4 rounded-xl border border-amber-200 bg-amber-50/40 hover:bg-amber-50/70 transition">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-amber-800">Reservasi Meja Online</span>
                            <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-amber-200 text-amber-900">{{ $ch['reservation']['percentage'] ?? ($ch['reservation']['revenue_percent'] ?? 0) }}% Omzet</span>
                        </div>
                        <div class="mt-3 flex items-baseline justify-between">
                            <p class="text-xl font-bold text-amber-950">@rupiah($ch['reservation']['gross_total'] ?? 0)</p>
                            <span class="text-xs font-medium text-amber-800">{{ $ch['reservation']['transactions'] ?? 0 }} reservasi</span>
                        </div>
                        <div class="mt-2 text-[11px] text-amber-700 flex justify-between">
                            <span>Omzet Bersih:</span>
                            <span class="font-medium text-amber-900">@rupiah($ch['reservation']['net_sales'] ?? 0)</span>
                        </div>
                    </div>

                    
                    <div class="p-4 rounded-xl border border-blue-200 bg-blue-50/40 hover:bg-blue-50/70 transition">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-blue-800">Pesan Antar (Delivery)</span>
                            <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-blue-200 text-blue-900">{{ $ch['delivery']['percentage'] ?? ($ch['delivery']['revenue_percent'] ?? 0) }}% Omzet</span>
                        </div>
                        <div class="mt-3 flex items-baseline justify-between">
                            <p class="text-xl font-bold text-blue-950">@rupiah($ch['delivery']['gross_total'] ?? 0)</p>
                            <span class="text-xs font-medium text-blue-800">{{ $ch['delivery']['transactions'] ?? 0 }} pesanan</span>
                        </div>
                        <div class="mt-2 text-[11px] text-blue-700 flex justify-between">
                            <span>Omzet Makanan Bersih:</span>
                            <span class="font-medium text-blue-900">@rupiah($ch['delivery']['net_sales'] ?? 0)</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                
                <div class="bg-white border border-stone-200/90 rounded-2xl p-5 shadow-xs">
                    <h3 class="font-bold text-base text-stone-900 pb-3 border-b border-stone-100">Per Metode Pembayaran</h3>
                    <div class="overflow-x-auto">
                        <table class="mt-2 min-w-full text-sm">
                            <thead class="text-left text-xs uppercase text-stone-600 font-semibold border-b border-stone-100">
                                <tr>
                                    <th class="py-2.5 font-semibold">Metode</th>
                                    <th class="py-2.5 text-right font-semibold">Transaksi</th>
                                    <th class="py-2.5 text-right font-semibold">Rincian Saluran</th>
                                    <th class="py-2.5 text-right font-semibold">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-stone-100 text-stone-800">
                                @foreach ($report['by_method'] as $m)
                                    <tr class="hover:bg-stone-50/60 transition">
                                        <td class="py-2.5 font-medium">
                                            <div class="flex items-center gap-2">
                                                <span class="font-semibold text-stone-900">{{ $m['label'] }}</span>
                                            </div>
                                        </td>
                                        <td class="py-2.5 text-right font-medium">{{ $m['transactions'] }}</td>
                                        <td class="py-2.5 text-right text-xs text-stone-500">
                                            <span>Kasir: @rupiah($m['pos_total'] ?? ($m['pos'] ?? 0))</span><br>
                                            <span class="text-amber-800">Reservasi: @rupiah($m['res_total'] ?? ($m['reservation'] ?? 0))</span><br>
                                            <span class="text-blue-700">Delivery: @rupiah($m['deliv_total'] ?? ($m['delivery'] ?? 0))</span>
                                        </td>
                                        <td class="py-2.5 text-right font-bold text-stone-900">@rupiah($m['total'])</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                
                <div class="bg-white border border-stone-200/90 rounded-2xl p-5 shadow-xs">
                    <h3 class="font-bold text-base text-stone-900 pb-3 border-b border-stone-100">Menu Terlaris (Semua Saluran)</h3>
                    <div class="overflow-x-auto">
                        <table class="mt-2 min-w-full text-sm">
                            <thead class="text-left text-xs uppercase text-stone-600 font-semibold border-b border-stone-100">
                                <tr>
                                    <th class="py-2.5 font-semibold">Menu</th>
                                    <th class="py-2.5 text-right font-semibold">Terjual</th>
                                    <th class="py-2.5 text-right font-semibold">Distribusi</th>
                                    <th class="py-2.5 text-right font-semibold">Pendapatan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-stone-100 text-stone-800">
                                @forelse ($report['top_products'] as $p)
                                    <tr class="hover:bg-stone-50/60 transition">
                                        <td class="py-2.5 font-medium text-stone-900">{{ $p['name'] }}</td>
                                        <td class="py-2.5 text-right font-semibold text-stone-900">{{ $p['qty'] ?? 0 }}</td>
                                        <td class="py-2.5 text-right text-xs text-stone-500">
                                            <span>POS {{ $p['pos_qty'] ?? 0 }}</span> ·
                                            <span class="text-amber-800">Res {{ $p['res_qty'] ?? 0 }}</span> ·
                                            <span class="text-blue-700">Deliv {{ $p['deliv_qty'] ?? 0 }}</span>
                                        </td>
                                        <td class="py-2.5 text-right font-bold text-amber-800">@rupiah($p['revenue'] ?? 0)</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-6 text-center text-stone-500 font-medium">Belum ada penjualan menu.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            @if ($isOwner)
                <div class="bg-white border border-stone-200/90 rounded-2xl p-5 shadow-xs">
                    <h3 class="font-bold text-base text-stone-900 pb-3 border-b border-stone-100">Penjualan Per Kasir & Staf Layanan</h3>
                    <div class="overflow-x-auto">
                        <table class="mt-2 min-w-full text-sm">
                            <thead class="text-left text-xs uppercase text-stone-600 font-semibold border-b border-stone-100">
                                <tr>
                                    <th class="py-2.5 font-semibold">Kasir / Operator</th>
                                    <th class="py-2.5 text-right font-semibold">Transaksi</th>
                                    <th class="py-2.5 text-right font-semibold">Omzet Bersih</th>
                                    <th class="py-2.5 text-right font-semibold">Total Nilai</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-stone-100 text-stone-800">
                                @forelse ($report['by_cashier'] as $c)
                                    <tr class="hover:bg-stone-50/60 transition">
                                        <td class="py-2.5 font-medium text-stone-900">{{ $c['name'] }}</td>
                                        <td class="py-2.5 text-right font-medium">{{ $c['transactions'] }}</td>
                                        <td class="py-2.5 text-right font-semibold text-stone-900">@rupiah($c['net_sales'])</td>
                                        <td class="py-2.5 text-right font-bold text-stone-900">@rupiah($c['gross_total'])</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-6 text-center text-stone-500 font-medium">Belum ada penjualan.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            
            <div class="bg-white border border-stone-200/90 rounded-2xl p-5 shadow-xs overflow-x-auto">
                <div class="flex items-center justify-between pb-3 border-b border-stone-100">
                    <h3 class="font-bold text-base text-stone-900">Rincian Harian Per Saluran</h3>
                    <span class="text-xs text-stone-500 font-medium">POS + Reservasi + Delivery</span>
                </div>
                <table class="mt-2 min-w-full text-sm">
                    <thead class="bg-stone-50 text-left text-xs uppercase text-stone-700 font-semibold border-b border-stone-100">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Tanggal</th>
                            <th class="px-4 py-3 text-right font-semibold">Kasir POS</th>
                            <th class="px-4 py-3 text-right font-semibold">Reservasi</th>
                            <th class="px-4 py-3 text-right font-semibold">Delivery</th>
                            <th class="px-4 py-3 text-right font-semibold">Total Trx</th>
                            <th class="px-4 py-3 text-right font-semibold">Diskon</th>
                            <th class="px-4 py-3 text-right font-semibold">Pajak</th>
                            <th class="px-4 py-3 text-right font-semibold">Total Omzet</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100 text-stone-800">
                        @forelse ($report['daily'] as $d)
                            <tr class="hover:bg-stone-50/60 transition">
                                <td class="px-4 py-3 font-medium text-stone-900">
                                    {{ \Carbon\Carbon::parse($d['date'])->translatedFormat('D, d M Y') }}
                                </td>
                                <td class="px-4 py-3 text-right text-xs">
                                    <span class="font-semibold text-stone-900">@rupiah($d['pos_sales'] ?? 0)</span>
                                    <span class="text-stone-400 block">({{ $d['pos_transactions'] ?? 0 }} trx)</span>
                                </td>
                                <td class="px-4 py-3 text-right text-xs">
                                    <span class="font-semibold text-amber-900">@rupiah($d['res_sales'] ?? 0)</span>
                                    <span class="text-stone-400 block">({{ $d['res_transactions'] ?? 0 }} trx)</span>
                                </td>
                                <td class="px-4 py-3 text-right text-xs">
                                    <span class="font-semibold text-blue-900">@rupiah($d['deliv_sales'] ?? 0)</span>
                                    <span class="text-stone-400 block">({{ $d['deliv_transactions'] ?? 0 }} trx)</span>
                                </td>
                                <td class="px-4 py-3 text-right font-bold text-stone-900">{{ $d['transactions'] ?? 0 }}</td>
                                <td class="px-4 py-3 text-right text-rose-600 font-medium">@rupiah($d['discount'] ?? 0)</td>
                                <td class="px-4 py-3 text-right font-medium">@rupiah($d['tax'] ?? 0)</td>
                                <td class="px-4 py-3 text-right font-bold text-stone-900">@rupiah($d['gross_total'] ?? 0)</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-stone-500 font-medium">Tidak ada penjualan pada periode ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</x-app-layout>
