@php $editing = isset($order) && $order; @endphp
<x-app-layout>

    <div class="py-6">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            <x-flash />
            <x-errors-box />

            <form method="POST"
                action="{{ $editing ? route('admin.purchase-orders.update', $order) : route('admin.purchase-orders.store') }}"
                class="bg-white border border-stone-200/90 rounded-2xl shadow-xs p-6 space-y-5"
                x-data="purchaseOrderForm(@js($ingredientMap), @js($rows))">
                @csrf
                @if ($editing)
                    @method('PUT')
                @endif

                <x-select name="supplier_id" label="Supplier" :options="$suppliers" :selected="$order->supplier_id ?? null"
                    blank="-- pilih supplier --" required />

                <div>
                    <x-input-label for="note" value="Catatan" />
                    <textarea id="note" name="note" rows="2"
                        class="mt-1 block w-full border-stone-300 rounded-xl shadow-xs text-sm focus:border-amber-500 focus:ring-amber-500">{{ old('note', $order->note ?? '') }}</textarea>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead
                            class="text-left text-xs uppercase text-black font-semibold border-b border-stone-100">
                            <tr>
                                <th class="py-2.5 text-black font-semibold">Bahan</th>
                                <th class="py-2.5 text-black font-semibold">Jumlah</th>
                                <th class="py-2.5 text-black font-semibold">Harga satuan (Rp)</th>
                                <th class="py-2.5 text-right text-black font-semibold">Subtotal</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody class="text-black">
                            <template x-for="(row, i) in rows" :key="i">
                                <tr class="text-black">
                                    <td class="py-1 pe-2">
                                        <select :name="'items[' + i + '][ingredient_id]'" x-model="row.ingredient_id"
                                            @change="prefill(row)" required
                                            class="w-full border-stone-300 rounded-xl shadow-xs text-sm text-black focus:border-amber-500 focus:ring-amber-500">
                                            <option value="">-- pilih bahan --</option>
                                            @foreach ($ingredients as $ing)
                                                <option value="{{ $ing->id }}">{{ $ing->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="py-1 pe-2">
                                        <div class="flex items-center gap-1">
                                            <input type="number" step="0.001" min="0.001" required
                                                x-model="row.quantity" :name="'items[' + i + '][quantity]'"
                                                class="w-28 border-stone-300 rounded-xl shadow-xs text-sm text-black focus:border-amber-500 focus:ring-amber-500">
                                            <span class="text-black text-xs font-medium"
                                                x-text="unit(row.ingredient_id)"></span>
                                        </div>
                                    </td>
                                    <td class="py-1 pe-2">
                                        <input type="number" step="0.01" min="0" required
                                            x-model="row.unit_price" :name="'items[' + i + '][unit_price]'"
                                            class="w-32 border-stone-300 rounded-xl shadow-xs text-sm text-black focus:border-amber-500 focus:ring-amber-500">
                                    </td>
                                    <td class="py-1 text-right whitespace-nowrap text-black font-medium" x-text="fmt(sub(row))"></td>
                                    <td class="py-1 ps-2 text-right">
                                        <button type="button" @click="remove(i)"
                                            class="text-rose-600 hover:text-rose-700 hover:underline text-xs font-semibold">Hapus</button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot class="text-black">
                            <tr class="font-semibold border-t border-stone-100 text-black">
                                <td colspan="3" class="pt-3 text-right text-black font-semibold">Total</td>
                                <td class="pt-3 text-right whitespace-nowrap text-black font-bold" x-text="fmt(total)"></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <button type="button" @click="add()"
                    class="text-sm text-amber-700 hover:text-amber-900 hover:underline font-semibold">+ Tambah
                    bahan</button>

                <div class="flex gap-3 pt-2">
                    <x-primary-button>Simpan sebagai Draft</x-primary-button>
                    <a
                        href="{{ $editing ? route('admin.purchase-orders.show', $order) : route('admin.purchase-orders.index') }}">
                        <x-secondary-button type="button">Batal</x-secondary-button>
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
