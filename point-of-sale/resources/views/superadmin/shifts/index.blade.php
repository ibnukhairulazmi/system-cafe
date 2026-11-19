<x-app-layout>
    @php
        $selectClass = 'mt-1 w-full rounded-xl border border-stone-300 bg-white px-3 py-2 text-sm text-stone-900 shadow-xs focus:border-amber-600 focus:ring-2 focus:ring-amber-500/20 focus:outline-none';
        $money = fn ($v) => $v === null ? '-' : (($v < 0 ? '-' : ($v > 0 ? '+' : '')) . 'Rp ' . number_format(abs((float) $v), 0, ',', '.'));
        $moneyClass = fn ($v) => $v === null ? 'text-stone-400' : ((float) $v < 0 ? 'text-red-700' : ((float) $v > 0 ? 'text-amber-700' : 'text-stone-500'));
    @endphp

    <div class="py-6">
        <div class="w-full px-4 lg:px-6 space-y-5">
            <form method="GET" x-data
                @change="if (['SELECT'].includes($event.target.tagName) || ['date','checkbox'].includes($event.target.type)) $el.requestSubmit()"
                class="bg-white border border-stone-200/90 rounded-2xl p-4 shadow-xs grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3 items-end">
                <div>
                    <x-input-label for="from" value="Dari" />
                    <x-text-input id="from" name="from" type="date" class="mt-1 w-full" :value="request('from')" />
                </div>
                <div>
                    <x-input-label for="to" value="Sampai" />
                    <x-text-input id="to" name="to" type="date" class="mt-1 w-full" :value="request('to')" />
                </div>
                <div>
                    <x-input-label for="user_id" value="Kasir" />
                    <select id="user_id" name="user_id" class="{{ $selectClass }}">
                        <option value="">Semua Kasir</option>
                        @foreach ($cashiers as $c)
                            <option value="{{ $c->id }}" @selected((string) request('user_id') === (string) $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="status" value="Status" />
                    <select id="status" name="status" class="{{ $selectClass }}">
                        <option value="">Semua</option>
                        @foreach ($statuses as $s)
                            <option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ $s->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <a href="{{ route('superadmin.shifts.index') }}"
                        class="inline-flex items-center justify-center px-4 py-2.5 border border-stone-200/90 rounded-xl text-xs font-semibold text-stone-600 hover:text-stone-900 hover:bg-stone-50 transition w-full">
                        Reset
                    </a>
                </div>
            </form>

            <div class="bg-white border border-stone-200/90 rounded-2xl p-4 text-sm flex flex-wrap gap-8 shadow-xs">
                <div><span class="text-xs text-stone-500 block">Jumlah Shift</span> <strong
                        class="text-base text-stone-900">{{ $shifts->total() }} shift</strong></div>
                <div><span class="text-xs text-stone-500 block">Total Selisih Kas (shift tertutup)</span> <strong
                        class="text-base {{ $moneyClass($totalDifference) }}">{{ $money($totalDifference) }}</strong></div>
            </div>

            <div class="bg-white border border-stone-200/90 rounded-2xl shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-stone-50 text-left text-xs uppercase text-stone-400 border-b border-stone-100">
                            <tr>
                                <th class="px-5 py-3 font-semibold">Kasir</th>
                                <th class="px-5 py-3 font-semibold">Buka</th>
                                <th class="px-5 py-3 font-semibold">Tutup</th>
                                <th class="px-5 py-3 text-right font-semibold">Kas Awal</th>
                                <th class="px-5 py-3 text-right font-semibold">Kas Seharusnya</th>
                                <th class="px-5 py-3 text-right font-semibold">Kas Aktual</th>
                                <th class="px-5 py-3 text-right font-semibold">Selisih</th>
                                <th class="px-5 py-3 text-right font-semibold">Transaksi</th>
                                <th class="px-5 py-3 font-semibold">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            @forelse ($shifts as $s)
                                @php $diff = $s->closed_at ? $s->difference : null; @endphp
                                <tr class="hover:bg-stone-50/60 transition {{ $diff !== null && (float) $diff !== 0.0 ? 'bg-red-50/40' : '' }}">
                                    <td class="px-5 py-3.5 font-semibold">
                                        <a href="{{ route('superadmin.shifts.show', $s) }}"
                                            class="text-amber-800 hover:text-amber-900 hover:underline">{{ $s->user?->name ?? '-' }}</a>
                                    </td>
                                    <td class="px-5 py-3.5 text-stone-600">{{ $s->opened_at->translatedFormat('d M Y H:i') }}</td>
                                    <td class="px-5 py-3.5 text-stone-600">{{ $s->closed_at?->translatedFormat('d M Y H:i') ?? '-' }}</td>
                                    <td class="px-5 py-3.5 text-right text-stone-700">@rupiah($s->opening_cash)</td>
                                    <td class="px-5 py-3.5 text-right text-stone-700">{{ $s->closed_at ? 'Rp ' . number_format((float) $s->expected_cash, 0, ',', '.') : '-' }}</td>
                                    <td class="px-5 py-3.5 text-right text-stone-700">{{ $s->closed_at ? 'Rp ' . number_format((float) $s->closing_cash, 0, ',', '.') : '-' }}</td>
                                    <td class="px-5 py-3.5 text-right font-bold {{ $moneyClass($diff) }}">{{ $money($diff) }}</td>
                                    <td class="px-5 py-3.5 text-right text-stone-700">{{ $s->transactions_count }}</td>
                                    <td class="px-5 py-3.5"><x-status :value="$s->status" /></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-5 py-10 text-center text-stone-400">Tidak ada shift ditemukan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{ $shifts->links() }}
        </div>
    </div>
</x-app-layout>