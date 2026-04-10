<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Карточки — {{ $world->name }} — Noema</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=cormorant-garamond:400,500,600,700|instrument-sans:400,500,600" rel="stylesheet">
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/cards-index.js'])
    @endif
    <style>
        .card-block-container { display: flex; flex-wrap: wrap; gap: 1rem; }
        .card-block-container .card-block {
            width: 200px !important; min-width: 200px !important; max-width: 200px !important;
            min-height: 120px !important; flex-shrink: 0 !important;
            border-radius: 0 !important;
        }
        /* Модалка создания истории (отдельно от настроек на странице истории) */
        .add-story-dialog {
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
        .add-story-dialog::backdrop { background: rgba(0,0,0,0.55); }
        .add-story-dialog:not([open]) { display: none !important; }
        .add-story-dialog[open] { display: block !important; }
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
    </style>
</head>
<body class="min-h-screen bg-base-100 flex flex-col">
    @include('site.partials.header')

    <main class="flex-1 p-6 max-w-[1344px] w-full mx-auto">
        <div class="mb-8 space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <h1 class="text-[1.875rem] font-semibold text-base-content leading-tight min-w-0" style="font-family: 'Cormorant Garamond', Georgia, serif;">Карточки</h1>
                <div class="flex items-center gap-1 shrink-0">
                    <a href="{{ route('worlds.dashboard', $world) }}" class="btn btn-ghost btn-square" title="Назад в дашборд" aria-label="Назад в дашборд">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M19 12H5M12 19l-7-7 7-7"/>
                        </svg>
                    </a>
                    <button type="button" class="btn btn-ghost btn-square" onclick="document.getElementById('addStoryModal').showModal()" title="Новая история" aria-label="Новая история">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                        </svg>
                    </button>
                    @include('partials.activity-log-button', ['world' => $world, 'cardsModuleJournal' => true])
                </div>
            </div>
            @if (session('success'))
                <p class="text-success">{{ session('success') }}</p>
            @endif
            @if ($cycleOptions->isNotEmpty())
                <form method="get" action="{{ route('cards.index', $world) }}" class="flex flex-wrap items-center gap-2">
                    <label for="cards-cycle-filter" class="text-sm text-base-content/70 whitespace-nowrap">Цикл</label>
                    <select id="cards-cycle-filter" name="cycle"
                        class="select select-bordered select-sm rounded-none bg-base-200 border-base-300 min-w-[12rem] max-w-[20rem]"
                        onchange="this.form.submit()">
                        <option value="" @selected($cycleFilter === null)>Все циклы</option>
                        @foreach ($cycleOptions as $opt)
                            <option value="{{ $opt }}" @selected((string) $cycleFilter === (string) $opt)>{{ $opt }}</option>
                        @endforeach
                    </select>
                    <noscript>
                        <button type="submit" class="btn btn-sm btn-primary rounded-none">Показать</button>
                    </noscript>
                </form>
            @endif
        </div>

        @if ($stories->isNotEmpty())
            <div class="card-block-container">
                @foreach ($stories as $story)
                    <a href="{{ route('cards.show', [$world, $story]) }}" class="card card-block bg-base-200 border border-base-300 hover:border-primary/30 transition-colors flex flex-col items-center justify-center gap-1 p-6 text-center">
                        <h2 class="text-lg font-semibold text-base-content">{{ $story->name }}</h2>
                        @if (filled($story->cycle))
                            <p class="text-xs text-base-content/55 leading-tight">{{ $story->cycle }}</p>
                        @endif
                    </a>
                @endforeach
            </div>
        @else
            @if (filled($cycleFilter))
                <p class="text-base-content/60 mb-6">Нет историй с выбранным циклом. <a href="{{ route('cards.index', $world) }}" class="link link-hover">Показать все истории</a>.</p>
            @else
                <p class="text-base-content/60 mb-6">Пока нет историй. Создайте первую.</p>
            @endif
        @endif
    </main>

    <dialog id="addStoryModal" class="add-story-dialog" aria-labelledby="add-story-heading">
        <div class="story-dialog__viewport">
            <div class="story-dialog__scrim" data-add-story-close tabindex="-1" aria-hidden="true"></div>
            <form method="POST" action="{{ route('cards.stories.store', $world) }}" class="story-dialog__panel" onclick="event.stopPropagation()">
                @csrf
                <h2 id="add-story-heading" class="text-xl font-semibold mb-4 pr-8">Новая история</h2>
                <label for="newStoryName" class="story-dialog__field-label block text-sm text-base-content/70 mb-1">Название</label>
                <input type="text" id="newStoryName" name="name" value="{{ old('name') }}" required placeholder="Название истории" maxlength="255"
                    class="input input-bordered w-full rounded-none bg-base-200 border-base-300 py-3 @error('name') input-error @enderror"
                    aria-describedby="newStoryName-desc">
                <p id="newStoryName-desc" class="text-xs text-base-content/50 mt-1">Обязательное поле, до 255 символов.</p>
                <p id="newStoryNameCounter" class="text-xs text-right mt-1 tabular-nums" aria-live="polite"></p>
                @error('name')
                    <p class="text-error text-sm mb-3">{{ $message }}</p>
                @enderror
                <label for="newStoryCycle" class="block text-sm text-base-content/70 mb-1 mt-4">Цикл</label>
                <input type="text" id="newStoryCycle" name="cycle" value="{{ old('cycle') }}" list="story-cycle-datalist" maxlength="255" placeholder="Выберите из списка или введите свой"
                    class="input input-bordered w-full rounded-none bg-base-200 border-base-300 @error('cycle') input-error @enderror"
                    aria-describedby="newStoryCycleCounter">
                <datalist id="story-cycle-datalist">
                    @foreach ($cycleOptions as $c)
                        <option value="{{ $c }}"></option>
                    @endforeach
                </datalist>
                <p id="newStoryCycleCounter" class="text-xs text-right mt-1 tabular-nums" aria-live="polite"></p>
                @error('cycle')
                    <p class="text-error text-sm mt-1">{{ $message }}</p>
                @enderror
                <label for="newStorySynopsis" class="block text-sm text-base-content/70 mb-1 mt-4">Синопсис</label>
                <textarea id="newStorySynopsis" name="synopsis" rows="5" placeholder="Краткое описание (необязательно)"
                    class="textarea textarea-bordered w-full rounded-none bg-base-200 border-base-300 resize-y min-h-[6rem] @error('synopsis') textarea-error @enderror"
                    aria-describedby="newStorySynopsisCounter">{{ old('synopsis') }}</textarea>
                <p id="newStorySynopsisCounter" class="text-xs text-right mt-1 tabular-nums" aria-live="polite"></p>
                @error('synopsis')
                    <p class="text-error text-sm mt-1">{{ $message }}</p>
                @enderror
                <div class="mt-6 flex flex-row-reverse flex-wrap gap-2 justify-end">
                    <button type="submit" class="btn btn-primary rounded-none">Создать</button>
                    <button type="button" class="btn btn-ghost rounded-none" data-add-story-close>Отмена</button>
                </div>
                <button type="button" class="story-dialog__close" data-add-story-close aria-label="Закрыть" title="Закрыть">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg>
                </button>
            </form>
        </div>
    </dialog>

    @include('site.partials.footer')
    @if ($errors->any() && ($errors->has('name') || $errors->has('synopsis') || $errors->has('cycle')))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var d = document.getElementById('addStoryModal');
                if (d) {
                    d.showModal();
                }
            });
        </script>
    @endif
</body>
</html>
