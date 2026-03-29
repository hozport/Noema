<?php

namespace App\Services;

use App\Models\Faction\Faction;
use App\Models\Faction\FactionEvent;
use App\Models\Timeline\TimelineEvent;
use App\Models\Timeline\TimelineLine;
use Illuminate\Support\Str;

final class FactionTimelineSyncService
{
    public function createLineFromFaction(Faction $faction, ?string $color = null): TimelineLine
    {
        $world = $faction->world;
        $color = ($color !== null && $color !== '' && preg_match('/^#[0-9A-Fa-f]{6}$/', $color))
            ? $color
            : '#457B9D';

        $sortOrder = (int) ($world->timelineLines()->where('is_main', false)->max('sort_order') ?? -1) + 1;

        $dated = $faction->factionEvents()->whereNotNull('epoch_year')->get();
        $startYear = 0;
        if ($dated->isNotEmpty()) {
            $startYear = (int) $dated->min('epoch_year');
        }

        $line = TimelineLine::query()->create([
            'world_id' => $world->id,
            'source_faction_id' => $faction->id,
            'name' => Str::limit($faction->name, 255),
            'start_year' => $startYear,
            'end_year' => null,
            'extends_to_canvas_end' => true,
            'color' => $color,
            'is_main' => false,
            'sort_order' => $sortOrder,
        ]);

        foreach ($faction->factionEvents()->whereNotNull('epoch_year')->orderBy('epoch_year')->orderBy('id')->get() as $fe) {
            if ($fe->timelineEvent()->exists()) {
                continue;
            }
            $this->createTimelineEventFromFactionEvent($faction, $fe, $line);
        }

        return $line;
    }

    /**
     * @throws \RuntimeException
     */
    public function pushFactionEventToLine(Faction $faction, FactionEvent $fe, TimelineLine $line): TimelineEvent
    {
        if ((int) $fe->faction_id !== (int) $faction->id) {
            throw new \InvalidArgumentException('Событие не принадлежит этой фракции.');
        }
        if ((int) $line->world_id !== (int) $faction->world_id) {
            throw new \InvalidArgumentException('Линия из другого мира.');
        }
        if ($fe->timelineEvent()->exists()) {
            throw new \RuntimeException('Это событие уже вынесено на таймлайн.');
        }
        if ($fe->epoch_year === null) {
            throw new \RuntimeException('Укажите год на шкале мира, чтобы разместить событие.');
        }

        return $this->createTimelineEventFromFactionEvent($faction, $fe, $line);
    }

    public function buildTimelineTitleForFactionEvent(FactionEvent $fe): string
    {
        $title = $fe->title;
        if ($fe->year_end !== null && (int) $fe->year_end !== (int) $fe->epoch_year) {
            $title = $fe->title.' ('.(int) $fe->epoch_year.'—'.(int) $fe->year_end.' г.)';
        }

        return Str::limit($title, 255);
    }

    private function createTimelineEventFromFactionEvent(Faction $faction, FactionEvent $fe, TimelineLine $line): TimelineEvent
    {
        $event = TimelineEvent::query()->create([
            'timeline_line_id' => $line->id,
            'title' => $this->buildTimelineTitleForFactionEvent($fe),
            'epoch_year' => (int) $fe->epoch_year,
            'month' => max(1, min(100, (int) $fe->month)),
            'day' => max(1, min(100, (int) $fe->day)),
            'breaks_line' => $line->is_main ? false : (bool) $fe->breaks_line,
            'source_type' => FactionEvent::class,
            'source_id' => $fe->id,
        ]);

        $event->factions()->syncWithoutDetaching([(int) $faction->id]);

        $line->recalculateBoundsFromEvents();

        return $event;
    }

    public function syncLinkedFactionEventFromTimeline(TimelineEvent $event): void
    {
        if ($event->source_type !== FactionEvent::class || $event->source_id === null) {
            return;
        }

        $fe = FactionEvent::query()->find($event->source_id);
        if ($fe === null) {
            return;
        }

        $fe->update([
            'title' => Str::limit((string) $event->title, 255),
            'epoch_year' => (int) $event->epoch_year,
            'month' => (int) $event->month,
            'day' => (int) $event->day,
            'breaks_line' => (bool) $event->breaks_line,
        ]);
    }
}
