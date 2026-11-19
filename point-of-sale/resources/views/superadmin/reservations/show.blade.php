<x-app-layout>
    <div class="py-6">
        <div class="w-full px-4 lg:px-6 space-y-5">
            <div class="flex items-center justify-between">
                <h1 class="text-lg font-bold text-stone-900">Reservasi {{ $reservation->code }}</h1>
                <a href="{{ route('superadmin.reservations.index') }}"
                    class="text-xs font-semibold text-stone-600 hover:text-stone-900">&larr; Kembali</a>
            </div>

            <div class="bg-white border border-stone-200/90 rounded-2xl p-5 shadow-xs">
                <dl class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                    <div><dt class="text-xs text-stone-500">Status</dt><dd class="mt-0.5"><x-status :value="$reservation->status" /></dd></div>
                    <div><dt class="text-xs text-stone-500">Pelanggan</dt><dd class="font-semibold text-stone-900">{{ $reservation->name }}</dd></div>
                    <div><dt class="text-xs text-stone-500">Telepon</dt><dd class="font-semibold text-stone-900">{{ $reservation->phone }}</dd></div>
                    <div><dt class="text-xs text-stone-500">Jumlah Tamu</dt><dd class="font-semibold text-stone-900">{{ $reservation->partyLabel() }}</dd></div>

                    <div><dt class="text-xs text-stone-500">Tanggal</dt><dd class="font-semibold text-stone-900">{{ $reservation->reserved_date->translatedFormat('d F Y') }}</dd></div>
                    <div><dt class="text-xs text-stone-500">Jam</dt><dd class="font-semibold text-stone-900">{{ substr((string) $reservation->reserved_time, 0, 5) }}@if ($reservation->reserved_end_time) - {{ substr((string) $reservation->reserved_end_time, 0, 5) }}@endif</dd></div>
                    <div><dt class="text-xs text-stone-500">Meja</dt><dd class="font-semibold text-stone-900">{{ $reservation->table?->name ?? '-' }}</dd></div>
                    <div><dt class="text-xs text-stone-500">Check-in</dt><dd class="font-semibold text-stone-900">{{ $reservation->checked_in_at?->translatedFormat('d M Y H:i') ?? '-' }}</dd></div>

                    <div><dt class="text-xs text-stone-500">Metode Bayar</dt><dd class="font-semibold text-stone-900">{{ $reservation->paymentMethodLabel() }}</dd></div>
                    <div><dt class="text-xs text-stone-500">Status Bayar</dt><dd class="font-semibold text-stone-900">{{ $reservation->paymentStatusLabel() }}</dd></div>
                    <div><dt class="text-xs text-stone-500">Total</dt><dd class="font-bold text-stone-900">@rupiah($reservation->total_amount)</dd></div>
                    <div><dt class="text-xs text-stone-500">Terbayar</dt><dd class="font-bold text-amber-800">@rupiah($reservation->paid_amount)</dd></div>

                    <div><dt class="text-xs text-stone-500">Sisa Tagihan</dt><dd class="font-semibold text-stone-900">@rupiah($reservation->remaining_amount)</dd></div>
                    <div><dt class="text-xs text-stone-500">Batas Bayar DP</dt><dd class="font-semibold text-stone-900">{{ $reservation->hold_expires_at?->translatedFormat('d M Y H:i') ?? '-' }}</dd></div>
                    <div><dt class="text-xs text-stone-500">Ditangani Oleh</dt><dd class="font-semibold text-stone-900">{{ $reservation->handler?->name ?? '-' }}</dd></div>
                    <div><dt class="text-xs text-stone-500">Waktu Ditangani</dt><dd class="font-semibold text-stone-900">{{ $reservation->handled_at?->translatedFormat('d M Y H:i') ?? '-' }}</dd></div>
                </dl>

                @if ($reservation->notes || $reservation->staff_note)
                    <div class="mt-4 grid md:grid-cols-2 gap-4 text-sm border-t border-stone-100 pt-4">
                        <div><dt class="text-xs text-stone-500">Catatan Pelanggan</dt><dd class="text-stone-700">{{ $reservation->notes ?: '-' }}</dd></div>
                        <div><dt class="text-xs text-stone-500">Catatan Staf</dt><dd class="text-stone-700">{{ $reservation->staff_note ?: '-' }}</dd></div>
                    </div>
                @endif
            </div>

            <div class="bg-white border border-stone-200/90 rounded-2xl shadow-xs overflow-hidden">
                <div class="px-5 py-3 text-sm font-semibold text-stone-900 border-b border-stone-100">Pesanan Menu</div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-stone-50 text-left text-xs uppercase text-stone-400 border-b border-stone-100">
                            <tr>
                                <th class="px-5 py-3 font-semibold">Menu</th>
                                <th class="px-5 py-3 text-right font-semibold">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            @forelse ($reservation->items as $item)
                                <tr>
                                    <td class="px-5 py-3.5 text-stone-700">{{ $item->product_name }}</td>
                                    <td class="px-5 py-3.5 text-right text-stone-900">{{ $item->quantity }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="px-5 py-8 text-center text-stone-400">Tidak ada pesanan menu.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white border border-stone-200/90 rounded-2xl shadow-xs overflow-hidden">
                <div class="px-5 py-3 text-sm font-semibold text-stone-900 border-b border-stone-100">Riwayat Pembayaran</div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-stone-50 text-left text-xs uppercase text-stone-400 border-b border-stone-100">
                            <tr>
                                <th class="px-5 py-3 font-semibold">Waktu</th>
                                <th class="px-5 py-3 text-right font-semibold">Nominal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            @forelse ($reservation->payments as $p)
                                <tr>
                                    <td class="px-5 py-3.5 text-stone-600">{{ $p->created_at->translatedFormat('d M Y H:i') }}</td>
                                    <td class="px-5 py-3.5 text-right font-bold text-stone-900">@rupiah($p->amount)</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="px-5 py-8 text-center text-stone-400">Belum ada pembayaran tercatat.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>