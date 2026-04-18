@extends('layouts.noema-app')

@php
    $pageTitle = match ($scope) {
        'account' => 'Общий журнал',
        'world' => 'Журнал — '.$world->name,
        'world_timeline' => 'Журнал таймлайна — '.$world->name,
        'cards_module' => 'История изменений — карточки — '.$world->name,
        'story' => 'Журнал истории — '.$story->name,
        'card' => 'Журнал карточки — '.$card->displayTitle(),
        'biographies_module' => 'Журнал — биографии — '.$world->name,
        'biography' => 'Журнал — '.$biography->name,
        'factions_module' => 'Журнал — фракции — '.$world->name,
        'faction' => 'Журнал — '.$faction->name,
        'bestiary_module' => 'Журнал — бестиарий — '.$world->name,
        'creature' => 'Журнал — '.$creature->name,
        'connections_module' => 'Журнал — связи — '.$world->name,
        'connection_board' => 'Журнал — '.$connectionBoard->name,
        'maps_module' => 'Журнал — карты — '.$world->name,
        default => 'Журнал — '.$world->name,
    };
    $logTableColspan = $scope === 'account' ? 4 : 3;
    $activityClearRoute = match ($scope) {
        'account' => route('account.activity.clear'),
        'world' => route('worlds.activity.clear', $world),
        'world_timeline' => route('worlds.activity.timeline.clear', $world),
        'cards_module' => route('cards.module.activity.clear', $world),
        'story' => route('cards.stories.activity.clear', [$world, $story]),
        'card' => route('cards.card.activity.clear', [$world, $story, $card]),
        'biographies_module' => route('biographies.module.activity.clear', $world),
        'biography' => route('biography.activity.clear', [$world, $biography]),
        'factions_module' => route('factions.module.activity.clear', $world),
        'faction' => route('faction.activity.clear', [$world, $faction]),
        'bestiary_module' => route('bestiary.module.activity.clear', $world),
        'creature' => route('bestiary.creature.activity.clear', [$world, $creature]),
        'connections_module' => route('connections.module.activity.clear', $world),
        'connection_board' => route('connections.board.activity.clear', [$world, $connectionBoard]),
        'maps_module' => route('maps.module.activity.clear', $world),
        default => null,
    };
@endphp

@section('title', $pageTitle.' — Noema')

@section('content')
    @if (session('success'))
        <p class="text-success mb-4" role="alert" data-auto-dismiss>{{ session('success') }}</p>
    @endif
    <div class="flex flex-wrap items-start justify-between gap-4 mb-8">
        <div class="min-w-0">
            <h1 class="font-display text-3xl md:text-4xl text-base-content font-semibold tracking-tight mb-2">
                @if ($scope === 'account')
                    Общий журнал
                @elseif ($scope === 'world')
                    Журнал изменений
                @elseif ($scope === 'world_timeline')
                    Журнал таймлайна
                @elseif ($scope === 'cards_module')
                    История изменений
                @elseif ($scope === 'story')
                    Журнал истории
                @elseif ($scope === 'card')
                    Журнал карточки
                @elseif ($scope === 'biographies_module')
                    Журнал модуля «Биографии»
                @elseif ($scope === 'biography')
                    Журнал биографии
                @elseif ($scope === 'factions_module')
                    Журнал модуля «Фракции»
                @elseif ($scope === 'faction')
                    Журнал фракции
                @elseif ($scope === 'bestiary_module')
                    Журнал бестиария
                @elseif ($scope === 'creature')
                    Журнал существа
                @elseif ($scope === 'connections_module')
                    Журнал модуля «Связи»
                @elseif ($scope === 'connection_board')
                    Журнал доски
                @elseif ($scope === 'maps_module')
                    Журнал карты мира
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
            @elseif ($scope === 'biographies_module')
                <p class="text-base-content/70 text-sm md:text-base">
                    Мир <span class="text-base-content font-medium">{{ $world->name }}</span> — только действия модуля «Биографии» (профили, события, выкладка на таймлайн).
                </p>
            @elseif ($scope === 'biography')
                <p class="text-base-content/70 text-sm md:text-base">
                    Биография <span class="text-base-content font-medium">{{ $biography->name }}</span> — профиль, события и линии таймлайна, созданные из этой биографии.
                </p>
            @elseif ($scope === 'factions_module')
                <p class="text-base-content/70 text-sm md:text-base">
                    Мир <span class="text-base-content font-medium">{{ $world->name }}</span> — только действия модуля «Фракции».
                </p>
            @elseif ($scope === 'faction')
                <p class="text-base-content/70 text-sm md:text-base">
                    Фракция <span class="text-base-content font-medium">{{ $faction->name }}</span> — профиль, события и линии таймлайна, связанные с этой фракцией.
                </p>
            @elseif ($scope === 'bestiary_module')
                <p class="text-base-content/70 text-sm md:text-base">
                    Мир <span class="text-base-content font-medium">{{ $world->name }}</span> — только действия модуля «Бестиарий».
                </p>
            @elseif ($scope === 'creature')
                <p class="text-base-content/70 text-sm md:text-base">
                    Существо <span class="text-base-content font-medium">{{ $creature->name }}</span> — записи, где объектом изменения является это существо.
                </p>
            @elseif ($scope === 'connections_module')
                <p class="text-base-content/70 text-sm md:text-base">
                    Мир <span class="text-base-content font-medium">{{ $world->name }}</span> — только действия модуля «Связи» (доски, блоки, нити).
                </p>
            @elseif ($scope === 'connection_board')
                <p class="text-base-content/70 text-sm md:text-base">
                    Доска <span class="text-base-content font-medium">{{ $connectionBoard->name }}</span> — создание доски, блоки и связи только на этой доске.
                </p>
            @elseif ($scope === 'maps_module')
                <p class="text-base-content/70 text-sm md:text-base">
                    Мир <span class="text-base-content font-medium">{{ $world->name }}</span> — только действия с картой (объекты на карте).
                </p>
            @else
                <p class="text-base-content/70 text-sm md:text-base">
                    Все события по вашему аккаунту во всех мирах и модулях, где ведётся журнал.
                </p>
            @endif
        </div>
        <div class="flex flex-wrap items-center gap-2 justify-end shrink-0">
            <div class="flex items-center gap-2">
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
                @elseif ($scope === 'biographies_module')
                    <a href="{{ route('biographies.index', $world) }}" class="btn btn-ghost btn-square rounded-none" title="Назад к биографиям" aria-label="Назад к биографиям">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M19 12H5M12 19l-7-7 7-7"/>
                        </svg>
                    </a>
                @elseif ($scope === 'biography')
                    <a href="{{ route('biographies.show', [$world, $biography]) }}" class="btn btn-ghost btn-square rounded-none" title="Назад к биографии" aria-label="Назад к биографии">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M19 12H5M12 19l-7-7 7-7"/>
                        </svg>
                    </a>
                @elseif ($scope === 'factions_module')
                    <a href="{{ route('factions.index', $world) }}" class="btn btn-ghost btn-square rounded-none" title="Назад к фракциям" aria-label="Назад к фракциям">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M19 12H5M12 19l-7-7 7-7"/>
                        </svg>
                    </a>
                @elseif ($scope === 'faction')
                    <a href="{{ route('factions.show', [$world, $faction]) }}" class="btn btn-ghost btn-square rounded-none" title="Назад к фракции" aria-label="Назад к фракции">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M19 12H5M12 19l-7-7 7-7"/>
                        </svg>
                    </a>
                @elseif ($scope === 'bestiary_module')
                    <a href="{{ route('bestiary.index', $world) }}" class="btn btn-ghost btn-square rounded-none" title="Назад к бестиарию" aria-label="Назад к бестиарию">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M19 12H5M12 19l-7-7 7-7"/>
                        </svg>
                    </a>
                @elseif ($scope === 'creature')
                    <a href="{{ route('bestiary.creatures.show', [$world, $creature]) }}" class="btn btn-ghost btn-square rounded-none" title="Назад к существу" aria-label="Назад к существу">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M19 12H5M12 19l-7-7 7-7"/>
                        </svg>
                    </a>
                @elseif ($scope === 'connections_module')
                    <a href="{{ route('worlds.connections', $world) }}" class="btn btn-ghost btn-square rounded-none" title="Назад к связям" aria-label="Назад к связям">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M19 12H5M12 19l-7-7 7-7"/>
                        </svg>
                    </a>
                @elseif ($scope === 'connection_board')
                    <a href="{{ route('worlds.connections.show', [$world, $connectionBoard]) }}" class="btn btn-ghost btn-square rounded-none" title="Назад к доске" aria-label="Назад к доске">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M19 12H5M12 19l-7-7 7-7"/>
                        </svg>
                    </a>
                @elseif ($scope === 'maps_module')
                    <a href="{{ route('worlds.maps', $world) }}" class="btn btn-ghost btn-square rounded-none" title="Назад к карте" aria-label="Назад к карте">
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
                @if ($activityClearRoute)
                    <form method="POST" action="{{ $activityClearRoute }}" class="inline" onsubmit="return confirm('Удалить все записи в этом журнале? Это действие нельзя отменить.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-ghost btn-square text-error rounded-none" title="Очистить журнал" aria-label="Очистить журнал">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                            </svg>
                        </button>
                    </form>
                @endif
            </div>
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
