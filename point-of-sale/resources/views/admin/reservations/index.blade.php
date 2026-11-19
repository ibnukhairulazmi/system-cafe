@php
    $tabs = [
        'all' => 'Semua',
        'awaiting_payment' => 'Menunggu',
        'confirmed' => 'Dikonfirmasi',
        'expired' => 'Batal',
    ];
    $statusClassMap = [
        'awaiting_payment' => 'pending',
        'confirmed' => 'confirmed',
        'expired' => 'cancelled',
    ];
    $methodLabels = ['qris' => 'QRIS', 'transfer' => 'Transfer', 'cash' => 'Tunai'];
    $typeLabels = ['dp' => 'DP', 'settlement' => 'Pelunasan', 'refund' => 'Refund'];
    $dpPercent = config('cafe.reservation.dp_percent', 50);
    $selectClass = 'block w-full rounded-xl border border-stone-300 bg-white px-3.5 py-2.5 text-sm text-stone-900 shadow-xs transition duration-150 hover:border-stone-400 focus:border-amber-600 focus:ring-2 focus:ring-amber-500/20 focus:outline-none';
@endphp
<x-app-layout>
    <div class="py-6">
        <div class="w-full px-4 lg:px-6 space-y-4">
            <x-flash />

            @if ($errors->any() || session('error'))
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    {{ session('error') ?? $errors->first() }}
                </div>
            @endif

            <div class="flex flex-wrap gap-2 text-sm">
                @foreach ($tabs as $key => $label )
                    <a href="{{ route('admin.reservations.index', array_merge(request()->except('page'), ['status' => $key])) }}"
                        class="rounded-full px-4 py-1.5 border text-xs sm:text-sm font-medium transition duration-150 {{ $selected === $key ? 'bg-amber-700 text-white border-amber-700 shadow-xs' : 'bg-white text-stone-600 border-stone-200 hover:bg-stone-50 hover:text-stone-900' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            <form method="GET" x-data @change="if ($event.target.type === 'date') $el.requestSubmit()"
                class="bg-white border border-stone-200/90 rounded-2xl p-4 shadow-xs flex flex-wrap gap-4 items-end">
                <input type="hidden" name="status" value="{{ $selected }}">
                <div class="flex-1 min-w-64">
                    <x-input-label for="q" value="Cari" />
                    <x-text-input id="q" name="q" class="mt-1" placeholder="Nama Pemesan atau Kode Reservasi..."
                        :value="$filters['q'] ?? ''" autocomplete="off" />
                </div>
                <div class="min-w-40">
                    <x-input-label for="date" value="Tanggal Kunjungan" />
                    <x-text-input id="date" name="date" type="date" class="mt-1" :value="$filters['date'] ?? ''" />
                </div>
                <div class="flex gap-2">
                    <x-primary-button>Cari</x-primary-button>
                    <a href="{{ route('admin.reservations.index', ['status' => $selected]) }}"
                        class="inline-flex items-center px-4 py-2.5 border border-stone-200/90 rounded-xl text-xs font-semibold text-stone-600 hover:text-stone-900 hover:bg-stone-50 transition">
                        Reset
                    </a>
                </div>
            </form>

            <div class="bg-white border border-stone-200/90 rounded-2xl shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-stone-50 text-left text-xs uppercase text-stone-400 border-b border-stone-100">
                            <tr>
                                <th class="px-5 py-3 font-semibold">Kode</th>
                                <th class="px-5 py-3 font-semibold">Pelanggan</th>
                                <th class="px-5 py-3 font-semibold">Jadwal</th>
                                <th class="px-5 py-3 font-semibold">Tamu</th>
                                <th class="px-5 py-3 font-semibold">Pesanan</th>
                                <th class="px-5 py-3 font-semibold">Total</th>
                                <th class="px-5 py-3 font-semibold">Metode Pembayaran</th>
                                <th class="px-5 py-3 font-semibold">Status</th>
                                <th class="px-5 py-3 text-right font-semibold">Aksi</th>
                            </tr>
                        </thead>

                        @forelse ($reservations as $r)
                            @php
                                $statusValue = $r->status->value;
                                $statusClass = $statusClassMap[$statusValue] ?? $statusValue;
                                $paidAmount = (float) $r->paid_amount;
                                $remaining = (float) $r->remaining_amount;
                                $methodLabel = $methodLabels[$r->payment_method] ?? null;
                                $isAwaiting = $statusValue === 'awaiting_payment';
                                $holdPassed = $isAwaiting && $r->hold_expires_at && $r->hold_expires_at->isPast();
                                $canRecordDp = $isAwaiting && $paidAmount <= 0 && ! $holdPassed;
                            @endphp
                            <tbody x-data="{ open: false }" class="border-b border-stone-100 last:border-0">
                                <tr class="hover:bg-stone-50/60 transition">
                                    <td class="px-5 py-3.5 text-xs">{{ $r->code }}</td>
                                    <td class="px-5 py-3.5">
                                        <div class="font-semibold">{{ $r->name }}</div>
                                        <div class="text-xs mt-2">+{{ $r->phone }}</div>
                                    </td>
                                    <td class="px-5 py-3.5 text-stone-700">
                                        <div class="font-medium">{{ $r->reserved_date->translatedFormat('D, d M Y') }}</div>
                                        <div class="text-xs text-stone-500">
                                            {{ substr((string) $r->reserved_time, 0, 5) }} WIB
                                        </div>
                                    </td>
                                    <td class="px-5 py-3.5 text-stone-700">{{ $r->party_size }} orang</td>
                                    <td class="px-5 py-3.5 text-stone-700">
                                        <div class="font-medium text-stone-900">
                                            {{ $r->items->count() > 0 ? $r->items->sum('quantity') . ' item' : '-' }}
                                        </div>
                                    </td>
                                    <td class="px-5 py-3.5 text-stone-700">
                                        <div class="font-mono font-medium text-stone-900">
                                            Rp {{ number_format($r->total_amount, 0, ',', '.') }}
                                        </div>
                                    </td>
                                    <td class="px-5 py-3.5">
                                        <span class="text-stone-700 uppercase">{{ $r->payment_method }}
                                    </td>
                                    <td class="px-5 py-3.5">
                                        <span
                                            class="{{ $statusClass }}">
                                            {{ $r->status->label() }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3.5">
                                        <div class="flex justify-end items-center gap-3 text-xs">
                                            <a href="{{ $r->whatsappUrl() }}" target="_blank" rel="noopener"
                                                class="font-semibold text-emerald-700 hover:text-emerald-800 hover:underline">WhatsApp</a>

                                            <button type="button" @click="open = !open"
                                                class="font-semibold text-stone-500 hover:text-stone-800 hover:underline"
                                                x-text="open ? 'Tutup' : 'Detail'">Detail</button>
                                        </div>
                                    </td>
                                </tr>

                                <tr x-show="open" style="display: none;" class="bg-stone-50/70">
                                    <td colspan="9" class="px-5 py-4">
                                        <div class="grid gap-6 lg:grid-cols-3">
                                            <div class="space-y-4">
                                                <div class="space-y-2">
                                                    <div class="text-xs font-semibold uppercase text-stone-400">Ringkasan Reservasi</div>
                                                    <div class="rounded-xl border border-stone-200 bg-white p-3 divide-y divide-stone-100 text-xs">
                                                        <div class="py-1.5 first:pt-0 flex justify-between gap-3">
                                                            <span class="text-stone-500">Tanggal</span>
                                                            <span class="font-medium text-stone-800">{{ $r->reserved_date->translatedFormat('D, d M Y') }}</span>
                                                        </div>
                                                        <div class="py-1.5 flex justify-between gap-3">
                                                            <span class="text-stone-500">Jam</span>
                                                            <span class="font-medium text-stone-800">{{ substr((string) $r->reserved_time, 0, 5) }} WIB</span>
                                                        </div>
                                                        <div class="py-1.5 flex justify-between gap-3">
                                                            <span class="text-stone-500">Meja</span>
                                                            <span class="font-medium text-stone-800 text-right">
                                                                @if ($r->table)
                                                                    {{ $r->table->name }}
                                                                @else
                                                                    -
                                                                @endif
                                                            </span>
                                                        </div>
                                                        <div class="py-1.5 flex justify-between gap-3">
                                                            <span class="text-stone-500">Tamu</span>
                                                            <span class="font-medium text-stone-800">{{ $r->party_size }} orang</span>
                                                        </div>
                                                        <div class="py-1.5 flex justify-between gap-3">
                                                            <span class="text-stone-500">Total menu</span>
                                                            <span class="font-mono font-medium text-stone-800">Rp {{ number_format($r->total_amount, 0, ',', '.') }}</span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="space-y-2">
                                                    <div class="text-xs font-semibold uppercase text-stone-400">
                                                        Pesanan Menu
                                                    </div>
                                                    @if ($r->items->isNotEmpty())
                                                        <div class="rounded-xl border border-stone-200 bg-white p-3 divide-y divide-stone-100 text-xs">
                                                            @foreach ($r->items as $item)
                                                                <div class="py-2 first:pt-0 last:pb-0 flex items-start justify-between gap-3">
                                                                    <div>
                                                                        <div class="text-stone-800">
                                                                            <span class="font-semibold text-amber-900">{{ $item->quantity }}x</span>
                                                                            <span class="font-medium ml-1">{{ $item->product_name }}</span>
                                                                        </div>
                                                                        <div class="text-[11px] text-stone-400">
                                                                            @ Rp {{ number_format($item->price, 0, ',', '.') }}
                                                                            @if ($item->note)
                                                                                · <span class="italic text-stone-500">{{ $item->note }}</span>
                                                                            @endif
                                                                        </div>
                                                                    </div>
                                                                    <div class="text-stone-700 font-mono font-medium text-right whitespace-nowrap">
                                                                        Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                            <div class="pt-2.5 mt-1 flex justify-between items-center font-semibold text-stone-900 text-xs">
                                                                <span>Total</span>
                                                                <span class="font-mono text-sm text-amber-900">
                                                                    Rp {{ number_format($r->total_amount, 0, ',', '.') }}
                                                                </span>
                                                            </div>
                                                        </div>
                                                    @else
                                                        <p class="text-xs text-stone-400 italic">Tidak ada rincian pesanan.</p>
                                                    @endif
                                                </div>
                                            </div>

                                            <div class="space-y-1 text-sm">
                                                <div class="text-xs font-semibold uppercase text-stone-400">Catatan Pelanggan</div>
                                                <div class="rounded-xl border border-stone-200 bg-white p-3 text-stone-700 text-xs leading-relaxed">
                                                    {{ $r->notes ?: 'Tidak ada catatan khusus.' }}
                                                </div>
                                                <p class="pt-2 text-xs text-stone-500">
                                                    Dikirim {{ $r->created_at->translatedFormat('d M Y H:i') }}
                                                    @if ($r->handler)
                                                        · Diproses {{ $r->handler->name }}
                                                        ({{ $r->handled_at?->translatedFormat('d M Y H:i') }})
                                                    @endif
                                                </p>

                                                <div class="pt-3 text-xs font-semibold uppercase text-stone-400">Riwayat Pembayaran</div>
                                                @if ($r->payments->isNotEmpty())
                                                    <div class="rounded-xl border border-stone-200 bg-white p-3 divide-y divide-stone-100 text-xs">
                                                        @foreach ($r->payments as $p)
                                                            <div class="py-2 first:pt-0 last:pb-0 flex items-start justify-between gap-3">
                                                                <div>
                                                                    <div class="font-medium text-stone-800">{{ $typeLabels[$p->type] ?? ucfirst($p->type) }}
                                                                        <span class="text-stone-400 font-normal">· {{ $methodLabels[$p->method] ?? strtoupper($p->method) }}</span>
                                                                    </div>
                                                                    <div class="text-[11px] text-stone-400">
                                                                        {{ $p->paid_at?->translatedFormat('d M Y H:i') }}
                                                                        @if ($p->receiver)
                                                                            · {{ $p->receiver->name }}
                                                                        @else
                                                                            · Otomatis (Midtrans)
                                                                        @endif
                                                                        @if ($p->reference)
                                                                            · Ref {{ $p->reference }}
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                                <div class="font-mono font-medium text-stone-700 whitespace-nowrap">
                                                                    Rp {{ number_format($p->amount, 0, ',', '.') }}
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <p class="text-xs text-stone-400 italic">Belum ada pembayaran.</p>
                                                @endif
                                            </div>

                                            <div class="space-y-4">
                                                @if ($canRecordDp)
                                                    <form method="POST" action="{{ route('admin.reservations.payments.store', $r) }}"
                                                        class="rounded-xl border border-stone-200 bg-white p-3.5 space-y-3">
                                                        @csrf
                                                        <input type="hidden" name="type" value="dp">
                                                        <input type="hidden" name="amount" value="{{ (int) $r->dp_amount }}">
                                                        <div class="text-xs font-semibold text-stone-700">Catat DP Manual</div>
                                                        <p class="text-[11px] text-stone-400">
                                                            Nominal DP {{ $dpPercent }}% = Rp {{ number_format($r->dp_amount, 0, ',', '.') }}.
                                                        </p>
                                                        <div>
                                                            <label for="method-{{ $r->id }}"
                                                                class="block text-xs font-medium text-stone-700">Metode</label>
                                                            <select id="method-{{ $r->id }}" name="method" class="mt-1 {{ $selectClass }}">
                                                                <option value="cash">Tunai</option>
                                                                <option value="transfer">Transfer</option>
                                                                <option value="qris">QRIS</option>
                                                            </select>
                                                        </div>
                                                        <div>
                                                            <label for="ref-{{ $r->id }}"
                                                                class="block text-xs font-medium text-stone-700">Referensi (opsional)</label>
                                                            <input id="ref-{{ $r->id }}" name="reference" type="text" maxlength="100"
                                                                class="mt-1 {{ $selectClass }}">
                                                        </div>
                                                        <x-primary-button class="w-full justify-center">Catat DP</x-primary-button>
                                                    </form>
                                                @endif

                                                <form method="POST" action="{{ route('admin.reservations.update', $r) }}"
                                                    class="rounded-xl border border-stone-200 bg-white p-3.5 space-y-3">
                                                    @csrf @method('PATCH')
                                                    <div>
                                                        <label for="note-{{ $r->id }}"
                                                            class="block text-xs font-medium text-stone-700">Catatan
                                                            staf</label>
                                                        <input id="note-{{ $r->id }}" name="staff_note" type="text"
                                                            maxlength="500" value="{{ $r->staff_note }}"
                                                            placeholder="Mis. area outdoor, kursi bayi"
                                                            class="mt-1 {{ $selectClass }}">
                                                    </div>
                                                    <x-primary-button class="w-full justify-center">Simpan Catatan</x-primary-button>
                                                </form>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        @empty
                            <tbody>
                                <tr>
                                    <td colspan="9" class="px-5 py-10 text-center text-stone-400">Tidak ada reservasi
                                        ditemukan.</td>
                                </tr>
                            </tbody>
                        @endforelse
                    </table>
                </div>
            </div>

            {{ $reservations->links() }}
        </div>
    </div>
</x-app-layout>