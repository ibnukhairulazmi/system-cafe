@php
    $menus = [
        'superadmin' => [
            ['Dashboard', 'superadmin.dashboard', 'superadmin.dashboard'],
            ['Transaksi', 'superadmin.transactions.index', 'superadmin.transactions.*'],
            ['Reservasi', 'superadmin.reservations.index', 'superadmin.reservations.*'],
            ['Pesanan Antar', 'superadmin.delivery-orders.index', 'superadmin.delivery-orders.*'],
            ['Shift', 'superadmin.shifts.index', 'superadmin.shifts.*'],
            ['Stok', 'superadmin.stock.index', 'superadmin.stock.*'],
            ['Pesanan Bahan', 'superadmin.purchase-orders.index', 'superadmin.purchase-orders.*'],
            ['Pegawai', 'superadmin.employees.index', 'superadmin.employees.*'],
            ['Laporan', 'superadmin.reports.sales', 'superadmin.reports.*'],
            ['Log Aktivitas', 'superadmin.activity-logs.index', 'superadmin.activity-logs.*'],
            ['Pengaturan', 'superadmin.settings.edit', 'superadmin.settings.*'],
        ],
        'admin' => [
            ['Dashboard', 'admin.dashboard', 'admin.dashboard'],
            ['Verifikasi', 'admin.verifications.index', 'admin.verifications.*'],
            ['Reservasi', 'admin.reservations.index', 'admin.reservations.*'],
            ['Pesanan Antar', 'admin.delivery-orders.index', 'admin.delivery-orders.*'],
            ['Kategori', 'admin.categories.index', 'admin.categories.*'],
            ['Menu', 'admin.products.index', 'admin.products.*'],
            ['Bahan', 'admin.ingredients.index', 'admin.ingredients.*'],
            ['Stok', 'admin.stock.index', 'admin.stock.*'],
            ['Supplier', 'admin.suppliers.index', 'admin.suppliers.*'],
            ['Pesanan Bahan', 'admin.purchase-orders.index', 'admin.purchase-orders.*'],
            ['Shift', 'admin.shifts.index', 'admin.shifts.*'],
            ['Laporan', 'admin.reports.sales', 'admin.reports.*'],
        ],
        'kasir' => [
            ['Shift', 'kasir.shift.index', 'kasir.shift.*'],
            ['Transaksi', 'kasir.pos', 'kasir.pos'],
            ['Meja', 'kasir.tables.index', 'kasir.tables.*'],
            ['Reservasi', 'kasir.reservations.index', 'kasir.reservations.*'],
            ['Pesanan Antar', 'kasir.delivery-orders.index', 'kasir.delivery-orders.*'],
            ['Riwayat', 'kasir.transactions.index', 'kasir.transactions.*'],
        ],
    ];

    $role = auth()->user()->role->value;
    $items = collect($menus[$role] ?? [])->filter(fn($m) => Route::has($m[1]));

    $icons = [
        'Dashboard' => '<rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/>',
        'Pegawai' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'Transaksi' => '<path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/><path d="M12 17.5v-11"/>',
        'Pesanan Bahan' => '<path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/>',
        'Log Aktivitas' => '<path d="M22 12h-4l-3 9L9 3l-3 9H2"/>',
        'Laporan' => '<path d="M3 3v18h18"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/>',
        'Pengaturan' => '<line x1="21" x2="14" y1="4" y2="4"/><line x1="10" x2="3" y1="4" y2="4"/><line x1="21" x2="12" y1="12" y2="12"/><line x1="8" x2="3" y1="12" y2="12"/><line x1="21" x2="16" y1="20" y2="20"/><line x1="12" x2="3" y1="20" y2="20"/><line x1="14" x2="14" y1="2" y2="6"/><line x1="8" x2="8" y1="10" y2="14"/><line x1="16" x2="16" y1="18" y2="22"/>',
        'Reservasi' => '<path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/><path d="m9 16 2 2 4-4"/>',
        'Pesanan Antar' => '<path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/>',
        'Kategori' => '<rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/>',
        'Menu' => '<path d="M10 2v2"/><path d="M14 2v2"/><path d="M16 8a1 1 0 0 1 1 1v8a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4V9a1 1 0 0 1 1-1h14a4 4 0 1 1 0 8h-1"/><path d="M6 2v2"/>',
        'Bahan' => '<path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"/><path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/>',
        'Stok' => '<path d="M22 8.35V20a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V8.35A2 2 0 0 1 3.26 6.5l8-3.2a2 2 0 0 1 1.48 0l8 3.2A2 2 0 0 1 22 8.35Z"/><path d="M6 18h12"/><path d="M6 14h12"/><rect width="12" height="12" x="6" y="10"/>',
        'Supplier' => '<path d="m2 7 4.41-4.41A2 2 0 0 1 7.83 2h8.34a2 2 0 0 1 1.42.59L22 7"/><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><path d="M15 22v-4a2 2 0 0 0-2-2h-2a2 2 0 0 0-2 2v4"/><path d="M2 7h20"/>',
        'Verifikasi' => '<circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/>',
        'Shift' => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
        'Riwayat' => '<path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l4 2"/>',
    ];
@endphp

@if ($role === 'kasir')
    <nav x-data="{ open: false }" class="sticky top-0 z-40 bg-white border-b border-stone-200/90 shadow-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid w-full h-16 grid-cols-[1fr_auto_1fr] items-center">
                <div class="flex justify-start">
                    <a href="{{ route('dashboard') }}" class="focus:outline-none">
                        <x-application-logo />
                    </a>
                </div>

                <div class="hidden space-x-1 lg:flex">
                    @foreach ($items as [$label, $route, $pattern])
                        <x-nav-link :href="route($route)" :active="request()->routeIs($pattern)">
                            {{ $label }}
                            <x-menu-badge :label="$label" />
                        </x-nav-link>
                    @endforeach
                </div>

                <div class="col-start-3 flex items-center justify-end gap-3">
                    <a class="text-right hover:opacity-80">
                        <div class="text-xs font-semibold text-stone-800">{{ Auth::user()->name }}</div>
                        <div class="text-[11px] text-stone-500 font-medium">{{ Auth::user()->role->label() }}</div>
                    </a>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" title="Keluar" aria-label="Keluar"
                            class="inline-flex items-center justify-center p-2 rounded-xl text-stone-400 hover:text-rose-600 hover:bg-stone-100 focus:outline-none transition duration-150">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                                <path fill="currentColor"
                                    d="M5 21q-.825 0-1.412-.587T3 19V5q0-.825.588-1.412T5 3h6q.425 0 .713.288T12 4t-.288.713T11 5H5v14h6q.425 0 .713.288T12 20t-.288.713T11 21zm12.175-8H10q-.425 0-.712-.288T9 12t.288-.712T10 11h7.175L15.3 9.125q-.275-.275-.275-.675t.275-.7t.7-.313t.725.288L20.3 11.3q.3.3.3.7t-.3.7l-3.575 3.575q-.3.3-.712.288t-.713-.313q-.275-.3-.262-.712t.287-.688z" />
                            </svg>
                        </button>
                    </form>

                    <div class="-me-2 flex items-center lg:hidden">
                        <button @click="open = ! open"
                            class="inline-flex items-center justify-center p-2 rounded-xl text-stone-500 hover:text-stone-700 hover:bg-stone-100 focus:outline-none transition duration-150">
                            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                <path :class="{ 'hidden': open, 'inline-flex': !open }" class="inline-flex"
                                    stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 6h16M4 12h16M4 18h16" />
                                <path :class="{ 'hidden': !open, 'inline-flex': open }" class="hidden"
                                    stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div x-show="open" x-transition @click.outside="open = false" style="display: none;"
            class="absolute right-4 sm:right-6 top-full mt-2 w-72 z-50 rounded-2xl lg:hidden bg-white border border-stone-200 shadow-xl max-h-[80vh] overflow-y-auto p-2">

            <div class="space-y-0.5">
                @foreach ($items as [$label, $route, $pattern])
                    <x-responsive-nav-link :href="route($route)" :active="request()->routeIs($pattern)">
                        <span class="flex items-center justify-between">
                            <span>{{ $label }}</span>
                            <x-menu-badge :label="$label" />
                        </span>
                    </x-responsive-nav-link>
                @endforeach
            </div>

            <div class="mt-2 pt-3 border-t border-stone-200 px-3 pb-2 flex items-center justify-between">
                <div>
                    <div class="font-semibold text-sm text-stone-800">{{ Auth::user()->name }}</div>
                    <div class="text-xs text-amber-800 font-medium">{{ Auth::user()->role->label() }}</div>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-rose-600 bg-rose-50 hover:bg-rose-100 transition">
                        Keluar
                    </button>
                </form>
            </div>
        </div>
    </nav>
@else


    <div x-data="{ open: false }">
        <div class="sticky top-0 z-40 flex h-14 items-center justify-between border-b border-stone-200/90 bg-white px-4 shadow-xs lg:hidden">
            <a href="{{ route('dashboard') }}" class="focus:outline-none">
                <x-application-logo />
            </a>
            <button @click="open = true" aria-label="Buka menu"
                class="inline-flex items-center justify-center rounded-xl p-2 text-stone-500 transition duration-150 hover:bg-stone-100 hover:text-stone-700 focus:outline-none">
                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
        </div>

        <div x-show="open" x-transition.opacity @click="open = false" style="display: none;"
            class="fixed inset-0 z-40 bg-stone-900/40 lg:hidden"></div>

        <aside style="width: var(--sidebar-w, 18rem)"
            class="fixed inset-y-0 left-0 z-50 flex flex-col border-r border-stone-200/90 bg-white transition-transform duration-200"
            :class="open ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">

            <div class="flex h-16 shrink-0 items-center justify-between px-4">
                <a href="{{ route('dashboard') }}" class="focus:outline-none">
                    <x-application-logo />
                </a>
                <button @click="open = false" aria-label="Tutup menu"
                    class="inline-flex items-center justify-center rounded-xl p-2 text-stone-400 transition duration-150 hover:bg-stone-100 hover:text-stone-700 focus:outline-none lg:hidden">
                    <svg class="h-5 w-5" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            
            <nav class="flex-1 space-y-0.5 overflow-y-auto px-3 py-2">
                @foreach ($items as [$label, $route, $pattern])
                    @php $active = request()->routeIs($pattern); @endphp
                    <a href="{{ route($route) }}" @click="open = false"
                        class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition duration-150 focus:outline-none
                            {{ $active ? 'bg-stone-100 font-semibold text-stone-900' : 'font-medium text-stone-600 hover:bg-stone-100 hover:text-stone-900' }}">
                        <svg class="h-4 w-4 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            {!! $icons[$label] ?? '<circle cx="12" cy="12" r="10"/>' !!}
                        </svg>
                        <span>{{ $label }}</span>
                        <x-menu-badge :label="$label" />
                    </a>
                @endforeach
            </nav>

            
            <div class="flex items-center justify-between gap-2 border-t border-stone-200 p-3">
                <a class="min-w-0 flex-1 rounded-lg px-2 py-1.5 hover:bg-stone-100">
                    <div class="truncate text-sm font-semibold text-stone-800">{{ Auth::user()->name }}</div>
                    <div class="truncate text-xs font-medium text-amber-800">{{ Auth::user()->role->label() }}</div>
                </a>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" title="Keluar" aria-label="Keluar"
                        class="inline-flex items-center justify-center rounded-xl p-2 text-stone-400 transition duration-150 hover:bg-stone-100 hover:text-rose-600 focus:outline-none">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24">
                            <path fill="currentColor"
                                d="M5 21q-.825 0-1.412-.587T3 19V5q0-.825.588-1.412T5 3h6q.425 0 .713.288T12 4t-.288.713T11 5H5v14h6q.425 0 .713.288T12 20t-.288.713T11 21zm12.175-8H10q-.425 0-.712-.288T9 12t.288-.712T10 11h7.175L15.3 9.125q-.275-.275-.275-.675t.275-.7t.7-.313t.725.288L20.3 11.3q.3.3.3.7t-.3.7l-3.575 3.575q-.3.3-.712.288t-.713-.313q-.275-.3-.262-.712t.287-.688z" />
                        </svg>
                    </button>
                </form>
            </div>
        </aside>
    </div>
@endif