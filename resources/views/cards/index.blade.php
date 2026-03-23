<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Карточки — {{ $world->name }} — Noema</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=cormorant-garamond:400,500,600,700|instrument-sans:400,500,600" rel="stylesheet">
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

</head>
<body class="min-h-screen bg-base-100 flex flex-col">
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
        <div class="flex items-center justify-between mb-8">
            <h1 class="text-[1.875rem] font-semibold text-base-content" style="font-family: 'Cormorant Garamond', Georgia, serif;">Карточки</h1>
            <div class="flex items-center gap-2">
            <a href="{{ route('worlds.dashboard', $world) }}" class="btn btn-ghost btn-square" title="Назад в дашборд">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
            </a>
            <button type="button" class="btn btn-ghost btn-square" onclick="addStoryModal.showModal()" title="Новая история">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
            </button>
            </div>
        </div>

        @if ($stories->isNotEmpty())
            <div class="card-block-container">
                @foreach ($stories as $story)
                    <a href="{{ route('cards.show', [$world, $story]) }}" class="card card-block bg-base-200 border border-base-300 hover:border-primary/30 transition-colors flex items-center justify-center p-6">
                        <h2 class="text-lg font-semibold text-base-content text-center">{{ $story->name }}</h2>
                    </a>
                @endforeach
            </div>
        @else
            <p class="text-base-content/60 mb-6">Пока нет историй. Создайте первую.</p>
        @endif
    </main>

    {{-- Модалка: новая история --}}
    <dialog id="addStoryModal" class="modal modal-middle">
        <form method="POST" action="{{ route('cards.stories.store', $world) }}" class="modal-box modal-styled rounded-none">
            @csrf
            <h2 class="text-xl font-semibold mb-4">Новая история</h2>
            <input type="text" name="name" required placeholder="Название истории"
                class="input input-bordered w-full rounded-none bg-base-200 border-base-300 mb-4 py-3">
            <div class="modal-action">
                <button type="button" class="btn btn-ghost rounded-none" onclick="addStoryModal.close()">Отмена</button>
                <button type="submit" class="btn btn-primary rounded-none">Создать</button>
            </div>
        </form>
        <form method="dialog" class="modal-backdrop"><button>close</button></form>
    </dialog>

    <style>
        .card-block-container { display: flex; flex-wrap: wrap; gap: 1rem; }
        .card-block-container .card-block {
            width: 200px !important; min-width: 200px !important; max-width: 200px !important;
            min-height: 120px !important; flex-shrink: 0 !important;
            border-radius: 0 !important;
        }
        dialog.modal:not([open]) { display: none !important; }
        dialog.modal[open] {
            position: fixed !important; top: 0 !important; left: 0 !important; right: 0 !important; bottom: 0 !important;
            width: 100vw !important; height: 100vh !important; margin: 0 !important; padding: 1rem !important;
            display: flex !important; align-items: center !important; justify-content: center !important;
            z-index: 999 !important; overflow-y: auto !important;
        }
        dialog.modal[open]::backdrop { background: rgba(0,0,0,0.6); }
        dialog.modal[open] .modal-backdrop {
            position: absolute !important; inset: 0 !important; z-index: -1 !important;
        }
        dialog.modal[open] .modal-box.modal-styled {
            margin: auto !important; flex-shrink: 0 !important;
            max-width: 560px; width: 90vw;
            padding: 2.5rem !important;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5) !important;
        }
        .modal-box.modal-styled h2 { margin-bottom: 1.5rem !important; font-size: 1.35rem !important; }
        .modal-box.modal-styled .modal-action { margin-top: 2rem !important; padding-top: 1.5rem !important; }
    </style>
    <footer class="py-4 text-center text-sm text-base-content/50 border-t border-base-300 mt-auto">
        &copy; GENEFIS MEDIA, {{ date('Y') }}
    </footer>
</body>
</html>
