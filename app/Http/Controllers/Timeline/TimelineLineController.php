<?php

namespace App\Http\Controllers\Timeline;

use App\Http\Controllers\Controller;
use App\Http\Requests\Timeline\StoreTimelineLineRequest;
use App\Models\ActivityLog;
use App\Http\Requests\Timeline\UpdateTimelineLineRequest;
use App\Models\Timeline\TimelineLine;
use App\Models\Worlds\World;
use Illuminate\Http\Request;

class TimelineLineController extends Controller
{
    public function store(StoreTimelineLineRequest $request, World $world)
    {
        if (! $world->onoff) {
            abort(404);
        }

        $data = $request->validated();
        $sortOrder = (int) ($world->timelineLines()->where('is_main', false)->max('sort_order') ?? -1) + 1;

        $line = $world->timelineLines()->create([
            'name' => $data['name'],
            'start_year' => $data['start_year'],
            'end_year' => $data['end_year'] !== null && $data['end_year'] !== '' ? (int) $data['end_year'] : null,
            'color' => $data['color'],
            'is_main' => false,
            'sort_order' => $sortOrder,
        ]);

        ActivityLog::record($request->user(), $world, 'timeline.line.created', 'Создана линия таймлайна «'.$line->name.'».', $line);

        return redirect()->route('worlds.timeline', $world);
    }

    public function update(UpdateTimelineLineRequest $request, World $world, TimelineLine $line)
    {
        if (! $world->onoff) {
            abort(404);
        }

        $data = $request->validated();
        $line->update([
            'name' => $data['name'],
            'start_year' => $data['start_year'],
            'end_year' => $data['end_year'] !== null && $data['end_year'] !== '' ? (int) $data['end_year'] : null,
            'color' => $data['color'],
        ]);

        ActivityLog::record($request->user(), $world, 'timeline.line.updated', 'Изменена линия «'.$line->name.'».', $line);

        return redirect()->route('worlds.timeline', $world);
    }

    public function destroy(Request $request, World $world, TimelineLine $line)
    {
        if ($world->user_id !== $request->user()->id || (int) $line->world_id !== (int) $world->id) {
            abort(403);
        }

        if (! $world->onoff) {
            abort(404);
        }

        if ($line->is_main) {
            abort(403, 'Нельзя удалить основную линию мира.');
        }

        $lineName = $line->name;
        ActivityLog::record($request->user(), $world, 'timeline.line.deleted', 'Удалена линия «'.$lineName.'».', $line);

        $line->delete();

        return redirect()->route('worlds.timeline', $world);
    }
}
