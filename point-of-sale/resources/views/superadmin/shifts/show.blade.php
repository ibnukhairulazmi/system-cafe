<x-app-layout>
    @php
        $money = fn ($v) => $v === null ? '-' : (($v < 0 ? '-' : ($v > 0 ? '+' : '')) . 'Rp ' . number_format(abs((float) $v), 0, ',', '.'));
        $moneyClass = fn ($v) => $v === null ? 'text-stone-400' : ((float) $v < 0 ? 'text-red-700' : ((float) $v > 0 ? 'text-amber-700' : 'text-stone-500'));
        $diff = $shift->closed_at ? $shift->difference : null;
    @endphp

    <div class="py-6">
        <div class="w-full px-4 lg:px-6 space-y-5">
            <div class="flex items-center justify-between">
                <h1 class="text-lg font-bold text-stone-900">Shift {{ $shift->user?->name ?? '-' }} · {{ $shift->opened_at->translatedFormat('d M Y') }}</h1>
                <a href="{{ route('superadmin.shifts.index') }}"
                    class="text-xs font-semibold text-stone-600 hover:text-stone-900">&larr; Kembali</a>
            </div>

            <div class="bg-white border border-stone-200/90 rounded-2xl p-5 shadow-xs">
                <dl class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                    <div><dt class="text-xs text-stone-500">Status</dt><dd class="mt-0.5"><x-status :value="$shift->status" /></dd></div>
                    <div><dt class="text-xs text-stone-500">Kasir</dt><dd class="font-semibold text-stone-900">{{ $shift->user?->name ?? '-' }}</dd></div>
                    <div><dt class="text-xs text-stone-500">Dibuka</dt><dd class="font-semibold text-stone-900">{{ $shift->opened_at->translatedFormat('d M Y H:i') }}</dd></div>
                    <div><dt class="text-xs text-stone-500">Ditutup</dt><dd class="font-semibold text-stone-900">{{ $shift->closed_at?->translatedFormat('d M Y H:i') ?? '-' }}</dd></div>

                    <div><dt class="text-xs text-stone-500">Kas Awal</dt><dd class="font-bold text-stone-900">@rupiah($shift->opening_cash)</dd></div>
                    <div><dt class="text-xs text-stone-500">Kas Seharusnya</dt><dd class="font-bold text-stone-900">{{ $shift->closed_at ? 'Rp ' . number_format((float) $shift->expected_cash, 0, ',', '.') : '-' }}</dd></div>
                    <div><dt class="text-xs text-stone-500">Kas Aktual</dt><dd class="font-bold text-stone-900">{{ $shift->closed_at ? 'Rp ' . number_format((float) $shift->closing_cash, 0, ',', '.') : '-' }}</dd></div>
                    <div><dt class="text-xs text-stone-500">Selisih</dt><dd class="font-bold {{ $moneyClass($diff) }}">{{ $money($diff) }}</dd></div>
                </dl>

                @if ($shift->note)
                    <div class="mt-4 text-sm border-t border-stone-100 pt-4">
                        <dt class="text-xs text-stone-500">Catatan</dt>
                        <dd class="text-stone-700">{{ $shift->note }}</dd>
                    </div>
                @endif
            </div>

            <div class="bg-white border border-stone-200/90 rounded-2xl shadow-xs overflow-hidden">
                <div class="px-5 py-3 text-sm font-semibold text-stone-900 border-b border-stone-100">
                    Transaksi pada Shift Ini ({{ $shift->transactions->count() }})
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-stone-50 text-left text-xs uppercase text-stone-400 border-b border-stone-100">
                            <tr>
                                <th class="px-5 py-3 font-semibold">Invoice</th>
                                <th class="px-5 py-3 font-semibold">Waktu</th>
                                <th class="px-5 py-3 font-semibold">Metode</th>
                                <th class="px-5 py-3 text-right font-semibold">Total</th>
                                <th class="px-5 py-3 font-semibold">Verifikasi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            @forelse ($shift->transactions as $t)
                                <tr class="hover:bg-stone-50/60 transition">
                                    <td class="px-5 py-3.5 font-semibold">
                                        <a href="{{ route('superadmin.transactions.show', $t) }}"
                                            class="text-amber-800 hover:text-amber-900 hover:underline">{{ $t->invoice_no }}</a>
                                    </td>
                                    <td class="px-5 py-3.5 text-stone-600">{{ $t->created_at->translatedFormat('d M Y H:i') }}</td>
                                    <td class="px-5 py-3.5 text-stone-700">{{ $t->payment_method->label() }}</td>
                                    <td class="px-5 py-3.5 text-right font-bold text-stone-900">@rupiah($t->total)</td>
                                    <td class="px-5 py-3.5"><x-status :value="$t->verification_status" /></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-5 py-10 text-center text-stone-400">Belum ada transaksi pada shift ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>