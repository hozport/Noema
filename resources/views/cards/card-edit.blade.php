<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.flash-toast-critical-css')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $card->displayTitle() }} — {{ $story->name }} — Noema</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=cormorant-garamond:400,500,600,700|instrument-sans:400,500,600|playfair-display:400,500,600,700" rel="stylesheet">
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/story-card-page.js'])
    @endif
    <style>
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
        #cardPageCmHost .cm-editor { min-height: min(48vh, 28rem); }
        #cardPageCmHost .cm-scroller { max-height: min(72vh, 48rem); }
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
        <div id="card-page-root"
            data-world-id="{{ $world->id }}"
            data-story-id="{{ $story->id }}"
            data-card-id="{{ $card->id }}"
            data-markup-entities-url="{{ route('worlds.markup.entities', $world) }}"
            data-markup-resolve-url="{{ route('worlds.markup.resolve', $world) }}">

            <div class="flex items-start justify-between mb-8 gap-4 flex-wrap">
                <div class="min-w-0 flex-1">
                    <h1 class="text-[1.875rem] font-semibold text-base-content leading-tight" style="font-family: 'Cormorant Garamond', Georgia, serif;">{{ $card->displayTitle() }}</h1>
                    <p class="text-sm text-base-content/55 mt-1">{{ $story->name }} — карточка {{ $card->number }}</p>
                </div>
                <div class="flex items-center gap-1 shrink-0 mt-0.5">
                    <a id="cardPageBackLink" href="{{ route('cards.show', [$world, $story]) }}" class="btn btn-ghost btn-square" title="Назад к карточкам истории" aria-label="Назад к карточкам истории">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M19 12H5M12 19l-7-7 7-7"/>
                        </svg>
                    </a>
                    <form method="POST" action="{{ route('cards.destroy', [$world, $card]) }}" class="inline" onsubmit="return confirm('Удалить эту карточку?');">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="redirect_to_story" value="1">
                        <button type="submit" class="btn btn-ghost btn-square text-error" title="Удалить карточку" aria-label="Удалить карточку">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                            </svg>
                        </button>
                    </form>
                    @include('partials.activity-log-button', [
                        'world' => $world,
                        'story' => $story,
                        'card' => $card,
                        'journalTitle' => 'Журнал карточки',
                    ])
                </div>
            </div>

            @if (session('success'))
                <p class="text-success mb-4" role="alert" data-auto-dismiss>{{ session('success') }}</p>
            @endif
            @if (session('error'))
                <p class="text-error mb-4" role="alert" data-auto-dismiss>{{ session('error') }}</p>
            @endif

            <form method="POST" action="{{ route('cards.update', [$world, $card]) }}" id="cardPageEditForm" class="w-full max-w-none">
                @csrf
                @method('PUT')
                <input type="hidden" name="is_highlighted" id="cardPageHighlightField" value="{{ old('is_highlighted', $card->is_highlighted ? '1' : '0') }}">

                <label for="cardPageTitleInput" class="block text-sm text-base-content/70 mb-1">Название</label>
                <input type="text" name="title" id="cardPageTitleInput" maxlength="255"
                    value="{{ old('title', $card->title) }}"
                    class="input input-bordered w-full rounded-none bg-base-200 border-base-300 @error('title') input-error @enderror"
                    placeholder="Карточка {{ $card->number }}"
                    aria-describedby="cardPageTitleCounter">
                <p id="cardPageTitleCounter" class="text-xs text-right mt-1 tabular-nums" aria-live="polite"></p>
                @error('title')
                    <p class="text-error text-sm mt-1">{{ $message }}</p>
                @enderror

                <div class="flex flex-wrap items-center justify-between gap-2 mt-6 mb-1">
                    <span id="cardPageContentHeading" class="text-sm font-medium text-base-content/70 shrink-0">Содержимое</span>
                    <div class="dropdown dropdown-end">
                        <button type="button" tabindex="0" class="btn btn-ghost btn-xs btn-square rounded-none min-h-0 h-7 w-7 shrink-0 border border-base-300 text-base-content/70 hover:text-base-content" aria-label="Справка по разметке содержимого" title="Справка">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                        </button>
                        <div tabindex="0" class="dropdown-content bg-base-200 border border-base-300 rounded-none z-[120] w-[min(calc(100vw-2rem),22rem)] max-h-[min(70vh,20rem)] overflow-y-auto shadow-lg p-3 mt-1 text-left text-xs text-base-content/90 leading-snug">
                            <p class="mb-3 last:mb-0">Клик по тексту — режим редактирования; клик вне области содержимого — снова просмотр с форматированием (без записи на сервер). После выделения — панель форматирования; правый клик — меню в точке курсора. В редакторе: Ctrl/Cmd+B/I/U, зачёркивание — Ctrl/Cmd+Shift+S. Теги: <code class="text-[0.8rem]">[b][/b]</code> <code class="text-[0.8rem]">[i][/i]</code> <code class="text-[0.8rem]">[u][/u]</code> <code class="text-[0.8rem]">[s][/s]</code>, ссылка <code class="text-[0.8rem]">[link module=M entity=E]…[/link]</code>. Экранирование <code class="text-[0.8rem]">\</code>.</p>
                            <p class="mb-0">Закрепление карточки (значок закладки) сохраняется только по кнопке «Сохранить». Каждый абзац при декомпозиции станет отдельной карточкой. Мягкий ориентир по объёму — около 100&nbsp;000 знаков.</p>
                        </div>
                    </div>
                </div>
                <span id="cardPageContent-desc" class="sr-only">Клик по тексту — редактирование; клик вне области содержимого — просмотр с форматированием.</span>
                <input type="hidden" name="content" id="cardPageContent" value="{{ old('content', $card->content ?? '') }}" autocomplete="off">
                <div id="cardPageMarkupBoundary">
                    <div id="cardPageMarkupViewWrap">
                        <div id="cardPageMarkupView" class="noema-markup-view textarea textarea-bordered w-full rounded-none bg-base-200 border-base-300 min-h-[min(40vh,24rem)] max-h-[min(60vh,36rem)] overflow-auto p-3 text-sm leading-relaxed cursor-pointer whitespace-pre-wrap" tabindex="-1" role="region" aria-describedby="cardPageContent-desc" aria-labelledby="cardPageContentHeading"></div>
                    </div>
                    <div id="cardPageMarkupEditWrap" class="hidden mt-2">
                        <div id="cardPageCmHost" class="rounded-none border border-base-300 bg-base-200 overflow-hidden min-h-[min(48vh,28rem)]"></div>
                    </div>
                </div>
                @error('content')
                    <p class="text-error text-sm mt-2">{{ $message }}</p>
                @enderror
                <p id="cardPageContentCounter" class="text-xs text-right mt-1 tabular-nums" aria-live="polite"></p>

                <div class="mt-8 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex flex-wrap gap-1 items-center">
                        <button type="button" id="cardPageHighlightBtn" class="btn btn-ghost btn-square rounded-none card-page-pin-btn @if(old('is_highlighted', $card->is_highlighted ? '1' : '0') === '1') card-page-pin-btn--active @endif" onclick="toggleCardPagePin()" title="Закрепить карточку на сетке (применится после «Сохранить»)" aria-label="Закрепить карточку" aria-pressed="{{ old('is_highlighted', $card->is_highlighted ? '1' : '0') === '1' ? 'true' : 'false' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
                        </button>
                        <button type="button" class="btn btn-ghost btn-square rounded-none" title="Декомпозировать" onclick="submitCardPageDecompose()">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                        </button>
                    </div>
                    <button type="submit" class="btn btn-primary rounded-none">Сохранить</button>
                </div>
            </form>

            <form id="cardPageDecomposeForm" method="POST" action="{{ route('cards.decompose', [$world, $card]) }}" class="hidden" aria-hidden="true">
                @csrf
                <input type="hidden" name="redirect_to_story" value="1">
            </form>
        </div>
    </main>

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
</body>
</html>
