<?php

namespace App\Http\Controllers\Biography;

use App\Http\Controllers\Controller;
use App\Http\Requests\Biography\StoreBiographyEventRequest;
use App\Models\ActivityLog;
use App\Http\Requests\Biography\UpdateBiographyEventRequest;
use App\Models\Biography\Biography;
use App\Models\Biography\BiographyEvent;
use App\Models\Worlds\World;
use App\Services\BiographyTimelineSyncService;
use Illuminate\Http\JsonResponse;

class BiographyEventController extends Controller
{
    public function store(StoreBiographyEventRequest $request, World $world, Biography $biography): JsonResponse
    {
        $this->authorizeBiography($world, $biography);

        $data = $request->validated();
        $event = $biography->biographyEvents()->create([
            'title' => $data['title'],
            'epoch_year' => $data['epoch_year'] ?? null,
            'year_end' => $data['year_end'] ?? null,
            'month' => $data['month'] ?? 1,
            'day' => $data['day'] ?? 1,
            'body' => $data['body'] ?? null,
            'breaks_line' => $request->boolean('breaks_line'),
        ]);

        ActivityLog::record($request->user(), $world, 'biography.event.created', 'В биографии «'.$biography->name.'» добавлено событие «'.$event->title.'».', $event);

        return response()->json([
            'ok' => true,
            'event' => $this->eventPayload($event),
        ]);
    }

    public function update(UpdateBiographyEventRequest $request, World $world, Biography $biography, BiographyEvent $biographyEvent): JsonResponse
    {
        $this->authorizeBiography($world, $biography);
        if ((int) $biographyEvent->biography_id !== (int) $biography->id) {
            abort(404);
        }

        $data = $request->validated();
        $biographyEvent->update([
            'title' => $data['title'],
            'epoch_year' => $data['epoch_year'] ?? null,
            'year_end' => $data['year_end'] ?? null,
            'month' => $data['month'] ?? 1,
            'day' => $data['day'] ?? 1,
            'body' => $data['body'] ?? null,
            'breaks_line' => $request->boolean('breaks_line'),
        ]);

        $fresh = $biographyEvent->fresh();
        $te = $fresh->timelineEvent()->with('line')->first();
        if ($te) {
            $line = $te->line;
            $sync = app(BiographyTimelineSyncService::class);
            $payload = [
                'title' => $sync->buildTimelineTitleForBiographyEvent($fresh),
                'breaks_line' => $line->is_main ? false : (bool) $fresh->breaks_line,
            ];
            if ($fresh->epoch_year !== null) {
                $payload['epoch_year'] = (int) $fresh->epoch_year;
                $payload['month'] = max(1, min(100, (int) $fresh->month));
                $payload['day'] = max(1, min(100, (int) $fresh->day));
            }
            $te->update($payload);
            $te->line->recalculateBoundsFromEvents();
        }

        ActivityLog::record($request->user(), $world, 'biography.event.updated', 'В биографии «'.$biography->name.'» изменено событие «'.$biographyEvent->title.'».', $biographyEvent);

        return response()->json([
            'ok' => true,
            'event' => $this->eventPayload($biographyEvent->fresh()),
        ]);
    }

    public function destroy(World $world, Biography $biography, BiographyEvent $biographyEvent): JsonResponse
    {
        $this->authorizeBiography($world, $biography);
        if ((int) $biographyEvent->biography_id !== (int) $biography->id) {
            abort(404);
        }

        $title = $biographyEvent->title;
        ActivityLog::record(auth()->user(), $world, 'biography.event.deleted', 'Удалено событие «'.$title.'» в биографии «'.$biography->name.'».', $biographyEvent);

        $biographyEvent->delete();

        return response()->json(['ok' => true]);
    }

    private function authorizeBiography(World $world, Biography $biography): void
    {
        if ($world->user_id !== auth()->id() || (int) $biography->world_id !== (int) $world->id) {
            abort(403);
        }
        if (! $world->onoff) {
            abort(404);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function eventPayload(BiographyEvent $e): array
    {
        return [
            'id' => $e->id,
            'title' => $e->title,
            'epoch_year' => $e->epoch_year,
            'year_end' => $e->year_end,
            'month' => $e->month,
            'day' => $e->day,
            'body' => $e->body,
            'breaks_line' => (bool) $e->breaks_line,
            'on_timeline' => $e->isOnTimeline(),
        ];
    }
}
