<?php

namespace App\Http\Controllers\Timeline;

use App\Http\Controllers\Controller;
use App\Http\Requests\Timeline\StoreTimelineEventRequest;
use App\Http\Requests\Timeline\UpdateTimelineEventRequest;
use App\Models\Timeline\TimelineEvent;
use App\Models\Timeline\TimelineLine;
use App\Models\Worlds\World;
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
        $event = TimelineEvent::query()->create([
            'timeline_line_id' => $data['timeline_line_id'],
            'title' => $data['title'],
            'epoch_year' => $data['epoch_year'],
            'month' => $data['month'],
            'day' => $data['day'],
            'breaks_line' => $breaksLine,
        ]);

        $event->line->recalculateBoundsFromEvents();

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

        $timelineEvent->line->recalculateBoundsFromEvents();

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
        $timelineEvent->biographies()->detach();
        $timelineEvent->delete();
        $line->recalculateBoundsFromEvents();

        return redirect()->route('worlds.timeline', $world);
    }
}
