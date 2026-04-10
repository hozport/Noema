@extends('layouts.noema-app')

@php
    $pageTitle = match ($scope) {
        'account' => 'Общий журнал',
        'world_timeline' => 'Журнал таймлайна — '.$world->name,
        'cards_module' => 'История изменений — карточки — '.$world->name,
        'story' => 'Журнал истории — '.$story->name,
        'card' => 'Журнал карточки — '.$card->displayTitle(),
        default => 'Журнал — '.$world->name,
    };
    $logTableColspan = $scope === 'account' ? 4 : 3;
@endphp

@section('title', $pageTitle.' — Noema')

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4 mb-8">
        <div class="min-w-0">
            <h1 class="font-display text-3xl md:text-4xl text-base-content font-semibold tracking-tight mb-2">
                @if ($scope === 'account')
                    Общий журнал
                @elseif ($scope === 'world_timeline')
                    Журнал таймлайна
                @elseif ($scope === 'cards_module')
                    История изменений
                @elseif ($scope === 'story')
                    Журнал истории
                @elseif ($scope === 'card')
                    Журнал карточки
                @else
                    Журнал изменений
                @endif
            </h1>
            @if ($scope === 'world')
                <p class="text-base-content/70 text-sm md:text-base">
                    Мир <span class="text-base-content font-medium">{{ $world->name }}</span> — все сохранённые действия в этом мире.
                </p>
            @elseif ($scope === 'world_timeline')
                <p class="text-base-content/70 text-sm md:text-base">
                    Мир <span class="text-base-content font-medium">{{ $world->name }}</span> — только действия с таймлайном (линии, события, выкладка из биографий и фракций).
                </p>
            @elseif ($scope === 'cards_module')
                <p class="text-base-content/70 text-sm md:text-base">
                    Мир <span class="text-base-content font-medium">{{ $world->name }}</span> — только действия модуля «Карточки» (истории и сюжетные карточки).
                </p>
            @elseif ($scope === 'story')
                <p class="text-base-content/70 text-sm md:text-base">
                    История <span class="text-base-content font-medium">{{ $story->name }}</span> — только действия с этой историей и её карточками.
                </p>
            @elseif ($scope === 'card')
                <p class="text-base-content/70 text-sm md:text-base">
                    Карточка <span class="text-base-content font-medium">{{ $card->displayTitle() }}</span> (история «{{ $story->name }}») — только записи, где объектом изменения является эта карточка.
                </p>
            @else
                <p class="text-base-content/70 text-sm md:text-base">
                    Все события по вашему аккаунту во всех мирах и модулях, где ведётся журнал.
                </p>
            @endif
        </div>
        <div class="flex items-center gap-1 shrink-0">
            @if ($scope === 'world')
                <a href="{{ route('worlds.dashboard', $world) }}" class="btn btn-ghost btn-square rounded-none" title="Назад в дашборд" aria-label="Назад в дашборд">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M19 12H5M12 19l-7-7 7-7"/>
                    </svg>
                </a>
            @elseif ($scope === 'world_timeline')
                <a href="{{ route('worlds.timeline', $world) }}" class="btn btn-ghost btn-square rounded-none" title="Назад к таймлайну" aria-label="Назад к таймлайну">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M19 12H5M12 19l-7-7 7-7"/>
                    </svg>
                </a>
            @elseif ($scope === 'story')
                <a href="{{ route('cards.show', [$world, $story]) }}" class="btn btn-ghost btn-square rounded-none" title="Назад к карточкам истории" aria-label="Назад к карточкам истории">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M19 12H5M12 19l-7-7 7-7"/>
                    </svg>
                </a>
            @elseif ($scope === 'card')
                <a href="{{ route('cards.card.edit', [$world, $story, $card]) }}" class="btn btn-ghost btn-square rounded-none" title="Назад к редактированию карточки" aria-label="Назад к редактированию карточки">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M19 12H5M12 19l-7-7 7-7"/>
                    </svg>
                </a>
            @elseif ($scope === 'cards_module')
                <a href="{{ route('cards.index', $world) }}" class="btn btn-ghost btn-square rounded-none" title="Назад к карточкам" aria-label="Назад к карточкам">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M19 12H5M12 19l-7-7 7-7"/>
                    </svg>
                </a>
            @else
                <a href="{{ route('worlds.index') }}" class="btn btn-ghost btn-square rounded-none" title="Мои миры" aria-label="Мои миры">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M19 12H5M12 19l-7-7 7-7"/>
                    </svg>
                </a>
            @endif
        </div>
    </div>

    <div class="overflow-x-auto border border-base-300 bg-base-200/40 rounded-none">
        <table class="table table-zebra w-full">
            <thead>
                <tr class="border-b border-base-300">
                    <th class="text-left font-medium text-base-content/90">Время</th>
                    <th class="text-left font-medium text-base-content/90">Кто</th>
                    @if ($scope === 'account')
                        <th class="text-left font-medium text-base-content/90 hidden lg:table-cell">Мир</th>
                    @endif
                    <th class="text-left font-medium text-base-content/90">Что изменено</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $log)
                    <tr>
                        <td class="whitespace-nowrap text-sm text-base-content/85 align-top">
                            {{ $log->created_at->timezone(config('app.timezone'))->format('d.m.Y H:i') }}
                        </td>
                        <td class="text-sm align-top min-w-[8rem]">
                            {{ $log->actor?->displayNameOrName() ?? '—' }}
                        </td>
                        @if ($scope === 'account')
                            <td class="text-sm align-top hidden lg:table-cell max-w-[12rem]">
                                @if ($log->world_id && $log->world)
                                    <a href="{{ route('worlds.activity', $log->world) }}" class="link link-hover truncate block">{{ $log->world->name }}</a>
                                @else
                                    <span class="text-base-content/50">—</span>
                                @endif
                            </td>
                        @endif
                        <td class="text-sm align-top">{{ $log->summary }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $logTableColspan }}" class="text-center text-base-content/60 py-12">Пока нет записей.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($logs->hasPages())
        <div class="mt-6 flex justify-center">
            {{ $logs->links('vendor.pagination.noema') }}
        </div>
    @endif
@endsection
