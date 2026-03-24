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
        @if (session('success'))
            <p class="text-success mb-4">{{ session('success') }}</p>
        @endif

        @if ($worlds->isNotEmpty())
            <div class="world-cards-grid mb-12">
                @foreach ($worlds as $world)
                    <div class="world-card bg-base-200 border border-base-300 hover:border-primary/30 transition-colors rounded-none overflow-hidden flex flex-col relative">
                        <button type="button"
                            class="world-card-delete btn btn-ghost btn-square btn-sm text-error hover:bg-error/20"
                            title="Удалить мир"
                            onclick="event.stopPropagation(); openDeleteWorldModal({{ $world->id }}, {{ json_encode($world->name) }})">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                            </svg>
                        </button>
                        <div class="world-card-inner flex flex-col flex-1 min-h-0">
                            @if ($world->imageUrl())
                                <div class="world-card-img-wrap shrink-0">
                                    <img src="{{ $world->imageUrl() }}" alt="" class="world-card-img">
                                </div>
                            @else
                                <div class="world-card-img-wrap world-card-img-placeholder shrink-0"></div>
                            @endif
                            <div class="world-card-body flex-1 flex flex-col min-h-0">
                                <h2 class="world-card-title text-base-content shrink-0 font-semibold">{{ $world->name }}</h2>
                                @if ($world->annotation)
                                    <p class="world-card-synopsis text-sm text-base-content/70">{{ $world->annotation }}</p>
                                @endif
                            </div>
                            <a href="{{ route('worlds.dashboard', $world) }}" class="world-card-enter btn btn-primary btn-sm rounded-none shrink-0">
                                Войти
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="flex justify-center">
            <a href="{{ route('worlds.create') }}" class="btn btn-primary min-h-0 normal-case text-base font-medium" style="padding: 1.25rem 3rem; border-radius: 0;">
                Создать Мир
            </a>
        </div>
    </main>

    <dialog id="deleteWorldModal" class="modal modal-middle">
        <div class="modal-box modal-styled rounded-none">
            <h2 class="text-xl font-semibold mb-4">Удалить мир?</h2>
            <p class="text-base-content/80 mb-2">Действительно ли вы хотите удалить мир «<span id="deleteWorldModalName"></span>»?</p>
            <p class="text-sm text-base-content/60 mb-4">Мир будет скрыт из списка; данные в базе сохранятся.</p>
            <form id="deleteWorldForm" method="POST" class="modal-action flex flex-row-reverse flex-wrap gap-2 justify-end">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-error rounded-none">Удалить</button>
                <button type="button" class="btn btn-ghost rounded-none" onclick="deleteWorldModal.close()">Отмена</button>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop"><button type="submit">close</button></form>
    </dialog>

    <style>
        .world-cards-grid { display: flex; flex-wrap: wrap; gap: 1rem; }
        .world-card {
            width: 250px !important; min-width: 250px !important; max-width: 250px !important;
            height: 340px !important; min-height: 340px !important; max-height: 340px !important;
            flex-shrink: 0 !important;
            border-radius: 0 !important;
        }
        .world-card-delete {
            position: absolute !important;
            top: 6px !important;
            right: 6px !important;
            left: auto !important;
            z-index: 30 !important;
        }
        .world-card-inner { min-height: 0; }
        .world-card-title {
            font-family: 'Cormorant Garamond', Georgia, serif !important;
            font-size: 1.625rem !important;
            line-height: 1.2 !important;
            margin-top: 1.25rem !important;
            margin-bottom: 0 !important;
        }
        .world-card-body {
            padding: 0.375rem 14px 8px !important;
        }
        .world-card-synopsis {
            margin-top: 1.125rem !important;
        }
        .world-card-enter {
            width: 100% !important;
            margin-top: auto !important;
            border-radius: 0 !important;
            min-height: 2.5rem !important;
            font-weight: 500 !important;
        }
        .world-card-img-wrap {
            width: 100%;
            height: 120px;
            overflow: hidden;
            margin: 0;
            padding: 0;
        }
        .world-card-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            display: block;
        }
        .world-card-img-placeholder {
            background: linear-gradient(135deg, rgba(255,255,255,0.06), rgba(255,255,255,0.02));
        }
        .world-card-synopsis {
            display: -webkit-box;
            -webkit-line-clamp: 5;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.4;
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
        .modal-box.modal-styled {
            margin: auto !important; flex-shrink: 0 !important;
            max-width: 480px; width: 90vw;
            padding: 2.5rem !important;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5) !important;
        }
    </style>

    <script>
        const deleteWorldModal = document.getElementById('deleteWorldModal');
        const deleteWorldForm = document.getElementById('deleteWorldForm');
        function openDeleteWorldModal(id, name) {
            document.getElementById('deleteWorldModalName').textContent = name;
            deleteWorldForm.action = '{{ url('/worlds') }}/' + id;
            deleteWorldModal.showModal();
        }
    </script>

    <footer class="py-4 text-center text-sm text-base-content/50 border-t border-base-300 mt-auto">
        &copy; GENEFIS MEDIA, {{ date('Y') }}
    </footer>
</body>
</html>
