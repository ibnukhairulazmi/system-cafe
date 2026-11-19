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

<body class="font-sans text-stone-900 antialiased selection:bg-amber-100 selection:text-amber-800">
    <div class="relative min-h-screen flex flex-col justify-center items-center px-4 py-12 bg-cover bg-center bg-no-repeat"
        style="background-image: url( {{ asset('images/cafe-bg.jpg') }} );">

        <div class="absolute inset-0 bg-stone-950/80"></div>

        <div class="relative z-10 w-full sm:max-w-md">
            <div class="bg-white shadow-2xl rounded-2xl border border-stone-200/90 p-8 sm:p-10">
                {{ $slot }}
            </div>
            <p class="mt-6 text-center text-xs text-stone-400">
                &copy; {{ date('Y') }} Ruang Teduh.
            </p>
        </div>
    </div>
</body>

</html>
