<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $world->name }} — Дашборд — Noema</title>
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
        .dashboard-card { display: flex; align-items: center; gap: 1rem; }
        .dashboard-card svg { flex-shrink: 0; opacity: 0.8; }
        dialog.world-settings-dialog:not([open]) { display: none !important; }
        dialog.world-settings-dialog[open] {
            position: fixed !important; top: 0 !important; left: 0 !important; right: 0 !important; bottom: 0 !important;
            width: 100vw !important; height: 100vh !important; margin: 0 !important; padding: 1rem !important;
            display: flex !important; align-items: center !important; justify-content: center !important;
            z-index: 999 !important; overflow-y: auto !important;
        }
        dialog.world-settings-dialog[open]::backdrop { background: rgba(0,0,0,0.6); }
        dialog.world-settings-dialog[open] .modal-backdrop { position: absolute !important; inset: 0 !important; z-index: -1 !important; }
        .world-settings-dialog .modal-box { max-width: 32rem; width: 100%; }
        dialog.delete-world-dashboard-dialog:not([open]) { display: none !important; }
        dialog.delete-world-dashboard-dialog[open] {
            position: fixed !important; top: 0 !important; left: 0 !important; right: 0 !important; bottom: 0 !important;
            width: 100vw !important; height: 100vh !important; margin: 0 !important; padding: 1rem !important;
            display: flex !important; align-items: center !important; justify-content: center !important;
            z-index: 1000 !important; overflow-y: auto !important;
        }
        dialog.delete-world-dashboard-dialog[open]::backdrop { background: rgba(0,0,0,0.6); }
    </style>
</head>
<body class="min-h-screen bg-base-100 flex flex-col">
    @include('site.partials.header')

    <main class="flex-1 p-6 max-w-[1344px] w-full mx-auto">
        @if (session('success'))
            <div role="alert" class="alert alert-success rounded-none mb-6 max-w-2xl">
                <span>{{ session('success') }}</span>
            </div>
        @endif

        {{-- Заголовок: название мира слева; справа: Назад к мирам, PDF, настройки --}}
        <div class="flex items-start justify-between mb-6 gap-4">
            <div class="min-w-0 flex-1">
                <h1 class="text-[1.875rem] font-semibold text-base-content leading-tight" style="font-family: 'Cormorant Garamond', Georgia, serif;">{{ $world->name }}</h1>
                @if ($world->annotation)
                    <p class="text-base-content/70 mt-2 max-w-2xl">{{ $world->annotation }}</p>
                @endif
            </div>
            <div class="flex flex-wrap items-center gap-1 shrink-0 mt-0.5 justify-end">
                <a href="{{ route('worlds.index') }}" class="btn btn-ghost btn-square shrink-0" title="Назад к мирам" aria-label="Назад к мирам">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M19 12H5M12 19l-7-7 7-7"/>
                    </svg>
                </a>
                <button type="button" class="btn btn-ghost btn-square shrink-0 opacity-80 hover:opacity-100" title="Экспорт в PDF — скоро" aria-label="PDF (скоро)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="7 10 12 15 17 10"/>
                        <line x1="12" y1="15" x2="12" y2="3"/>
                    </svg>
                </button>
                <button type="button" class="btn btn-ghost btn-square shrink-0" title="Настройки мира" aria-label="Настройки мира" onclick="document.getElementById('worldSettingsModal').showModal()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="3"/>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                    </svg>
                </button>
                <button type="button" class="btn btn-ghost btn-square shrink-0 text-error hover:bg-error/15" title="Удалить мир" aria-label="Удалить мир" onclick="document.getElementById('deleteWorldFromDashboardModal').showModal()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                    </svg>
                </button>
                @include('partials.activity-log-button', ['world' => $world])
            </div>
        </div>

        {{-- Блок 1: История --}}
        <section class="mb-12">
            <h2 class="text-xl font-medium text-base-content/80 mb-4">История</h2>
            <div class="card-block-container">
                <a href="{{ route('worlds.timeline', $world) }}" class="card card-block bg-base-200 border border-base-300 hover:border-primary/30 transition-colors dashboard-card p-6">
                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12 6 12 12 16 14"/>
                    </svg>
                    <h3 class="text-lg font-semibold text-base-content">Таймлайн</h3>
                </a>
                <a href="{{ route('cards.index', $world) }}" class="card card-block bg-base-200 border border-base-300 hover:border-primary/30 transition-colors dashboard-card p-6">
                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="7" height="7"/>
                        <rect x="14" y="3" width="7" height="7"/>
                        <rect x="3" y="14" width="7" height="7"/>
                        <rect x="14" y="14" width="7" height="7"/>
                    </svg>
                    <h3 class="text-lg font-semibold text-base-content">Карточки</h3>
                </a>
                <a href="{{ route('worlds.connections', $world) }}" class="card card-block bg-base-200 border border-base-300 hover:border-primary/30 transition-colors dashboard-card p-6">
                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="5" cy="12" r="3"/>
                        <circle cx="19" cy="5" r="3"/>
                        <circle cx="19" cy="19" r="3"/>
                        <line x1="7.5" y1="10.5" x2="17" y2="6.5"/>
                        <line x1="7.5" y1="13.5" x2="17" y2="17.5"/>
                    </svg>
                    <h3 class="text-lg font-semibold text-base-content">Связи</h3>
                </a>
            </div>
        </section>

        {{-- Блок 2: Энциклопедия --}}
        <section>
            <h2 class="text-xl font-medium text-base-content/80 mb-4">Энциклопедия</h2>
            <div class="card-block-container">
                <a href="{{ route('worlds.maps', $world) }}" class="card card-block bg-base-200 border border-base-300 hover:border-primary/30 transition-colors dashboard-card p-6">
                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"/>
                        <line x1="8" y1="2" x2="8" y2="18"/>
                        <line x1="16" y1="6" x2="16" y2="22"/>
                    </svg>
                    <h3 class="text-lg font-semibold text-base-content">Карты</h3>
                </a>
                <a href="{{ route('factions.index', $world) }}" class="card card-block bg-base-200 border border-base-300 hover:border-primary/30 transition-colors dashboard-card p-6">
                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                        <path d="M2 17l10 5 10-5"/>
                        <path d="M2 12l10 5 10-5"/>
                    </svg>
                    <h3 class="text-lg font-semibold text-base-content">Фракции</h3>
                </a>
                <a href="{{ route('biographies.index', $world) }}" class="card card-block bg-base-200 border border-base-300 hover:border-primary/30 transition-colors dashboard-card p-6">
                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                    <h3 class="text-lg font-semibold text-base-content">Биографии</h3>
                </a>
                <a href="{{ route('bestiary.index', $world) }}" class="card card-block bg-base-200 border border-base-300 hover:border-primary/30 transition-colors dashboard-card p-6">
                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                        <path d="M8 7h8"/>
                    </svg>
                    <h3 class="text-lg font-semibold text-base-content">Бестиарий</h3>
                </a>
            </div>
        </section>
    </main>

    <dialog id="deleteWorldFromDashboardModal" class="modal modal-middle delete-world-dashboard-dialog">
        <div class="modal-box rounded-none border border-base-300 bg-base-200 shadow-2xl p-6 md:p-8 max-w-lg w-full">
            <h2 class="font-display text-xl font-semibold text-base-content mb-2">Удалить мир</h2>
            <p class="text-sm text-base-content/70 mb-4">Мир «{{ $world->name }}» будет скрыт из списка. Чтобы подтвердить, введите слово <span class="font-mono text-base-content">УДАЛИТЬ</span> заглавными буквами.</p>
            <form method="POST" action="{{ route('worlds.destroy', $world) }}" id="deleteWorldFromDashboardForm">
                @csrf
                @method('DELETE')
                <div class="form-control w-full mb-4">
                    <label class="label py-1" for="delete-world-dashboard-confirm"><span class="label-text">Подтверждение</span></label>
                    <input type="text" id="delete-world-dashboard-confirm" autocomplete="off" class="input input-bordered w-full rounded-none bg-base-100 border-base-300 font-mono" placeholder="УДАЛИТЬ">
                </div>
                <div class="modal-action flex flex-wrap gap-2 justify-end">
                    <button type="button" class="btn btn-ghost rounded-none" onclick="document.getElementById('deleteWorldFromDashboardModal').close()">Отмена</button>
                    <button type="submit" id="delete-world-dashboard-submit" class="btn btn-error rounded-none" disabled>Удалить</button>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop"><button type="submit" class="cursor-default opacity-0 absolute inset-0 w-full h-full" aria-label="Закрыть">close</button></form>
    </dialog>

    <dialog id="worldSettingsModal" class="modal modal-middle world-settings-dialog">
        <div class="modal-box rounded-none border border-base-300 bg-base-200 shadow-2xl p-6 md:p-8">
            <h2 class="font-display text-xl font-semibold text-base-content mb-1">Настройки мира</h2>
            <p class="text-sm text-base-content/60 mb-6">{{ $world->name }}</p>
            <form method="POST" action="{{ route('worlds.update', $world) }}" enctype="multipart/form-data" class="space-y-4" id="worldSettingsForm">
                @csrf
                @method('PUT')
                <div class="form-control w-full">
                    <label class="label py-1" for="world-settings-name"><span class="label-text">Название</span></label>
                    <input type="text" id="world-settings-name" name="name" value="{{ old('name', $world->name) }}" required maxlength="255"
                        class="input input-bordered w-full rounded-none bg-base-100 border-base-300 @error('name') input-error @enderror">
                    @error('name')
                        <p class="text-error text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="form-control w-full">
                    <label class="label py-1" for="world-settings-annotation"><span class="label-text">Описание</span></label>
                    <textarea id="world-settings-annotation" name="annotation" rows="4" maxlength="1000"
                        class="textarea textarea-bordered w-full rounded-none bg-base-100 border-base-300 min-h-[6rem] @error('annotation') textarea-error @enderror"
                        placeholder="Краткий синопсис или описание мира">{{ old('annotation', $world->annotation) }}</textarea>
                    @error('annotation')
                        <p class="text-error text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="form-control w-full">
                    <label class="label py-1" for="world-settings-reference"><span class="label-text">Точка отсчёта</span></label>
                    <input type="text" id="world-settings-reference" name="reference_point" value="{{ old('reference_point', $world->reference_point) }}" maxlength="255"
                        class="input input-bordered w-full rounded-none bg-base-100 border-base-300 @error('reference_point') input-error @enderror"
                        placeholder="Например, метка нуля на таймлайне">
                    <p class="text-xs text-base-content/50 mt-1">Отображается на шкале времени и в связанных подсказках.</p>
                    @error('reference_point')
                        <p class="text-error text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="form-control w-full">
                    <span class="label-text block mb-2">Изображение мира</span>
                    @if ($world->imageUrl())
                        <div class="mb-3 flex items-start gap-4">
                            <div class="border border-base-300 bg-base-300/20 overflow-hidden shrink-0 inline-flex max-w-[200px] max-h-[200px] items-center justify-center">
                                <img src="{{ $world->imageUrl() }}" alt="" class="max-w-[200px] max-h-[200px] w-auto h-auto object-contain block" id="worldSettingsCurrentImg">
                            </div>
                            <label class="label cursor-pointer justify-start gap-2 py-0">
                                <input type="checkbox" name="remove_image" value="1" class="checkbox checkbox-sm rounded-none border-base-300" @checked(old('remove_image'))>
                                <span class="label-text text-sm">Удалить текущее изображение</span>
                            </label>
                        </div>
                    @endif
                    <input type="file" id="world-settings-image" name="image" accept="image/*"
                        class="file-input file-input-bordered w-full rounded-none bg-base-100 border-base-300 @error('image') file-input-error @enderror">
                    @error('image')
                        <p class="text-error text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-base-content/50 mt-1">PNG, JPG или WebP, до 2 МБ. Новый файл заменит текущий.</p>
                </div>
                <div class="modal-action flex flex-wrap gap-2 justify-end pt-4">
                    <button type="button" class="btn btn-ghost rounded-none" onclick="document.getElementById('worldSettingsModal').close()">Отмена</button>
                    <button type="submit" class="btn btn-primary rounded-none">Сохранить</button>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop"><button type="submit" class="cursor-default opacity-0 absolute inset-0 w-full h-full" aria-label="Закрыть">close</button></form>
    </dialog>

    <script>
        (function () {
            const modal = document.getElementById('worldSettingsModal');
            const fileInput = document.getElementById('world-settings-image');
            const currentImg = document.getElementById('worldSettingsCurrentImg');
            @if (session('open_world_settings'))
            modal?.showModal();
            @endif
            fileInput?.addEventListener('change', function () {
                const f = this.files && this.files[0];
                if (!f || !f.type.startsWith('image/') || !currentImg) return;
                currentImg.src = URL.createObjectURL(f);
            });

            const delModal = document.getElementById('deleteWorldFromDashboardModal');
            const delInput = document.getElementById('delete-world-dashboard-confirm');
            const delSubmit = document.getElementById('delete-world-dashboard-submit');
            const EXPECTED = 'УДАЛИТЬ';
            function syncDeleteConfirm() {
                if (!delInput || !delSubmit) return;
                delSubmit.disabled = delInput.value !== EXPECTED;
            }
            delInput?.addEventListener('input', syncDeleteConfirm);
            delModal?.addEventListener('close', function () {
                if (delInput) delInput.value = '';
                syncDeleteConfirm();
            });
        })();
    </script>

    @include('site.partials.footer')
</body>
</html>
