<?php

namespace App\Http\Controllers\Biography;

use App\Http\Controllers\Controller;
use App\Http\Requests\Biography\CreateBiographyTimelineLineRequest;
use App\Http\Requests\Biography\PushBiographyEventToTimelineRequest;
use App\Models\ActivityLog;
use App\Models\Biography\Biography;
use App\Models\Biography\BiographyEvent;
use App\Models\Timeline\TimelineLine;
use App\Models\Worlds\World;
use App\Services\BiographyTimelineSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BiographyTimelineController extends Controller
{
    public function __construct(
        private readonly BiographyTimelineSyncService $syncService
    ) {}

    public function createLine(CreateBiographyTimelineLineRequest $request, World $world, Biography $biography): JsonResponse
    {
        $this->authorizeBiography($world, $biography);

        $color = $request->validated()['color'] ?? null;

        try {
            $line = $this->syncService->createLineFromBiography($biography, $color);
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'line' => $e->getMessage(),
            ]);
        }

        ActivityLog::record($request->user(), $world, 'biography.timeline.line_created', 'Для биографии «'.$biography->name.'» создана линия на таймлайне.', $line);

        return response()->json([
            'ok' => true,
            'message' => 'Линия создана, события размещены на таймлайне.',
            'line_id' => $line->id,
        ]);
    }

    /**
     * Удаляет линию таймлайна, созданную из этой биографии (события на линии исчезают с таймлайна;
     * записи в биографии и сами события биографии не удаляются).
     */
    public function removeLine(Request $request, World $world, Biography $biography): JsonResponse
    {
        $this->authorizeBiography($world, $biography);

        $line = TimelineLine::query()
            ->where('world_id', $world->id)
            ->where('source_biography_id', $biography->id)
            ->first();

        if (! $line) {
            return response()->json([
                'ok' => false,
                'message' => 'Линия этой биографии на таймлайне не найдена.',
            ], 404);
        }

        if ($line->is_main) {
            abort(403);
        }

        ActivityLog::record($request->user(), $world, 'biography.timeline.line_removed', 'С таймлайна убрана линия биографии «'.$biography->name.'».', $line);

        $line->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Линия убрана с таймлайна. События в биографии сохранены — их можно снова вынести на линию.',
        ]);
    }

    public function pushEvent(PushBiographyEventToTimelineRequest $request, World $world, Biography $biography): JsonResponse
    {
        $this->authorizeBiography($world, $biography);

        $data = $request->validated();
        $be = BiographyEvent::query()->where('biography_id', $biography->id)->findOrFail($data['biography_event_id']);
        $line = TimelineLine::query()->where('world_id', $world->id)->findOrFail($data['timeline_line_id']);

        try {
            $this->syncService->pushBiographyEventToLine($biography, $be, $line);
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages([
                'push' => $e->getMessage(),
            ]);
        }

        ActivityLog::record($request->user(), $world, 'biography.timeline.event_pushed', 'Событие «'.$be->title.'» (биография «'.$biography->name.'») вынесено на таймлайн.', $be);

        return response()->json([
            'ok' => true,
            'message' => 'Событие добавлено на таймлайн.',
        ]);
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
}
