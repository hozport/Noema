{{-- $world, $faction, $timelineLines, $factionEventsPayload, $factionTimelineLineId --}}
<p id="faction-events-notice" class="hidden text-sm text-success px-4 pt-4 border-b border-base-300 bg-base-200/50" role="status"></p>
<div
    id="faction-events-root"
    class="faction-events border border-base-300 bg-base-200/40 rounded-none"
    data-faction-name="{{ $faction->name }}"
    data-timeline-lines='@json($timelineLines)'
    data-faction-events='@json($factionEventsPayload)'
    data-events-store-url="{{ route('factions.events.store', [$world, $faction]) }}"
    data-event-update-base="{{ url('/worlds/'.$world->id.'/factions/'.$faction->id.'/events') }}"
    data-create-line-url="{{ route('factions.timeline.create-line', [$world, $faction]) }}"
    data-remove-line-url="{{ route('factions.timeline.remove-line', [$world, $faction]) }}"
    data-push-event-url="{{ route('factions.timeline.push-event', [$world, $faction]) }}"
    data-faction-line-on-timeline="{{ $factionTimelineLineId ? '1' : '0' }}"
>
    <div class="flex flex-wrap items-start justify-between gap-3 p-4 border-b border-base-300">
        <div class="min-w-0">
            <h2 class="text-sm font-medium text-base-content/80">События</h2>
            <p class="text-xs text-base-content/50 mt-1 max-w-xl">
                События хранятся у фракции. Их можно вынести на линии таймлайна мира: по одному или все сразу на новую линию с названием фракции.
            </p>
        </div>
        @if ($factionTimelineLineId)
            <button type="button" class="btn btn-outline btn-error btn-sm rounded-none shrink-0 faction-events-create-line" aria-describedby="faction-events-remove-line-hint">
                Удалить с таймлайна
            </button>
        @else
            <button type="button" class="btn btn-outline btn-sm rounded-none shrink-0 faction-events-create-line" aria-describedby="faction-events-create-line-hint">
                Создать линию и разместить все события
            </button>
        @endif
    </div>
    <p id="faction-events-remove-line-hint" class="sr-only">Удалит линию фракции с таймлайна вместе с событиями на ней. Записи во фракции останутся.</p>
    <p id="faction-events-create-line-hint" class="sr-only">Создаётся линия фракции и размещаются события с годом на таймлайне мира.</p>

    <div class="border-b border-base-300 bg-base-100/30">
        <div class="collapse collapse-arrow rounded-none border-0 bg-transparent">
            <input type="checkbox" aria-label="Показать или скрыть форму нового события">
            <div class="collapse-title text-xs font-semibold uppercase tracking-wide text-base-content/50 px-4 py-3 min-h-0">
                Новое событие
            </div>
            <div class="collapse-content p-0">
                <form class="faction-events-add-form space-y-3 px-4 pb-4 pt-0" novalidate>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="sm:col-span-2">
                            <label for="faction-event-title" class="block text-sm text-base-content/70 mb-1">Название</label>
                            <input type="text" id="faction-event-title" name="title" maxlength="500" required
                                class="input input-bordered input-sm w-full rounded-none bg-base-200 border-base-300"
                                placeholder="Например: Основание, Союз с…" autocomplete="off">
                        </div>
                        <div>
                            <label for="faction-event-year" class="block text-sm text-base-content/70 mb-1">Год на шкале мира</label>
                            <input type="number" id="faction-event-year" name="year" step="1" min="0"
                                class="input input-bordered input-sm w-full rounded-none bg-base-200 border-base-300"
                                placeholder="Для размещения на таймлайне">
                        </div>
                        <div>
                            <label for="faction-event-year-end" class="block text-sm text-base-content/70 mb-1">Конец периода (необязательно)</label>
                            <input type="number" id="faction-event-year-end" name="year_end" step="1" min="0"
                                class="input input-bordered input-sm w-full rounded-none bg-base-200 border-base-300"
                                placeholder="Если событие длится несколько лет">
                        </div>
                        <div>
                            <label for="faction-event-month" class="block text-sm text-base-content/70 mb-1">Месяц</label>
                            <input type="number" id="faction-event-month" name="month" value="1" min="1" max="100" required
                                class="input input-bordered input-sm w-full rounded-none bg-base-200 border-base-300">
                        </div>
                        <div>
                            <label for="faction-event-day" class="block text-sm text-base-content/70 mb-1">День</label>
                            <input type="number" id="faction-event-day" name="day" value="1" min="1" max="100" required
                                class="input input-bordered input-sm w-full rounded-none bg-base-200 border-base-300">
                        </div>
                        <div class="sm:col-span-2">
                            <label for="faction-event-body" class="block text-sm text-base-content/70 mb-1">Описание</label>
                            <textarea id="faction-event-body" name="body" rows="3"
                                class="textarea textarea-bordered textarea-sm w-full rounded-none bg-base-200 border-base-300"
                                placeholder="Подробности события"></textarea>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="label cursor-pointer justify-start gap-3 py-1">
                                <input type="checkbox" id="faction-event-breaks-line" name="breaks_line" value="1" class="checkbox checkbox-sm rounded-none border-base-300">
                                <span class="label-text text-sm text-base-content/80">Обрывает линию после этой точки на таймлайне (на основной линии мира не применяется)</span>
                            </label>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-primary btn-sm rounded-none">Добавить событие</button>
                        <button type="button" class="btn btn-ghost btn-sm rounded-none faction-events-clear-draft">Очистить поля</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="p-4">
        <div class="flex items-baseline justify-between gap-2 mb-3">
            <h3 class="text-xs font-semibold uppercase tracking-wide text-base-content/50">Список событий</h3>
            <span class="text-xs text-base-content/45 faction-events-count" aria-live="polite">0 событий</span>
        </div>
        <div class="faction-events-empty text-sm text-base-content/50 py-6 text-center border border-dashed border-base-300 rounded-none bg-base-200/20">
            Пока нет событий. Раскройте блок «Новое событие» выше и добавьте первое.
        </div>
        <ul class="faction-events-list space-y-3 hidden" role="list"></ul>
    </div>
</div>

<template id="faction-event-row-template">
    <li class="faction-events-item border border-base-300 rounded-none bg-base-100 flex flex-col sm:flex-row sm:items-stretch gap-0">
        <div class="flex-1 min-w-0 p-3 sm:p-4 border-b sm:border-b-0 sm:border-r border-base-300">
            <p class="font-medium text-base-content faction-events-item-title"></p>
            <p class="text-xs text-base-content/50 mt-1 faction-events-item-when"></p>
            <p class="text-xs text-base-content/60 mt-0.5 faction-events-item-breaks hidden">Конец линии на таймлайне</p>
            <p class="text-xs text-primary/90 mt-1 faction-events-item-on-timeline hidden">На таймлайне</p>
            <p class="text-sm text-base-content/75 mt-2 whitespace-pre-wrap faction-events-item-body"></p>
        </div>
        <div class="flex sm:flex-col gap-2 p-3 sm:p-4 border-t sm:border-t-0 border-base-300 sm:w-52 shrink-0 sm:justify-center bg-base-200/30">
            <button type="button" class="btn btn-secondary btn-sm rounded-none faction-events-send">
                Отправить на таймлайн
            </button>
            <button type="button" class="btn btn-ghost btn-sm rounded-none text-error hover:bg-error/10 faction-events-delete">
                Удалить
            </button>
        </div>
    </li>
</template>

<dialog id="faction-send-timeline-dialog" class="biography-dialog" aria-labelledby="faction-send-timeline-title">
    <div class="biography-dialog__viewport">
        <div class="biography-dialog__scrim" data-faction-dialog-close tabindex="-1" aria-hidden="true"></div>
        <div class="biography-dialog__panel" onclick="event.stopPropagation()">
            <button type="button" class="biography-dialog__close" data-faction-dialog-close aria-label="Закрыть" title="Закрыть">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
            </button>
            <h2 id="faction-send-timeline-title" class="text-lg font-semibold mb-1 pr-8">Отправить на таймлайн</h2>
            <p class="text-sm text-base-content/60 mb-4 faction-send-timeline-event-label"></p>
            <fieldset class="space-y-3 mb-6">
                <legend class="text-sm text-base-content/70 mb-2">Выберите линию</legend>
                <div class="faction-send-timeline-radios space-y-2"></div>
            </fieldset>
            <div class="flex flex-row-reverse flex-wrap gap-2 justify-end">
                <button type="button" class="btn btn-primary rounded-none faction-send-timeline-confirm">Отправить</button>
                <button type="button" class="btn btn-ghost rounded-none" data-faction-dialog-close>Отмена</button>
            </div>
        </div>
    </div>
</dialog>

<dialog id="faction-create-line-dialog" class="biography-dialog" aria-labelledby="faction-create-line-title">
    <div class="biography-dialog__viewport">
        <div class="biography-dialog__scrim" data-faction-dialog-close tabindex="-1" aria-hidden="true"></div>
        <div class="biography-dialog__panel" onclick="event.stopPropagation()">
            <button type="button" class="biography-dialog__close" data-faction-dialog-close aria-label="Закрыть" title="Закрыть">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
            </button>
            <h2 id="faction-create-line-title" class="text-lg font-semibold mb-3 pr-8">Создать линию фракции</h2>
            <p class="text-sm text-base-content/80 mb-2 faction-create-line-lead"></p>
            <label class="block text-sm text-base-content/70 mb-1">Цвет линии</label>
            <input type="color" value="#457B9D" class="h-10 w-full max-w-xs rounded-none border border-base-300 cursor-pointer mb-4 faction-create-line-color">
            <p class="text-xs text-base-content/50 mb-6">
                Будет создана линия с названием фракции. На неё попадут все события с указанным годом, которые ещё не на таймлайне.
            </p>
            <div class="flex flex-row-reverse flex-wrap gap-2 justify-end">
                <button type="button" class="btn btn-primary rounded-none faction-create-line-confirm">Создать линию</button>
                <button type="button" class="btn btn-ghost rounded-none" data-faction-dialog-close>Отмена</button>
            </div>
        </div>
    </div>
</dialog>
