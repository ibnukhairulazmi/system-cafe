
<div class="bg-white border border-stone-200/90 rounded-2xl p-6 grid grid-cols-2 md:grid-cols-3 gap-5 text-sm shadow-xs">
    <div>
        <p class="text-xs text-stone-500">Waktu Transaksi</p>
        <p class="font-semibold text-stone-900 mt-0.5">{{ $transaction->created_at->translatedFormat('d M Y H:i') }}</p>
    </div>
    <div>
        <p class="text-xs text-stone-500">Kasir Bertugas</p>
        <p class="font-semibold text-stone-900 mt-0.5">{{ $transaction->cashier->name }}</p>
    </div>
    <div>
        <p class="text-xs text-stone-500">Shift</p>
        <p class="font-semibold text-stone-900 mt-0.5">#{{ $transaction->shift_id }}
            ({{ $transaction->shift->opened_at->translatedFormat('d M H:i') }})</p>
    </div>
    <div>
        <p class="text-xs text-stone-500">Metode Pembayaran</p>
        <p class="font-semibold text-stone-900 mt-0.5">{{ $transaction->payment_method->label() }}</p>
    </div>
    <div>
        <p class="text-xs text-stone-500">Status Verifikasi</p>
        <div class="mt-0.5 font-semibold"><x-status :value="$transaction->verification_status" /></div>
    </div>
    @if ($transaction->verifier)
        <div class="col-span-2 md:col-span-3 pt-2 border-t border-stone-100">
            <p class="text-xs text-stone-500">Diverifikasi oleh</p>
            <p class="font-semibold text-stone-900 mt-0.5">{{ $transaction->verifier->name }} &middot;
                {{ $transaction->verified_at?->translatedFormat('d M Y H:i') }}</p>
        </div>
    @endif
    @if ($transaction->note)
        <div class="col-span-2 md:col-span-3 bg-stone-50 p-3 rounded-xl border border-stone-200">
            <p class="text-xs text-stone-500">Catatan verifikasi</p>
            <p class="font-medium text-stone-800 mt-0.5">{{ $transaction->note }}</p>
        </div>
    @endif
</div>

<div class="bg-white border border-stone-200/90 rounded-2xl shadow-xs overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-stone-50 text-left text-xs uppercase text-black font-semibold border-b border-stone-100">
                <tr>
                    <th class="px-5 py-3 font-semibold text-black">Menu</th>
                    <th class="px-5 py-3 text-right font-semibold text-black">Harga</th>
                    <th class="px-5 py-3 text-right font-semibold text-black">Qty</th>
                    <th class="px-5 py-3 text-right font-semibold text-black">Subtotal</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-100 text-black">
                @foreach ($transaction->items as $item)
                    <tr class="text-black">
                        <td class="px-5 py-3.5 font-medium text-black">{{ $item->product_name }}@if ($item->note)
                                <span class="block text-xs text-black font-normal mt-0.5">{{ $item->note }}</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 text-right text-black font-medium">@rupiah($item->price)</td>
                        <td class="px-5 py-3.5 text-right text-black font-medium">{{ $item->quantity }}</td>
                        <td class="px-5 py-3.5 text-right font-semibold text-black">@rupiah($item->subtotal)</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="text-sm bg-stone-50/50 border-t border-stone-100 text-black">
                <tr>
                    <td colspan="3" class="px-5 py-2 text-right text-black font-medium">Subtotal</td>
                    <td class="px-5 py-2 text-right font-semibold text-black">@rupiah($transaction->subtotal)</td>
                </tr>
                <tr>
                    <td colspan="3" class="px-5 py-1 text-right text-rose-600">Diskon</td>
                    <td class="px-5 py-1 text-right font-medium text-rose-600">-@rupiah($transaction->discount)</td>
                </tr>
                <tr>
                    <td colspan="3" class="px-5 py-1 text-right text-black font-medium">Pajak (@qty($transaction->tax_rate)%)</td>
                    <td class="px-5 py-1 text-right font-semibold text-black">@rupiah($transaction->tax)</td>
                </tr>
                <tr>
                    <td colspan="3" class="px-5 py-1 text-right text-black font-medium">Pembulatan</td>
                    <td class="px-5 py-1 text-right font-semibold text-black">@rupiah($transaction->rounding)</td>
                </tr>
                <tr class="font-bold border-t border-stone-200 text-black">
                    <td colspan="3" class="px-5 py-2.5 text-right text-black text-base font-bold">Total Tagihan</td>
                    <td class="px-5 py-2.5 text-right text-amber-800 text-base font-bold">@rupiah($transaction->total)</td>
                </tr>
                <tr>
                    <td colspan="3" class="px-5 py-1 text-right text-black font-medium">Dibayar</td>
                    <td class="px-5 py-1 text-right font-semibold text-black">@rupiah($transaction->paid_amount)</td>
                </tr>
                <tr>
                    <td colspan="3" class="px-5 pb-3 py-1 text-right text-black font-medium">Kembalian</td>
                    <td class="px-5 pb-3 py-1 text-right font-semibold text-emerald-700">@rupiah($transaction->change_amount)</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
