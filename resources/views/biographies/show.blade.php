<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $biography->name }} — Биографии — Noema</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=cormorant-garamond:400,500,600,700|instrument-sans:400,500,600" rel="stylesheet">
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/biographies.js'])
    @endif
    <style>
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
        .biography-hero-img { width: 100%; height: auto; object-fit: cover; border: 1px solid rgba(255,255,255,0.12); }
        .biography-events .biography-events-item:last-child { margin-bottom: 0; }
        #biography-send-timeline-dialog .biography-dialog__panel,
        #biography-create-line-dialog .biography-dialog__panel { max-width: min(520px, calc(100vw - 2rem)); }
    </style>
</head>
<body class="min-h-screen bg-base-100 flex flex-col">
    @include('site.partials.header')

    <main class="flex-1 p-6 max-w-[1344px] w-full mx-auto" data-markup-resolve-url="{{ route('worlds.markup.resolve', $world) }}">
        <div class="flex items-start justify-between gap-4 mb-6">
            <div class="min-w-0 flex-1">
                <h1 class="text-[1.875rem] font-semibold text-base-content leading-tight" style="font-family: 'Cormorant Garamond', Georgia, serif;">{{ $biography->name }}</h1>
                @include('biographies.partials.biography-header-meta', ['world' => $world, 'biography' => $biography])
            </div>
            <div class="flex items-center gap-1 shrink-0 mt-0.5">
                <a href="{{ route('biographies.index', $world) }}" class="btn btn-ghost btn-square" title="Назад к списку" aria-label="Назад к списку">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M19 12H5M12 19l-7-7 7-7"/>
                    </svg>
                </a>
                <a href="{{ route('biography.pdf', [$world, $biography]) }}" class="btn btn-ghost btn-square" title="Сохранить как PDF" aria-label="Сохранить как PDF">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <path d="M12 18V9M9 15l3 3 3-3"/>
                    </svg>
                </a>
                <button type="button" class="btn btn-ghost btn-square" id="biography-open-edit" title="Редактировать" aria-label="Редактировать">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                    </svg>
                </button>
                <form method="POST" action="{{ route('biographies.destroy', [$world, $biography]) }}" class="inline" onsubmit="return confirm('Удалить эту биографию? Связи с родственниками, друзьями, врагами, фракциями и событиями будут сняты; связанные записи на таймлайне могут быть удалены.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-ghost btn-square text-error" title="Удалить биографию" aria-label="Удалить биографию">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                        </svg>
                    </button>
                </form>
                @include('partials.activity-log-button', ['world' => $world])
            </div>
        </div>

        @if (session('success'))
            <p class="text-success mb-4">{{ session('success') }}</p>
        @endif

        <div class="flex flex-col lg:flex-row lg:gap-8 gap-6 items-start mb-8">
            <div class="w-full lg:w-[20%] shrink-0 min-w-0">
                @if ($biography->imageUrl())
                    <img src="{{ $biography->imageUrl() }}" alt="{{ $biography->name }}" class="biography-hero-img rounded-none">
                @else
                    <div class="aspect-square bg-base-200 border border-base-300 flex items-center justify-center text-base-content/25">
                        <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </div>
                @endif
            </div>
            <div class="min-w-0 flex-1 space-y-8">
                @if (filled(trim((string) $biography->short_description)))
                    <section>
                        <h2 class="text-sm font-medium text-base-content/60 mb-2">Краткое описание</h2>
                        <div class="noema-markup-view text-base-content leading-relaxed">{!! $biography->shortDescriptionMarkupHtml() !!}</div>
                    </section>
                @endif

                @if (filled(trim((string) $biography->full_description)))
                    <section>
                        <h2 class="text-sm font-medium text-base-content/60 mb-2">Полное описание</h2>
                        <div class="noema-markup-view text-base-content leading-relaxed">{!! $biography->fullDescriptionMarkupHtml() !!}</div>
                    </section>
                @endif

                @include('biographies.partials.biography-events-block', ['world' => $world, 'biography' => $biography, 'timelineLines' => $timelineLines, 'biographyEventsPayload' => $biographyEventsPayload, 'biographyTimelineLineId' => $biographyTimelineLineId])

                @if ($biography->relatives->isNotEmpty())
                    <section>
                        <h2 class="text-sm font-medium text-base-content/60 mb-2">Родственные связи</h2>
                        <ul class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-x-4 gap-y-2 text-base-content list-none pl-0 m-0">
                            @foreach ($biography->relatives->sortBy('name') as $rel)
                                <li class="min-w-0 flex flex-wrap items-baseline gap-x-2 gap-y-0.5">
                                    @php
                                        $kinLabel = \App\Support\BiographyKinship::displayLabel($rel->pivot->kinship ?? null, $rel->pivot->kinship_custom ?? null);
                                    @endphp
                                    @if ($kinLabel !== '')
                                        <span class="text-base-content/75 shrink-0">{{ $kinLabel }}:</span>
                                    @endif
                                    <a href="{{ route('biographies.show', [$world, $rel]) }}" class="link link-hover break-words">{{ $rel->name }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                @if ($biography->friends->isNotEmpty())
                    <section>
                        <h2 class="text-sm font-medium text-base-content/60 mb-2">Друзья</h2>
                        <ul class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-x-4 gap-y-2 text-base-content list-none pl-0 m-0">
                            @foreach ($biography->friends->sortBy('name') as $f)
                                <li class="min-w-0"><a href="{{ route('biographies.show', [$world, $f]) }}" class="link link-hover break-words">{{ $f->name }}</a></li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                @if ($biography->enemies->isNotEmpty())
                    <section>
                        <h2 class="text-sm font-medium text-base-content/60 mb-2">Враги</h2>
                        <ul class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-x-4 gap-y-2 text-base-content list-none pl-0 m-0">
                            @foreach ($biography->enemies->sortBy('name') as $e)
                                <li class="min-w-0"><a href="{{ route('biographies.show', [$world, $e]) }}" class="link link-hover break-words">{{ $e->name }}</a></li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                @if ($socialMembershipFactions->isNotEmpty())
                    <section>
                        <h2 class="text-sm font-medium text-base-content/60 mb-2">Состоит в фракции</h2>
                        <ul class="list-none text-base-content space-y-4 pl-0">
                            @foreach ($socialMembershipFactions as $mf)
                                <li class="border border-base-300/60 rounded-none bg-base-200/20 p-4">
                                    <a href="{{ route('factions.show', [$world, $mf]) }}" class="link link-hover font-medium text-base-content">{{ $mf->name }}</a>
                                    @if (filled(trim((string) $mf->short_description)))
                                        <div class="noema-markup-view text-sm text-base-content/75 mt-2 leading-relaxed">{!! $mf->shortDescriptionMarkupHtml() !!}</div>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif
            </div>
        </div>
    </main>

    <dialog id="biography-edit-dialog" class="biography-dialog" aria-labelledby="biography-edit-title">
        <div class="biography-dialog__viewport">
            <div class="biography-dialog__scrim" data-biography-dialog-close tabindex="-1" aria-hidden="true"></div>
            <form method="POST" action="{{ route('biographies.update', [$world, $biography]) }}" enctype="multipart/form-data" class="biography-dialog__panel" onclick="event.stopPropagation()"
                data-markup-entities-url="{{ route('worlds.markup.entities', $world) }}"
                data-markup-resolve-url="{{ route('worlds.markup.resolve', $world) }}">
                @csrf
                @method('PUT')
                <button type="button" class="biography-dialog__close" data-biography-dialog-close aria-label="Закрыть" title="Закрыть">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
                </button>
                <h2 id="biography-edit-title" class="text-xl font-semibold mb-4 pr-8">Редактировать: {{ $biography->name }}</h2>
                @include('biographies.partials.biography-form-body', [
                    'biography' => $biography,
                    'world' => $world,
                    'allBiographies' => $allBiographiesForForm,
                    'formSuffix' => 'edit',
                    'raceFactions' => $raceFactions,
                    'peopleFactions' => $peopleFactions,
                    'countryFactions' => $countryFactions,
                    'membershipFactions' => $membershipFactions,
                ])
                <div class="mt-6 flex flex-row-reverse flex-wrap gap-2 justify-end">
                    <button type="submit" class="btn btn-primary rounded-none">Сохранить</button>
                    <button type="button" class="btn btn-ghost rounded-none" data-biography-dialog-close>Отмена</button>
                </div>
            </form>
        </div>
    </dialog>

    @include('partials.markup-link-modal')
    @include('site.partials.footer')

    @if ($errors->any())
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.getElementById('biography-edit-dialog')?.showModal();
            });
        </script>
    @endif
</body>
</html>
