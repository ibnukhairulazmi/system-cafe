@php $editing = isset($product); @endphp
<x-app-layout>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" enctype="multipart/form-data"
                action="{{ $editing ? route('admin.products.update', $product) : route('admin.products.store') }}"
                class="bg-white shadow-sm sm:rounded-lg p-6 space-y-5">
                @csrf
                @if ($editing)
                    @method('PUT')
                @endif

                <x-select name="category_id" label="Kategori" :options="$categories" :selected="$product->category_id ?? null" blank="-- pilih --"
                    required />
                <x-field name="sku" label="SKU" :value="$product->sku ?? null" required />
                <x-field name="name" label="Nama menu" :value="$product->name ?? null" required />
                <x-field name="price" label="Harga (Rp)" type="number" step="500" min="0"
                    :value="isset($product) ? (int) $product->price : null" required
                    hint="Harga pada transaksi lama tidak berubah (tersimpan sebagai snapshot)." />

                <div>
                    <x-input-label for="description" value="Deskripsi" />
                    <textarea id="description" name="description" rows="3"
                        class="block w-full rounded-xl border border-stone-300 bg-white px-3.5 py-2.5 text-sm text-stone-900
                    placeholder-stone-400 shadow-xs transition duration-150
                    hover:border-stone-400
                    focus:border-amber-600 focus:bg-white focus:ring-2 focus:ring-amber-500/20 focus:outline-none
                    disabled:cursor-not-allowed disabled:bg-stone-100 disabled:text-stone-400">{{ old('description', $product->description ?? '') }}</textarea>
                    <x-input-error :messages="$errors->get('description')" class="mt-2" />
                </div>

                <div x-data="{
                    current: @js($product->image_url ?? null),
                    picked: null,
                    removed: false,
                    get shown() { return this.picked ?? (this.removed ? null : this.current); },
                    pick(e) {
                        const file = e.target.files[0];
                        this.picked = file ? URL.createObjectURL(file) : null;
                        if (file) this.removed = false;
                    },
                    toggleRemove() {
                        if (this.removed) {
                            this.$refs.file.value = '';
                            this.picked = null;
                        }
                    }
                }">
                    <x-input-label for="image" value="Foto menu" />
                    <div class="mt-2 flex items-center gap-4">
                        <div
                            class="flex h-24 w-24 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-stone-200 bg-stone-50">
                            <template x-if="shown">
                                <img :src="shown" alt="Pratinjau foto menu" class="h-full w-full object-cover">
                            </template>
                            <template x-if="!shown">
                                <span class="px-2 text-center text-xs text-stone-400">Belum ada foto</span>
                            </template>
                        </div>

                        <div class="space-y-2">
                            <input id="image" name="image" type="file" x-ref="file"
                                accept="image/jpeg,image/png,image/webp" @change="pick($event)"
                                class="block w-full text-sm text-stone-600 file:mr-3 file:rounded-lg file:border-0 file:bg-amber-50 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-amber-800 hover:file:bg-amber-100">

                            @if ($editing && $product->image_path)
                                <label class="inline-flex items-center gap-2 text-xs text-stone-600">
                                    <input type="checkbox" name="remove_image" value="1" x-model="removed"
                                        @change="toggleRemove()" class="rounded border-gray-300">
                                    Hapus foto saat ini
                                </label>
                            @endif

                            <p class="text-xs text-stone-400">JPG, PNG, atau WebP, maksimal 2 MB. Rasio persegi (1:1)
                                paling cocok untuk tampilan website.</p>
                        </div>
                    </div>
                    <x-input-error :messages="$errors->get('image')" class="mt-2" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="inline-flex items-center gap-2 text-sm">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300"
                            @checked(old('is_active', $product->is_active ?? true))>
                        Tampil di POS
                    </label>

                    <label class="inline-flex items-center gap-2 text-sm">
                        <input type="hidden" name="show_on_web" value="0">
                        <input type="checkbox" name="show_on_web" value="1" class="rounded border-gray-300"
                            @checked(old('show_on_web', $product->show_on_web ?? true))>
                        Tampil di website
                    </label>
                </div>

                <x-field name="sort_order" label="Urutan di website" type="number" min="0" step="1"
                    :value="$product->sort_order ?? 0"
                    hint="Angka kecil tampil lebih dulu dalam kategorinya." />

                <div class="flex gap-3">
                    <x-primary-button>Simpan</x-primary-button>
                    <a href="{{ route('admin.products.index') }}"><x-secondary-button
                            type="button">Batal</x-secondary-button></a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>