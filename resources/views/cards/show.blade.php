<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $story->name }} — Карточки — Noema</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=cormorant-garamond:400,500,600,700|instrument-sans:400,500,600|playfair-display:400,500,600,700" rel="stylesheet">
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/story-cards.js'])
    @endif
    <style>
        #story-cards-sortable {
            --story-cards-gap: 1rem;
            display: flex;
            flex-wrap: wrap;
            gap: var(--story-cards-gap);
            align-content: flex-start;
            width: 100%;
        }
        .story-card-wrap {
            box-sizing: border-box;
            flex: 0 1 calc((100% - 3 * var(--story-cards-gap)) / 4);
            max-width: calc((100% - 3 * var(--story-cards-gap)) / 4);
            min-width: 0;
            height: 340px !important;
            min-height: 340px !important;
            max-height: 340px !important;
            border-radius: 0 !important;
            cursor: grab;
        }
        .story-card-wrap:active { cursor: grabbing; }
        .story-card-ghost { opacity: 0.45; }
        .story-card-inner {
            position: relative;
            height: 100%;
            display: flex;
            flex-direction: column;
            min-height: 0;
            background: #fff !important;
            color: #1f2937 !important;
            border: 1px solid rgba(0,0,0,0.1) !important;
            padding: 0 !important;
        }
        .story-card-wrap--highlighted .story-card-inner {
            border: 5px solid #dc2626 !important;
        }
        .story-card-highlight-flag {
            display: none;
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            z-index: 5;
            color: #dc2626;
            line-height: 0;
            pointer-events: none;
        }
        .story-card-wrap--highlighted .story-card-highlight-flag {
            display: block;
        }
        .card-order-number {
            font-family: 'Playfair Display', 'Cormorant Garamond', Georgia, serif;
            font-size: 2.25rem;
            font-weight: 600;
            line-height: 1;
            text-align: center;
            color: #111827;
            letter-spacing: 0.02em;
            margin: 0;
            padding: 0.65rem 0.5rem 0.5rem;
            border-bottom: 1px solid rgba(0,0,0,0.08);
            flex-shrink: 0;
        }
        .story-card-body { flex: 1; min-height: 0; display: flex; flex-direction: column; padding: 0.75rem 14px 8px; }
        .story-card-title-display {
            color: #1f2937 !important;
            font-size: 0.9375rem !important;
            font-weight: 600 !important;
            line-height: 1.25 !important;
            margin: 0;
        }
        .story-card-preview { color: #4b5563 !important; font-size: 0.875rem; line-height: 1.35; margin-top: 0.5rem;
            display: -webkit-box; -webkit-line-clamp: 5; -webkit-box-orient: vertical; overflow: hidden; }
        /* Как .world-card-enter у карточек миров */
        .story-card-more {
            width: 100% !important;
            margin-top: auto !important;
            border-radius: 0 !important;
            min-height: 2.5rem !important;
            font-weight: 500 !important;
        }

        /* Модалки: весь экран без полосы от 100vw/скроллбара */
        .story-dialog {
            position: fixed !important;
            inset: 0 !important;
            width: 100% !important;
            height: 100% !important;
            max-width: none !important;
            max-height: none !important;
            margin: 0 !important;
            padding: 0 !important;
            border: none !important;
            background: transparent !important;
            overflow: hidden !important;
            box-sizing: border-box !important;
        }
        .story-dialog::backdrop { background: rgba(0,0,0,0.55); }
        .story-dialog:not([open]) { display: none !important; }
        .story-dialog[open] { display: block !important; }
        .story-dialog__viewport {
            position: relative;
            width: 100%;
            height: 100%;
            min-height: 100dvh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            box-sizing: border-box;
        }
        .story-dialog__scrim {
            position: absolute;
            inset: 0;
            z-index: 0;
            cursor: pointer;
        }
        .story-dialog__panel {
            position: relative;
            z-index: 1;
            width: min(640px, calc(100vw - 2rem));
            max-height: min(85vh, calc(100dvh - 2rem));
            overflow: auto;
            background: var(--color-base-100, #1d232a);
            padding: 2.5rem 2.5rem 2rem;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
            border-radius: 0;
        }
        .story-dialog__close {
            position: absolute;
            top: 0.75rem;
            right: 0.75rem;
            z-index: 2;
            width: 2.5rem;
            height: 2.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            border: none;
            background: transparent;
            color: var(--color-base-content, #fff);
            opacity: 0.75;
            cursor: pointer;
            border-radius: 0;
        }
        .story-dialog__close:hover { opacity: 1; }
        .story-dialog__panel .story-dialog__field-label { padding-right: 2.5rem; }
        .story-cards-stat { margin-top: 2.5rem; }
        .story-page-synopsis {
            margin-top: 40px;
            max-width: 48rem;
        }
        .story-synopsis-stat-divider {
            border: none;
            border-top: 1px solid rgba(255, 255, 255, 0.12);
            margin: 24px 0;
            max-width: 48rem;
        }
        .story-synopsis-stat-divider + .story-cards-stat {
            margin-top: 0;
        }
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
        <div id="story-page-root" data-world-id="{{ $world->id }}" data-story-id="{{ $story->id }}">
        <div class="flex items-center justify-between mb-8 gap-4">
            <h1 class="text-[1.875rem] font-semibold text-base-content" style="font-family: 'Cormorant Garamond', Georgia, serif;">{{ $story->name }}</h1>
            <div class="flex items-center gap-1 shrink-0">
                <a href="{{ route('cards.index', $world) }}" class="btn btn-ghost btn-square" title="Назад к списку историй">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M19 12H5M12 19l-7-7 7-7"/>
                    </svg>
                </a>
                <a href="{{ route('cards.stories.pdf', [$world, $story]) }}" class="btn btn-ghost btn-square" title="Скачать PDF">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <path d="M12 18V9M9 15l3 3 3-3"/>
                    </svg>
                </a>
                <button type="button" class="btn btn-ghost btn-square" title="Настройки истории" onclick="document.getElementById('storySettingsModal').showModal()" aria-label="Настройки истории">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="3"/>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                    </svg>
                </button>
            </div>
        </div>

        @if (session('success'))
            <p class="text-success mb-4">{{ session('success') }}</p>
        @endif
        @if (session('error'))
            <p class="text-error mb-4">{{ session('error') }}</p>
        @endif

        @if ($story->cards->isEmpty())
            <p class="text-base-content/70 mb-8" role="status">Карточек пока не существует.</p>
        @else
        <div class="mb-6 max-w-md">
            <input type="search" id="story-cards-search" name="story_cards_search" autocomplete="off"
                placeholder="Название, номер или фрагмент текста для фильтрации"
                class="input input-bordered w-full rounded-none bg-base-200 border-base-300">
        </div>
        <div id="story-cards-sortable" class="story-cards-sortable" data-reorder-url="{{ route('cards.reorder', [$world, $story]) }}">
            @foreach ($story->cards as $card)
                @php
                    $previewForSearch = $card->content ? Str::limit(str_replace(["\r\n", "\n"], ' ', $card->content), 800) : '';
                    $searchBlob = Str::lower($card->displayTitle() . ' ' . $previewForSearch . ' ' . $card->number);
                @endphp
                <div class="story-card-wrap card border-0 shadow-none @if($card->is_highlighted) story-card-wrap--highlighted @endif"
                    data-card-id="{{ $card->id }}"
                    data-card-number="{{ $card->number }}"
                    data-card-title="{{ e($card->title ?? '') }}"
                    data-search-text="{{ e($searchBlob) }}">
                    <div class="story-card-inner">
                        <span class="story-card-highlight-flag" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/>
                            </svg>
                        </span>
                        <div class="card-order-number">{{ $card->number }}</div>
                        <div class="story-card-body">
                            <div class="story-card-toolbar">
                                <h3 class="story-card-title-display font-semibold min-w-0 line-clamp-2">{{ $card->displayTitle() }}</h3>
                            </div>
                            @php
                                $preview = $card->content ? Str::limit(str_replace("\n\n", ' ', $card->content), 100) : '';
                            @endphp
                            @if ($preview)
                                <p class="story-card-preview">{{ $preview }}</p>
                            @endif
                        </div>
                        <button type="button" class="story-card-more btn btn-primary btn-sm rounded-none shrink-0" title="Подробнее — открыть редактирование карточки" onclick="openEditModal(this, {{ json_encode(route('cards.update', [$world, $card])) }}, {{ json_encode($card->title) }}, {{ json_encode($card->content ?? '') }}, {{ (int) $card->number }}, {{ json_encode(route('cards.decompose', [$world, $card])) }}, {{ json_encode(route('cards.destroy', [$world, $card])) }}, {{ json_encode(route('cards.highlight', [$world, $card])) }})">
                            Подробнее
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
        @endif

        @if (filled($story->synopsis))
            <section class="story-page-synopsis" aria-labelledby="story-synopsis-heading">
                <h2 id="story-synopsis-heading" class="text-sm font-medium text-base-content/60 mb-2">Синопсис</h2>
                <p class="text-base text-base-content/90 whitespace-pre-wrap leading-relaxed">{{ $story->synopsis }}</p>
            </section>
            <hr class="story-synopsis-stat-divider" aria-hidden="true">
        @endif

        <p class="story-cards-stat text-sm text-base-content/75">Количество карточек: {{ $story->cards->count() }}</p>
        </div>
    </main>

    <dialog id="storySettingsModal" class="story-dialog" aria-labelledby="story-settings-heading">
        <div class="story-dialog__viewport">
            <div class="story-dialog__scrim" data-story-settings-close tabindex="-1" aria-hidden="true"></div>
            <form method="POST" action="{{ route('cards.stories.update', [$world, $story]) }}" class="story-dialog__panel" onclick="event.stopPropagation()">
                @csrf
                @method('PUT')
                <h2 id="story-settings-heading" class="text-xl font-semibold mb-4 pr-8">Настройки истории</h2>
                <label for="storySettingsName" class="story-dialog__field-label block text-sm text-base-content/70 mb-1">Название</label>
                <input type="text" name="name" id="storySettingsName" value="{{ old('name', $story->name) }}" required maxlength="255"
                    class="input input-bordered w-full rounded-none bg-base-200 border-base-300 @error('name') input-error @enderror"
                    aria-describedby="storySettingsName-desc">
                <p id="storySettingsName-desc" class="text-xs text-base-content/50 mt-1">Обязательное поле, до 255 символов.</p>
                <p id="storySettingsNameCounter" class="text-xs text-right mt-1 tabular-nums" aria-live="polite"></p>
                @error('name')
                    <p class="text-error text-sm mt-1">{{ $message }}</p>
                @enderror
                <label for="storySettingsSynopsis" class="block text-sm text-base-content/70 mb-1 mt-4">Синопсис</label>
                <textarea name="synopsis" id="storySettingsSynopsis" rows="6" placeholder="Краткое описание истории (необязательно)"
                    class="textarea textarea-bordered w-full rounded-none bg-base-200 border-base-300 resize-y min-h-[8rem] @error('synopsis') textarea-error @enderror"
                    aria-describedby="storySettingsSynopsis-desc">{{ old('synopsis', $story->synopsis) }}</textarea>
                <p id="storySettingsSynopsis-desc" class="text-xs text-base-content/50 mt-1">Краткое описание; длинные тексты допустимы. Мягкий ориентир — до ~8000 знаков (проверка на сервере может отличаться).</p>
                <p id="storySettingsSynopsisCounter" class="text-xs text-right mt-1 tabular-nums" aria-live="polite"></p>
                @error('synopsis')
                    <p class="text-error text-sm mt-1">{{ $message }}</p>
                @enderror
                <div class="mt-6 flex flex-row-reverse flex-wrap gap-2 justify-end">
                    <button type="submit" id="storySettingsSubmitBtn" class="btn btn-primary rounded-none">Сохранить</button>
                    <button type="button" class="btn btn-ghost rounded-none" data-story-settings-close>Отмена</button>
                </div>
                <button type="button" class="story-dialog__close" data-story-settings-close aria-label="Закрыть" title="Закрыть">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg>
                </button>
            </form>
        </div>
    </dialog>

    <dialog id="editModal" class="story-dialog" aria-label="Редактирование карточки">
        <div class="story-dialog__viewport">
            <div class="story-dialog__scrim" data-edit-modal-close tabindex="-1" aria-hidden="true"></div>
            <form method="POST" action="" id="editForm" class="story-dialog__panel" onclick="event.stopPropagation()">
                @csrf
                @method('PUT')
                <label for="editModalTitleInput" class="story-dialog__field-label block text-sm text-base-content/70 mb-1">Название</label>
                <input type="text" name="title" id="editModalTitleInput" maxlength="255"
                    class="input input-bordered w-full rounded-none bg-base-200 border-base-300"
                    aria-describedby="editModalTitleInput-desc">
                <p id="editModalTitleInput-desc" class="text-xs text-base-content/50 mt-1">Необязательно; пустое имя на карточке показывается как «Карточка N». До 255 символов.</p>
                <p id="editModalTitleCounter" class="text-xs text-right mt-1 tabular-nums" aria-live="polite"></p>
                <label for="editModalContent" class="block text-sm text-base-content/70 mb-1 mt-4">Содержимое</label>
                <textarea name="content" id="editModalContent" rows="12" placeholder="Введите текст. Каждый абзац (разделяйте пустой строкой) станет отдельной карточкой при декомпозиции."
                    class="textarea textarea-bordered w-full rounded-none bg-base-200 border-base-300 resize-none"
                    aria-describedby="editModalContent-desc"></textarea>
                <p id="editModalContent-desc" class="text-xs text-base-content/50 mt-1">Каждый абзац (пустая строка между блоками) при декомпозиции станет отдельной карточкой. Мягкий ориентир по объёму — около 100&nbsp;000 знаков.</p>
                <p id="editModalContentCounter" class="text-xs text-right mt-1 tabular-nums" aria-live="polite"></p>
                <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex flex-wrap gap-1 items-center">
                        <button type="button" id="editModalHighlightBtn" class="btn btn-ghost btn-square rounded-none" onclick="highlightCardFromModal()">
                            <span class="edit-modal-highlight-icon-add inline-flex" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
                            </span>
                            <span class="edit-modal-highlight-icon-remove inline-flex" style="display: none" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/>
                                    <line x1="5" y1="5" x2="19" y2="19"/>
                                </svg>
                            </span>
                        </button>
                        <button type="button" class="btn btn-ghost btn-square rounded-none" title="Декомпозировать" onclick="submitModalDecompose()">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                        </button>
                        <button type="button" class="btn btn-error btn-square rounded-none" title="Удалить карточку" onclick="submitModalDelete()">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                        </button>
                    </div>
                    <div class="flex flex-row-reverse flex-wrap gap-2 justify-end">
                        <button type="submit" class="btn btn-primary rounded-none">Сохранить</button>
                        <button type="button" class="btn btn-ghost rounded-none" data-edit-modal-close>Отмена</button>
                    </div>
                </div>
                <button type="button" class="story-dialog__close" data-edit-modal-close aria-label="Закрыть" title="Закрыть">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg>
                </button>
            </form>
        </div>
    </dialog>

    <form id="modalDecomposeForm" method="POST" action="" class="hidden" aria-hidden="true">
        @csrf
    </form>
    <form id="modalDeleteForm" method="POST" action="" class="hidden" aria-hidden="true">
        @csrf
        @method('DELETE')
    </form>

    <footer class="py-4 text-center text-sm text-base-content/50 border-t border-base-300 mt-auto">
        &copy; GENEFIS MEDIA, {{ date('Y') }}
    </footer>

    @if ($errors->any() && ($errors->has('name') || $errors->has('synopsis')))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var el = document.getElementById('storySettingsModal');
                if (el) {
                    el.showModal();
                }
            });
        </script>
    @endif
</body>
</html>
