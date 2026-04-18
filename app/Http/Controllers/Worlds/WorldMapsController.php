<?php

namespace App\Http\Controllers\Worlds;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Worlds\World;
use App\Models\Worlds\WorldMapSprite;
use App\Models\Worlds\WorldSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class WorldMapsController extends Controller
{
    /**
     * Карты мира: географическое полотно и привязки.
     */
    public function show(Request $request, World $world): View
    {
        $this->assertMapAccess($request, $world);

        $setting = $world->setting instanceof WorldSetting ? $world->setting : WorldSetting::Fantasy;

        $fillUrl = null;
        if (is_string($world->map_fill_path) && $world->map_fill_path !== '' && Storage::disk('public')->exists($world->map_fill_path)) {
            // Корневой относительный URL: не зависит от APP_URL (иначе при другом хосте/HTTPS чем в .env
            // img.src указывает на неверный origin и loadImage падает — заливка не восстанавливается).
            $fillUrl = '/storage/'.$world->map_fill_path;
            $abs = Storage::disk('public')->path($world->map_fill_path);
            if (is_file($abs)) {
                $fillUrl .= '?v='.filemtime($abs);
            }
        }

        $mapPageMeta = [
            'spriteBaseUrl' => url('/sprites'),
            'mapSprites' => $world->mapSprites()->get(['id', 'sprite_path', 'pos_x', 'pos_y', 'title', 'description']),
            'mapsSpriteStoreUrl' => route('worlds.maps.sprites.store', $world),
            'mapsSpriteUpdateUrlPattern' => url('/worlds/'.$world->id.'/maps/sprites/__ID__'),
            'mapsCanvasSaveUrl' => route('worlds.maps.canvas.update', $world),
            'mapDrawingLines' => $world->map_drawing_lines ?? [],
            'mapFillUrl' => $fillUrl,
            'worldSetting' => $setting->value,
            'mapObjectLabelFontFamily' => $setting->mapObjectLabelFontFamily(),
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
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:65535'],
        ]);

        $path = $this->normalizedSpritePathOrNull($validated['sprite_path']);
        if ($path === null || ! $this->publicSpriteFileExists($path)) {
            abort(422, 'Недопустимый путь к спрайту.');
        }

        $titleIn = $validated['title'] ?? null;
        $descIn = $validated['description'] ?? null;
        $sprite = $world->mapSprites()->create([
            'sprite_path' => $path,
            'pos_x' => $validated['pos_x'],
            'pos_y' => $validated['pos_y'],
            'title' => $titleIn === null || (is_string($titleIn) && trim($titleIn) === '') ? null : trim((string) $titleIn),
            'description' => $descIn === null || (is_string($descIn) && trim($descIn) === '') ? null : trim((string) $descIn),
        ]);

        ActivityLog::record($request->user(), $world, 'map.sprite.created', 'На карту добавлен объект (спрайт).', $sprite);

        return response()->json([
            'id' => $sprite->id,
            'sprite_path' => $sprite->sprite_path,
            'pos_x' => (float) $sprite->pos_x,
            'pos_y' => (float) $sprite->pos_y,
            'title' => $sprite->title,
            'description' => $sprite->description,
        ], 201);
    }

    public function updateSprite(Request $request, World $world, WorldMapSprite $worldMapSprite): JsonResponse
    {
        $this->assertMapAccess($request, $world);

        if ($worldMapSprite->world_id !== $world->id) {
            abort(404);
        }

        $validated = $request->validate([
            'pos_x' => ['sometimes', 'required_with:pos_y', 'numeric'],
            'pos_y' => ['sometimes', 'required_with:pos_x', 'numeric'],
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:65535'],
        ]);

        if (isset($validated['pos_x'], $validated['pos_y'])) {
            $worldMapSprite->pos_x = $validated['pos_x'];
            $worldMapSprite->pos_y = $validated['pos_y'];
        }
        if (array_key_exists('title', $validated)) {
            $t = $validated['title'];
            $worldMapSprite->title = $t === null || (is_string($t) && trim($t) === '') ? null : trim($t);
        }
        if (array_key_exists('description', $validated)) {
            $t = $validated['description'];
            $worldMapSprite->description = $t === null || (is_string($t) && trim($t) === '') ? null : trim($t);
        }
        $worldMapSprite->save();

        ActivityLog::record($request->user(), $world, 'map.sprite.updated', 'Изменён объект на карте.', $worldMapSprite);

        return response()->json([
            'id' => $worldMapSprite->id,
            'sprite_path' => $worldMapSprite->sprite_path,
            'pos_x' => (float) $worldMapSprite->pos_x,
            'pos_y' => (float) $worldMapSprite->pos_y,
            'title' => $worldMapSprite->title,
            'description' => $worldMapSprite->description,
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

    /**
     * Сохранение линий ландшафта/границ и растровой заливки карты мира.
     */
    public function updateCanvas(Request $request, World $world): JsonResponse
    {
        $this->assertMapAccess($request, $world);

        $validated = $request->validate([
            'lines' => ['present', 'array', 'max:5000'],
            'lines.*.points' => ['required', 'array', 'min:4'],
            'lines.*.points.*' => ['numeric'],
            'lines.*.stroke' => ['required', 'string', 'max:128'],
            'lines.*.dash' => ['nullable', 'array'],
            'lines.*.dash.*' => ['numeric'],
            'lines.*.strokeWidth' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:20'],
            'fill_png_base64' => ['sometimes', 'nullable', 'string', 'max:16777215'],
        ]);

        $world->map_drawing_lines = $validated['lines'];

        if ($request->has('fill_png_base64')) {
            $fillRaw = $request->input('fill_png_base64');
            if ($fillRaw === null || $fillRaw === '') {
                if (is_string($world->map_fill_path) && $world->map_fill_path !== '') {
                    Storage::disk('public')->delete($world->map_fill_path);
                }
                $world->map_fill_path = null;
            } else {
                $binary = base64_decode($fillRaw, true);
                if ($binary === false || strlen($binary) > 25 * 1024 * 1024) {
                    abort(422, 'Недопустимые данные заливки.');
                }
                $dir = 'worlds/'.$world->id;
                Storage::disk('public')->makeDirectory($dir);
                $path = $dir.'/map_fill.png';
                Storage::disk('public')->put($path, $binary);
                if (is_string($world->map_fill_path) && $world->map_fill_path !== '' && $world->map_fill_path !== $path) {
                    Storage::disk('public')->delete($world->map_fill_path);
                }
                $world->map_fill_path = $path;
            }
        }

        $world->save();

        ActivityLog::record($request->user(), $world, 'map.canvas.updated', 'Сохранены линии и заливка карты мира.');

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
