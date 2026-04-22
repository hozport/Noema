<?php

namespace App\Http\Controllers\Worlds;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Worlds\World;
use App\Models\Worlds\WorldMap;
use App\Models\Worlds\WorldMapSprite;
use App\Models\Worlds\WorldSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class WorldMapsController extends Controller
{
    /**
     * Список карт мира (карточки).
     */
    public function index(Request $request, World $world): View
    {
        $this->assertMapAccess($request, $world);

        $maps = $world->maps()->get(['id', 'world_id', 'title', 'width', 'height', 'updated_at', 'map_fill_path']);

        return view('maps.index', [
            'world' => $world,
            'maps' => $maps,
        ]);
    }

    /**
     * Настройки модуля «Карты» на уровне мира: размеры нового холста по умолчанию.
     *
     * @param  Request  $request  HTTP-запрос
     * @param  World  $world  Мир
     */
    public function updateModuleSettings(Request $request, World $world): RedirectResponse
    {
        $this->assertMapAccess($request, $world);

        $validated = $request->validate([
            'maps_default_width' => ['required', 'integer', 'min:'.WorldMap::MIN_SIDE, 'max:'.WorldMap::MAX_SIDE],
            'maps_default_height' => ['required', 'integer', 'min:'.WorldMap::MIN_SIDE, 'max:'.WorldMap::MAX_SIDE],
        ]);

        $world->maps_default_width = (int) $validated['maps_default_width'];
        $world->maps_default_height = (int) $validated['maps_default_height'];
        $world->save();

        ActivityLog::record(
            $request->user(),
            $world,
            'map.module_settings',
            'Сохранены настройки модуля «Карты»: размеры по умолчанию '.$world->maps_default_width.'×'.$world->maps_default_height.' px.',
            $world,
        );

        return redirect()
            ->route('worlds.maps.index', $world)
            ->with('success', 'Настройки сохранены.');
    }

    /**
     * Создание карты (размер и название).
     */
    public function store(Request $request, World $world): RedirectResponse
    {
        $this->assertMapAccess($request, $world);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'width' => ['required', 'integer', 'min:'.WorldMap::MIN_SIDE, 'max:'.WorldMap::MAX_SIDE],
            'height' => ['required', 'integer', 'min:'.WorldMap::MIN_SIDE, 'max:'.WorldMap::MAX_SIDE],
        ]);

        $map = $world->maps()->create([
            'title' => trim($validated['title']),
            'width' => (int) $validated['width'],
            'height' => (int) $validated['height'],
            'map_drawing_lines' => [],
            'map_fill_path' => null,
        ]);

        ActivityLog::record($request->user(), $world, 'map.created', 'Создана карта: '.$map->title.'.', $map);

        return redirect()
            ->route('worlds.maps.show', [$world, $map])
            ->with('success', 'Карта создана.');
    }

    /**
     * Редактор карты (холст Konva).
     */
    public function show(Request $request, World $world, WorldMap $map): View
    {
        $this->assertMapAccess($request, $world);
        $this->assertMapBelongsToWorld($world, $map);

        $setting = $world->setting instanceof WorldSetting ? $world->setting : WorldSetting::Fantasy;

        $fillUrl = $map->fillPreviewUrl();

        $mapPageMeta = [
            'mapId' => $map->id,
            'mapTitle' => $map->title,
            'mapWidth' => (int) $map->width,
            'mapHeight' => (int) $map->height,
            'spriteBaseUrl' => url('/sprites'),
            'mapSprites' => $map->mapSprites()->get(['id', 'sprite_path', 'pos_x', 'pos_y', 'title', 'description']),
            'mapsSpriteStoreUrl' => route('worlds.maps.sprites.store', [$world, $map]),
            'mapsSpriteUpdateUrlPattern' => url('/worlds/'.$world->id.'/maps/'.$map->id.'/sprites/__ID__'),
            'mapsCanvasSaveUrl' => route('worlds.maps.canvas.update', [$world, $map]),
            'mapsFillUploadUrl' => route('worlds.maps.fill.store', [$world, $map]),
            'mapDrawingLines' => $map->map_drawing_lines ?? [],
            'mapFillUrl' => $fillUrl,
            'worldSetting' => $setting->value,
            'mapObjectLabelFontFamily' => $setting->mapObjectLabelFontFamily(),
            'mapsMapUpdateUrl' => route('worlds.maps.update', [$world, $map]),
            'mapsMapDestroyUrl' => route('worlds.maps.destroy', [$world, $map]),
        ];

        return view('maps.show', [
            'world' => $world,
            'map' => $map,
            'mapPageMeta' => $mapPageMeta,
        ]);
    }

    /**
     * Настройки карты: название и размер.
     */
    public function update(Request $request, World $world, WorldMap $map): RedirectResponse
    {
        $this->assertMapAccess($request, $world);
        $this->assertMapBelongsToWorld($world, $map);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'width' => ['required', 'integer', 'min:'.WorldMap::MIN_SIDE, 'max:'.WorldMap::MAX_SIDE],
            'height' => ['required', 'integer', 'min:'.WorldMap::MIN_SIDE, 'max:'.WorldMap::MAX_SIDE],
        ]);

        $newW = (int) $validated['width'];
        $newH = (int) $validated['height'];
        $sizeChanged = $map->width !== $newW || $map->height !== $newH;

        if ($sizeChanged && is_string($map->map_fill_path) && $map->map_fill_path !== '') {
            Storage::disk('public')->delete($map->map_fill_path);
            $map->map_fill_path = null;
        }

        $map->title = trim($validated['title']);
        $map->width = $newW;
        $map->height = $newH;
        $map->save();

        ActivityLog::record($request->user(), $world, 'map.updated', 'Изменены настройки карты: '.$map->title.'.', $map);

        return redirect()
            ->route('worlds.maps.show', [$world, $map])
            ->with('success', 'Настройки карты сохранены.');
    }

    /**
     * Удаление карты (спрайты и файлы — каскадом и в модели).
     */
    public function destroy(Request $request, World $world, WorldMap $map): RedirectResponse
    {
        $this->assertMapAccess($request, $world);
        $this->assertMapBelongsToWorld($world, $map);

        $title = $map->title;
        $map->delete();

        ActivityLog::record($request->user(), $world, 'map.deleted', 'Удалена карта: '.$title.'.');

        return redirect()
            ->route('worlds.maps.index', $world)
            ->with('success', 'Карта удалена.');
    }

    public function storeSprite(Request $request, World $world, WorldMap $map): JsonResponse
    {
        $this->assertMapAccess($request, $world);
        $this->assertMapBelongsToWorld($world, $map);

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
        $sprite = $map->mapSprites()->create([
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

    public function updateSprite(Request $request, World $world, WorldMap $map, WorldMapSprite $mapSprite): JsonResponse
    {
        $this->assertMapAccess($request, $world);
        $this->assertMapBelongsToWorld($world, $map);

        if ($mapSprite->world_map_id !== $map->id) {
            abort(404);
        }

        $validated = $request->validate([
            'pos_x' => ['sometimes', 'required_with:pos_y', 'numeric'],
            'pos_y' => ['sometimes', 'required_with:pos_x', 'numeric'],
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:65535'],
        ]);

        if (isset($validated['pos_x'], $validated['pos_y'])) {
            $mapSprite->pos_x = $validated['pos_x'];
            $mapSprite->pos_y = $validated['pos_y'];
        }
        if (array_key_exists('title', $validated)) {
            $t = $validated['title'];
            $mapSprite->title = $t === null || (is_string($t) && trim($t) === '') ? null : trim($t);
        }
        if (array_key_exists('description', $validated)) {
            $t = $validated['description'];
            $mapSprite->description = $t === null || (is_string($t) && trim($t) === '') ? null : trim($t);
        }
        $mapSprite->save();

        ActivityLog::record($request->user(), $world, 'map.sprite.updated', 'Изменён объект на карте.', $mapSprite);

        return response()->json([
            'id' => $mapSprite->id,
            'sprite_path' => $mapSprite->sprite_path,
            'pos_x' => (float) $mapSprite->pos_x,
            'pos_y' => (float) $mapSprite->pos_y,
            'title' => $mapSprite->title,
            'description' => $mapSprite->description,
        ]);
    }

    public function destroySprite(Request $request, World $world, WorldMap $map, WorldMapSprite $mapSprite): JsonResponse
    {
        $this->assertMapAccess($request, $world);
        $this->assertMapBelongsToWorld($world, $map);

        if ($mapSprite->world_map_id !== $map->id) {
            abort(404);
        }

        ActivityLog::record($request->user(), $world, 'map.sprite.deleted', 'С карты удалён объект.', $mapSprite);

        $mapSprite->delete();

        return response()->json(['ok' => true]);
    }

    /**
     * Сохранение линий ландшафта и границ (JSON). Растр заливки — отдельно {@see storeMapFill}.
     */
    public function updateCanvas(Request $request, World $world, WorldMap $map): JsonResponse
    {
        $this->assertMapAccess($request, $world);
        $this->assertMapBelongsToWorld($world, $map);

        $validated = $request->validate([
            'lines' => ['present', 'array', 'max:5000'],
            'lines.*.points' => ['required', 'array', 'min:4'],
            'lines.*.points.*' => ['numeric'],
            'lines.*.stroke' => ['required', 'string', 'max:128'],
            'lines.*.dash' => ['nullable', 'array'],
            'lines.*.dash.*' => ['numeric'],
            'lines.*.strokeWidth' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:20'],
            'clear_fill' => ['sometimes', 'boolean'],
        ]);

        $map->map_drawing_lines = $validated['lines'];

        if ($request->boolean('clear_fill')) {
            if (is_string($map->map_fill_path) && $map->map_fill_path !== '') {
                Storage::disk('public')->delete($map->map_fill_path);
            }
            $map->map_fill_path = null;
        }

        $map->save();

        ActivityLog::record($request->user(), $world, 'map.canvas.updated', 'Сохранены линии карты.', $map);

        return response()->json(['ok' => true]);
    }

    /**
     * Отдаёт сохранённую PNG заливку карты владельцу мира
     *
     * Файл читается с диска `storage/app/public`; не требует симлинка `public/storage` (в отличие от URL `/storage/…`).
     *
     * @param  Request  $request  HTTP-запрос
     * @param  World  $world  Мир
     * @param  WorldMap  $map  Карта
     * @return BinaryFileResponse Ответ с телом PNG
     */
    public function showMapFillPng(Request $request, World $world, WorldMap $map): BinaryFileResponse
    {
        $this->assertMapAccess($request, $world);
        $this->assertMapBelongsToWorld($world, $map);

        $relative = $map->map_fill_path;
        if (! is_string($relative) || $relative === '' || ! Storage::disk('public')->exists($relative)) {
            abort(404);
        }
        $absolute = Storage::disk('public')->path($relative);
        if (! is_file($absolute)) {
            abort(404);
        }

        return response()->file($absolute, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'inline; filename="map_fill.png"',
            'Cache-Control' => 'private, max-age=0, must-revalidate',
        ]);
    }

    /**
     * Загрузка PNG заливки карты (multipart)
     *
     * Отдельный запрос от линий: файл в теле без гигантского base64 в JSON (ниже риск лимитов PHP/прокси).
     * На проде убедитесь, что `upload_max_filesize` и `post_max_size` в php.ini (и лимит тела в nginx)
     * не меньше максимального размера PNG холста; иначе загрузка оборвётся до Laravel.
     * Диагностика: при `config('app.map_fill_debug')` или `APP_DEBUG` пишет в лог строки с префиксом `[noema-map-fill]`.
     */
    public function storeMapFill(Request $request, World $world, WorldMap $map): JsonResponse
    {
        $this->assertMapAccess($request, $world);
        $this->assertMapBelongsToWorld($world, $map);

        if (config('app.map_fill_debug') || config('app.debug')) {
            Log::info('[noema-map-fill] storeMapFill enter', [
                'user_id' => $request->user()->id,
                'world_id' => $world->id,
                'map_id' => $map->id,
                'map_fill_path_before' => $map->map_fill_path,
                'content_length' => $request->header('Content-Length'),
            ]);
        }

        try {
            $validated = $request->validate([
                // extensions вместо mimes: часть окружений неверно определяет MIME у PNG с canvas (fileinfo).
                'fill' => ['required', 'file', 'extensions:png', 'max:102400'],
            ]);
        } catch (ValidationException $e) {
            if (config('app.map_fill_debug') || config('app.debug')) {
                Log::warning('[noema-map-fill] storeMapFill validation failed', [
                    'errors' => $e->errors(),
                    'world_id' => $world->id,
                    'map_id' => $map->id,
                ]);
            }

            throw $e;
        }

        $file = $validated['fill'];
        $binary = $file->get();
        if (config('app.map_fill_debug') || config('app.debug')) {
            Log::info('[noema-map-fill] storeMapFill after read', [
                'bytes' => strlen($binary),
                'client_mime' => $file->getClientMimeType(),
                'client_name' => $file->getClientOriginalName(),
            ]);
        }
        if (strlen($binary) < 8 || ! str_starts_with($binary, "\x89PNG\r\n\x1a\n")) {
            if (config('app.map_fill_debug') || config('app.debug')) {
                Log::warning('[noema-map-fill] storeMapFill rejected: bad PNG signature', ['head_hex' => bin2hex(substr($binary, 0, 16))]);
            }
            abort(422, 'Недопустимые данные заливки.');
        }
        if (strlen($binary) > 25 * 1024 * 1024) {
            abort(422, 'Недопустимые данные заливки.');
        }

        $dir = 'worlds/'.$world->id.'/maps/'.$map->id;
        Storage::disk('public')->makeDirectory($dir);
        $path = $dir.'/map_fill.png';
        Storage::disk('public')->put($path, $binary);
        if (is_string($map->map_fill_path) && $map->map_fill_path !== '' && $map->map_fill_path !== $path) {
            Storage::disk('public')->delete($map->map_fill_path);
        }
        $map->map_fill_path = $path;
        $map->save();

        if (config('app.map_fill_debug') || config('app.debug')) {
            Log::info('[noema-map-fill] storeMapFill saved', [
                'path' => $path,
                'disk_exists' => Storage::disk('public')->exists($path),
            ]);
        }

        ActivityLog::record($request->user(), $world, 'map.fill.updated', 'Сохранена заливка карты.', $map);

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

    private function assertMapBelongsToWorld(World $world, WorldMap $map): void
    {
        if ($map->world_id !== $world->id) {
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
