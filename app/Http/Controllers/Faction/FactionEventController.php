<?php

namespace App\Http\Controllers\Faction;

use App\Http\Controllers\Controller;
use App\Http\Requests\Faction\StoreFactionEventRequest;
use App\Models\ActivityLog;
use App\Http\Requests\Faction\UpdateFactionEventRequest;
use App\Models\Faction\Faction;
use App\Models\Faction\FactionEvent;
use App\Models\Worlds\World;
use App\Services\FactionTimelineSyncService;
use Illuminate\Http\JsonResponse;

class FactionEventController extends Controller
{
    public function store(StoreFactionEventRequest $request, World $world, Faction $faction): JsonResponse
    {
        $this->authorizeFaction($world, $faction);

        $data = $request->validated();
        $event = $faction->factionEvents()->create([
            'title' => $data['title'],
            'epoch_year' => $data['epoch_year'] ?? null,
            'year_end' => $data['year_end'] ?? null,
            'month' => $data['month'] ?? 1,
            'day' => $data['day'] ?? 1,
            'body' => $data['body'] ?? null,
            'breaks_line' => $request->boolean('breaks_line'),
        ]);

        ActivityLog::record($request->user(), $world, 'faction.event.created', 'Во фракции «'.$faction->name.'» добавлено событие «'.$event->title.'».', $event);

        return response()->json([
            'ok' => true,
            'event' => $this->eventPayload($event),
        ]);
    }

    public function update(UpdateFactionEventRequest $request, World $world, Faction $faction, FactionEvent $factionEvent): JsonResponse
    {
        $this->authorizeFaction($world, $faction);
        if ((int) $factionEvent->faction_id !== (int) $faction->id) {
            abort(404);
        }

        $data = $request->validated();
        $factionEvent->update([
            'title' => $data['title'],
            'epoch_year' => $data['epoch_year'] ?? null,
            'year_end' => $data['year_end'] ?? null,
            'month' => $data['month'] ?? 1,
            'day' => $data['day'] ?? 1,
            'body' => $data['body'] ?? null,
            'breaks_line' => $request->boolean('breaks_line'),
        ]);

        $fresh = $factionEvent->fresh();
        $te = $fresh->timelineEvent()->with('line')->first();
        if ($te) {
            $line = $te->line;
            $sync = app(FactionTimelineSyncService::class);
            $payload = [
                'title' => $sync->buildTimelineTitleForFactionEvent($fresh),
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

        ActivityLog::record($request->user(), $world, 'faction.event.updated', 'Во фракции «'.$faction->name.'» изменено событие «'.$factionEvent->title.'».', $factionEvent);

        return response()->json([
            'ok' => true,
            'event' => $this->eventPayload($factionEvent->fresh()),
        ]);
    }

    public function destroy(World $world, Faction $faction, FactionEvent $factionEvent): JsonResponse
    {
        $this->authorizeFaction($world, $faction);
        if ((int) $factionEvent->faction_id !== (int) $faction->id) {
            abort(404);
        }

        $title = $factionEvent->title;
        ActivityLog::record(auth()->user(), $world, 'faction.event.deleted', 'Удалено событие «'.$title.'» во фракции «'.$faction->name.'».', $factionEvent);

        $factionEvent->delete();

        return response()->json(['ok' => true]);
    }

    private function authorizeFaction(World $world, Faction $faction): void
    {
        if ($world->user_id !== auth()->id() || (int) $faction->world_id !== (int) $world->id) {
            abort(403);
        }
        if (! $world->onoff) {
            abort(404);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function eventPayload(FactionEvent $e): array
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
