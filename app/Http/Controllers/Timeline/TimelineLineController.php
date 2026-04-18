<?php

namespace App\Http\Controllers\Timeline;

use App\Http\Controllers\Controller;
use App\Http\Requests\Timeline\StoreTimelineLineRequest;
use App\Http\Requests\Timeline\UpdateTimelineLineRequest;
use App\Models\ActivityLog;
use App\Models\Timeline\TimelineLine;
use App\Models\Worlds\World;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    /**
     * Сдвиг дополнительной линии на одну позицию вверх или вниз (обмен sort_order с соседом).
     *
     * Основная линия мира не участвует; с ней нельзя меняться местами.
     *
     * @param  Request  $request  Поле direction: up|down
     * @param  World  $world  Мир
     * @param  TimelineLine  $line  Линия (не основная)
     */
    public function move(Request $request, World $world, TimelineLine $line): JsonResponse|RedirectResponse
    {
        if ($world->user_id !== $request->user()->id || (int) $line->world_id !== (int) $world->id) {
            abort(403);
        }

        if (! $world->onoff) {
            abort(404);
        }

        if ($line->is_main) {
            abort(403, 'Основную линию нельзя перемещать.');
        }

        $validated = $request->validate([
            'direction' => ['required', 'string', 'in:up,down'],
        ]);

        $direction = $validated['direction'];

        $lines = $world->timelineLines()
            ->orderBy('is_main')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $idx = $lines->search(fn (TimelineLine $l) => (int) $l->id === (int) $line->id);
        if ($idx === false) {
            abort(404);
        }

        $neighborIdx = $direction === 'up' ? $idx - 1 : $idx + 1;
        if ($neighborIdx < 0 || $neighborIdx >= $lines->count()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Нет соседней линии для обмена.'], 422);
            }

            return redirect()->route('worlds.timeline', $world)->with('error', 'Нельзя переместить линию в этом направлении.');
        }

        /** @var TimelineLine $neighbor */
        $neighbor = $lines->get($neighborIdx);
        if ($neighbor->is_main) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'С основной линией нельзя меняться местами.'], 422);
            }

            return redirect()->route('worlds.timeline', $world)->with('error', 'С основной линией нельзя меняться местами.');
        }

        DB::transaction(function () use ($line, $neighbor): void {
            $sortLine = (int) $line->sort_order;
            $sortNeighbor = (int) $neighbor->sort_order;
            $line->update(['sort_order' => $sortNeighbor]);
            $neighbor->update(['sort_order' => $sortLine]);
        });

        ActivityLog::record($request->user(), $world, 'timeline.line.moved', 'Изменён порядок линии «'.$line->name.'».', $line);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->route('worlds.timeline', $world)->with('success', 'Порядок линий обновлён.');
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
