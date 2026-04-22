<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.flash-toast-critical-css')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Карты — {{ $world->name }} — Noema</title>
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
        /* Превью заливки: без повторения плиткой, крупная карта читается как миниатюра */
        .map-index-card > span:first-of-type {
            background-repeat: no-repeat;
        }
    </style>
</head>
<body class="min-h-screen bg-base-100 flex flex-col">
    @include('site.partials.header')

    <main class="flex-1 p-6 max-w-[1344px] w-full mx-auto">
        <x-noema-page-head>
            <x-slot name="title">
                <h1 class="text-[1.875rem] font-semibold text-base-content leading-tight min-w-0" style="font-family: 'Cormorant Garamond', Georgia, serif;">Карты</h1>
            </x-slot>
            <x-slot name="center">
                <button type="button" class="btn btn-primary btn-sm btn-square shrink-0 rounded-none" onclick="document.getElementById('addWorldMapModal').showModal()" title="Новая карта" aria-label="Новая карта">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                </button>
            </x-slot>
            <x-slot name="actions">
                <a href="{{ route('worlds.dashboard', $world) }}" class="btn btn-ghost btn-square" title="Назад в дашборд" aria-label="Назад в дашборд">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M19 12H5M12 19l-7-7 7-7"/>
                    </svg>
                </a>
                <button type="button" class="btn btn-ghost btn-square" title="Настройки модуля «Карты»" aria-label="Настройки модуля «Карты»" onclick="document.getElementById('mapsModuleSettingsModal').showModal()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="3"/>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                    </svg>
                </button>
                @include('partials.activity-log-button', ['world' => $world, 'mapsModuleJournal' => true])
            </x-slot>
            <x-slot name="below">
                <p class="text-base-content/60 mb-6 max-w-2xl">{{ $world->name }} — отдельные географические холсты (размер каждого задаётся при создании и в настройках).</p>
            </x-slot>
        </x-noema-page-head>

        @if (session('success'))
            <p class="text-success mb-4" role="alert" data-auto-dismiss>{{ session('success') }}</p>
        @endif

        @if ($maps->isNotEmpty())
            <div class="card-block-container">
                @foreach ($maps as $m)
                    @php
                        $mapPreviewUrl = $m->fillPreviewUrl();
                    @endphp
                    <a href="{{ route('worlds.maps.show', [$world, $m]) }}" class="card card-block map-index-card relative overflow-hidden flex flex-col items-center justify-center min-h-[120px] border border-base-300 hover:border-primary/30 transition-colors {{ $mapPreviewUrl ? '' : 'bg-base-200' }}">
                        @if ($mapPreviewUrl)
                            <span class="absolute inset-0 bg-cover bg-center" style="background-image: url('{{ e($mapPreviewUrl) }}')" aria-hidden="true"></span>
                            <span class="absolute inset-0 bg-black/55" aria-hidden="true"></span>
                        @endif
                        <div class="relative z-10 flex flex-col items-center justify-center gap-1 p-6 text-center">
                            <h2 class="text-lg font-semibold {{ $mapPreviewUrl ? 'text-base-100 drop-shadow-sm' : 'text-base-content' }}">{{ $m->title }}</h2>
                            <p class="text-xs tabular-nums {{ $mapPreviewUrl ? 'text-base-100/85' : 'text-base-content/50' }}">{{ $m->width }}×{{ $m->height }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
        @else
            <p class="text-base-content/60 mb-6">Пока нет карт. Создайте первую.</p>
        @endif
    </main>

    <dialog id="addWorldMapModal" class="modal modal-middle" aria-labelledby="add-map-heading">
        <div class="modal-box rounded-none border border-base-300 bg-base-200 shadow-2xl p-6 md:p-8 max-w-lg w-full">
            <form method="POST" action="{{ route('worlds.maps.store', $world) }}" class="space-y-4">
                @csrf
                <h2 id="add-map-heading" class="font-display text-xl font-semibold text-base-content mb-1">Новая карта</h2>
                <p class="text-sm text-base-content/60 mb-6">{{ $world->name }}</p>
                <div class="form-control w-full">
                    <label class="label py-1" for="newMapTitle"><span class="label-text">Название</span></label>
                    <input type="text" id="newMapTitle" name="title" value="{{ old('title') }}" required placeholder="Название карты" maxlength="255"
                        class="input input-bordered w-full rounded-none bg-base-100 border-base-300 @error('title') input-error @enderror">
                    @error('title')
                        <p class="text-error text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="form-control w-full">
                        <label class="label py-1" for="newMapWidth"><span class="label-text">Ширина (px)</span></label>
                        <input type="number" id="newMapWidth" name="width" value="{{ old('width', $world->mapsDefaultWidth()) }}" required min="{{ \App\Models\Worlds\WorldMap::MIN_SIDE }}" max="{{ \App\Models\Worlds\WorldMap::MAX_SIDE }}" step="1"
                            class="input input-bordered w-full rounded-none bg-base-100 border-base-300 tabular-nums @error('width') input-error @enderror">
                        @error('width')
                            <p class="text-error text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="form-control w-full">
                        <label class="label py-1" for="newMapHeight"><span class="label-text">Высота (px)</span></label>
                        <input type="number" id="newMapHeight" name="height" value="{{ old('height', $world->mapsDefaultHeight()) }}" required min="{{ \App\Models\Worlds\WorldMap::MIN_SIDE }}" max="{{ \App\Models\Worlds\WorldMap::MAX_SIDE }}" step="1"
                            class="input input-bordered w-full rounded-none bg-base-100 border-base-300 tabular-nums @error('height') input-error @enderror">
                        @error('height')
                            <p class="text-error text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                <p class="text-xs text-base-content/50">{{ \App\Models\Worlds\WorldMap::MIN_SIDE }}…{{ \App\Models\Worlds\WorldMap::MAX_SIDE }} px по каждой стороне.</p>
                <div class="modal-action flex flex-wrap gap-2 justify-end pt-4">
                    <button type="button" class="btn btn-ghost rounded-none" onclick="document.getElementById('addWorldMapModal').close()">Отмена</button>
                    <button type="submit" class="btn btn-primary rounded-none">Создать</button>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop"><button type="submit" class="cursor-default opacity-0 absolute inset-0 w-full h-full" aria-label="Закрыть">close</button></form>
    </dialog>

    <dialog id="mapsModuleSettingsModal" class="modal modal-middle" aria-labelledby="maps-module-settings-heading">
        <div class="modal-box noema-settings-modal-box rounded-none border border-base-300 bg-base-200 shadow-2xl p-6 md:p-8 w-full">
            <div class="noema-settings-modal-inner">
                <form method="POST" action="{{ route('worlds.maps.settings.update', $world) }}" class="min-h-0">
                @csrf
                @method('PUT')
                <div class="noema-settings-modal-body space-y-4">
                <h2 id="maps-module-settings-heading" class="font-display text-xl font-semibold text-base-content mb-6">Настройки</h2>
                @include('partials.settings-maps-defaults-fields', [
                    'widthDefault' => $world->mapsDefaultWidth(),
                    'heightDefault' => $world->mapsDefaultHeight(),
                    'idPrefix' => 'maps-module',
                    'hint' => 'Для формы «Новая карта»: ширина и высота холста подставляются автоматически, их можно изменить перед созданием.',
                ])
                </div>
                <div class="modal-action flex flex-wrap gap-2 justify-end pt-4">
                    <button type="button" class="btn btn-ghost rounded-none" onclick="document.getElementById('mapsModuleSettingsModal').close()">Отмена</button>
                    <button type="submit" class="btn btn-primary rounded-none">Сохранить</button>
                </div>
                <div class="noema-settings-modal-grow" aria-hidden="true"></div>
            </form>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop"><button type="submit" class="cursor-default opacity-0 absolute inset-0 w-full h-full" aria-label="Закрыть">close</button></form>
    </dialog>

    @include('site.partials.footer')
    @if ($errors->has('title') || $errors->has('width') || $errors->has('height'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.getElementById('addWorldMapModal')?.showModal();
            });
        </script>
    @endif
    @if ($errors->has('maps_default_width') || $errors->has('maps_default_height'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.getElementById('mapsModuleSettingsModal')?.showModal();
            });
        </script>
    @endif
</body>
</html>
