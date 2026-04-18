@php
    $linesForJson = $timelineLines->map(fn ($l) => [
        'id' => $l->id,
        'name' => $l->name,
        'is_main' => (bool) $l->is_main,
        'start_year' => (int) $l->start_year,
        'end_year' => $l->end_year !== null ? (int) $l->end_year : null,
        'color' => $l->color,
        'source_biography_id' => $l->source_biography_id !== null ? (int) $l->source_biography_id : null,
    ])->values()->all();
    $eventsForJson = isset($timelineEventsForJs)
        ? $timelineEventsForJs->map(fn ($e) => [
            'id' => $e->id,
            'timeline_line_id' => $e->timeline_line_id,
            'title' => $e->title,
            'epoch_year' => (int) $e->epoch_year,
            'month' => (int) $e->month,
            'day' => (int) $e->day,
            'breaks_line' => (bool) $e->breaks_line,
        ])->values()->all()
        : [];
@endphp
<script type="application/json" id="timeline-lines-config">{!! json_encode($linesForJson, JSON_THROW_ON_ERROR) !!}</script>
<script type="application/json" id="timeline-events-config">{!! json_encode($eventsForJson, JSON_THROW_ON_ERROR) !!}</script>
@php
    $timelineEventSourceOptions = $timelineEventSourceOptions ?? [
        'biographies' => [],
        'factions' => [],
        'biography_events_by_biography' => [],
        'faction_events_by_faction' => [],
        'biography_event_lookup' => [],
        'faction_event_lookup' => [],
    ];
@endphp
<script type="application/json" id="timeline-event-source-options">{!! json_encode($timelineEventSourceOptions, JSON_THROW_ON_ERROR) !!}</script>
<script type="application/json" id="timeline-page-meta">{!! json_encode([
    'worldId' => $world->id,
    'csrf' => csrf_token(),
    'urls' => [
        'lineUpdate' => url('/worlds/'.$world->id.'/timeline/lines/__ID__'),
        'lineDestroy' => url('/worlds/'.$world->id.'/timeline/lines/__ID__'),
        'lineMove' => url('/worlds/'.$world->id.'/timeline/lines/__ID__/move'),
        'eventUpdate' => url('/worlds/'.$world->id.'/timeline/events/__ID__'),
        'eventDestroy' => url('/worlds/'.$world->id.'/timeline/events/__ID__'),
    ],
], JSON_THROW_ON_ERROR) !!}</script>

<dialog id="timeline-line-dialog" class="timeline-dialog" aria-labelledby="timeline-line-dialog-title">
    <div class="timeline-dialog__viewport">
        <div class="timeline-dialog__scrim" data-timeline-dialog-close tabindex="-1" aria-hidden="true"></div>
        <form method="POST" action="{{ route('timeline.lines.store', $world) }}" class="timeline-dialog__panel" id="timeline-line-form" data-store-url="{{ route('timeline.lines.store', $world) }}" onclick="event.stopPropagation()">
            @csrf
            <input type="hidden" name="form_context" id="timeline-line-form-context" value="line_create">
            <input type="hidden" name="edit_line_id" id="timeline-line-edit-id" value="{{ old('edit_line_id') }}" @if (! old('edit_line_id')) disabled @endif>
            <button type="button" class="timeline-dialog__close" data-timeline-dialog-close aria-label="Закрыть" title="Закрыть">
                <span aria-hidden="true">&times;</span>
            </button>
            <h2 id="timeline-line-dialog-title" class="text-lg font-semibold text-base-content px-4 pt-4 pr-12">Новая линия</h2>
            <div class="timeline-dialog__scroll px-4 pb-2">
                @if ($errors->has('name') || $errors->has('start_year') || $errors->has('end_year') || $errors->has('color'))
                    <div class="alert alert-error rounded-none text-sm mb-3">{{ $errors->first() }}</div>
                @endif
                <label class="form-control w-full">
                    <span class="label-text text-base-content/80">Название</span>
                    <input type="text" name="name" value="{{ old('name') }}" required maxlength="255" class="input input-bordered rounded-none w-full bg-base-200 border-base-300" autocomplete="off">
                </label>
                <div class="grid grid-cols-2 gap-3 mt-3">
                    <label class="form-control w-full">
                        <span class="label-text text-base-content/80">Год начала</span>
                        <input type="number" name="start_year" id="timeline-line-start-year" value="{{ old('start_year', 0) }}" required min="0" class="input input-bordered rounded-none w-full bg-base-200 border-base-300">
                    </label>
                    <label class="form-control w-full">
                        <span class="label-text text-base-content/80">Год окончания <span class="opacity-60">(необяз.)</span></span>
                        <input type="number" name="end_year" id="timeline-line-end-year" value="{{ old('end_year') }}" min="0" class="input input-bordered rounded-none w-full bg-base-200 border-base-300" placeholder="—">
                    </label>
                </div>
                <label class="form-control w-full mt-3">
                    <span class="label-text text-base-content/80">Цвет</span>
                    <input type="color" name="color" id="timeline-line-color" value="{{ old('color', '#457B9D') }}" class="h-10 w-full rounded-none cursor-pointer border border-base-300 bg-base-200">
                </label>
            </div>
            <div class="timeline-dialog__footer">
                <button type="button" class="btn btn-ghost rounded-none" data-timeline-dialog-close>Отмена</button>
                <button type="submit" class="btn btn-primary rounded-none" id="timeline-line-submit">Создать линию</button>
            </div>
        </form>
    </div>
</dialog>

<dialog id="timeline-event-dialog" class="timeline-dialog" aria-labelledby="timeline-event-dialog-title">
    <div class="timeline-dialog__viewport">
        <div class="timeline-dialog__scrim" data-timeline-dialog-close tabindex="-1" aria-hidden="true"></div>
        <form method="POST" action="{{ route('timeline.events.store', $world) }}" class="timeline-dialog__panel" id="timeline-event-form" data-store-url="{{ route('timeline.events.store', $world) }}" onclick="event.stopPropagation()">
            @csrf
            <input type="hidden" name="form_context" id="timeline-event-form-context" value="event_create">
            <input type="hidden" name="edit_event_id" id="timeline-event-edit-id" value="{{ old('edit_event_id') }}" @if (! old('edit_event_id')) disabled @endif>
            <button type="button" class="timeline-dialog__close" data-timeline-dialog-close aria-label="Закрыть" title="Закрыть">
                <span aria-hidden="true">&times;</span>
            </button>
            <h2 id="timeline-event-dialog-title" class="text-lg font-semibold text-base-content px-4 pt-4 pr-12">Новое событие</h2>
            <div class="timeline-dialog__scroll px-4 pb-2">
                @if ($errors->has('timeline_line_id') || $errors->has('title') || $errors->has('epoch_year') || $errors->has('month') || $errors->has('day') || $errors->has('breaks_line') || $errors->has('biography_event_id') || $errors->has('faction_event_id'))
                    <div class="alert alert-error rounded-none text-sm mb-3">
                        @foreach (['timeline_line_id', 'title', 'epoch_year', 'month', 'day', 'breaks_line', 'biography_event_id', 'faction_event_id'] as $f)
                            @error($f)
                                <div>{{ $message }}</div>
                            @enderror
                        @endforeach
                    </div>
                @endif
                <input type="hidden" name="biography_event_id" id="timeline-event-biography-event-id" value="{{ old('biography_event_id') }}" @if (! old('biography_event_id')) disabled @endif>
                <input type="hidden" name="faction_event_id" id="timeline-event-faction-event-id" value="{{ old('faction_event_id') }}" @if (! old('faction_event_id')) disabled @endif>
                <div id="timeline-event-source-wrap" class="hidden space-y-3 mb-1">
                    <p class="text-xs text-base-content/60">Событие можно привязать к записи в модуле — поля ниже заполнятся из источника, их можно изменить.</p>
                    <label class="form-control w-full">
                        <span class="label-text text-base-content/80">Источник</span>
                        <select id="timeline-event-source-module" class="select select-bordered rounded-none w-full bg-base-200 border-base-300">
                            <option value="">— Не выбрано —</option>
                            <option value="biographies">Биографии</option>
                            <option value="factions">Фракции</option>
                        </select>
                    </label>
                    <label class="form-control w-full">
                        <span class="label-text text-base-content/80">Персонаж или фракция</span>
                        <select id="timeline-event-source-entity" class="select select-bordered rounded-none w-full bg-base-200 border-base-300" disabled>
                            <option value="">— Сначала выберите источник —</option>
                        </select>
                    </label>
                    <label class="form-control w-full">
                        <span class="label-text text-base-content/80">Событие</span>
                        <select id="timeline-event-source-event" class="select select-bordered rounded-none w-full bg-base-200 border-base-300" disabled>
                            <option value="">— Сначала выберите персонажа или фракцию —</option>
                        </select>
                    </label>
                </div>
                <div id="timeline-event-line-field-wrap">
                    <label class="form-control w-full">
                        <span class="label-text text-base-content/80">Линия</span>
                        <select name="timeline_line_id" id="timeline-event-line-id" required class="select select-bordered rounded-none w-full bg-base-200 border-base-300">
                            @foreach ($timelineLines as $l)
                                <option value="{{ $l->id }}" data-is-main="{{ $l->is_main ? '1' : '0' }}">{{ $l->name }}{{ $l->is_main ? ' (основная)' : '' }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>
                <label class="form-control w-full mt-3">
                    <span class="label-text text-base-content/80">Название</span>
                    <input type="text" name="title" id="timeline-event-title" value="{{ old('title') }}" required maxlength="255" class="input input-bordered rounded-none w-full bg-base-200 border-base-300" autocomplete="off">
                </label>
                <div class="grid grid-cols-3 gap-2 mt-3">
                    <label class="form-control w-full">
                        <span class="label-text text-base-content/80">Год</span>
                        <input type="number" name="epoch_year" id="timeline-event-epoch-year" value="{{ old('epoch_year', 0) }}" required min="0" class="input input-bordered rounded-none w-full bg-base-200 border-base-300">
                    </label>
                    <label class="form-control w-full">
                        <span class="label-text text-base-content/80">Месяц</span>
                        <input type="number" name="month" id="timeline-event-month" value="{{ old('month', 1) }}" required min="1" max="100" class="input input-bordered rounded-none w-full bg-base-200 border-base-300">
                    </label>
                    <label class="form-control w-full">
                        <span class="label-text text-base-content/80">День</span>
                        <input type="number" name="day" id="timeline-event-day" value="{{ old('day', 1) }}" required min="1" max="100" class="input input-bordered rounded-none w-full bg-base-200 border-base-300">
                    </label>
                </div>
                <label id="timeline-event-breaks-line-label" class="label cursor-pointer justify-start gap-3 mt-4">
                    <input type="checkbox" name="breaks_line" id="timeline-event-breaks-line" value="1" class="checkbox checkbox-sm rounded-none border-base-300" @checked(old('breaks_line'))>
                    <span class="label-text text-base-content/80">Обрывает линию после этой точки</span>
                </label>
            </div>
            <div class="timeline-dialog__footer">
                <button type="button" class="btn btn-ghost rounded-none" data-timeline-dialog-close>Отмена</button>
                <button type="submit" class="btn btn-primary rounded-none" id="timeline-event-submit">Создать событие</button>
            </div>
        </form>
    </div>
</dialog>
