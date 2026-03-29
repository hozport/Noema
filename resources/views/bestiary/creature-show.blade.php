<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $creature->name }} — Бестиарий — Noema</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=cormorant-garamond:400,500,600,700|instrument-sans:400,500,600" rel="stylesheet">
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/bestiary.js'])
    @endif
    <style>
        .bestiary-dialog {
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
        .bestiary-dialog::backdrop { background: rgba(0,0,0,0.55); }
        .bestiary-dialog:not([open]) { display: none !important; }
        .bestiary-dialog[open] { display: block !important; }
        .bestiary-dialog__viewport {
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
        .bestiary-dialog__scrim { position: absolute; inset: 0; z-index: 0; cursor: pointer; }
        .bestiary-dialog__panel {
            position: relative;
            z-index: 1;
            width: 60vw;
            max-width: min(60vw, calc(100vw - 2rem));
            height: 80vh;
            max-height: min(80vh, calc(100dvh - 2rem));
            display: flex;
            flex-direction: column;
            overflow: hidden;
            background: var(--color-base-100, #1d232a);
            padding: 0;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
            border-radius: 0;
        }
        .bestiary-dialog__scroll {
            flex: 1;
            min-height: 0;
            overflow-y: auto;
            padding: 1.5rem 2rem 1rem;
        }
        .bestiary-dialog__footer {
            flex-shrink: 0;
            padding: 0.75rem 1rem 1rem;
            border-top: 1px solid rgba(255,255,255,0.1);
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            justify-content: flex-end;
        }
        .bestiary-dialog__close {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
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
        .bestiary-dialog__close:hover { opacity: 1; }
        .creature-hero-img { width: 100%; height: auto; object-fit: cover; border: 1px solid rgba(255,255,255,0.12); }
        .bestiary-creature-card { width: 200px; min-width: 200px; max-width: 200px; border-radius: 0 !important; }
        .bestiary-creature-img { width: 100%; aspect-ratio: 1; object-fit: cover; background: var(--color-base-200, #2a323c); }
        .creature-gallery-lightbox {
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
        .creature-gallery-lightbox::backdrop { background: rgba(0,0,0,0.85); }
        .creature-gallery-lightbox:not([open]) { display: none !important; }
        .creature-gallery-lightbox[open] {
            display: flex !important;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            box-sizing: border-box;
        }
        .creature-gallery-lightbox__inner { position: relative; max-width: min(90vw, 1200px); max-height: 90vh; padding: 0; margin: 0; }
        .creature-gallery-lightbox__inner img { max-width: 100%; max-height: 85vh; width: auto; height: auto; display: block; }
        .creature-gallery-lightbox__close {
            position: absolute;
            top: -2.25rem;
            right: 0;
            color: #fff;
            background: transparent;
            border: none;
            cursor: pointer;
            padding: 0.25rem;
            font-size: 1.75rem;
            line-height: 1;
        }
    </style>
</head>
<body class="min-h-screen bg-base-100 flex flex-col">
    @include('site.partials.header')

    <main class="flex-1 p-6 max-w-[1344px] w-full mx-auto">
        @if (session('success'))
            <p class="text-success mb-4">{{ session('success') }}</p>
        @endif

        <div class="flex items-start justify-between gap-4 mb-6">
            <div class="min-w-0 flex-1">
                <h1 class="text-[1.875rem] font-semibold text-base-content leading-tight" style="font-family: 'Cormorant Garamond', Georgia, serif;">{{ $creature->name }}</h1>
                @if (filled($creature->scientific_name))
                    <p class="text-base-content/60 text-lg mt-1">{{ $creature->scientific_name }}</p>
                @endif
            </div>
            <div class="flex items-center gap-1 shrink-0 mt-0.5">
                <a href="{{ route('bestiary.index', $world) }}" class="btn btn-ghost btn-square" title="Назад к бестиарию" aria-label="Назад к бестиарию">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M19 12H5M12 19l-7-7 7-7"/>
                    </svg>
                </a>
                <a href="{{ route('bestiary.creatures.pdf', [$world, $creature]) }}" class="btn btn-ghost btn-square" title="Сохранить как PDF" aria-label="Сохранить как PDF">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <path d="M12 18V9M9 15l3 3 3-3"/>
                    </svg>
                </a>
                <button type="button" class="btn btn-ghost btn-square" id="creature-open-edit" title="Редактировать" aria-label="Редактировать">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                    </svg>
                </button>
                <form method="POST" action="{{ route('bestiary.creatures.destroy', [$world, $creature]) }}" class="inline" onsubmit="return confirm('Удалить это существо? Связи с другими существами и изображения галереи будут удалены.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-ghost btn-square text-error" title="Удалить существо" aria-label="Удалить существо">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row lg:gap-8 gap-6 items-start mb-8">
            <div class="w-full lg:w-[20%] shrink-0 min-w-0">
                @if ($creature->imageUrl())
                    <img src="{{ $creature->imageUrl() }}" alt="{{ $creature->name }}" class="creature-hero-img rounded-none">
                @else
                    <div class="aspect-square bg-base-200 border border-base-300 flex items-center justify-center text-base-content/25">
                        <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" aria-hidden="true"><path d="M4 16l4.586-4.586a2 2 0 0 1 2.828 0L16 16m-2-2l1.586-1.586a2 2 0 0 1 2.828 0L20 14"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M22 19H2"/></svg>
                    </div>
                @endif
            </div>
            <div class="min-w-0 flex-1 space-y-8">
                @if (filled($creature->short_description))
                    <section>
                        <h2 class="text-sm font-medium text-base-content/60 mb-2">Краткое описание</h2>
                        <p class="text-base-content whitespace-pre-wrap leading-relaxed">{{ $creature->short_description }}</p>
                    </section>
                @endif

                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-x-4 gap-y-3 text-sm">
                    <div>
                        <span class="text-base-content/60">Вид</span>
                        <p class="text-base-content mt-0.5">{{ $creature->species_kind ?: '—' }}</p>
                    </div>
                    <div>
                        <span class="text-base-content/60">Рост</span>
                        <p class="text-base-content mt-0.5">{{ $creature->height_text ?: '—' }}</p>
                    </div>
                    <div>
                        <span class="text-base-content/60">Вес</span>
                        <p class="text-base-content mt-0.5">{{ $creature->weight_text ?: '—' }}</p>
                    </div>
                    <div>
                        <span class="text-base-content/60">Продолжительность жизни</span>
                        <p class="text-base-content mt-0.5">{{ $creature->lifespan_text ?: '—' }}</p>
                    </div>
                    <div>
                        <span class="text-base-content/60">Ореол обитания</span>
                        <p class="text-base-content mt-0.5 whitespace-pre-wrap">{{ $creature->habitat_text ?: '—' }}</p>
                    </div>
                </div>

                @if (filled($creature->full_description))
                    <section>
                        <h2 class="text-sm font-medium text-base-content/60 mb-2">Полное описание</h2>
                        <div class="text-base-content whitespace-pre-wrap leading-relaxed">{{ $creature->full_description }}</div>
                    </section>
                @endif

                @if ($creature->relatedCreatures->isNotEmpty())
                    <section>
                        <h2 class="text-sm font-medium text-base-content/60 mb-2">Родственные существа</h2>
                        <ul class="list-disc list-inside text-base-content space-y-1">
                            @foreach ($creature->relatedCreatures->sortBy('name') as $rel)
                                <li><a href="{{ route('bestiary.creatures.show', [$world, $rel]) }}" class="link link-hover">{{ $rel->name }}</a></li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                @if ($creature->foodCreatures->isNotEmpty() || (is_array($creature->food_custom) && count($creature->food_custom ?? []) > 0))
                    <section>
                        <h2 class="text-sm font-medium text-base-content/60 mb-2">Пища</h2>
                        @if ($creature->foodCreatures->isNotEmpty())
                            <p class="text-base-content mb-2">Звери: {{ $creature->foodCreatures->sortBy('name')->pluck('name')->join(', ') }}</p>
                        @endif
                        @if (is_array($creature->food_custom) && count($creature->food_custom ?? []) > 0)
                            <ul class="list-disc list-inside text-base-content space-y-1">
                                @foreach ($creature->food_custom as $line)
                                    <li>{{ $line }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </section>
                @endif

                @if ($creature->galleryImages->isNotEmpty())
                    <section>
                        <h2 class="text-sm font-medium text-base-content/60 mb-3">Галерея</h2>
                        <div class="flex flex-wrap gap-3">
                            @foreach ($creature->galleryImages as $g)
                                <button type="button"
                                    class="gallery-thumb p-0 border border-base-300 bg-transparent cursor-pointer overflow-hidden focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 focus:ring-offset-base-100"
                                    data-gallery-open="{{ $g->url() }}"
                                    aria-label="Открыть изображение в полном размере">
                                    <img src="{{ $g->url() }}" alt="" class="w-32 h-32 object-cover block pointer-events-none">
                                </button>
                            @endforeach
                        </div>
                    </section>
                @endif

            </div>
        </div>
    </main>

    <dialog id="bestiary-gallery-lightbox" class="creature-gallery-lightbox" aria-label="Просмотр изображения">
        <div class="creature-gallery-lightbox__inner">
            <button type="button" class="creature-gallery-lightbox__close" data-gallery-lightbox-close aria-label="Закрыть">&times;</button>
            <img src="" alt="" id="bestiary-gallery-lightbox-img">
        </div>
    </dialog>

    <dialog id="creature-edit-dialog" class="bestiary-dialog" aria-labelledby="creature-edit-title">
        <div class="bestiary-dialog__viewport">
            <div class="bestiary-dialog__scrim" data-bestiary-dialog-close tabindex="-1" aria-hidden="true"></div>
            <form method="POST" action="{{ route('bestiary.creatures.update', [$world, $creature]) }}" enctype="multipart/form-data" class="bestiary-dialog__panel" onclick="event.stopPropagation()">
                @csrf
                @method('PUT')
                <button type="button" class="bestiary-dialog__close" data-bestiary-dialog-close aria-label="Закрыть" title="Закрыть">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
                </button>
                <div class="bestiary-dialog__scroll pr-8 pt-2">
                    <h2 id="creature-edit-title" class="text-lg font-semibold mb-4 pr-8 border-b border-base-300 pb-2">Редактировать: {{ $creature->name }}</h2>
                    @include('bestiary.partials.creature-form-body', [
                        'creature' => $creature,
                        'world' => $world,
                        'allCreatures' => $allCreaturesForForm,
                        'speciesSuggestions' => $speciesSuggestions,
                        'formSuffix' => 'edit',
                    ])
                </div>
                <div class="bestiary-dialog__footer">
                    <button type="button" class="btn btn-ghost rounded-none" data-bestiary-dialog-close>Отмена</button>
                    <button type="submit" class="btn btn-primary rounded-none">Сохранить</button>
                </div>
            </form>
        </div>
    </dialog>

    @include('site.partials.footer')

    @if ($errors->any())
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.getElementById('creature-edit-dialog')?.showModal();
            });
        </script>
    @endif
</body>
</html>
