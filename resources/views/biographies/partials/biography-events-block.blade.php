{{--
  $world, $biography, $timelineLines, $biographyEventsPayload, $biographyTimelineLineId (null|int)
--}}
<p id="biography-events-notice" class="hidden text-sm text-success px-4 pt-4 border-b border-base-300 bg-base-200/50" role="status"></p>
<div
    id="biography-events-root"
    class="biography-events border border-base-300 bg-base-200/40 rounded-none"
    data-biography-name="{{ $biography->name }}"
    data-timeline-lines='@json($timelineLines)'
    data-biography-events='@json($biographyEventsPayload)'
    data-events-store-url="{{ route('biographies.events.store', [$world, $biography]) }}"
    data-event-update-base="{{ url('/worlds/'.$world->id.'/biographies/'.$biography->id.'/events') }}"
    data-create-line-url="{{ route('biographies.timeline.create-line', [$world, $biography]) }}"
    data-remove-line-url="{{ route('biographies.timeline.remove-line', [$world, $biography]) }}"
    data-push-event-url="{{ route('biographies.timeline.push-event', [$world, $biography]) }}"
    data-biography-line-on-timeline="{{ $biographyTimelineLineId ? '1' : '0' }}"
>
    <div class="flex flex-wrap items-start justify-between gap-3 p-4 border-b border-base-300">
        <div class="min-w-0">
            <h2 class="text-sm font-medium text-base-content/80">События</h2>
            <p class="text-xs text-base-content/50 mt-1 max-w-xl">
                События хранятся в биографии. Их можно вынести на линии таймлайна мира: по одному или все сразу на новую линию с именем персонажа.
            </p>
        </div>
        @if ($biographyTimelineLineId)
            <button type="button" class="btn btn-outline btn-error btn-sm rounded-none shrink-0 biography-events-create-line" aria-describedby="biography-events-remove-line-hint">
                Удалить с таймлайна
            </button>
        @else
            <button type="button" class="btn btn-outline btn-sm rounded-none shrink-0 biography-events-create-line" aria-describedby="biography-events-create-line-hint">
                Создать линию и разместить все события
            </button>
        @endif
    </div>
    <p id="biography-events-create-line-hint" class="sr-only">Откроется подтверждение: будет создана линия персонажа и размещены все события биографии на таймлайне мира.</p>
    <p id="biography-events-remove-line-hint" class="sr-only">Удалит линию персонажа с таймлайна мира вместе с событиями на ней. Записи в биографии останутся, их можно снова вынести на таймлайн.</p>

    <div class="border-b border-base-300 bg-base-100/30">
        <div class="collapse collapse-arrow rounded-none border-0 bg-transparent">
            <input type="checkbox" aria-label="Показать или скрыть форму нового события">
            <div class="collapse-title text-xs font-semibold uppercase tracking-wide text-base-content/50 px-4 py-3 min-h-0">
                Новое событие
            </div>
            <div class="collapse-content p-0">
                <form class="biography-events-add-form space-y-3 px-4 pb-4 pt-0" novalidate>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="sm:col-span-2">
                            <label for="biography-event-title" class="block text-sm text-base-content/70 mb-1">Название</label>
                            <input type="text" id="biography-event-title" name="title" maxlength="500" required
                                class="input input-bordered input-sm w-full rounded-none bg-base-200 border-base-300"
                                placeholder="Например: Рождение, Битва при…" autocomplete="off">
                        </div>
                        <div>
                            <label for="biography-event-year" class="block text-sm text-base-content/70 mb-1">Год на шкале мира</label>
                            <input type="number" id="biography-event-year" name="year" step="1" min="0"
                                class="input input-bordered input-sm w-full rounded-none bg-base-200 border-base-300"
                                placeholder="Для размещения на таймлайне">
                        </div>
                        <div>
                            <label for="biography-event-year-end" class="block text-sm text-base-content/70 mb-1">Конец периода (необязательно)</label>
                            <input type="number" id="biography-event-year-end" name="year_end" step="1" min="0"
                                class="input input-bordered input-sm w-full rounded-none bg-base-200 border-base-300"
                                placeholder="Если событие длится несколько лет">
                        </div>
                        <div>
                            <label for="biography-event-month" class="block text-sm text-base-content/70 mb-1">Месяц</label>
                            <input type="number" id="biography-event-month" name="month" value="1" min="1" max="100" required
                                class="input input-bordered input-sm w-full rounded-none bg-base-200 border-base-300">
                        </div>
                        <div>
                            <label for="biography-event-day" class="block text-sm text-base-content/70 mb-1">День</label>
                            <input type="number" id="biography-event-day" name="day" value="1" min="1" max="100" required
                                class="input input-bordered input-sm w-full rounded-none bg-base-200 border-base-300">
                        </div>
                        <div class="sm:col-span-2">
                            <label for="biography-event-body" class="block text-sm text-base-content/70 mb-1">Описание</label>
                            <textarea id="biography-event-body" name="body" rows="3"
                                class="textarea textarea-bordered textarea-sm w-full rounded-none bg-base-200 border-base-300"
                                placeholder="Подробности события"></textarea>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="label cursor-pointer justify-start gap-3 py-1">
                                <input type="checkbox" id="biography-event-breaks-line" name="breaks_line" value="1" class="checkbox checkbox-sm rounded-none border-base-300">
                                <span class="label-text text-sm text-base-content/80">Обрывает линию после этой точки на таймлайне (на основной линии мира не применяется)</span>
                            </label>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-primary btn-sm rounded-none">Добавить событие</button>
                        <button type="button" class="btn btn-ghost btn-sm rounded-none biography-events-clear-draft">Очистить поля</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="p-4">
        <div class="flex items-baseline justify-between gap-2 mb-3">
            <h3 class="text-xs font-semibold uppercase tracking-wide text-base-content/50">Список событий</h3>
            <span class="text-xs text-base-content/45 biography-events-count" aria-live="polite">0 событий</span>
        </div>
        <div class="biography-events-empty text-sm text-base-content/50 py-6 text-center border border-dashed border-base-300 rounded-none bg-base-200/20">
            Пока нет событий. Раскройте блок «Новое событие» выше и добавьте первое.
        </div>
        <ul class="biography-events-list space-y-3 hidden" role="list"></ul>
    </div>
</div>

<template id="biography-event-row-template">
    <li class="biography-events-item border border-base-300 rounded-none bg-base-100 flex flex-col sm:flex-row sm:items-stretch gap-0">
        <div class="flex-1 min-w-0 p-3 sm:p-4 border-b sm:border-b-0 sm:border-r border-base-300">
            <p class="font-medium text-base-content biography-events-item-title"></p>
            <p class="text-xs text-base-content/50 mt-1 biography-events-item-when"></p>
            <p class="text-xs text-base-content/60 mt-0.5 biography-events-item-breaks hidden">Конец линии на таймлайне</p>
            <p class="text-xs text-primary/90 mt-1 biography-events-item-on-timeline hidden">На таймлайне</p>
            <p class="text-sm text-base-content/75 mt-2 whitespace-pre-wrap biography-events-item-body"></p>
        </div>
        <div class="flex sm:flex-col gap-2 p-3 sm:p-4 border-t sm:border-t-0 border-base-300 sm:w-52 shrink-0 sm:justify-center bg-base-200/30">
            <button type="button" class="btn btn-secondary btn-sm rounded-none biography-events-send">
                Отправить на таймлайн
            </button>
            <button type="button" class="btn btn-ghost btn-sm rounded-none text-error hover:bg-error/10 biography-events-delete">
                Удалить
            </button>
        </div>
    </li>
</template>

<dialog id="biography-send-timeline-dialog" class="biography-dialog" aria-labelledby="biography-send-timeline-title">
    <div class="biography-dialog__viewport">
        <div class="biography-dialog__scrim" data-biography-dialog-close tabindex="-1" aria-hidden="true"></div>
        <div class="biography-dialog__panel" onclick="event.stopPropagation()">
            <button type="button" class="biography-dialog__close" data-biography-dialog-close aria-label="Закрыть" title="Закрыть">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
            </button>
            <h2 id="biography-send-timeline-title" class="text-lg font-semibold mb-1 pr-8">Отправить на таймлайн</h2>
            <p class="text-sm text-base-content/60 mb-4 biography-send-timeline-event-label"></p>
            <fieldset class="space-y-3 mb-6">
                <legend class="text-sm text-base-content/70 mb-2">Выберите линию</legend>
                <div class="biography-send-timeline-radios space-y-2"></div>
            </fieldset>
            <div class="flex flex-row-reverse flex-wrap gap-2 justify-end">
                <button type="button" class="btn btn-primary rounded-none biography-send-timeline-confirm">Отправить</button>
                <button type="button" class="btn btn-ghost rounded-none" data-biography-dialog-close>Отмена</button>
            </div>
        </div>
    </div>
</dialog>

<dialog id="biography-create-line-dialog" class="biography-dialog" aria-labelledby="biography-create-line-title">
    <div class="biography-dialog__viewport">
        <div class="biography-dialog__scrim" data-biography-dialog-close tabindex="-1" aria-hidden="true"></div>
        <div class="biography-dialog__panel" onclick="event.stopPropagation()">
            <button type="button" class="biography-dialog__close" data-biography-dialog-close aria-label="Закрыть" title="Закрыть">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
            </button>
            <h2 id="biography-create-line-title" class="text-lg font-semibold mb-3 pr-8">Создать линию персонажа</h2>
            <p class="text-sm text-base-content/80 mb-2 biography-create-line-lead"></p>
            <label class="block text-sm text-base-content/70 mb-1">Цвет линии</label>
            <input type="color" value="#457B9D" class="h-10 w-full max-w-xs rounded-none border border-base-300 cursor-pointer mb-4 biography-create-line-color">
            <p class="text-xs text-base-content/50 mb-6">
                Будет создана линия с именем персонажа. На неё попадут все события с указанным годом, которые ещё не на таймлайне.
            </p>
            <div class="flex flex-row-reverse flex-wrap gap-2 justify-end">
                <button type="button" class="btn btn-primary rounded-none biography-create-line-confirm">Создать линию</button>
                <button type="button" class="btn btn-ghost rounded-none" data-biography-dialog-close>Отмена</button>
            </div>
        </div>
    </div>
</dialog>
