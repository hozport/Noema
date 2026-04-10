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
        /* max-height ≈ 5 строк: без -webkit-line-clamp — он плохо дружит с несколькими <p> внутри */
        .story-card-preview { color: #4b5563 !important; font-size: 0.875rem; line-height: 1.35; margin-top: 0.5rem;
            max-height: calc(0.875rem * 1.35 * 5 + 0.25rem); overflow: hidden; }
        .story-card-preview-p { margin: 0 0 0.35em 0; }
        .story-card-preview-p:last-child { margin-bottom: 0; }
        /* Как .world-card-enter у карточек миров */
        .story-card-more {
            width: 100% !important;
            margin-top: auto !important;
            border-radius: 0 !important;
            min-height: 2.5rem !important;
            font-weight: 500 !important;
        }

        /* Модалки: весь экран без полосы от 100vw/скроллбара (.story-settings-dialog — только настройки истории; .story-dialog — карточка, ссылки и пр.) */
        .story-dialog,
        .story-settings-dialog {
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
        .story-dialog::backdrop,
        .story-settings-dialog::backdrop { background: rgba(0,0,0,0.55); }
        .story-dialog:not([open]),
        .story-settings-dialog:not([open]) { display: none !important; }
        .story-dialog[open],
        .story-settings-dialog[open] { display: block !important; }
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
            width: 100%;
            max-width: none;
        }
        .story-synopsis-stat-divider {
            border: none;
            border-top: 1px solid rgba(255, 255, 255, 0.12);
            margin: 24px 0;
            width: 100%;
            max-width: none;
        }
        .story-synopsis-stat-divider + .story-cards-stat {
            margin-top: 0;
        }
        .story-book-ornament {
            padding: 100px 0;
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
        }
        .story-book-ornament__icon {
            width: 56px;
            height: 56px;
            flex-shrink: 0;
        }
        .story-book-ornament + .story-page-synopsis {
            margin-top: 0;
        }
        .story-book-ornament + .story-cards-stat {
            margin-top: 0;
        }
        /* Переключатель «отображение карточек»: без рамки, цвета темы */
        .story-card-display-mode-row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.65rem 1rem;
            justify-content: flex-start;
            width: 100%;
        }
        .story-card-display-mode-label {
            font-size: 0.875rem;
            line-height: 1.25rem;
            color: color-mix(in oklab, var(--color-base-content) 88%, transparent);
        }
        .story-card-display-mode-switch {
            position: relative;
            display: inline-block;
            width: 2.75rem;
            height: 1.375rem;
            flex-shrink: 0;
            cursor: pointer;
            vertical-align: middle;
        }
        .story-card-display-mode-input {
            position: absolute;
            inset: 0;
            margin: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
            z-index: 2;
        }
        .story-card-display-mode-track {
            display: block;
            width: 100%;
            height: 100%;
            min-height: 1.375rem;
            background-color: var(--color-base-300);
            border: none;
            border-radius: 0;
            pointer-events: none;
            position: relative;
            z-index: 0;
            transition: background-color 0.15s ease;
        }
        .story-card-display-mode-track::after {
            content: '';
            position: absolute;
            top: 2px;
            left: 2px;
            width: 1.125rem;
            height: calc(100% - 4px);
            min-height: 0.875rem;
            background-color: var(--color-base-content);
            opacity: 0.42;
            border-radius: 0;
            transition: transform 0.15s ease, opacity 0.15s ease;
        }
        .story-card-display-mode-input:checked + .story-card-display-mode-track {
            background-color: color-mix(in oklab, var(--color-primary) 48%, var(--color-base-300));
        }
        .story-card-display-mode-input:checked + .story-card-display-mode-track::after {
            transform: translateX(1.375rem);
            opacity: 0.92;
        }
        .story-card-display-mode-input:focus-visible + .story-card-display-mode-track {
            outline: 2px solid color-mix(in oklab, var(--color-primary) 65%, transparent);
            outline-offset: 2px;
        }
        /* Стили разметки и ссылок на сущности — в resources/css/app.css (.noema-markup-view) */
        #editModalCmHost .cm-editor { min-height: 12rem; }
        #editModalCmHost .cm-scroller { max-height: 22rem; }
        .card-page-pin-btn.card-page-pin-btn--active {
            color: #dc2626 !important;
        }
        .card-page-pin-btn.card-page-pin-btn--active:hover {
            color: #ef4444 !important;
        }
    </style>
</head>
<body class="min-h-screen bg-base-100 flex flex-col">
    @include('site.partials.header')

    <main class="flex-1 p-6 max-w-[1344px] w-full mx-auto">
        <div id="story-page-root" data-world-id="{{ $world->id }}" data-story-id="{{ $story->id }}"
            data-markup-entities-url="{{ route('worlds.markup.entities', $world) }}"
            data-markup-resolve-url="{{ route('worlds.markup.resolve', $world) }}">
        <div class="flex items-start justify-between mb-8 gap-4">
            <div class="min-w-0">
                <h1 class="text-[1.875rem] font-semibold text-base-content leading-tight" style="font-family: 'Cormorant Garamond', Georgia, serif;">{{ $story->name }}</h1>
                @if (filled($story->cycle))
                    <p class="text-sm text-base-content/55 mt-1">{{ $story->cycle }}</p>
                @endif
            </div>
            <div class="flex items-center gap-1 shrink-0 mt-0.5">
                <a href="{{ route('cards.index', $world) }}" class="btn btn-ghost btn-square" title="Назад к списку историй" aria-label="Назад к списку историй">
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
                <form method="POST" action="{{ route('cards.stories.destroy', [$world, $story]) }}" class="inline" onsubmit="return confirm('Удалить эту историю? Все её карточки будут удалены безвозвратно. Узлы с этими карточками на досках связей также исчезнут.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-ghost btn-square text-error" title="Удалить историю" aria-label="Удалить историю">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                        </svg>
                    </button>
                </form>
                @include('partials.activity-log-button', ['world' => $world, 'story' => $story])
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
                    $previewForSearch = $card->content ? Str::limit(str_replace(["\r\n", "\n"], ' ', $card->getPlainContentForPreview()), 800) : '';
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
                            @if (filled($card->content))
                                <div class="story-card-preview noema-markup-view">{!! $card->getMarkupHtmlParagraphsForGrid() !!}</div>
                            @endif
                        </div>
                        @if (($story->card_display_mode ?? \App\Models\Cards\Story::CARD_DISPLAY_MODAL) === \App\Models\Cards\Story::CARD_DISPLAY_PAGE)
                            <a href="{{ route('cards.card.edit', [$world, $story, $card]) }}" class="story-card-more btn btn-primary btn-sm rounded-none shrink-0 inline-flex items-center justify-center no-underline" title="Подробнее — открыть страницу карточки">
                                Подробнее
                            </a>
                        @else
                            <button type="button" class="story-card-more btn btn-primary btn-sm rounded-none shrink-0" title="Подробнее — открыть редактирование карточки" onclick="openEditModal(this, {{ json_encode(route('cards.update', [$world, $card])) }}, {{ json_encode($card->title) }}, {{ json_encode($card->content ?? '') }}, {{ (int) $card->number }}, {{ json_encode(route('cards.decompose', [$world, $card])) }}, {{ json_encode(route('cards.destroy', [$world, $card])) }})">
                                Подробнее
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
        @endif

        <div class="story-book-ornament" role="presentation" aria-hidden="true">
            <svg class="story-book-ornament__icon text-base-content/35" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/>
                <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
            </svg>
        </div>

        @if (filled($story->synopsis))
            <section class="story-page-synopsis" aria-labelledby="story-synopsis-heading">
                <h2 id="story-synopsis-heading" class="text-sm font-medium text-base-content/60 mb-2">Синопсис</h2>
                <p class="text-base text-base-content/90 whitespace-pre-wrap leading-relaxed">{{ $story->synopsis }}</p>
            </section>
            <hr class="story-synopsis-stat-divider" aria-hidden="true">
        @endif

        <p class="story-cards-stat text-sm text-base-content/75 w-full max-w-none">Количество карточек: {{ $story->cards->count() }}</p>
        </div>
    </main>

    <dialog id="storySettingsModal" class="story-settings-dialog" aria-labelledby="story-settings-heading">
        <div class="story-dialog__viewport">
            <div class="story-dialog__scrim" data-story-settings-close tabindex="-1" aria-hidden="true"></div>
            <form method="POST" action="{{ route('cards.stories.update', [$world, $story]) }}" class="story-dialog__panel" onclick="event.stopPropagation()">
                @csrf
                @method('PUT')
                @php
                    $cardDisplayMode = old('card_display_mode', $story->card_display_mode ?: \App\Models\Cards\Story::CARD_DISPLAY_MODAL);
                @endphp
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
                <label for="storySettingsCycle" class="block text-sm text-base-content/70 mb-1 mt-4">Цикл</label>
                <input type="text" name="cycle" id="storySettingsCycle" value="{{ old('cycle', $story->cycle) }}" list="story-settings-cycle-datalist" maxlength="255" placeholder="Выберите из списка или введите свой"
                    class="input input-bordered w-full rounded-none bg-base-200 border-base-300 @error('cycle') input-error @enderror"
                    aria-describedby="storySettingsCycleCounter">
                <datalist id="story-settings-cycle-datalist">
                    @foreach ($storyCycles as $c)
                        <option value="{{ $c }}"></option>
                    @endforeach
                </datalist>
                <p id="storySettingsCycleCounter" class="text-xs text-right mt-1 tabular-nums" aria-live="polite"></p>
                @error('cycle')
                    <p class="text-error text-sm mt-1">{{ $message }}</p>
                @enderror
                <label for="storySettingsSynopsis" class="block text-sm text-base-content/70 mb-1 mt-4">Синопсис</label>
                <textarea name="synopsis" id="storySettingsSynopsis" rows="6" placeholder="Краткое описание истории (необязательно)"
                    class="textarea textarea-bordered w-full rounded-none bg-base-200 border-base-300 resize-y min-h-[8rem] @error('synopsis') textarea-error @enderror"
                    aria-describedby="storySettingsSynopsisCounter">{{ old('synopsis', $story->synopsis) }}</textarea>
                <p id="storySettingsSynopsisCounter" class="text-xs text-right mt-1 tabular-nums" aria-live="polite"></p>
                @error('synopsis')
                    <p class="text-error text-sm mt-1">{{ $message }}</p>
                @enderror

                <input type="hidden" name="card_display_mode" id="storySettingsCardDisplayMode" value="{{ $cardDisplayMode }}">

                <h3 class="text-sm font-medium text-base-content/70 mt-6 mb-3">Отображение карточек</h3>
                <div class="story-card-display-mode-row" role="group" aria-labelledby="story-settings-card-display-label">
                    <span id="story-settings-card-display-label" class="sr-only">Способ отображения карточек</span>
                    <span class="story-card-display-mode-label">В модальном окне</span>
                    <label class="story-card-display-mode-switch">
                        <input type="checkbox" id="storySettingsCardDisplayToggle" class="story-card-display-mode-input" aria-label="Переключить: модальное окно или отдельная страница" @checked($cardDisplayMode === \App\Models\Cards\Story::CARD_DISPLAY_PAGE)>
                        <span class="story-card-display-mode-track"></span>
                    </label>
                    <span class="story-card-display-mode-label">На отдельной странице</span>
                </div>
                @error('card_display_mode')
                    <p class="text-error text-sm mt-2">{{ $message }}</p>
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

    <dialog id="editModal" class="story-dialog" aria-label="Редактирование карточки"
        data-world-id="{{ $world->id }}" data-story-id="{{ $story->id }}"
        data-markup-entities-url="{{ route('worlds.markup.entities', $world) }}"
        data-markup-resolve-url="{{ route('worlds.markup.resolve', $world) }}">
        <div class="story-dialog__viewport">
            <div class="story-dialog__scrim" data-edit-modal-close tabindex="-1" aria-hidden="true"></div>
            <form method="POST" action="" id="editForm" class="story-dialog__panel" onclick="event.stopPropagation()">
                @csrf
                @method('PUT')
                <input type="hidden" name="is_highlighted" id="editModalHighlightField" value="0">
                <label for="editModalTitleInput" class="story-dialog__field-label block text-sm text-base-content/70 mb-1">Название</label>
                <input type="text" name="title" id="editModalTitleInput" maxlength="255"
                    class="input input-bordered w-full rounded-none bg-base-200 border-base-300"
                    aria-describedby="editModalTitleInput-desc">
                <p id="editModalTitleInput-desc" class="text-xs text-base-content/50 mt-1">Необязательно; пустое имя на карточке показывается как «Карточка N». До 255 символов.</p>
                <p id="editModalTitleCounter" class="text-xs text-right mt-1 tabular-nums" aria-live="polite"></p>
                <div class="flex flex-wrap items-center justify-between gap-2 mt-4 mb-1">
                    <span id="editModalContentHeading" class="text-sm font-medium text-base-content/70 shrink-0">Содержимое</span>
                    <div class="dropdown dropdown-end">
                        <button type="button" tabindex="0" class="btn btn-ghost btn-xs btn-square rounded-none min-h-0 h-7 w-7 shrink-0 border border-base-300 text-base-content/70 hover:text-base-content" aria-label="Справка по разметке содержимого" title="Справка">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                        </button>
                        <div tabindex="0" class="dropdown-content bg-base-200 border border-base-300 rounded-none z-[120] w-[min(calc(100vw-2rem),22rem)] max-h-[min(70vh,20rem)] overflow-y-auto shadow-lg p-3 mt-1 text-left text-xs text-base-content/90 leading-snug">
                            <p class="mb-3 last:mb-0">Клик по тексту — режим редактирования; клик вне области содержимого — снова просмотр с форматированием (запись на сервер только по «Сохранить»). После выделения фрагмента сразу появляется панель форматирования; правый клик — то же меню в точке курсора. Горячие клавиши в редакторе: Ctrl/Cmd+B/I/U, зачёркивание — Ctrl/Cmd+Shift+S. Теги: <code class="text-[0.8rem]">[b][/b]</code> <code class="text-[0.8rem]">[i][/i]</code> <code class="text-[0.8rem]">[u][/u]</code> <code class="text-[0.8rem]">[s][/s]</code>, ссылка <code class="text-[0.8rem]">[link module=M entity=E]…[/link]</code>. Экранирование <code class="text-[0.8rem]">\</code>.</p>
                            <p class="mb-0">Каждый абзац (пустая строка между блоками) при декомпозиции станет отдельной карточкой. Перенос строки внутри тегов не допускается. Мягкий ориентир по объёму — около 100&nbsp;000 знаков.</p>
                        </div>
                    </div>
                </div>
                <span id="editModalContent-desc" class="sr-only">Клик по тексту — редактирование; клик вне области содержимого — просмотр с форматированием; сохранение на сервер по кнопке «Сохранить».</span>
                <input type="hidden" name="content" id="editModalContent" value="" autocomplete="off">
                <div id="editModalMarkupBoundary">
                    <div id="editModalMarkupViewWrap">
                        <div id="editModalMarkupView" class="noema-markup-view textarea textarea-bordered w-full rounded-none bg-base-200 border-base-300 min-h-[12rem] max-h-[22rem] overflow-auto p-3 text-sm leading-relaxed cursor-pointer whitespace-pre-wrap" tabindex="-1" role="region" aria-describedby="editModalContent-desc" aria-labelledby="editModalContentHeading"></div>
                    </div>
                    <div id="editModalMarkupEditWrap" class="hidden mt-2">
                        <div id="editModalCmHost" class="rounded-none border border-base-300 bg-base-200 overflow-hidden min-h-[12rem]"></div>
                    </div>
                </div>
                @error('content')
                    <p class="text-error text-sm mt-2">{{ $message }}</p>
                @enderror
                <p id="editModalContentCounter" class="text-xs text-right mt-1 tabular-nums" aria-live="polite"></p>
                <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex flex-wrap gap-1 items-center">
                        <button type="button" id="editModalHighlightBtn" class="btn btn-ghost btn-square rounded-none card-page-pin-btn" onclick="toggleEditModalHighlight()" aria-pressed="false">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
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

    <dialog id="linkEntityModal" class="story-dialog" aria-labelledby="link-entity-heading">
        <div class="story-dialog__viewport">
            <div class="story-dialog__scrim" data-link-modal-close tabindex="-1" aria-hidden="true"></div>
            <div class="story-dialog__panel max-w-md" onclick="event.stopPropagation()">
                <h2 id="link-entity-heading" class="text-lg font-semibold mb-3 pr-8">Ссылка на сущность</h2>
                <label for="linkModuleSelect" class="block text-sm text-base-content/70 mb-1">Модуль</label>
                <select id="linkModuleSelect" class="select select-bordered w-full rounded-none bg-base-200 border-base-300 mb-3"></select>
                <label for="linkEntitySelect" class="block text-sm text-base-content/70 mb-1">Сущность</label>
                <select id="linkEntitySelect" class="select select-bordered w-full rounded-none bg-base-200 border-base-300 mb-4"></select>
                <div class="flex flex-row-reverse gap-2 justify-end">
                    <button type="button" id="linkModalConfirm" class="btn btn-primary rounded-none">Вставить</button>
                    <button type="button" id="linkModalCancel" class="btn btn-ghost rounded-none" data-link-modal-close>Отмена</button>
                </div>
                <button type="button" class="story-dialog__close" data-link-modal-close aria-label="Закрыть" title="Закрыть">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg>
                </button>
            </div>
        </div>
    </dialog>

    @include('site.partials.footer')

    @if ($errors->any() && ($errors->has('name') || $errors->has('synopsis') || $errors->has('cycle') || $errors->has('card_display_mode')))
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
