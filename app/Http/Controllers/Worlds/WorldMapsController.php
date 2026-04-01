<?php

namespace App\Http\Controllers\Worlds;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Worlds\World;
use App\Models\Worlds\WorldMapSprite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorldMapsController extends Controller
{
    /**
     * Карты мира: географическое полотно и привязки.
     */
    public function show(Request $request, World $world): View
    {
        $this->assertMapAccess($request, $world);

        $mapPageMeta = [
            'spriteBaseUrl' => url('/sprites'),
            'mapSprites' => $world->mapSprites()->get(['id', 'sprite_path', 'pos_x', 'pos_y']),
            'mapsSpriteStoreUrl' => route('worlds.maps.sprites.store', $world),
            'mapsSpriteUpdateUrlPattern' => url('/worlds/'.$world->id.'/maps/sprites/__ID__'),
        ];

        return view('maps.show', [
            'world' => $world,
            'mapPageMeta' => $mapPageMeta,
        ]);
    }

    public function storeSprite(Request $request, World $world): JsonResponse
    {
        $this->assertMapAccess($request, $world);

        $validated = $request->validate([
            'sprite_path' => ['required', 'string', 'max:512'],
            'pos_x' => ['required', 'numeric'],
            'pos_y' => ['required', 'numeric'],
        ]);

        $path = $this->normalizedSpritePathOrNull($validated['sprite_path']);
        if ($path === null || ! $this->publicSpriteFileExists($path)) {
            abort(422, 'Недопустимый путь к спрайту.');
        }

        $sprite = $world->mapSprites()->create([
            'sprite_path' => $path,
            'pos_x' => $validated['pos_x'],
            'pos_y' => $validated['pos_y'],
        ]);

        ActivityLog::record($request->user(), $world, 'map.sprite.created', 'На карту добавлен объект (спрайт).', $sprite);

        return response()->json([
            'id' => $sprite->id,
            'sprite_path' => $sprite->sprite_path,
            'pos_x' => (float) $sprite->pos_x,
            'pos_y' => (float) $sprite->pos_y,
        ], 201);
    }

    public function updateSprite(Request $request, World $world, WorldMapSprite $worldMapSprite): JsonResponse
    {
        $this->assertMapAccess($request, $world);

        if ($worldMapSprite->world_id !== $world->id) {
            abort(404);
        }

        $validated = $request->validate([
            'pos_x' => ['required', 'numeric'],
            'pos_y' => ['required', 'numeric'],
        ]);

        $worldMapSprite->update([
            'pos_x' => $validated['pos_x'],
            'pos_y' => $validated['pos_y'],
        ]);

        ActivityLog::record($request->user(), $world, 'map.sprite.updated', 'Изменено положение объекта на карте.', $worldMapSprite);

        return response()->json([
            'id' => $worldMapSprite->id,
            'sprite_path' => $worldMapSprite->sprite_path,
            'pos_x' => (float) $worldMapSprite->pos_x,
            'pos_y' => (float) $worldMapSprite->pos_y,
        ]);
    }

    public function destroySprite(Request $request, World $world, WorldMapSprite $worldMapSprite): JsonResponse
    {
        $this->assertMapAccess($request, $world);

        if ($worldMapSprite->world_id !== $world->id) {
            abort(404);
        }

        ActivityLog::record($request->user(), $world, 'map.sprite.deleted', 'С карты удалён объект.', $worldMapSprite);

        $worldMapSprite->delete();

        return response()->json(['ok' => true]);
    }

    private function assertMapAccess(Request $request, World $world): void
    {
        if ($world->user_id !== $request->user()->id) {
            abort(403);
        }

        if (! $world->onoff) {
            abort(404);
        }
    }

    private function normalizedSpritePathOrNull(string $raw): ?string
    {
        $s = str_replace('\\', '/', trim($raw));
        if ($s === '' || str_contains($s, '..')) {
            return null;
        }
        foreach (explode('/', $s) as $part) {
            if ($part === '..') {
                return null;
            }
        }

        return $s;
    }

    private function publicSpriteFileExists(string $relativePath): bool
    {
        $full = public_path('sprites'.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativePath));

        return is_file($full);
    }
}
