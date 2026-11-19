<x-app-layout>
    @php
        $selectClass = 'mt-1 w-full rounded-xl border border-stone-300 bg-white px-3 py-2 text-sm text-stone-900 shadow-xs focus:border-amber-600 focus:ring-2 focus:ring-amber-500/20 focus:outline-none';
        $confirmed = \App\Enums\ReservationStatus::Confirmed;
    @endphp

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            <x-flash />
            <x-errors-box />

            <form method="GET" x-data
                @change="if ($event.target.tagName === 'SELECT' || $event.target.type === 'date') $el.requestSubmit()"
                @input.debounce.300ms="if ($event.target.name === 'search') $el.requestSubmit()"
                class="bg-white border border-stone-200/90 rounded-2xl p-4 shadow-xs flex flex-wrap gap-4 items-end">
                <div class="min-w-200">
                    <x-input-label for="search" value="Cari" />
                    <x-text-input id="search" name="search" type="text" class="mt-1 w-full"
                        placeholder="Kode / nama / telepon" :value="request('search')"
                        x-init="if ($el.value) { $el.focus(); $el.setSelectionRange($el.value.length, $el.value.length) }" />
                </div>
                <div>
                    <x-input-label for="date" value="Tanggal Reservasi" />
                    <x-text-input id="date" name="date" type="date" class="mt-1 w-full" :value="$date" />
                </div>
                <div>
                    <a href="{{ route('kasir.reservations.index') }}"
                        class="inline-flex items-center justify-center px-4 py-2.5 border border-stone-200/90 rounded-xl text-xs font-semibold text-stone-600 hover:text-stone-900 hover:bg-stone-50 transition w-full">
                        Hari Ini
                    </a>
                </div>
            </form>

            <div class="bg-white border border-stone-200/90 rounded-2xl shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-stone-50 text-left text-xs uppercase text-stone-400 border-b border-stone-100">
                            <tr>
                                <th class="px-5 py-3 font-semibold">Jam</th>
                                <th class="px-5 py-3 font-semibold">Kode</th>
                                <th class="px-5 py-3 font-semibold">Pelanggan</th>
                                <th class="px-5 py-3 font-semibold">Meja</th>
                                <th class="px-5 py-3 font-semibold">Status</th>
                                <th class="px-5 py-3 text-right font-semibold">Total</th>
                                <th class="px-5 py-3 text-right font-semibold">Sisa Tagihan</th>
                                <th class="px-5 py-3 text-right font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            @forelse ($reservations as $r)
                                @php
                                    $isConfirmed = $r->status === $confirmed;
                                    $isActive = in_array($r->status, \App\Enums\ReservationStatus::active(), true);
                                    $remaining = $r->remaining_amount;
                                @endphp
                                <tr class="hover:bg-stone-50/60 transition">
                                    <td class="px-5 py-3.5 font-bold text-stone-900">{{ substr((string) $r->reserved_time, 0, 5) }}</td>
                                    <td class="px-5 py-3.5 font-semibold">
                                        <a href="{{ route('kasir.reservations.show', $r) }}"
                                            class="text-amber-800 hover:text-amber-900 hover:underline">{{ $r->code }}</a>
                                    </td>
                                    <td class="px-5 py-3.5 text-stone-700">
                                        {{ $r->name }}
                                        <span class="block text-xs text-stone-400">{{ $r->partyLabel() }} · {{ $r->phone }}</span>
                                    </td>
                                    <td class="px-5 py-3.5 text-stone-700">{{ $r->table?->name ?? '-' }}</td>
                                    <td class="px-5 py-3.5">
                                        <x-status :value="$r->status" />
                                        @if ($r->checked_in_at)
                                            <span class="block mt-1 text-[11px] font-semibold text-emerald-700">Check-in {{ $r->checked_in_at->format('H:i') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5 text-right text-stone-700">@rupiah($r->total_amount)</td>
                                    <td class="px-5 py-3.5 text-right font-bold {{ $remaining > 0 ? 'text-rose-600' : 'text-emerald-700' }}">
                                        {{ $remaining > 0 ? 'Rp ' . number_format($remaining, 0, ',', '.') : 'Lunas' }}
                                    </td>
                                    <td class="px-5 py-3.5">
                                        <div class="flex justify-end items-center gap-2">
                                            
                                            @if ($isConfirmed && ! $r->checked_in_at)
                                                <form method="POST" action="{{ route('kasir.reservations.check-in', $r) }}"
                                                    onsubmit="return confirm('Check-in tamu {{ e($r->name) }}?')">
                                                    @csrf
                                                    <button class="inline-flex items-center rounded-lg px-3 py-1.5 text-xs font-semibold bg-amber-700 text-white hover:bg-amber-800 transition">Check-in</button>
                                                </form>
                                            @endif

                                            
                                            @if ($r->status === \App\Enums\ReservationStatus::Seated && $remaining <= 0)
                                                <form method="POST" action="{{ route('kasir.reservations.complete', $r) }}"
                                                    onsubmit="return confirm('Selesaikan reservasi {{ e($r->code) }}? Pastikan tamu sudah keluar dan meja dikosongkan.')">
                                                    @csrf
                                                    <button class="inline-flex items-center rounded-lg px-3 py-1.5 text-xs font-semibold bg-emerald-700 text-white hover:bg-emerald-800 transition">Selesai</button>
                                                </form>
                                            @endif

                                            <a href="{{ route('kasir.reservations.show', $r) }}"
                                                class="inline-flex items-center rounded-lg px-3 py-1.5 text-xs font-semibold border border-stone-300 text-stone-700 hover:bg-stone-50 transition">
                                                {{ $isActive && $remaining > 0 ? 'Pelunasan' : 'Detail' }}
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-5 py-10 text-center text-stone-400">Tidak ada reservasi pada tanggal ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{ $reservations->links() }}
        </div>
    </div>
</x-app-layout>