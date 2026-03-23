<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Мои Миры — Noema</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=cormorant-garamond:400,500,600,700|instrument-sans:400,500,600" rel="stylesheet">
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="min-h-screen bg-base-100 flex flex-col">
    {{-- Header: логотип слева, выход справа --}}
    <header class="flex items-center justify-between p-6 border-b border-base-300">
        <a href="{{ route('worlds.index') }}" class="font-display text-xl tracking-widest text-base-content/80 hover:text-base-content transition">
            <span class="text-base-content/50">GENEFIS MEDIA's</span>
            <span class="block text-2xl font-semibold text-base-content">NOEMA</span>
        </a>
        <form method="POST" action="{{ route('logout') }}" class="inline">
            @csrf
            <button type="submit" class="btn btn-ghost btn-sm btn-square text-base-content/70 hover:text-base-content hover:bg-base-200" title="Выход">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                    <polyline points="16 17 21 12 16 7"/>
                    <line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
            </button>
        </form>
    </header>

    <main class="flex-1 p-6 max-w-[1344px] w-full mx-auto">
        {{-- Список миров --}}
        @if ($worlds->isNotEmpty())
            <div class="card-block-container mb-12">
                @foreach ($worlds as $world)
                    <a href="{{ route('worlds.dashboard', $world) }}" class="card card-block bg-base-200 border border-base-300 hover:border-primary/30 transition-colors block p-6">
                        @if ($world->imageUrl())
                            <img src="{{ $world->imageUrl() }}" alt="" class="w-16 h-16 object-cover mb-3">
                        @endif
                        <h2 class="text-lg font-semibold text-base-content" style="font-family: 'Cormorant Garamond', Georgia, serif;">{{ $world->name }}</h2>
                        @if ($world->annotation)
                            <p class="text-sm text-base-content/70 mt-2 line-clamp-2">{{ $world->annotation }}</p>
                        @endif
                    </a>
                @endforeach
            </div>
        @endif

        {{-- Кнопка Создать Мир --}}
        <div class="flex justify-center">
            <a href="{{ route('worlds.create') }}" class="btn btn-primary min-h-0 normal-case text-base font-medium" style="padding: 1.25rem 3rem; border-radius: 0;">
                Создать Мир
            </a>
        </div>
    </main>
    <style>
        .card-block-container { display: flex; flex-wrap: wrap; gap: 1rem; }
        .card-block-container .card-block {
            width: 200px !important; min-width: 200px !important; max-width: 200px !important;
            min-height: 120px !important; flex-shrink: 0 !important;
            border-radius: 0 !important;
        }
    </style>
    <footer class="py-4 text-center text-sm text-base-content/50 border-t border-base-300 mt-auto">
        &copy; GENEFIS MEDIA, {{ date('Y') }}
    </footer>
</body>
</html>
