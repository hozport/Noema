@extends('layouts.noema-app')

@section('title', 'Настройки — Noema')

@section('content')
    <div class="mb-6">
        <a href="{{ route('worlds.index') }}" class="link link-hover text-base-content/70 text-sm">← Назад к мирам</a>
    </div>

    <h1 class="font-display text-3xl md:text-4xl text-base-content font-semibold tracking-tight mb-2">Настройки</h1>
    <p class="text-base text-base-content/75 max-w-3xl leading-relaxed mb-10">
        Общие параметры интерфейса и отдельные блоки для модулей Noema. Порядок карточек на странице «Мои миры» и размеры новых карт по умолчанию можно менять здесь и в соответствующих разделах — сохраняются одни и те же значения аккаунта. Размеры карт для каждого мира дополнительно настраиваются в модуле «Карты» внутри мира (поверх значений с этой страницы и при создании мира).
    </p>

    @if (session('success'))
        <div role="alert" class="alert alert-success rounded-none mb-8 max-w-3xl" data-auto-dismiss>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <div class="flex flex-col gap-10 max-w-3xl">
        {{-- Общие --}}
        <section class="border border-base-300 bg-base-200/30 rounded-none">
            <div class="px-5 py-4 border-b border-base-300 bg-base-200/50">
                <h2 class="font-display text-xl font-semibold text-base-content">Общие настройки</h2>
                <p class="text-sm text-base-content/60 mt-1">Тема оформления, язык интерфейса, плотность списков.</p>
            </div>
            <div class="p-5 md:p-6 space-y-4">
                <div class="form-control w-full max-w-md">
                    <label class="label"><span class="label-text">Тема</span></label>
                    <select class="select select-bordered rounded-none bg-base-100 border-base-300 w-full" disabled>
                        <option selected>Тёмная (по умолчанию)</option>
                        <option>Светлая</option>
                        <option>Системная</option>
                    </select>
                </div>
                <div class="form-control w-full max-w-md">
                    <label class="label"><span class="label-text">Язык интерфейса</span></label>
                    <select class="select select-bordered rounded-none bg-base-100 border-base-300 w-full" disabled>
                        <option selected>Русский</option>
                    </select>
                </div>
                <p class="text-xs text-base-content/50">Сохранение этих параметров будет доступно в следующих версиях.</p>
            </div>
        </section>

        @php
            $modules = [
                ['slug' => 'worlds', 'title' => 'Миры', 'desc' => 'Список миров, создание, обложка и дашборд.'],
                ['slug' => 'maps', 'title' => 'Карты', 'desc' => 'Географические холсты: несколько карт на мир, ландшафт, объекты и заливка.'],
                ['slug' => 'cards', 'title' => 'Карточки', 'desc' => 'Истории и карточки: от общего к частному, разметка, ссылки на сущности.'],
                ['slug' => 'timeline', 'title' => 'Таймлайн', 'desc' => 'Линии времени и события на шкале.'],
                ['slug' => 'connections', 'title' => 'Связи', 'desc' => 'Доски связей: узлы и рёбра между сущностями мира.'],
                ['slug' => 'factions', 'title' => 'Фракции', 'desc' => 'Организации, профили и события.'],
                ['slug' => 'biographies', 'title' => 'Биографии', 'desc' => 'Профили персонажей и связь с таймлайном.'],
                ['slug' => 'bestiary', 'title' => 'Бестиарий', 'desc' => 'Существа, поля карточек и экспорт.'],
            ];
            $user = auth()->user();
            $worldsListSortCurrent = $user->worlds_list_sort ?? \App\Models\User::WORLDS_SORT_UPDATED_AT;
        @endphp

        @foreach ($modules as $mod)
            <section class="border border-base-300 bg-base-200/30 rounded-none">
                <div class="px-5 py-4 border-b border-base-300 bg-base-200/50">
                    <h2 class="font-display text-xl font-semibold text-base-content">{{ $mod['title'] }}</h2>
                    <p class="text-sm text-base-content/60 mt-1">{{ $mod['desc'] }}</p>
                </div>
                <div class="p-5 md:p-6">
                    @if ($mod['slug'] === 'worlds')
                        <form method="POST" action="{{ route('account.worlds-display.update') }}" class="space-y-4">
                            @csrf
                            @method('PUT')
                            <p class="text-sm text-base-content/70 mb-2">Порядок карточек на странице «Мои миры» (тот же выбор, что в модальном окне настроек на той странице).</p>
                            @include('partials.settings-worlds-list-sort-fields', ['currentSort' => $worldsListSortCurrent, 'selectId' => 'account-settings-worlds-sort'])
                            <div class="flex flex-wrap gap-2 justify-end pt-1">
                                <button type="submit" class="btn btn-primary rounded-none">Сохранить</button>
                            </div>
                        </form>
                    @elseif ($mod['slug'] === 'maps')
                        <form method="POST" action="{{ route('account.settings.maps-defaults.update') }}" class="space-y-4">
                            @csrf
                            @method('PUT')
                            @include('partials.settings-maps-defaults-fields', [
                                'widthDefault' => $user->mapsDefaultWidth(),
                                'heightDefault' => $user->mapsDefaultHeight(),
                                'idPrefix' => 'account-maps',
                                'hint' => 'Ширина и высота нового холста подставляются при создании мира и в форме «Новая карта» в каждом мире, пока вы не зададите другие значения в настройках модуля «Карты» внутри мира.',
                            ])
                            <div class="flex flex-wrap gap-2 justify-end pt-1">
                                <button type="submit" class="btn btn-primary rounded-none">Сохранить</button>
                            </div>
                        </form>
                    @else
                        <p class="text-sm text-base-content/55">Настройки модуля «{{ $mod['title'] }}» будут добавлены по мере развития функционала.</p>
                    @endif
                </div>
            </section>
        @endforeach
    </div>
@endsection
