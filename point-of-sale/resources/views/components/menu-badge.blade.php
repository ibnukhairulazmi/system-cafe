@props(['label'])

@php
    $key = [
        'Reservasi'     => 'reservations',
        'Pesanan Antar' => 'delivery_orders',
    ][$label] ?? null;
@endphp

@if($key)
    <span x-data x-cloak
        x-show="$store.orderCounts && $store.orderCounts.{{ $key }} > 0"
        class="ml-auto inline-flex items-center gap-1.5 pl-2">
        <span class="relative flex h-2.5 w-2.5">
            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-400 opacity-75"></span>
            <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-red-500"></span>
        </span>
        <span class="text-xs font-semibold text-red-600"
              x-text="$store.orderCounts.{{ $key }}"></span>
    </span>
@endif