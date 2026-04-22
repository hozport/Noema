<?php

namespace App\Http\Controllers\Markup;

use App\Http\Controllers\Controller;
use App\Markup\EntityModule;
use App\Models\Bestiary\Creature;
use App\Models\Biography\Biography;
use App\Models\Faction\Faction;
use App\Models\Timeline\TimelineLine;
use App\Models\Worlds\World;
use App\Models\Worlds\WorldMapSprite;
use App\Services\Markup\EntityPreviewResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

/**
 * API списков сущностей и превью для разметки карточек
 *
 * Списки кешируются по миру и модулю; resolve — с кешем в резолвере и ограничением частоты.
 */
class MarkupEntityController extends Controller
{
    public function __construct(
        private EntityPreviewResolver $resolver
    ) {}

    /**
     * Список id и подписей сущностей выбранного модуля в мире
     *
     * @param  Request  $request  Параметр `module` — значение `EntityModule`
     * @param  World  $world  Мир
     */
    public function entities(Request $request, World $world): JsonResponse
    {
        $this->authorizeWorld($world);

        $module = (int) $request->query('module', 0);
        $enum = EntityModule::tryFrom($module);

        if ($enum === null) {
            return response()->json(['items' => []]);
        }

        $ttl = (int) config('markup.entities_ttl_seconds', 600);
        $cacheKey = sprintf('markup:entities:world:%d:module:%d', $world->id, $module);

        $items = Cache::remember($cacheKey, $ttl, function () use ($world, $enum) {
            return match ($enum) {
                EntityModule::MapObject => WorldMapSprite::query()
                    ->whereHas('worldMap', fn ($q) => $q->where('world_id', $world->id))
                    ->orderBy('id')
                    ->get(['id', 'title'])
                    ->filter(fn (WorldMapSprite $s) => $s->qualifiesForMarkupEntityLink())
                    ->map(fn (WorldMapSprite $s) => [
                        'id' => $s->id,
                        'label' => trim((string) $s->title),
                    ])
                    ->values()
                    ->all(),
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
        });

        return response()->json(['items' => $items]);
    }

    /**
     * Пакетное разрешение превью по ссылкам refs
     *
     * @param  Request  $request  Поле `refs` — массив `{ module, entity }`
     * @param  World  $world  Мир
     */
    public function resolve(Request $request, World $world): JsonResponse
    {
        $this->authorizeWorld($world);

        $limit = (int) config('markup.resolve_rate_limit_per_minute', 120);
        $rateKey = sprintf('markup-resolve:user:%d', auth()->id());

        $executed = RateLimiter::attempt(
            $rateKey,
            $limit,
            function () use ($request, $world) {
                $validated = $request->validate([
                    'refs' => ['required', 'array', 'max:80'],
                    'refs.*.module' => ['required', 'integer'],
                    'refs.*.entity' => ['required', 'integer', 'min:1'],
                ]);

                $previews = $this->resolver->resolveBatch($world, $validated['refs']);

                return response()->json(['previews' => $previews]);
            },
            60
        );

        if ($executed === false) {
            return response()->json([
                'message' => 'Слишком много запросов. Подождите минуту.',
            ], 429);
        }

        return $executed;
    }

    /**
     * Проверка владельца и доступности мира
     *
     * @param  World  $world  Мир
     */
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
