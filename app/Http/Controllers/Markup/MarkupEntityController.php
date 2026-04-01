<?php

namespace App\Http\Controllers\Markup;

use App\Http\Controllers\Controller;
use App\Markup\EntityModule;
use App\Models\Bestiary\Creature;
use App\Models\Biography\Biography;
use App\Models\Faction\Faction;
use App\Models\Timeline\TimelineLine;
use App\Models\Worlds\World;
use App\Services\Markup\EntityPreviewResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarkupEntityController extends Controller
{
    public function __construct(
        private EntityPreviewResolver $resolver
    ) {}

    public function entities(Request $request, World $world): JsonResponse
    {
        $this->authorizeWorld($world);

        $module = (int) $request->query('module', 0);
        $enum = EntityModule::tryFrom($module);

        if ($enum === null) {
            return response()->json(['items' => []]);
        }

        $items = match ($enum) {
            EntityModule::MapStub => [],
            EntityModule::TimelineLine => TimelineLine::query()
                ->where('world_id', $world->id)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(['id', 'name'])
                ->map(fn (TimelineLine $l) => [
                    'id' => $l->id,
                    'label' => $l->name,
                ])
                ->values()
                ->all(),
            EntityModule::BestiaryCreature => Creature::query()
                ->where('world_id', $world->id)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Creature $c) => [
                    'id' => $c->id,
                    'label' => $c->name,
                ])
                ->values()
                ->all(),
            EntityModule::Biography => Biography::query()
                ->where('world_id', $world->id)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Biography $b) => [
                    'id' => $b->id,
                    'label' => $b->name,
                ])
                ->values()
                ->all(),
            EntityModule::Faction => Faction::query()
                ->where('world_id', $world->id)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Faction $f) => [
                    'id' => $f->id,
                    'label' => $f->name,
                ])
                ->values()
                ->all(),
        };

        return response()->json(['items' => $items]);
    }

    public function resolve(Request $request, World $world): JsonResponse
    {
        $this->authorizeWorld($world);

        $validated = $request->validate([
            'refs' => ['required', 'array', 'max:80'],
            'refs.*.module' => ['required', 'integer'],
            'refs.*.entity' => ['required', 'integer', 'min:1'],
        ]);

        $previews = $this->resolver->resolveBatch($world, $validated['refs']);

        return response()->json(['previews' => $previews]);
    }

    private function authorizeWorld(World $world): void
    {
        if ($world->user_id !== auth()->id()) {
            abort(403);
        }
        if (! $world->onoff) {
            abort(404);
        }
    }
}
