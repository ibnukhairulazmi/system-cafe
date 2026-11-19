<x-app-layout>
    @php
        $selectClass = 'mt-1 w-full rounded-xl border border-stone-300 bg-white px-3.5 py-2.5 text-sm text-stone-900 shadow-xs focus:border-amber-600 focus:ring-2 focus:ring-amber-500/20 focus:outline-none';
        $status = $reservation->status;
        $isConfirmed = $status === \App\Enums\ReservationStatus::Confirmed;
        $canSettle = in_array($status, \App\Enums\ReservationStatus::active(), true) && $reservation->remaining_amount > 0;
        $canComplete = $status === \App\Enums\ReservationStatus::Seated && $reservation->remaining_amount <= 0;
        $remaining = $reservation->remaining_amount;
    @endphp

    <div class="py-6">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            <x-flash />
            <x-errors-box />

            <div class="flex items-center justify-between">
                <h1 class="text-lg font-bold text-stone-900">Reservasi {{ $reservation->code }}</h1>
                <a href="{{ route('kasir.reservations.index') }}"
                    class="text-xs font-semibold text-stone-600 hover:text-stone-900">&larr; Kembali</a>
            </div>

            <div class="bg-white border border-stone-200/90 rounded-2xl p-6 shadow-xs">
                <dl class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                    <div><dt class="text-xs text-stone-500">Status</dt><dd class="mt-0.5"><x-status :value="$reservation->status" /></dd></div>
                    <div><dt class="text-xs text-stone-500">Pelanggan</dt><dd class="font-semibold text-stone-900">{{ $reservation->name }}</dd></div>
                    <div><dt class="text-xs text-stone-500">Telepon</dt><dd class="font-semibold text-stone-900">{{ $reservation->phone }}</dd></div>
                    <div><dt class="text-xs text-stone-500">Jumlah Tamu</dt><dd class="font-semibold text-stone-900">{{ $reservation->partyLabel() }}</dd></div>

                    <div><dt class="text-xs text-stone-500">Tanggal</dt><dd class="font-semibold text-stone-900">{{ $reservation->reserved_date->translatedFormat('d F Y') }}</dd></div>
                    <div><dt class="text-xs text-stone-500">Jam</dt><dd class="font-semibold text-stone-900">{{ substr((string) $reservation->reserved_time, 0, 5) }}@if ($reservation->reserved_end_time) - {{ substr((string) $reservation->reserved_end_time, 0, 5) }}@endif</dd></div>
                    <div><dt class="text-xs text-stone-500">Meja</dt><dd class="font-semibold text-stone-900">{{ $reservation->table?->name ?? '-' }}</dd></div>
                    <div><dt class="text-xs text-stone-500">Check-in</dt><dd class="font-semibold {{ $reservation->checked_in_at ? 'text-emerald-700' : 'text-stone-900' }}">{{ $reservation->checked_in_at?->translatedFormat('d M H:i') ?? 'Belum' }}</dd></div>
                </dl>

                @if ($reservation->notes)
                    <div class="mt-4 text-sm border-t border-stone-100 pt-4">
                        <dt class="text-xs text-stone-500">Catatan Pelanggan</dt>
                        <dd class="text-stone-700">{{ $reservation->notes }}</dd>
                    </div>
                @endif

                <div class="mt-4 flex flex-wrap gap-2 border-t border-stone-100 pt-4">
                    @if ($isConfirmed && ! $reservation->checked_in_at)
                        <form method="POST" action="{{ route('kasir.reservations.check-in', $reservation) }}"
                            onsubmit="return confirm('Check-in tamu {{ e($reservation->name) }}?')">
                            @csrf
                            <x-primary-button>Check-in Tamu</x-primary-button>
                        </form>
                    @endif
                    <a href="{{ $reservation->whatsappUrl() }}" target="_blank" rel="noopener"
                        class="inline-flex items-center rounded-xl px-4 py-2.5 text-xs font-semibold border border-stone-300 text-stone-700 hover:bg-stone-50 transition">
                        Hubungi via WhatsApp
                    </a>
                </div>
            </div>

            
            <div class="bg-white border border-stone-200/90 rounded-2xl p-6 shadow-xs space-y-4">
                <h3 class="font-bold text-base text-stone-900 pb-3 border-b border-stone-100">Pembayaran</h3>

                <dl class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm bg-stone-50/70 p-4 rounded-xl border border-stone-100">
                    <div><dt class="text-xs text-stone-500">Total Tagihan</dt><dd class="font-bold text-stone-900 mt-0.5">@rupiah($reservation->total_amount)</dd></div>
                    <div><dt class="text-xs text-stone-500">Sudah Dibayar (DP)</dt><dd class="font-bold text-amber-800 mt-0.5">@rupiah($reservation->paid_amount)</dd></div>
                    <div><dt class="text-xs text-stone-500">Sisa Tagihan</dt><dd class="font-bold mt-0.5 {{ $remaining > 0 ? 'text-rose-600' : 'text-emerald-700' }}">{{ $remaining > 0 ? 'Rp ' . number_format($remaining, 0, ',', '.') : 'Lunas' }}</dd></div>
                    <div><dt class="text-xs text-stone-500">Status Bayar</dt><dd class="font-bold text-stone-900 mt-0.5">{{ $reservation->paymentStatusLabel() }}</dd></div>
                </dl>

                @if ($canSettle)
                    <form method="POST" action="{{ route('kasir.reservations.settle', $reservation) }}"
                        onsubmit="return confirm('Terima pelunasan Rp {{ number_format($remaining, 0, ',', '.') }} dari {{ e($reservation->name) }}?')"
                        class="space-y-4">
                        @csrf
                        <div>
                            <x-input-label for="method" value="Metode pelunasan" />
                            <select id="method" name="method" class="{{ $selectClass }}" required>
                                <option value="cash" @selected(old('method', 'cash') === 'cash')>Tunai</option>
                                <option value="qris" @selected(old('method') === 'qris')>QRIS</option>
                                <option value="transfer" @selected(old('method') === 'transfer')>Transfer Bank</option>
                            </select>
                            <p class="mt-1.5 text-xs text-stone-500">Nominal otomatis sebesar sisa tagihan. Pembayaran tunai akan tercatat pada shift Anda.</p>
                        </div>
                        <x-primary-button>Terima Pelunasan Rp {{ number_format($remaining, 0, ',', '.') }}</x-primary-button>
                    </form>
                @elseif ($remaining <= 0)
                    <p class="text-sm font-semibold text-emerald-700">Reservasi ini sudah lunas.</p>
                @else
                    <p class="text-sm text-stone-500">Pelunasan baru bisa diterima setelah reservasi dikonfirmasi (DP masuk).</p>
                @endif
            </div>

            
            <div class="bg-white border border-stone-200/90 rounded-2xl shadow-xs overflow-hidden">
                <div class="px-5 py-3 text-sm font-semibold text-stone-900 border-b border-stone-100">Pesanan Menu</div>
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

            
            <div class="bg-white border border-stone-200/90 rounded-2xl shadow-xs overflow-hidden">
                <div class="px-5 py-3 text-sm font-semibold text-stone-900 border-b border-stone-100">Riwayat Pembayaran</div>
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
</x-app-layout>