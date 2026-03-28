<?php

namespace App\Services;

use App\Models\Biography\Biography;
use App\Models\Biography\BiographyEvent;
use App\Models\Timeline\TimelineEvent;
use App\Models\Timeline\TimelineLine;
use Illuminate\Support\Str;

final class BiographyTimelineSyncService
{
    /**
     * Создаёт линию с именем персонажа и выкладывает на неё все события биографии с заданным годом (ещё не на таймлайне).
     */
    public function createLineFromBiography(Biography $biography, ?string $color = null): TimelineLine
    {
        $world = $biography->world;
        $color = ($color !== null && $color !== '' && preg_match('/^#[0-9A-Fa-f]{6}$/', $color))
            ? $color
            : '#457B9D';

        $sortOrder = (int) ($world->timelineLines()->where('is_main', false)->max('sort_order') ?? -1) + 1;

        $dated = $biography->biographyEvents()->whereNotNull('epoch_year')->get();
        $startYear = 0;
        if ($dated->isNotEmpty()) {
            $startYear = (int) $dated->min('epoch_year');
        }

        $line = TimelineLine::query()->create([
            'world_id' => $world->id,
            'source_biography_id' => $biography->id,
            'name' => Str::limit($biography->name, 255),
            'start_year' => $startYear,
            'end_year' => null,
            'extends_to_canvas_end' => true,
            'color' => $color,
            'is_main' => false,
            'sort_order' => $sortOrder,
        ]);

        foreach ($biography->biographyEvents()->whereNotNull('epoch_year')->orderBy('epoch_year')->orderBy('id')->get() as $be) {
            if ($be->timelineEvent()->exists()) {
                continue;
            }
            $this->createTimelineEventFromBiographyEvent($biography, $be, $line);
        }

        return $line;
    }

    /**
     * Создаёт событие таймлайна из записи биографии на выбранной линии.
     *
     * @throws \RuntimeException
     */
    public function pushBiographyEventToLine(Biography $biography, BiographyEvent $be, TimelineLine $line): TimelineEvent
    {
        if ((int) $be->biography_id !== (int) $biography->id) {
            throw new \InvalidArgumentException('Событие не принадлежит этой биографии.');
        }
        if ((int) $line->world_id !== (int) $biography->world_id) {
            throw new \InvalidArgumentException('Линия из другого мира.');
        }
        if ($be->timelineEvent()->exists()) {
            throw new \RuntimeException('Это событие уже вынесено на таймлайн.');
        }
        if ($be->epoch_year === null) {
            throw new \RuntimeException('Укажите год на шкале мира, чтобы разместить событие.');
        }

        return $this->createTimelineEventFromBiographyEvent($biography, $be, $line);
    }

    private function createTimelineEventFromBiographyEvent(Biography $biography, BiographyEvent $be, TimelineLine $line): TimelineEvent
    {
        $title = $be->title;
        if ($be->year_end !== null && (int) $be->year_end !== (int) $be->epoch_year) {
            $title = $be->title.' ('.(int) $be->epoch_year.'—'.(int) $be->year_end.' г.)';
        }

        $event = TimelineEvent::query()->create([
            'timeline_line_id' => $line->id,
            'title' => Str::limit($title, 255),
            'epoch_year' => (int) $be->epoch_year,
            'month' => max(1, min(100, (int) $be->month)),
            'day' => max(1, min(100, (int) $be->day)),
            'breaks_line' => $line->is_main ? false : (bool) $be->breaks_line,
            'source_type' => BiographyEvent::class,
            'source_id' => $be->id,
        ]);

        $event->biographies()->syncWithoutDetaching([(int) $biography->id]);

        $line->recalculateBoundsFromEvents();

        return $event;
    }
}
