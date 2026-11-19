<x-app-layout>

    <div class="py-6">
        <div class="w-full px-4 lg:px-6 space-y-4">
            <x-flash />

            
            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4 xl:gap-4">
                @foreach ($cards as $c)
                    <a href="{{ $c['url'] }}"
                        class="group flex h-full min-w-0 flex-col justify-between gap-4 rounded-2xl border border-stone-200 bg-white p-4 shadow-xs transition hover:border-amber-600 xl:p-5">
                        
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-xs font-medium text-stone-500 xl:text-sm">{{ $c['label'] }}</p>

                                <span class="inline-flex shrink-0 items-center gap-1 whitespace-nowrap rounded-full border px-2 py-0.5 text-xs font-medium {{ $c['badge_class'] }}">
                                    @if ($c['ok'])
                                        
                                        <svg style="width: 12px; height: 12px;" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="22 7 13.5 15.5 8.5 10.5 2 17" />
                                            <polyline points="16 7 22 7 22 13" />
                                        </svg>
                                    @else
                                        
                                        <svg style="width: 12px; height: 12px;" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="22 17 13.5 8.5 8.5 13.5 2 7" />
                                            <polyline points="16 17 22 17 22 11" />
                                        </svg>
                                    @endif
                                    <span>{{ $c['badge'] }}</span>
                                </span>
                            </div>

                            <p class="text-2xl font-bold tracking-tight tabular-nums xl:text-3xl {{ $c['value_class'] }}">
                                {{ $c['value'] }}
                            </p>
                        </div>

                        
                        <div class="space-y-1">
                            <div class="flex items-center gap-1.5">
                                <p class="text-xs font-semibold text-stone-900 xl:text-sm">
                                    {{ $c['title'] }}
                                </p>

                                @if ($c['ok'])
                                    <svg style="width: 14px; height: 14px;" class="shrink-0 text-stone-700 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="22 7 13.5 15.5 8.5 10.5 2 17" />
                                        <polyline points="16 7 22 7 22 13" />
                                    </svg>
                                @else
                                    <svg style="width: 14px; height: 14px;" class="shrink-0 text-stone-700 transition-transform group-hover:translate-x-0.5 group-hover:translate-y-0.5"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="22 17 13.5 8.5 8.5 13.5 2 7" />
                                        <polyline points="16 17 22 17 22 11" />
                                    </svg>
                                @endif
                            </div>

                            <p class="text-xs text-stone-500">
                                {{ $c['desc'] }}
                            </p>
                        </div>
                    </a>
                @endforeach
            </div>

            
            <div class="bg-white border border-stone-200/90 rounded-2xl p-4 shadow-xs">
                <div class="flex items-center gap-2 pb-3">
                    <h3 class="font-bold text-amber-900">Perlu Ditindaklanjuti</h3>
                    <span
                        class="rounded-full bg-stone-200 px-1.5 text-xs font-semibold text-stone-600">{{ $actions->count() }}</span>
                </div>

                @if ($actions->isNotEmpty())
                    <div class="grid grid-cols-1 gap-2 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ($actions as $a)
                            <a href="{{ $a['url'] }}"
                                class="group flex items-center justify-between gap-3 rounded-lg border border-stone-200 px-3 py-2 hover:border-amber-600 transition">
                                <div class="flex min-w-0 items-center gap-2">
                                    <span class="size-2 shrink-0 rounded-full {{ $a['dot'] }}"></span>
                                    <p class="truncate text-sm text-stone-700">{{ $a['label'] }}</p>
                                </div>
                                <div class="flex shrink-0 items-center gap-2">
                                    <span class="text-base font-bold {{ $a['text'] }}">{{ $a['count'] }}</span>
                                    <span class="text-xs font-semibold text-amber-800 group-hover:underline">
                                        {{ $a['cta'] }}
                                    </span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @else
                    <p class="py-2 text-sm text-stone-400">Tidak ada yang perlu ditindaklanjuti saat ini.</p>
                @endif
            </div>

            
            <div x-data="{ tab: 'today' }" class="space-y-3">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="inline-flex max-w-full overflow-x-auto rounded-lg bg-stone-100 p-1 gap-1">
                        <button type="button" @click="tab = 'today'"
                            :class="tab === 'today' ? 'bg-white text-stone-900 shadow-xs border-stone-200' :
                                'text-stone-500 hover:text-stone-900 border-transparent'"
                            class="inline-flex items-center gap-2 whitespace-nowrap rounded-md border px-3 py-1.5 text-sm font-medium transition">
                            Reservasi Hari Ini
                            <span
                                class="rounded-full bg-stone-200 px-1.5 text-xs font-semibold text-stone-600">{{ $todayCount }}</span>
                        </button>
                        <button type="button" @click="tab = 'latest'"
                            :class="tab === 'latest' ? 'bg-white text-stone-900 shadow-xs border-stone-200' :
                                'text-stone-500 hover:text-stone-900 border-transparent'"
                            class="inline-flex items-center gap-2 whitespace-nowrap rounded-md border px-3 py-1.5 text-sm font-medium transition">
                            Reservasi Baru Dipesan
                            <span
                                class="rounded-full bg-stone-200 px-1.5 text-xs font-semibold text-stone-600">{{ $latestCount }}</span>
                        </button>
                        <button type="button" @click="tab = 'delivery'"
                            :class="tab === 'delivery' ? 'bg-white text-stone-900 shadow-xs border-stone-200' :
                                'text-stone-500 hover:text-stone-900 border-transparent'"
                            class="inline-flex items-center gap-2 whitespace-nowrap rounded-md border px-3 py-1.5 text-sm font-medium transition">
                            Delivery Aktif
                            <span
                                class="rounded-full bg-stone-200 px-1.5 text-xs font-semibold text-stone-600">{{ $deliveryCount }}</span>
                        </button>
                    </div>

                    <a x-show="tab !== 'delivery'" href="{{ route('admin.reservations.index') }}"
                        class="inline-flex items-center rounded-lg border border-stone-200 bg-white px-3 py-1.5 text-sm font-medium text-amber-800 shadow-xs hover:border-amber-600 hover:text-amber-900 transition">
                        Lihat semua reservasi
                    </a>
                    <a x-show="tab === 'delivery'" x-cloak href="{{ route('admin.delivery-orders.index') }}"
                        class="inline-flex items-center rounded-lg border border-stone-200 bg-white px-3 py-1.5 text-sm font-medium text-amber-800 shadow-xs hover:border-amber-600 hover:text-amber-900 transition">
                        Lihat semua delivery
                    </a>
                </div>

                
                <div x-show="tab === 'today'"
                    class="overflow-hidden rounded-2xl border border-stone-200/90 bg-white shadow-xs">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-stone-50 text-left text-xs uppercase text-black font-semibold border-b border-stone-100">
                                <tr>
                                    <th class="px-4 py-2.5 font-semibold text-black">Kode</th>
                                    <th class="px-4 py-2.5 font-semibold text-black">Jam</th>
                                    <th class="px-4 py-2.5 font-semibold text-black">Nama</th>
                                    <th class="px-4 py-2.5 font-semibold text-black">Tamu</th>
                                    <th class="px-4 py-2.5 font-semibold text-black">Menu</th>
                                    <th class="px-4 py-2.5 font-semibold text-black">Meja</th>
                                    <th class="px-4 py-2.5 font-semibold text-black">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-stone-100 text-black">
                                @forelse ($todayReservations as $r)
                                    <tr class="text-sm text-black">
                                        <td class="px-4 py-2.5 whitespace-nowrap text-black font-medium">{{ $r->code }}</td>
                                        <td class="px-4 py-2.5 whitespace-nowrap text-black font-medium">
                                            {{ substr($r->reserved_time, 0, 5) }} WIB
                                            @if ($loop->first)
                                                <span
                                                    class="ml-1 rounded-full bg-amber-100 px-1.5 py-0.5 text-xs font-semibold text-amber-800">Berikutnya</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2.5 text-black font-medium">{{ $r->name }}</td>
                                        <td class="px-4 py-2.5 whitespace-nowrap text-black font-medium">{{ $r->partyLabel() }}</td>
                                        <td class="px-4 py-2.5 text-black font-medium">
                                            @if ($r->menu_text)
                                                {{ $r->menu_text }}
                                            @else
                                                <span class="text-black font-normal">Tidak ada</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2.5 whitespace-nowrap text-black font-medium">
                                            @if ($r->table?->name)
                                                {{ $r->table->name }}
                                            @else
                                                <span class="text-black font-normal">Belum dipilih</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2.5">
                                            <span
                                                class="inline-flex rounded-full text-sm font-medium {{ $r->status->value }}">
                                                {{ $r->status->label() }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="py-6 text-center text-black font-medium">
                                            Tidak ada reservasi tersisa untuk hari ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                
                <div x-show="tab === 'latest'" x-cloak
                    class="overflow-hidden rounded-2xl border border-stone-200/90 bg-white shadow-xs">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-stone-50 text-left text-xs uppercase text-black font-semibold border-b border-stone-100">
                                <tr>
                                    <th class="px-4 py-2.5 font-semibold text-black">Kode</th>
                                    <th class="px-4 py-2.5 font-semibold text-black">Nama</th>
                                    <th class="px-4 py-2.5 font-semibold text-black">Jadwal</th>
                                    <th class="px-4 py-2.5 font-semibold text-black">Tamu</th>
                                    <th class="px-4 py-2.5 font-semibold text-black">Status</th>
                                    <th class="px-4 py-2.5 font-semibold text-black">Dipesan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-stone-100 text-black">
                                @forelse ($latestReservations as $r)
                                    <tr class="text-black">
                                        <td class="px-4 py-2.5 font-mono text-xs text-black font-medium">{{ $r->code }}</td>
                                        <td class="px-4 py-2.5 font-medium text-black">{{ $r->name }}</td>
                                        <td class="px-4 py-2.5 text-black">
                                            <div class="font-medium text-black">
                                                {{ $r->reserved_date->translatedFormat('D, d M Y') }}</div>
                                            <div class="text-xs text-black font-medium">
                                                {{ substr($r->reserved_time, 0, 5) }} WIB
                                            </div>
                                        </td>
                                        <td class="px-4 py-2.5 text-black font-medium">{{ $r->partyLabel() }}</td>
                                        <td class="px-4 py-2.5">
                                            <span
                                                class="inline-flex rounded-full text-xs font-semibold {{ $r->status->value }}">
                                                {{ $r->status->label() }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-2.5 text-xs text-black font-medium">
                                            {{ $r->created_at->diffForHumans() }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="py-6 text-center text-black font-medium">Belum ada reservasi
                                            yang akan datang.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                
                <div x-show="tab === 'delivery'" x-cloak
                    class="overflow-hidden rounded-2xl border border-stone-200/90 bg-white shadow-xs">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-stone-50 text-left text-xs uppercase text-black font-semibold border-b border-stone-100">
                                <tr>
                                    <th class="px-4 py-2.5 font-semibold text-black">Kode</th>
                                    <th class="px-4 py-2.5 font-semibold text-black">Dipesan</th>
                                    <th class="px-4 py-2.5 font-semibold text-black">Pelanggan</th>
                                    <th class="px-4 py-2.5 font-semibold text-black">Alamat</th>
                                    <th class="px-4 py-2.5 font-semibold text-black">Menu</th>
                                    <th class="px-4 py-2.5 font-semibold text-black">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-stone-100 text-black">
                                @forelse ($activeDeliveries as $d)
                                    <tr class="text-sm text-black">
                                        <td class="px-4 py-2.5 font-mono text-xs text-black font-medium whitespace-nowrap">
                                            {{ $d->display_code }}</td>
                                        <td class="px-4 py-2.5 text-xs text-black font-medium whitespace-nowrap">
                                            {{ $d->created_at?->diffForHumans() ?? '-' }}
                                            @if ($loop->first)
                                                <span
                                                    class="ml-1 rounded-full bg-amber-100 px-1.5 py-0.5 font-semibold text-amber-800">Terlama</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2.5 font-medium text-black">{{ $d->display_name }}</td>
                                        <td class="px-4 py-2.5 text-black font-medium">{{ $d->display_address }}</td>
                                        <td class="px-4 py-2.5 text-black font-medium">
                                            @if ($d->menu_text)
                                                {{ $d->menu_text }}
                                            @else
                                                <span class="text-black font-normal">Tidak ada</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2.5">
                                            <span
                                                class="inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800">
                                                {{ $d->status_text ?: '-' }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="py-6 text-center text-black font-medium">
                                            Tidak ada delivery order yang masih aktif.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            
            <div class="space-y-3">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <h3 class="text-base font-bold text-amber-800">Stok Bahan Menipis</h3>
                        <span
                            class="rounded-full bg-stone-200 px-1.5 text-xs font-semibold text-stone-600">{{ $lowStockCount }}</span>
                    </div>
                    <a href="{{ route('admin.purchase-orders.create') }}"
                        class="inline-flex items-center rounded-lg border border-stone-200 bg-white px-3 py-1.5 text-sm font-medium text-amber-800 shadow-xs hover:border-amber-600 hover:text-amber-900 transition">
                        + Buat pesanan bahan
                    </a>
                </div>

                <div class="overflow-hidden rounded-2xl border border-stone-200/90 bg-white shadow-xs">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-stone-50 text-left text-xs uppercase text-black font-semibold border-b border-stone-100">
                                <tr>
                                    <th class="px-4 py-2.5 font-semibold text-black">Bahan</th>
                                    <th class="px-4 py-2.5 text-right font-semibold text-black">Stok Saat Ini</th>
                                    <th class="px-4 py-2.5 text-right font-semibold text-black">Batas Minimum</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-stone-100 text-black">
                                @forelse ($lowStock as $ing)
                                    <tr class="text-black">
                                        <td class="px-4 py-2.5 font-medium text-black">{{ $ing->name }}</td>
                                        <td
                                            class="px-4 py-2.5 text-right font-semibold {{ (float) $ing->stock <= 0 ? 'text-rose-600' : 'text-amber-700' }}">
                                            @qty($ing->stock) {{ $ing->unit }}</td>
                                        <td class="px-4 py-2.5 text-right text-black font-medium">@qty($ing->min_stock)
                                            {{ $ing->unit }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="py-6 text-center text-black font-medium">Semua stok bahan
                                            dalam kondisi aman.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>