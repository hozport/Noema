<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $story->name }} — Карточки — Noema</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=cormorant-garamond:400,500,600,700|instrument-sans:400,500,600" rel="stylesheet">
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    <style>
        .card-block-container { display: flex; flex-wrap: wrap; gap: 1rem; }
        .card-block-container .card-block {
            width: 200px !important; min-width: 200px !important; max-width: 200px !important;
            min-height: 120px !important; flex-shrink: 0 !important;
            border-radius: 0 !important;
        }
        .card-block-container.story-cards-page .card-block {
            width: 240px !important; min-width: 240px !important; max-width: 240px !important;
            min-height: 160px !important;
            padding: 25px !important;
            background: #fff !important;
            color: #1f2937 !important;
            border-color: rgba(0,0,0,0.1) !important;
        }
        .story-cards-page .card-block h3 { color: #1f2937 !important; }
        .story-cards-page .card-block p { color: #4b5563 !important; }
        .story-cards-page .card-block .text-primary { color: #2563eb !important; }
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
            max-width: 640px; width: 90vw;
            padding: 2.5rem !important;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5) !important;
        }
        .modal-box.modal-styled h2 { margin-bottom: 1.5rem !important; font-size: 1.35rem !important; }
        .modal-box.modal-styled .modal-action { margin-top: 2rem !important; padding-top: 1.5rem !important; }
    </style>
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
            <h1 class="text-[1.875rem] font-semibold text-base-content" style="font-family: 'Cormorant Garamond', Georgia, serif;">{{ $story->name }}</h1>
            <a href="{{ route('cards.index', $world) }}" class="btn btn-ghost btn-square" title="Все истории">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
            </a>
        </div>

        @if (session('success'))
            <p class="text-success mb-4">{{ session('success') }}</p>
        @endif
        @if (session('error'))
            <p class="text-error mb-4">{{ session('error') }}</p>
        @endif

        <div class="card-block-container story-cards-page">
            @foreach ($story->cards as $card)
                <div class="card card-block border story-card">
                    <div class="flex justify-between items-start gap-2">
                        <h3 class="font-semibold text-base-content text-base">{{ $card->title }}</h3>
                        <div class="flex gap-1 shrink-0">
                            <button type="button" class="btn btn-ghost btn-sm btn-square" onclick="openEditModal({{ json_encode(route('cards.update', [$world, $card])) }}, {{ json_encode($card->title) }}, {{ json_encode($card->content ?? '') }})" title="Редактировать">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            </button>
                            <form method="POST" action="{{ route('cards.decompose', [$world, $card]) }}" class="inline" onsubmit="return confirm('Каждый абзац станет отдельной карточкой. Продолжить?');">
                                @csrf
                                <button type="submit" class="btn btn-ghost btn-sm btn-square" title="Декомпозировать">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                                </button>
                            </form>
                        </div>
                    </div>
                    @php
                        $preview = $card->content ? Str::limit(str_replace("\n\n", ' ', $card->content), 80) : '';
                    @endphp
                    @if ($preview)
                        <p class="text-sm text-base-content/70 mt-2 line-clamp-2">{{ $preview }}</p>
                    @endif
                    <button type="button" class="mt-3 text-left text-sm text-primary hover:underline" onclick="openViewModal({{ json_encode($card->title) }}, {{ json_encode($card->content ?? '') }})">
                        {{ $card->content ? 'Содержимое' : 'Пусто' }} →
                    </button>
                </div>
            @endforeach
        </div>
    </main>

    {{-- Модалка просмотра --}}
    <dialog id="viewModal" class="modal modal-middle">
        <div class="modal-box modal-styled rounded-none">
            <h2 id="viewModalTitle" class="text-xl font-semibold mb-4"></h2>
            <div id="viewModalContent" class="prose prose-invert max-w-none whitespace-pre-wrap py-2"></div>
            <div class="modal-action">
                <form method="dialog">
                    <button class="btn btn-primary rounded-none">Закрыть</button>
                </form>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop"><button>close</button></form>
    </dialog>

    {{-- Модалка редактирования --}}
    <dialog id="editModal" class="modal modal-middle">
        <form method="POST" action="" id="editForm" class="modal-box modal-styled rounded-none">
            @csrf
            @method('PUT')
            <h2 id="editModalTitle" class="text-xl font-semibold mb-4"></h2>
            <textarea name="content" id="editModalContent" rows="12" placeholder="Введите текст. Каждый абзац (разделяйте пустой строкой) станет отдельной карточкой при декомпозиции."
                class="textarea textarea-bordered w-full rounded-none bg-base-200 border-base-300 resize-none"></textarea>
            <div class="modal-action">
                <button type="button" class="btn btn-ghost rounded-none" onclick="editModal.close()">Отмена</button>
                <button type="submit" class="btn btn-primary rounded-none">Сохранить</button>
            </div>
        </form>
        <form method="dialog" class="modal-backdrop"><button>close</button></form>
    </dialog>

    <footer class="py-4 text-center text-sm text-base-content/50 border-t border-base-300 mt-auto">
        &copy; GENEFIS MEDIA, {{ date('Y') }}
    </footer>

    <script>
        const viewModal = document.getElementById('viewModal');
        const editModal = document.getElementById('editModal');

        function openViewModal(title, content) {
            document.getElementById('viewModalTitle').textContent = title;
            document.getElementById('viewModalContent').textContent = content || '(пусто)';
            viewModal.showModal();
        }

        function openEditModal(actionUrl, title, content) {
            document.getElementById('editModalTitle').textContent = title;
            document.getElementById('editModalContent').value = content || '';
            document.getElementById('editForm').action = actionUrl;
            editModal.showModal();
        }
    </script>
</body>
</html>
