@extends('layouts.noema-app')

@section('title', 'Настройки — Noema')

@section('content')
    <div class="mb-6">
        <a href="{{ route('worlds.index') }}" class="link link-hover text-base-content/70 text-sm">← Назад к мирам</a>
    </div>

    <h1 class="font-display text-3xl md:text-4xl text-base-content font-semibold tracking-tight mb-2">Настройки</h1>
    <p class="text-base text-base-content/75 max-w-3xl leading-relaxed mb-10">
        Общие параметры интерфейса и отдельные блоки для модулей Noema. Значения по умолчанию и сохранение появятся позже.
    </p>

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
                <p class="text-xs text-base-content/50">Сохранение настроек будет доступно в следующих версиях.</p>
            </div>
        </section>

        @php
            $modules = [
                ['title' => 'Миры', 'desc' => 'Дашборд мира, список миров, создание и обложки.'],
                ['title' => 'Карточки', 'desc' => 'Карточный метод: от общего к частному, теги, ссылки на другие разделы.'],
                ['title' => 'Таймлайн', 'desc' => 'Линии времени и события на шкале.'],
                ['title' => 'Бестиарий', 'desc' => 'Существа, поля карточек и экспорт.'],
                ['title' => 'Биографии', 'desc' => 'Профили персонажей и связь с таймлайном.'],
                ['title' => 'Доска связей', 'desc' => 'Визуальная карта связей в мире.'],
            ];
        @endphp

        @foreach ($modules as $mod)
            <section class="border border-base-300 bg-base-200/30 rounded-none">
                <div class="px-5 py-4 border-b border-base-300 bg-base-200/50">
                    <h2 class="font-display text-xl font-semibold text-base-content">{{ $mod['title'] }}</h2>
                    <p class="text-sm text-base-content/60 mt-1">{{ $mod['desc'] }}</p>
                </div>
                <div class="p-5 md:p-6">
                    <p class="text-sm text-base-content/55">Настройки модуля «{{ $mod['title'] }}» будут добавлены по мере развития функционала.</p>
                </div>
            </section>
        @endforeach
    </div>
@endsection
