<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Биографии — {{ $world->name }} — Noema</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=cormorant-garamond:400,500,600,700|instrument-sans:400,500,600" rel="stylesheet">
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/biographies.js'])
    @endif
    <style>
        .biography-nav-letters {
            display: grid;
            grid-template-columns: repeat(var(--biography-nav-cols, 17), minmax(0, 1fr));
            gap: 0.25rem;
            width: 100%;
        }
        .biography-nav-letters a { text-align: center; padding: 0.2rem 0.35rem; font-size: 0.875rem; line-height: 1.2; border-radius: 0; text-decoration: none; min-width: 0; }
        .biography-nav-letters a.is-active { background: oklch(var(--p) / 0.25); color: oklch(var(--pc)); font-weight: 700; }
        .biography-nav-letters a:not(.is-active) { color: var(--color-base-content, #fff); opacity: 0.65; }
        .biography-nav-letters a.has-bios:not(.is-active) { font-weight: 700; opacity: 1; color: var(--color-base-content, #fff); }
        .biography-nav-letters a:not(.is-active):hover { opacity: 1; background: var(--color-base-200, #2a323c); }
        .biography-nav-letters a.has-bios:not(.is-active):hover { opacity: 1; }
        .biography-nav-letters a.is-empty { opacity: 0.45; font-weight: 400; }
        .biography-card { width: 200px; min-width: 200px; max-width: 200px; border-radius: 0 !important; }
        .biography-card-img { width: 100%; aspect-ratio: 1; object-fit: cover; background: var(--color-base-200, #2a323c); }
        .biography-dialog {
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
        }
        .biography-dialog::backdrop { background: rgba(0,0,0,0.55); }
        .biography-dialog:not([open]) { display: none !important; }
        .biography-dialog[open] { display: block !important; }
        .biography-dialog__viewport {
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
        .biography-dialog__scrim { position: absolute; inset: 0; z-index: 0; cursor: pointer; }
        .biography-dialog__panel {
            position: relative;
            z-index: 1;
            width: min(720px, calc(100vw - 2rem));
            max-height: min(90vh, calc(100dvh - 2rem));
            overflow: auto;
            background: var(--color-base-100, #1d232a);
            padding: 2.5rem 2.5rem 2rem;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
            border-radius: 0;
        }
        .biography-dialog__close {
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
        }
        .biography-dialog__close:hover { opacity: 1; }
        .biography-dialog__panel .biography-dialog__field-label { padding-right: 2.5rem; }
    </style>
</head>
<body class="min-h-screen bg-base-100 flex flex-col">
    @include('site.partials.header')

    <main class="flex-1 p-6 max-w-[1344px] w-full mx-auto">
        @php
            $bioIndexQ = filled($searchQuery) ? ['q' => $searchQuery] : [];
        @endphp
        <div class="mb-10 space-y-5">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <h1 class="text-[1.875rem] font-semibold text-base-content leading-tight min-w-0" style="font-family: 'Cormorant Garamond', Georgia, serif;">Биографии</h1>
                <div class="flex items-center gap-1 shrink-0">
                    <a href="{{ route('worlds.dashboard', $world) }}" class="btn btn-ghost btn-square" title="Назад в дашборд" aria-label="Назад в дашборд">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M19 12H5M12 19l-7-7 7-7"/>
                        </svg>
                    </a>
                    <a href="{{ route('biographies.pdf', $world) }}" class="btn btn-ghost btn-square" title="Выгрузить PDF" aria-label="Выгрузить PDF">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <path d="M12 18V9M9 15l3 3 3-3"/>
                        </svg>
                    </a>
                    <button type="button" class="btn btn-ghost btn-square" id="biography-create-open" title="Новая биография" aria-label="Новая биография">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                        </svg>
                    </button>
                    @include('partials.activity-log-button', ['world' => $world])
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-3 min-w-0">
                    <div class="join rounded-none shrink-0" role="group" aria-label="Набор букв">
                        <a href="{{ route('biographies.index', array_merge(['world' => $world, 'script' => \App\Support\BestiaryAlphabet::SCRIPT_CYR], $bioIndexQ)) }}"
                            class="btn btn-sm join-item rounded-none {{ $script === \App\Support\BestiaryAlphabet::SCRIPT_CYR ? 'btn-primary' : 'btn-ghost' }}">А–Я</a>
                        <a href="{{ route('biographies.index', array_merge(['world' => $world, 'script' => \App\Support\BestiaryAlphabet::SCRIPT_LAT], $bioIndexQ)) }}"
                            class="btn btn-sm join-item rounded-none {{ $script === \App\Support\BestiaryAlphabet::SCRIPT_LAT ? 'btn-primary' : 'btn-ghost' }}">A–Z</a>
                    </div>
                    <form method="get" action="{{ route('biographies.index', $world) }}" class="flex flex-wrap items-center gap-2 flex-1 min-w-0 max-w-xl">
                        <input type="hidden" name="script" value="{{ $script }}">
                        <input type="hidden" name="letter" value="{{ $letter }}">
                        <input type="search" name="q" value="{{ $searchQuery ?? '' }}" placeholder="Начните вводить имя…" autocomplete="off"
                            class="input input-bordered input-sm rounded-none flex-1 min-w-[10rem] bg-base-200 border-base-300">
                        <button type="submit" class="btn btn-sm btn-primary rounded-none shrink-0">Найти</button>
                        @if (filled($searchQuery))
                            <a href="{{ route('biographies.index', ['world' => $world, 'script' => $script, 'letter' => $letter]) }}" class="btn btn-sm btn-ghost rounded-none shrink-0">Сбросить</a>
                        @endif
                    </form>
            </div>

            <nav class="biography-nav-letters" style="--biography-nav-cols: {{ $navColumnCount }};" aria-label="Алфавитный указатель">
                @foreach ($navLetters as $L)
                    @php
                        $cnt = $counts[$L] ?? 0;
                        $isActive = $L === $letter;
                        $classes = 'block w-full ' . ($isActive ? 'is-active' : '') . ($cnt === 0 ? ' is-empty' : '') . ($cnt > 0 ? ' has-bios' : '');
                        $label = $L === '0-9' ? '1–9' : ($L === \App\Support\BestiaryAlphabet::OTHER_BUCKET ? '…' : $L);
                    @endphp
                    <a href="{{ route('biographies.index', array_merge(['world' => $world, 'script' => $script, 'letter' => $L], $bioIndexQ)) }}" class="{{ $classes }}">{{ $label }}</a>
                @endforeach
            </nav>
        </div>

        @php
            $letterTitle = $letter === '0-9'
                ? '1–9'
                : ($letter === \App\Support\BestiaryAlphabet::OTHER_BUCKET ? '…' : $letter);
        @endphp
        <div class="text-center mb-8">
            @if (filled($searchQuery))
                <h1 class="text-[2rem] font-semibold text-base-content" style="font-family: 'Cormorant Garamond', Georgia, serif;">Поиск по биографиям</h1>
                <p class="text-base-content/70 mt-2">Совпадения по имени во всём модуле (все буквы выбранного алфавита).</p>
                <p class="text-base-content/60 text-sm mt-2">Запрос: «{{ $searchQuery }}»</p>
            @else
                <h1 class="text-[2rem] font-semibold text-base-content" style="font-family: 'Cormorant Garamond', Georgia, serif;">{{ $letterTitle }}</h1>
                <p class="text-base-content/70 mt-2">
                    @if ($letter === '0-9')
                        Биографии с именем на цифру (1–9)
                    @elseif ($letter === \App\Support\BestiaryAlphabet::OTHER_BUCKET)
                        Биографии, у которых первая буква имени не относится к выбранному алфавиту
                    @else
                        Биографии на букву {{ $letter }}
                    @endif
                </p>
            @endif
        </div>

        @if ($selectedBiographies->isEmpty())
            @if (filled($searchQuery) && $totalBiographies > 0)
                <p class="text-center text-base-content/60 mb-12">Ничего не найдено. Попробуйте другой запрос или <a href="{{ route('biographies.index', ['world' => $world, 'script' => $script, 'letter' => $letter]) }}" class="link">сбросьте поиск</a>.</p>
            @elseif (filled($searchQuery))
                <p class="text-center text-base-content/60 mb-12">Нет биографий в этом мире.</p>
            @else
                <p class="text-center text-base-content/60 mb-12">Нет биографий для выбранной буквы.</p>
            @endif
        @else
            <div class="flex flex-wrap gap-4 justify-center mb-12">
                @foreach ($selectedBiographies as $bio)
                    <article class="card biography-card bg-base-200 border border-base-300 shadow-none">
                        <figure class="px-0 pt-0 pb-0">
                            @if ($bio->imageUrl())
                                <img src="{{ $bio->imageUrl() }}" alt="{{ $bio->name }}" class="biography-card-img rounded-none">
                            @else
                                <div class="biography-card-img flex items-center justify-center text-base-content/30 rounded-none" aria-hidden="true">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                </div>
                            @endif
                        </figure>
                        <div class="card-body px-4 pt-3 pb-4 gap-1 items-stretch text-center">
                            <h2 class="card-title text-lg font-semibold justify-center leading-tight">{{ $bio->name }}</h2>
                            <p class="text-sm text-base-content/50">{{ $bio->lifeYearsLabel() }}</p>
                            <a href="{{ route('biographies.show', [$world, $bio]) }}" class="btn btn-primary btn-sm rounded-none mt-2">Подробнее</a>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif

        @if ($recentBiographies->isNotEmpty())
            <div class="border-t border-base-300 pt-8 mt-8">
                @include('biographies.partials.recent-biographies', ['world' => $world, 'recentBiographies' => $recentBiographies])
            </div>
        @endif

        <div class="border-t border-base-300 pt-6 flex flex-wrap justify-center items-baseline gap-x-6 gap-y-1 text-sm text-base-content/80 @if ($recentBiographies->isEmpty()) mt-8 @endif">
            <span>Всего биографий: <strong class="text-base-content">{{ $totalBiographies }}</strong></span>
            @if (filled($searchQuery))
                <span>Найдено по запросу: <strong class="text-base-content">{{ $selectedBiographies->count() }}</strong></span>
            @else
                <span>
                    @if ($letter === '0-9')
                        На группу 1–9:
                    @elseif ($letter === \App\Support\BestiaryAlphabet::OTHER_BUCKET)
                        В группе «…»:
                    @else
                        На выбранную букву ({{ $letter }}):
                    @endif
                    <strong class="text-base-content">{{ $counts[$letter] ?? 0 }}</strong>
                </span>
            @endif
        </div>
    </main>

    <dialog id="biography-create-dialog" class="biography-dialog" aria-labelledby="biography-create-title">
        <div class="biography-dialog__viewport">
            <div class="biography-dialog__scrim" data-biography-dialog-close tabindex="-1" aria-hidden="true"></div>
            <form method="POST" action="{{ route('biographies.store', $world) }}" enctype="multipart/form-data" class="biography-dialog__panel" onclick="event.stopPropagation()">
                @csrf
                <button type="button" class="biography-dialog__close" data-biography-dialog-close aria-label="Закрыть" title="Закрыть">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
                </button>
                <h2 id="biography-create-title" class="text-xl font-semibold mb-4 pr-8">Новая биография</h2>
                @include('biographies.partials.biography-form-body', [
                    'biography' => null,
                    'world' => $world,
                    'allBiographies' => $allBiographies,
                    'formSuffix' => 'create',
                    'raceFactions' => $raceFactions,
                    'peopleFactions' => $peopleFactions,
                    'countryFactions' => $countryFactions,
                    'membershipFactions' => $membershipFactions,
                ])
                <div class="mt-6 flex flex-row-reverse flex-wrap gap-2 justify-end">
                    <button type="submit" class="btn btn-primary rounded-none">Создать</button>
                    <button type="button" class="btn btn-ghost rounded-none" data-biography-dialog-close>Отмена</button>
                </div>
            </form>
        </div>
    </dialog>

    @include('site.partials.footer')

    @if ($errors->any())
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.getElementById('biography-create-dialog')?.showModal();
            });
        </script>
    @endif
</body>
</html>
