<?php

namespace App\Http\Controllers\Timeline;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Timeline\TimelineEvent;
use App\Models\Timeline\TimelineLine;
use App\Models\Worlds\World;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TimelineClearController extends Controller
{
    public function store(Request $request, World $world): RedirectResponse
    {
        if ($world->user_id !== $request->user()->id) {
            abort(403);
        }

        if (! $world->onoff) {
            abort(404);
        }

        DB::transaction(function () use ($world) {
            $lines = TimelineLine::query()->where('world_id', $world->id)->get();
            $mainLine = $lines->firstWhere('is_main', true);

            foreach ($lines->where('is_main', false) as $line) {
                $line->delete();
            }

            if ($mainLine) {
                TimelineEvent::query()
                    ->where('timeline_line_id', $mainLine->id)
                    ->where('is_reference_marker', false)
                    ->get()
                    ->each(function (TimelineEvent $event) {
                        $event->biographies()->detach();
                        $event->factions()->detach();
                        $event->delete();
                    });

                $mainLine->recalculateBoundsFromEvents();
            }
        });

        ActivityLog::record($request->user(), $world, 'timeline.cleared', 'Таймлайн очищен: удалены второстепенные линии и все события, кроме маркера точки отсчёта на основной линии.');

        return redirect()
            ->route('worlds.timeline', $world)
            ->with('success', 'Таймлайн очищен.');
    }
}
