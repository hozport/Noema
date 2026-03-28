<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Связи — {{ $world->name }} — Noema</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=cormorant-garamond:400,500,600,700|instrument-sans:400,500,600" rel="stylesheet">
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="min-h-screen bg-base-100 flex flex-col">
    @include('site.partials.header')

    <main class="flex-1 p-6 max-w-[1344px] w-full mx-auto">
        <div class="flex flex-wrap items-start justify-between gap-4 mb-8">
            <div class="min-w-0">
                <h1 class="text-[1.875rem] font-semibold text-base-content leading-tight" style="font-family: 'Cormorant Garamond', Georgia, serif;">Связи</h1>
                <p class="text-base-content/60 mt-2 max-w-2xl">{{ $world->name }}</p>
            </div>
            <a href="{{ route('worlds.dashboard', $world) }}" class="btn btn-ghost btn-square shrink-0 mt-0.5" title="Назад в дашборд" aria-label="Назад в дашборд">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
            </a>
        </div>

        <div class="border border-dashed border-base-300 bg-base-200/40 rounded-none p-8 max-w-3xl">
            <p class="text-base-content/90 leading-relaxed">
                Здесь будет доска: на одном полотне — биографии, события, существа бестиария, местности с карты и другие сущности мира, соединённые настраиваемыми связями.
            </p>
            <p class="text-base-content/55 text-sm mt-4">
                Раздел в разработке.
            </p>
        </div>
    </main>

    @include('site.partials.footer')
</body>
</html>
