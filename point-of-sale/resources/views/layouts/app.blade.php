<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Ruang Teduh') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/icon.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,500;0,600;0,700;1,500;1,600&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<x-order-notifier />
<body class="font-sans antialiased text-stone-900 bg-stone-50 selection:bg-amber-100 selection:text-amber-800 role-{{ auth()->user()?->role?->value }} {{ (auth()->user()?->role?->value === 'admin' || request()->is('admin*')) ? 'role-admin' : '' }}">
    @php $useSidebar = auth()->user()->role->value !== 'kasir'; @endphp

    <div class="min-h-screen bg-stone-50">
        @include('layouts.navigation')

        @if ($useSidebar)
            <style>
                @media (min-width: 1024px) {
                    .sidebar-content { padding-left: var(--sidebar-w, 18rem); }
                }
            </style>
        @endif

        <div @class(['sidebar-content' => $useSidebar])>
            @isset($header)
                <header> ... </header>
            @endisset

            <main>
                {{ $slot }}
            </main>
        </div>
    </div>
</body>

</html>
    