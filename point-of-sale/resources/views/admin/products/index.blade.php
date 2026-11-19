<x-app-layout>
    <div class="py-6">
        <div class="w-full px-4 lg:px-6 space-y-4">
            <x-flash />
            <form method="GET" x-data="{
                controller: null,
                async submit() {
                    this.controller?.abort();
                    this.controller = new AbortController();
            
                    const params = new URLSearchParams(new FormData(this.$el));
                    [...params].forEach(([k, v]) => { if (!v) params.delete(k); });
                    const url = window.location.pathname + (params.toString() ? '?' + params : '');
            
                    document.getElementById('menu-results')?.classList.add('opacity-60', 'transition-opacity');
            
                    try {
                        const res = await fetch(url, {
                            headers: { 'X-Requested-With': 'XMLHttpRequest' },
                            signal: this.controller.signal,
                        });
                        const doc = new DOMParser().parseFromString(await res.text(), 'text/html');
                        const fresh = doc.getElementById('menu-results');
            
                        if (!fresh) { window.location = url; return; }
            
                        document.getElementById('menu-results').replaceWith(fresh);
                        history.replaceState(null, '', url);
                    } catch (e) {
                        if (e.name !== 'AbortError') window.location = url;
                    } finally {
                        document.getElementById('menu-results')?.classList.remove('opacity-60');
                        if (document.activeElement?.name !== 'q' && this.typing) this.$refs.q?.focus();
                    }
                },
                typing: false
            }" @submit.prevent="submit()"
                @change="if ($event.target.tagName === 'SELECT') { typing = false; submit(); }"
                @input.debounce.300ms="if ($event.target.name === 'q') { typing = true; submit(); }"
                class="bg-white border border-stone-200/90 rounded-2xl p-4 shadow-xs flex flex-wrap gap-4 items-end">
                <div class="flex-1 min-w-64">
                    <x-input-label for="q" value="Cari Menu" />
                    <x-text-input id="q" name="q" x-ref="q" class="mt-1"
                        placeholder="Ketik nama menu atau SKU..." :value="$filters['q'] ?? ''" autocomplete="off" />
                </div>
                <div class="min-w-44">
                    <x-select name="category_id" label="Kategori" :options="$categories" :selected="$filters['category_id'] ?? null"
                        blank="Semua Kategori" />
                </div>
                <div class="min-w-36">
                    <x-select name="status" label="Status" :options="['active' => 'Aktif', 'inactive' => 'Nonaktif']" :selected="$filters['status'] ?? null" blank="Semua Status" />
                </div>
                <div class="ml-auto">
                    <a href="{{ route('admin.products.create') }}">
                        <x-primary-button type="button">Tambah Menu</x-primary-button>
                    </a>
                </div>
            </form>

            <div id="menu-results" class="space-y-4">
                <div class="bg-white border border-stone-200/90 rounded-2xl shadow-xs overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead
                                class="bg-stone-50 text-left text-xs uppercase text-black font-semibold border-b border-stone-100">
                                <tr>
                                    <th class="px-5 py-3 font-semibold text-black">Foto</th>
                                    <th class="px-5 py-3 font-semibold text-black">SKU</th>
                                    <th class="px-5 py-3 font-semibold text-black">Menu</th>
                                    <th class="px-5 py-3 font-semibold text-black">Kategori</th>
                                    <th class="px-5 py-3 text-right font-semibold text-black">Harga</th>
                                    <th class="px-5 py-3 font-semibold text-black">Resep</th>
                                    <th class="px-5 py-3 font-semibold text-black">Status</th>
                                    <th class="px-5 py-3 text-right font-semibold text-black">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-stone-100 text-black">
                                @forelse ($products as $p)
                                    <tr class="hover:bg-stone-50/60 transition text-black">
                                        <td class="px-5 py-3.5">
                                            @if ($p->image_url)
                                                <img src="{{ $p->image_url }}" alt="{{ $p->name }}" loading="lazy"
                                                    class="h-11 w-11 rounded-lg border border-stone-200 object-cover">
                                            @else
                                                <div
                                                    class="flex h-11 w-11 items-center justify-center rounded-lg border border-dashed border-stone-300 text-[10px] text-black">
                                                    -</div>
                                            @endif
                                        </td>
                                        <td class="px-5 py-3.5 text-black font-mono text-xs font-medium">{{ $p->sku }}
                                        </td>
                                        <td class="px-5 py-3.5 font-semibold text-black">{{ $p->name }}</td>
                                        <td class="px-5 py-3.5 text-black font-medium">{{ $p->category->name }}</td>
                                        <td class="px-5 py-3.5 text-right font-bold text-amber-800">@rupiah($p->price)
                                        </td>
                                        <td class="px-5 py-3.5">
                                            <a href="{{ route('admin.products.recipe.edit', $p) }}"
                                                class="{{ $p->ingredients_count ? 'text-amber-800 hover:text-amber-900' : 'text-black hover:underline' }} text-xs font-semibold hover:underline">
                                                {{ $p->ingredients_count ? $p->ingredients_count . ' bahan' : 'Atur resep' }}
                                            </a>
                                        </td>
                                        <td class="px-5 py-3.5 text-black font-medium">
                                            {{ $p->is_active ? 'Aktif' : 'Nonaktif' }}
                                            @if ($p->show_on_web)
                                                <span class="ml-1 text-xs text-black font-normal">· Web</span>
                                            @endif
                                        </td>
                                        <td class="px-5 py-3.5">
                                            <div class="flex justify-end gap-3 text-xs">
                                                <a href="{{ route('admin.products.edit', $p) }}"
                                                    class="font-semibold text-amber-800 hover:text-amber-900 hover:underline">Edit</a>
                                                <form method="POST" action="{{ route('admin.products.destroy', $p) }}"
                                                    onsubmit="return confirm('Hapus menu {{ e($p->name) }}?')">
                                                    @csrf @method('DELETE')
                                                    <button
                                                        class="font-semibold text-rose-600 hover:text-rose-700 hover:underline">Hapus</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="px-5 py-10 text-center text-black font-medium">Belum ada menu yang terdaftar.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                {{ $products->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
