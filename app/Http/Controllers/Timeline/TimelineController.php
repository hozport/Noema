<?php

namespace App\Http\Controllers\Timeline;

use App\Http\Controllers\Controller;
use App\Models\Timeline\TimelineEvent;
use App\Models\Worlds\World;
use App\Support\TimelineVisualBuilder;
use Illuminate\Http\Request;

class TimelineController extends Controller
{
    public function show(Request $request, World $world)
    {
        if ($world->user_id !== $request->user()->id) {
            abort(403);
        }

        if (! $world->onoff) {
            abort(404);
        }

        $visual = TimelineVisualBuilder::build($world);
        $timelineLines = $world->timelineLines()->orderBy('is_main')->orderBy('sort_order')->orderBy('id')->get();

        $timelineEventsForJs = TimelineEvent::query()
            ->whereHas('line', fn ($q) => $q->where('world_id', $world->id))
            ->orderBy('id')
            ->get(['id', 'timeline_line_id', 'title', 'epoch_year', 'month', 'day', 'breaks_line']);

        return view('timeline.show', compact('world', 'visual', 'timelineLines', 'timelineEventsForJs'));
    }
}
