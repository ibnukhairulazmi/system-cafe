@auth
@php
    $role = auth()->user()->role->value;
    $notifyEnabled = in_array($role, ['admin', 'kasir'], true);

    $initial = $notifyEnabled
        ? \App\Http\Controllers\NotificationCountController::counts()
        : ['reservations' => 0, 'delivery_orders' => 0];

    $reservationUrl = \Illuminate\Support\Facades\Route::has($role . '.reservations.index')
        ? route($role . '.reservations.index') : '#';
    $deliveryUrl = \Illuminate\Support\Facades\Route::has($role . '.delivery-orders.index')
        ? route($role . '.delivery-orders.index') : '#';
@endphp

@if($notifyEnabled)

<div x-data x-cloak
     class="pointer-events-none fixed right-4 top-4 z-[100] flex w-80 max-w-[calc(100vw-2rem)] flex-col gap-2">
    <template x-for="t in $store.toasts.items" :key="t.id">
        <div x-transition
             class="pointer-events-auto rounded-xl border border-gray-200 bg-white p-4 shadow-lg">
            <div class="flex items-start gap-3">
                <span class="relative mt-1 flex h-2.5 w-2.5 shrink-0">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-400 opacity-75"></span>
                    <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-red-500"></span>
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-gray-900" x-text="t.title"></p>
                    <p class="mt-0.5 text-sm text-gray-600" x-text="t.message"></p>
                    <a :href="t.url"
                       class="mt-2 inline-block text-sm font-medium text-indigo-600 hover:underline">
                        Lihat sekarang
                    </a>
                </div>
                <button type="button" @click="$store.toasts.remove(t.id)"
                        class="text-gray-400 hover:text-gray-600" aria-label="Tutup">✕</button>
            </div>
        </div>
    </template>
</div>

<script>
document.addEventListener('alpine:init', () => {
    const endpoint = @json(route('notifications.counts'));
    const urls = { reservations: @json($reservationUrl), delivery_orders: @json($deliveryUrl) };
    const initial = @json($initial);

    Alpine.store('orderCounts', {
        reservations: initial.reservations,
        delivery_orders: initial.delivery_orders,
    });

    Alpine.store('toasts', {
        items: [],
        seq: 0,
        push(title, message, url) {
            const id = ++this.seq;
            this.items.push({ id, title, message, url });
            setTimeout(() => this.remove(id), 8000);
        },
        remove(id) {
            this.items = this.items.filter(i => i.id !== id);
        },
    });

    let audioCtx = null;
    function beep() {
        try {
            audioCtx = audioCtx || new (window.AudioContext || window.webkitAudioContext)();
            if (audioCtx.state === 'suspended') audioCtx.resume();
            const osc = audioCtx.createOscillator();
            const gain = audioCtx.createGain();
            osc.connect(gain);
            gain.connect(audioCtx.destination);
            osc.frequency.value = 880;
            gain.gain.value = 0.1;
            osc.start();
            osc.stop(audioCtx.currentTime + 0.25);
        } catch (e) {  }
    }

    async function poll() {
        try {
            const res = await fetch(endpoint, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            if (!res.ok) return;
            const data = await res.json();
            const store = Alpine.store('orderCounts');
            const toasts = Alpine.store('toasts');
            let rang = false;

            if (data.reservations > store.reservations) {
                toasts.push('Reservasi baru', data.reservations + ' reservasi menunggu diproses.', urls.reservations);
                rang = true;
            }
            if (data.delivery_orders > store.delivery_orders) {
                toasts.push('Pesanan antar baru', data.delivery_orders + ' pesanan antar menunggu diproses.', urls.delivery_orders);
                rang = true;
            }

            store.reservations = data.reservations;
            store.delivery_orders = data.delivery_orders;
            if (rang) beep();
        } catch (e) {  }
    }

    setInterval(poll, 15000);
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) poll();
    });
});
</script>
@endif
@endauth