<?php

namespace App\Http\Controllers\Faction;

use App\Http\Controllers\Controller;
use App\Http\Requests\Faction\CreateFactionTimelineLineRequest;
use App\Http\Requests\Faction\PushFactionEventToTimelineRequest;
use App\Models\Faction\Faction;
use App\Models\Faction\FactionEvent;
use App\Models\Timeline\TimelineLine;
use App\Models\Worlds\World;
use App\Services\FactionTimelineSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class FactionTimelineController extends Controller
{
    public function __construct(
        private readonly FactionTimelineSyncService $syncService
    ) {}

    public function createLine(CreateFactionTimelineLineRequest $request, World $world, Faction $faction): JsonResponse
    {
        $this->authorizeFaction($world, $faction);

        $color = $request->validated()['color'] ?? null;

        try {
            $line = $this->syncService->createLineFromFaction($faction, $color);
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'line' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'ok' => true,
            'message' => 'Линия создана, события размещены на таймлайне.',
            'line_id' => $line->id,
        ]);
    }

    public function removeLine(World $world, Faction $faction): JsonResponse
    {
        $this->authorizeFaction($world, $faction);

        $line = TimelineLine::query()
            ->where('world_id', $world->id)
            ->where('source_faction_id', $faction->id)
            ->first();

        if (! $line) {
            return response()->json([
                'ok' => false,
                'message' => 'Линия этой фракции на таймлайне не найдена.',
            ], 404);
        }

        if ($line->is_main) {
            abort(403);
        }

        $line->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Линия убрана с таймлайна. События во фракции сохранены — их можно снова вынести на линию.',
        ]);
    }

    public function pushEvent(PushFactionEventToTimelineRequest $request, World $world, Faction $faction): JsonResponse
    {
        $this->authorizeFaction($world, $faction);

        $data = $request->validated();
        $fe = FactionEvent::query()->where('faction_id', $faction->id)->findOrFail($data['faction_event_id']);
        $line = TimelineLine::query()->where('world_id', $world->id)->findOrFail($data['timeline_line_id']);

        try {
            $this->syncService->pushFactionEventToLine($faction, $fe, $line);
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages([
                'push' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'ok' => true,
            'message' => 'Событие добавлено на таймлайн.',
        ]);
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
}
