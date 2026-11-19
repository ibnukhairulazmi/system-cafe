<x-app-layout>
    @php
        $total     = $tables->count();
        $occupied  = $tables->where('is_occupied', true)->count();
        $available = $total - $occupied;
        $grouped   = $tables->groupBy(fn ($t) => $t->area ?: 'Lainnya');
    @endphp

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-flash />

            <div class="mt-2 space-y-6">
                
                <div class="grid grid-cols-3 gap-3.5">
                    <div class="bg-white border border-stone-200/90 rounded-2xl p-4 shadow-xs">
                        <p class="text-xs font-medium text-stone-500">Total Meja</p>
                        <p class="mt-1 text-2xl font-bold text-stone-900">{{ $total }}</p>
                    </div>
                    <div class="bg-white border border-stone-200/90 rounded-2xl p-4 shadow-xs">
                        <p class="text-xs font-medium text-stone-500">Kosong</p>
                        <p class="mt-1 text-2xl font-bold text-emerald-700">{{ $available }}</p>
                    </div>
                    <div class="bg-white border border-stone-200/90 rounded-2xl p-4 shadow-xs">
                        <p class="text-xs font-medium text-stone-500">Terisi</p>
                        <p class="mt-1 text-2xl font-bold text-rose-600">{{ $occupied }}</p>
                    </div>
                </div>

                @forelse ($grouped as $area => $areaTables)
                    <section class="space-y-3">
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-stone-500">
                            {{ $area }}
                            <span class="ml-1 font-medium normal-case text-stone-400">({{ $areaTables->count() }} meja)</span>
                        </h3>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3.5">
                            @foreach ($areaTables as $t)
                                @php
                                    $next = $t->next_reservation;

                                    if ($t->is_occupied) {
                                        $cardClass  = 'bg-rose-50 border-rose-200';
                                        $badgeClass = 'bg-rose-100 text-rose-700';
                                        $badgeText  = 'Terisi';
                                    } elseif ($t->reserved_soon) {
                                        $cardClass  = 'bg-amber-50 border-amber-200';
                                        $badgeClass = 'bg-amber-100 text-amber-800';
                                        $badgeText  = 'Segera Dipesan';
                                    } else {
                                        $cardClass  = 'bg-white border-stone-200/90';
                                        $badgeClass = 'bg-emerald-100 text-emerald-700';
                                        $badgeText  = 'Kosong';
                                    }
                                @endphp

                                <div class="flex flex-col rounded-2xl border p-4 shadow-xs {{ $cardClass }}">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0">
                                            <p class="font-semibold text-stone-900 truncate">{{ $t->name }}</p>
                                            <p class="text-xs text-stone-500">Kapasitas {{ $t->capacity }} orang</p>
                                        </div>
                                        <span class="shrink-0 rounded-lg px-2 py-0.5 text-xs font-semibold {{ $badgeClass }}">
                                            {{ $badgeText }}
                                        </span>
                                    </div>

                                    
                                    <div class="mt-3 min-h-10 space-y-1 text-xs text-stone-600">
                                        @if ($t->is_occupied && $t->occupied_at)
                                            <p>
                                                Terisi sejak <span class="font-semibold text-stone-800">{{ $t->occupied_at->format('H:i') }}</span>
                                                ({{ $t->occupied_at->diffForHumans(null, true) }})
                                            </p>
                                        @endif

                                        @if ($next)
                                            <p class="{{ $t->reserved_soon ? 'font-semibold text-amber-800' : '' }}">
                                                Reservasi {{ substr($next->reserved_time, 0, 5) }} &middot; {{ $next->name }}
                                            </p>
                                        @endif
                                    </div>

                                    
                                    <div class="mt-4 flex flex-wrap gap-2">
                                        @if ($t->is_occupied)
                                            <form method="POST" action="{{ route('kasir.tables.release', $t) }}" class="flex-1">
                                                @csrf
                                                <button type="submit"
                                                    class="w-full rounded-xl bg-amber-700 py-2.5 text-xs font-semibold text-white shadow-xs hover:bg-amber-800 active:bg-amber-900 focus:outline-none focus:ring-2 focus:ring-amber-500/20 transition duration-150">
                                                    Kosongkan
                                                </button>
                                            </form>
                                        @else
                                            @if ($next)
                                                <form method="POST" action="{{ route('kasir.reservations.check-in', $next) }}" class="flex-1">
                                                    @csrf
                                                    <button type="submit"
                                                        class="w-full rounded-xl bg-amber-700 py-2.5 text-xs font-semibold text-white shadow-xs hover:bg-amber-800 active:bg-amber-900 focus:outline-none focus:ring-2 focus:ring-amber-500/20 transition duration-150">
                                                        Check-in Tamu
                                                    </button>
                                                </form>
                                            @endif

                                            <form method="POST" action="{{ route('kasir.tables.occupy', $t) }}" class="flex-1"
                                                @if ($t->reserved_soon)
                                                    onsubmit="return confirm('{{ $t->name }} direservasi pukul {{ substr($next->reserved_time, 0, 5) }}. Tetap tandai terisi?')"
                                                @endif>
                                                @csrf
                                                @if ($t->reserved_soon)
                                                    <input type="hidden" name="force" value="1">
                                                @endif
                                                <button type="submit"
                                                    class="w-full rounded-xl border border-stone-200 bg-stone-50 py-2.5 text-xs font-semibold text-stone-700 hover:bg-stone-100 hover:border-stone-300 focus:outline-none focus:ring-2 focus:ring-amber-500/20 transition duration-150">
                                                    Tandai Terisi
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @empty
                    <div class="py-12 text-center text-stone-400 bg-white rounded-2xl border border-stone-200/90 p-6">
                        <p class="text-sm">Belum ada meja aktif.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
