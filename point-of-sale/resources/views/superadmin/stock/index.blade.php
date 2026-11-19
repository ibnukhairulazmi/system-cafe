<x-app-layout>
    @php
        $selectClass = 'mt-1 w-full rounded-xl border border-stone-300 bg-white px-3 py-2 text-sm text-stone-900 shadow-xs focus:border-amber-600 focus:ring-2 focus:ring-amber-500/20 focus:outline-none';
        $qty = fn ($v) => $v === null ? '-' : rtrim(rtrim(number_format((float) $v, 3, ',', '.'), '0'), ',');
    @endphp

    <div class="py-6">
        <div class="w-full px-4 lg:px-6 space-y-5">
            <form method="GET" x-data
                @change="if (['SELECT'].includes($event.target.tagName) || ['date','checkbox'].includes($event.target.type)) $el.requestSubmit()"
                class="bg-white border border-stone-200/90 rounded-2xl p-4 shadow-xs flex flex-wrap items-end gap-3">
                @if (request('ingredient_id'))
                    <input type="hidden" name="ingredient_id" value="{{ request('ingredient_id') }}">
                @endif
                <div class="w-full lg:w-auto lg:flex-1 lg:min-w-[200px]">
                    <x-input-label for="search" value="Cari Bahan" />
                    <x-text-input id="search" name="search" type="text" class="mt-1 w-full"
                        placeholder="Nama bahan" :value="request('search')" />
                </div>
                <div class="w-full sm:w-[170px]">
                    <x-input-label for="type" value="Jenis Mutasi" />
                    <select id="type" name="type" class="{{ $selectClass }}">
                        <option value="">Semua</option>
                        @foreach ($types as $t)
                            <option value="{{ $t->value }}" @selected(request('type') === $t->value)>{{ $t->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-full sm:w-[160px]">
                    <x-input-label for="from" value="Dari" />
                    <x-text-input id="from" name="from" type="date" class="mt-1 w-full" :value="request('from')" />
                </div>
                <div class="w-full sm:w-[160px]">
                    <x-input-label for="to" value="Sampai" />
                    <x-text-input id="to" name="to" type="date" class="mt-1 w-full" :value="request('to')" />
                </div>
                <div class="w-full sm:w-[130px] space-y-1.5 pb-1">
                    <label class="flex items-center gap-2 text-xs font-semibold text-stone-600 whitespace-nowrap">
                        <input type="checkbox" name="low_only" value="1" @checked(request()->boolean('low_only'))
                            class="rounded border-stone-300 text-amber-700 focus:ring-amber-500/20">
                        Stok menipis
                    </label>
                    <label class="flex items-center gap-2 text-xs font-semibold text-stone-600 whitespace-nowrap">
                        <input type="checkbox" name="mismatch_only" value="1" @checked(request()->boolean('mismatch_only'))
                            class="rounded border-stone-300 text-amber-700 focus:ring-amber-500/20">
                        Tidak sinkron
                    </label>
                </div>
                <div class="w-full sm:w-[100px]">
                    <a href="{{ route('superadmin.stock.index') }}"
                        class="inline-flex items-center justify-center px-4 py-2.5 border border-stone-200/90 rounded-xl text-xs font-semibold text-stone-600 hover:text-stone-900 hover:bg-stone-50 transition w-full">
                        Reset
                    </a>
                </div>
            </form>
            
            <div class="bg-white border border-stone-200/90 rounded-2xl shadow-xs overflow-hidden">
                <div class="px-5 py-3 text-sm font-semibold text-stone-900 border-b border-stone-100">Stok Bahan</div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-stone-50 text-left text-xs uppercase text-stone-400 border-b border-stone-100">
                            <tr>
                                <th class="px-5 py-3 font-semibold">Bahan</th>
                                <th class="px-5 py-3 text-right font-semibold">Stok Sistem</th>
                                <th class="px-5 py-3 text-right font-semibold">Stok Cafe</th>
                                <th class="px-5 py-3 text-right font-semibold">Stok Minimum</th>
                                <th class="px-5 py-3 font-semibold">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            @forelse ($ingredients as $i)
                                @php
                                    $mismatch = $i->ledger_stock !== null && abs((float) $i->ledger_stock - (float) $i->stock) > 0.0005;
                                    $low = (float) $i->stock <= (float) $i->min_stock;
                                @endphp
                                <tr class="hover:bg-stone-50/60 transition {{ $mismatch ? 'bg-red-50/40' : '' }}">
                                    <td class="px-5 py-3.5 font-semibold">
                                        <a href="{{ route('superadmin.stock.index', ['ingredient_id' => $i->id]) }}"
                                            class="text-amber-800 hover:text-amber-900 hover:underline">{{ $i->name }}</a>
                                    </td>
                                    <td class="px-5 py-3.5 text-right font-bold text-stone-900">{{ $qty($i->stock) }}</td>
                                    <td class="px-5 py-3.5 text-right text-stone-700">{{ $qty($i->ledger_stock) }}</td>
                                    <td class="px-5 py-3.5 text-right text-stone-500">{{ $qty($i->min_stock) }}</td>
                                    <td class="px-5 py-3.5 space-x-1">
                                        @if ($mismatch)
                                            <span class="rounded-md bg-red-100 px-1.5 py-0.5 text-[11px] font-semibold text-red-700">Tidak sinkron</span>
                                        @endif
                                        @if ($low)
                                            <span class="rounded-md bg-amber-100 px-1.5 py-0.5 text-[11px] font-semibold text-amber-800">Menipis</span>
                                        @endif
                                        @if (! $mismatch && ! $low)
                                            <span class="text-stone-400">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-5 py-10 text-center text-stone-400">Tidak ada bahan ditemukan.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{ $ingredients->links() }}

            
            <div class="bg-white border border-stone-200/90 rounded-2xl shadow-xs overflow-hidden">
                <div class="px-5 py-3 text-sm font-semibold text-stone-900 border-b border-stone-100 flex items-center justify-between">
                    <span>Riwayat Mutasi Stok</span>
                    @if (request('ingredient_id'))
                        <a href="{{ route('superadmin.stock.index', request()->except('ingredient_id', 'movements_page')) }}"
                            class="text-xs font-semibold text-stone-600 hover:text-stone-900">Tampilkan semua bahan</a>
                    @endif
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-stone-50 text-left text-xs uppercase text-stone-400 border-b border-stone-100">
                            <tr>
                                <th class="px-5 py-3 font-semibold">Waktu</th>
                                <th class="px-5 py-3 font-semibold">Bahan</th>
                                <th class="px-5 py-3 font-semibold">Jenis</th>
                                <th class="px-5 py-3 text-right font-semibold">Jumlah</th>
                                <th class="px-5 py-3 text-right font-semibold">Stok Setelahnya</th>
                                <th class="px-5 py-3 font-semibold">Pelaku</th>
                                <th class="px-5 py-3 font-semibold">Referensi</th>
                                <th class="px-5 py-3 font-semibold">Catatan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            @forelse ($movements as $m)
                                <tr class="hover:bg-stone-50/60 transition">
                                    <td class="px-5 py-3.5 text-stone-600">{{ $m->created_at->translatedFormat('d M Y H:i') }}</td>
                                    <td class="px-5 py-3.5 font-semibold text-stone-900">{{ $m->ingredient?->name ?? '-' }}</td>
                                    <td class="px-5 py-3.5 text-stone-700">{{ $m->type->label() }}</td>
                                    <td class="px-5 py-3.5 text-right font-bold text-stone-900">{{ $qty($m->quantity) }}</td>
                                    <td class="px-5 py-3.5 text-right text-stone-700">{{ $qty($m->stock_after) }}</td>
                                    <td class="px-5 py-3.5 text-stone-700">{{ $m->user?->name ?? '-' }}</td>
                                    <td class="px-5 py-3.5 text-stone-500">
                                        {{ $m->reference_type ? class_basename($m->reference_type) . ' #' . $m->reference_id : '-' }}
                                    </td>
                                    <td class="px-5 py-3.5 text-stone-500">{{ $m->note ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="px-5 py-10 text-center text-stone-400">Tidak ada mutasi stok ditemukan.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{ $movements->links() }}
        </div>
    </div>
</x-app-layout>