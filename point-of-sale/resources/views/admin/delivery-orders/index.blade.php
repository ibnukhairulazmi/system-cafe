@php
    $tabs = [
        'all' => 'Semua',
        'pending' => 'Menunggu',
        'confirmed' => 'Dikonfirmasi',
        'on_delivery' => 'Dikirim',
        'delivered' => 'Selesai',
        'cancelled' => 'Dibatalkan',
    ];
    $statusClassMap = [
        'pending' => 'pending',
        'confirmed' => 'confirmed',
        'on_delivery' => 'confirmed',
        'delivered' => 'confirmed',
        'cancelled' => 'cancelled',
    ];
    $tierLabels = [
        'fast' => 'Fast Track',
        'hemat' => 'Hemat',
    ];
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
                @foreach ($tabs as $key => $label)
                    <a href="{{ route('admin.delivery-orders.index', array_merge(request()->except('page'), ['status' => $key])) }}"
                        class="rounded-full px-4 py-1.5 border text-xs sm:text-sm font-medium transition duration-150 {{ $selected === $key ? 'bg-amber-700 text-white border-amber-700 shadow-xs' : 'bg-white text-stone-600 border-stone-200 hover:bg-stone-50 hover:text-stone-900' }}">
                        {{ $label }}@if ($key !== 'all' && isset($counts))
                            ({{ $counts[$key] ?? 0 }})
                        @endif
                    </a>
                @endforeach
            </div>

            <form method="GET" x-data @change="if ($event.target.type === 'date') $el.requestSubmit()"
                class="bg-white border border-stone-200/90 rounded-2xl p-4 shadow-xs flex flex-wrap gap-4 items-end">
                <input type="hidden" name="status" value="{{ $selected }}">
                <div class="flex-1 min-w-64">
                    <x-input-label for="q" value="Cari" />
                    <x-text-input id="q" name="q" class="mt-1" placeholder="Nama Penerima, WhatsApp, Kode, atau Alamat..."
                        :value="$filters['q'] ?? ''" autocomplete="off" />
                </div>
                <div class="min-w-40">
                    <x-input-label for="date" value="Tanggal Pengantaran" />
                    <x-text-input id="date" name="date" type="date" class="mt-1" :value="$filters['date'] ?? ''" />
                </div>
                <div class="flex gap-2">
                    <x-primary-button>Cari</x-primary-button>
                    <a href="{{ route('admin.delivery-orders.index', ['status' => $selected]) }}"
                        class="inline-flex items-center px-4 py-2.5 border border-stone-200/90 rounded-xl text-xs font-semibold text-stone-600 hover:text-stone-900 hover:bg-stone-50 transition">
                        Reset
                    </a>
                </div>
            </form>

            <div class="bg-white border border-stone-200/90 rounded-2xl shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-stone-50 text-left text-xs uppercase text-black font-semibold border-b border-stone-100">
                            <tr>
                                <th class="px-5 py-3 font-semibold text-black">Kode</th>
                                <th class="px-5 py-3 font-semibold text-black">Pelanggan</th>
                                <th class="px-5 py-3 font-semibold text-black">Jadwal &amp; Layanan</th>
                                <th class="px-5 py-3 font-semibold text-black">Alamat Tujuan</th>
                                <th class="px-5 py-3 font-semibold text-black">Pesanan</th>
                                <th class="px-5 py-3 font-semibold text-black">Total</th>
                                <th class="px-5 py-3 font-semibold text-black">Metode Pembayaran</th>
                                <th class="px-5 py-3 font-semibold text-black">Status</th>
                                <th class="px-5 py-3 text-right font-semibold text-black">Aksi</th>
                            </tr>
                        </thead>

                        @forelse ($orders as $o)
                            @php
                                $statusValue = $o->status->value;
                                $statusClass = $statusClassMap[$statusValue] ?? $statusValue;
                            @endphp
                            <tbody x-data="{ open: false }" class="border-b border-stone-100 last:border-0 text-black">
                                <tr class="hover:bg-stone-50/60 transition text-black">
                                    <td class="px-5 py-3.5 text-xs text-black font-mono font-medium">{{ $o->code }}</td>
                                    <td class="px-5 py-3.5 text-black">
                                        <div class="font-semibold text-black">{{ $o->customer_name }}</div>
                                        <div class="text-xs mt-2 text-black font-medium">+{{ $o->customer_phone }}</div>
                                    </td>
                                    <td class="px-5 py-3.5 text-black">
                                        <div class="font-medium text-black">
                                            @if ($o->is_asap)
                                                <span class="text-rose-600 font-semibold">Kirim Segera</span>
                                            @else
                                                {{ $o->delivery_date->translatedFormat('D, d M Y') }}
                                            @endif
                                        </div>
                                        <div class="text-xs text-black font-medium">
                                            @if (!$o->is_asap)
                                                {{ $o->delivery_time }} WIB ·
                                            @endif
                                            {{ $tierLabels[$o->delivery_tier] ?? 'Hemat' }}
                                        </div>
                                    </td>
                                    <td class="px-5 py-3.5 text-black max-w-56">
                                        <div class="font-medium text-black line-clamp-1" title="{{ $o->delivery_address }}">
                                            {{ $o->delivery_address }}
                                        </div>
                                        @if ($o->landmark)
                                            <div class="text-xs text-black font-medium italic truncate" title="Patokan: {{ $o->landmark }}">
                                                Patokan: {{ $o->landmark }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5 text-black">
                                        <div class="font-medium text-black">
                                            {{ $o->items->count() > 0 ? $o->items->sum('quantity') . ' item' : '-' }}
                                        </div>
                                    </td>
                                    <td class="px-5 py-3.5 text-black">
                                        <div class="font-mono font-medium text-black">
                                            Rp {{ number_format($o->total_amount, 0, ',', '.') }}
                                        </div>
                                    </td>
                                    <td class="px-5 py-3.5 text-black">
                                        <span class="text-black uppercase font-medium">{{ $o->paymentMethodLabel() }}</span>
                                    </td>
                                    <td class="px-5 py-3.5">
                                        <span class="{{ $statusClass }}">
                                            {{ $o->status->label() }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3.5">
                                        <div class="flex justify-end items-center gap-3 text-xs">
                                            <a href="{{ $o->whatsappUrl() }}" target="_blank" rel="noopener"
                                                class="font-semibold text-emerald-700 hover:text-emerald-800 hover:underline">WhatsApp</a>

                                            <button type="button" @click="open = !open"
                                                class="font-semibold text-black hover:underline"
                                                x-text="open ? 'Tutup' : 'Detail'">Detail</button>
                                        </div>
                                    </td>
                                </tr>

                                <tr x-show="open" style="display: none;" class="bg-stone-50/70">
                                    <td colspan="9" class="px-5 py-4">
                                        <div class="grid gap-6 lg:grid-cols-3">
                                            
                                            <div class="space-y-4">
                                                <div class="space-y-2">
                                                    <div class="text-xs font-semibold uppercase text-stone-400">Ringkasan Pengantaran</div>
                                                    <div class="rounded-xl border border-stone-200 bg-white p-3 divide-y divide-stone-100 text-xs">
                                                        <div class="py-1.5 first:pt-0 flex justify-between gap-3">
                                                            <span class="text-stone-500">Waktu</span>
                                                            <span class="font-medium text-stone-800 text-right">
                                                                @if ($o->is_asap)
                                                                    Kirim Segera (ASAP)
                                                                @else
                                                                    {{ $o->delivery_date->translatedFormat('D, d M Y') }} · {{ $o->delivery_time }} WIB
                                                                @endif
                                                            </span>
                                                        </div>
                                                        <div class="py-1.5 flex justify-between gap-3">
                                                            <span class="text-stone-500">Layanan</span>
                                                            <span class="font-medium text-stone-800">{{ $o->deliveryTierLabel() }}</span>
                                                        </div>
                                                        <div class="py-1.5 flex justify-between gap-3">
                                                            <span class="text-stone-500">Status Pembayaran</span>
                                                            <span class="font-medium text-stone-800">
                                                                {{ $o->payment_status === 'paid' ? 'Lunas' : 'Belum Lunas / COD' }}
                                                            </span>
                                                        </div>
                                                        <div class="py-1.5 flex justify-between gap-3">
                                                            <span class="text-stone-500">Total Pembayaran</span>
                                                            <span class="font-mono font-medium text-stone-800">Rp {{ number_format($o->total_amount, 0, ',', '.') }}</span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="space-y-2">
                                                    <div class="text-xs font-semibold uppercase text-stone-400">
                                                        Pesanan Menu
                                                    </div>
                                                    @if ($o->items->isNotEmpty())
                                                        <div class="rounded-xl border border-stone-200 bg-white p-3 divide-y divide-stone-100 text-xs">
                                                            @foreach ($o->items as $item)
                                                                <div class="py-2 first:pt-0 last:pb-0 flex items-start justify-between gap-3">
                                                                    <div>
                                                                        <div class="text-stone-800">
                                                                            <span class="font-semibold text-amber-900">{{ $item->quantity }}x</span>
                                                                            <span class="font-medium ml-1">{{ $item->product_name }}</span>
                                                                        </div>
                                                                        <div class="text-[11px] text-stone-400">
                                                                            @ Rp {{ number_format($item->price, 0, ',', '.') }}
                                                                        </div>
                                                                    </div>
                                                                    <div class="text-stone-700 font-mono font-medium text-right whitespace-nowrap">
                                                                        Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                                                                    </div>
                                                                </div>
                                                            @endforeach

                                                            <div class="py-2 space-y-1 text-[11px] text-stone-500">
                                                                <div class="flex justify-between">
                                                                    <span>Subtotal Menu</span>
                                                                    <span class="font-mono">Rp {{ number_format($o->subtotal_amount, 0, ',', '.') }}</span>
                                                                </div>
                                                                <div class="flex justify-between">
                                                                    <span>Ongkos Kirim ({{ $o->deliveryTierLabel() }})</span>
                                                                    <span class="font-mono">Rp {{ number_format($o->delivery_fee, 0, ',', '.') }}</span>
                                                                </div>
                                                            </div>

                                                            <div class="pt-2.5 mt-1 flex justify-between items-center font-semibold text-stone-900 text-xs">
                                                                <span>Total</span>
                                                                <span class="font-mono text-sm text-amber-900">
                                                                    Rp {{ number_format($o->total_amount, 0, ',', '.') }}
                                                                </span>
                                                            </div>
                                                        </div>
                                                    @else
                                                        <p class="text-xs text-stone-400 italic">Tidak ada rincian pesanan.</p>
                                                    @endif
                                                </div>
                                            </div>

                                            
                                            <div class="space-y-3 text-sm">
                                                <div>
                                                    <div class="text-xs font-semibold uppercase text-stone-400 mb-1">Alamat Pengiriman</div>
                                                    <div class="rounded-xl border border-stone-200 bg-white p-3 text-stone-700 text-xs leading-relaxed">
                                                        <p class="font-medium text-stone-800">{{ $o->delivery_address }}</p>
                                                        @if ($o->landmark)
                                                            <p class="mt-1 text-stone-500 italic">Patokan: {{ $o->landmark }}</p>
                                                        @endif
                                                    </div>
                                                </div>

                                                <div>
                                                    <div class="text-xs font-semibold uppercase text-stone-400 mb-1">Catatan Pelanggan</div>
                                                    <div class="rounded-xl border border-stone-200 bg-white p-3 text-stone-700 text-xs leading-relaxed">
                                                        {{ $o->customer_note ?: 'Tidak ada catatan khusus.' }}
                                                    </div>
                                                </div>

                                                <p class="pt-1 text-xs text-stone-500">
                                                    Dikirim {{ $o->created_at->translatedFormat('d M Y H:i') }}
                                                    @if ($o->handler)
                                                        · Diproses {{ $o->handler->name }}
                                                    @endif
                                                </p>
                                            </div>

                                            
                                            <div class="space-y-4">
                                                <form method="POST" action="{{ route('admin.delivery-orders.update', $o) }}"
                                                    class="rounded-xl border border-stone-200 bg-white p-3.5 space-y-3">
                                                    @csrf @method('PATCH')
                                                    <div>
                                                        <label for="deliv-status-{{ $o->id }}"
                                                            class="block text-xs font-medium text-stone-700">Status Pesanan</label>
                                                        <select id="deliv-status-{{ $o->id }}" name="status"
                                                            class="mt-1 {{ $selectClass }}">
                                                            @foreach (\App\Enums\DeliveryOrderStatus::cases() as $s)
                                                                <option value="{{ $s->value }}" @selected($o->status === $s)>
                                                                    {{ $s->label() }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>

                                                    <div>
                                                        <label for="deliv-pay-{{ $o->id }}"
                                                            class="block text-xs font-medium text-stone-700">Status Pembayaran</label>
                                                        <select id="deliv-pay-{{ $o->id }}" name="payment_status"
                                                            class="mt-1 {{ $selectClass }}">
                                                            <option value="unpaid" @selected($o->payment_status === 'unpaid')>Belum Lunas / COD</option>
                                                            <option value="paid" @selected($o->payment_status === 'paid')>Lunas</option>
                                                        </select>
                                                    </div>

                                                    <div>
                                                        <label for="deliv-note-{{ $o->id }}"
                                                            class="block text-xs font-medium text-stone-700">Catatan staf</label>
                                                        <input id="deliv-note-{{ $o->id }}" name="staff_note" type="text"
                                                            maxlength="500" value="{{ $o->staff_note }}"
                                                            placeholder="Mis. diantar Mas Budi, no resi..."
                                                            class="mt-1 {{ $selectClass }}">
                                                    </div>

                                                    <x-primary-button class="w-full justify-center">Simpan Perubahan</x-primary-button>
                                                </form>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        @empty
                            <tbody>
                                <tr>
                                    <td colspan="9" class="px-5 py-10 text-center text-black font-medium">Tidak ada pesanan antar ditemukan.</td>
                                </tr>
                            </tbody>
                        @endforelse
                    </table>
                </div>
            </div>

            {{ $orders->links() }}
        </div>
    </div>
</x-app-layout>