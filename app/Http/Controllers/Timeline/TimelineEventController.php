<?php

namespace App\Http\Controllers\Timeline;

use App\Http\Controllers\Controller;
use App\Http\Requests\Timeline\StoreTimelineEventRequest;
use App\Http\Requests\Timeline\UpdateTimelineEventRequest;
use App\Models\ActivityLog;
use App\Models\Biography\BiographyEvent;
use App\Models\Faction\FactionEvent;
use App\Models\Timeline\TimelineEvent;
use App\Models\Timeline\TimelineLine;
use App\Models\Worlds\World;
use App\Services\BiographyTimelineSyncService;
use App\Services\FactionTimelineSyncService;
use Illuminate\Http\Request;

class TimelineEventController extends Controller
{
    public function store(StoreTimelineEventRequest $request, World $world)
    {
        if (! $world->onoff) {
            abort(404);
        }

        $data = $request->validated();
        $line = TimelineLine::query()->where('world_id', $world->id)->findOrFail($data['timeline_line_id']);
        $breaksLine = $line->is_main ? false : $request->boolean('breaks_line');

        $base = [
            'timeline_line_id' => $data['timeline_line_id'],
            'title' => $data['title'],
            'epoch_year' => $data['epoch_year'],
            'month' => $data['month'],
            'day' => $data['day'],
            'breaks_line' => $breaksLine,
        ];

        if (! empty($data['biography_event_id'])) {
            $be = BiographyEvent::query()->with('biography')->findOrFail($data['biography_event_id']);
            $event = TimelineEvent::query()->create($base + [
                'source_type' => BiographyEvent::class,
                'source_id' => $be->id,
            ]);
            $event->biographies()->syncWithoutDetaching([(int) $be->biography_id]);
        } elseif (! empty($data['faction_event_id'])) {
            $fe = FactionEvent::query()->with('faction')->findOrFail($data['faction_event_id']);
            $event = TimelineEvent::query()->create($base + [
                'source_type' => FactionEvent::class,
                'source_id' => $fe->id,
            ]);
            $event->factions()->syncWithoutDetaching([(int) $fe->faction_id]);
        } else {
            $event = TimelineEvent::query()->create($base);
        }

        $event->line->recalculateBoundsFromEvents();

        ActivityLog::record($request->user(), $world, 'timeline.event.created', 'Создано событие «'.$event->title.'».', $event);

        return redirect()->route('worlds.timeline', $world);
    }

    public function update(UpdateTimelineEventRequest $request, World $world, TimelineEvent $timelineEvent)
    {
        if (! $world->onoff) {
            abort(404);
        }

        $data = $request->validated();
        $line = $timelineEvent->line;
        $breaksLine = $line->is_main ? false : $request->boolean('breaks_line');
        $timelineEvent->update([
            'title' => $data['title'],
            'epoch_year' => $data['epoch_year'],
            'month' => $data['month'],
            'day' => $data['day'],
            'breaks_line' => $breaksLine,
        ]);

        if ($timelineEvent->source_type === BiographyEvent::class) {
            app(BiographyTimelineSyncService::class)->syncLinkedBiographyEventFromTimeline($timelineEvent);
        }
        if ($timelineEvent->source_type === FactionEvent::class) {
            app(FactionTimelineSyncService::class)->syncLinkedFactionEventFromTimeline($timelineEvent);
        }

        $timelineEvent->line->recalculateBoundsFromEvents();

        ActivityLog::record($request->user(), $world, 'timeline.event.updated', 'Изменено событие «'.$timelineEvent->title.'».', $timelineEvent);

        return redirect()->route('worlds.timeline', $world);
    }

    public function destroy(Request $request, World $world, TimelineEvent $timelineEvent)
    {
        if ($world->user_id !== $request->user()->id || (int) $timelineEvent->line->world_id !== (int) $world->id) {
            abort(403);
        }

        if (! $world->onoff) {
            abort(404);
        }

        $line = $timelineEvent->line;
        $title = $timelineEvent->title;
        ActivityLog::record($request->user(), $world, 'timeline.event.deleted', 'Удалено событие «'.$title.'».', $timelineEvent);

        $timelineEvent->biographies()->detach();
        $timelineEvent->factions()->detach();
        $timelineEvent->delete();
        $line->recalculateBoundsFromEvents();

        return redirect()->route('worlds.timeline', $world);
    }
}
