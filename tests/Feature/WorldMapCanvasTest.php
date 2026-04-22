<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Worlds\World;
use App\Models\Worlds\WorldMap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WorldMapCanvasTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_save_map_canvas(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);
        $map = WorldMap::query()->create([
            'world_id' => $world->id,
            'title' => 'Карта',
            'width' => 3000,
            'height' => 3000,
            'map_drawing_lines' => [],
            'map_fill_path' => null,
        ]);

        $this->putJson(route('worlds.maps.canvas.update', [$world, $map]), [
            'lines' => [],
        ])->assertUnauthorized();
    }

    public function test_guest_cannot_download_map_fill_png(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);
        $map = WorldMap::query()->create([
            'world_id' => $world->id,
            'title' => 'Карта',
            'width' => 3000,
            'height' => 3000,
            'map_drawing_lines' => [],
            'map_fill_path' => null,
        ]);
        $path = 'worlds/'.$world->id.'/maps/'.$map->id.'/map_fill.png';
        Storage::disk('public')->makeDirectory(\dirname($path));
        Storage::disk('public')->put($path, 'x');
        $map->map_fill_path = $path;
        $map->save();

        $this->get(route('worlds.maps.fill.show', [$world, $map]))->assertRedirect();
    }

    public function test_owner_can_download_map_fill_png_via_route(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);
        $map = WorldMap::query()->create([
            'world_id' => $world->id,
            'title' => 'Карта',
            'width' => 3000,
            'height' => 3000,
            'map_drawing_lines' => [],
            'map_fill_path' => null,
        ]);
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
        $path = 'worlds/'.$world->id.'/maps/'.$map->id.'/map_fill.png';
        Storage::disk('public')->makeDirectory(\dirname($path));
        Storage::disk('public')->put($path, $png);
        $map->map_fill_path = $path;
        $map->save();

        $res = $this->actingAs($user)->get(route('worlds.maps.fill.show', [$world, $map]));
        $res->assertOk();
        $res->assertHeader('content-type', 'image/png');
        $this->assertStringStartsWith("\x89PNG\r\n\x1a\n", $res->streamedContent());
    }

    public function test_guest_cannot_upload_map_fill(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);
        $map = WorldMap::query()->create([
            'world_id' => $world->id,
            'title' => 'Карта',
            'width' => 3000,
            'height' => 3000,
            'map_drawing_lines' => [],
            'map_fill_path' => null,
        ]);
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
        $file = UploadedFile::fake()->createWithContent('map_fill.png', $png);

        $this->post(route('worlds.maps.fill.store', [$world, $map]), [
            'fill' => $file,
        ])->assertRedirect();
    }

    public function test_owner_can_save_map_canvas_lines_and_clear_fill(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);
        $map = WorldMap::query()->create([
            'world_id' => $world->id,
            'title' => 'Карта',
            'width' => 3000,
            'height' => 3000,
            'map_drawing_lines' => [],
            'map_fill_path' => null,
        ]);
        $path = 'worlds/'.$world->id.'/maps/'.$map->id.'/map_fill.png';
        Storage::disk('public')->makeDirectory(\dirname($path));
        Storage::disk('public')->put($path, 'old');
        $map->map_fill_path = $path;
        $map->save();

        $this->actingAs($user)->putJson(route('worlds.maps.canvas.update', [$world, $map]), [
            'lines' => [
                [
                    'points' => [0, 0, 100, 200],
                    'stroke' => 'rgba(52, 48, 42, 0.92)',
                    'dash' => null,
                ],
            ],
            'clear_fill' => true,
        ])->assertOk()->assertJson(['ok' => true]);

        $map->refresh();
        $this->assertCount(1, $map->map_drawing_lines ?? []);
        $this->assertSame('rgba(52, 48, 42, 0.92)', $map->map_drawing_lines[0]['stroke']);
        $this->assertNull($map->map_fill_path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_owner_can_save_map_fill_png_via_multipart(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);
        $map = WorldMap::query()->create([
            'world_id' => $world->id,
            'title' => 'Карта',
            'width' => 3000,
            'height' => 3000,
            'map_drawing_lines' => [],
            'map_fill_path' => null,
        ]);

        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
        $file = UploadedFile::fake()->createWithContent('map_fill.png', $png);

        $this->actingAs($user)->post(route('worlds.maps.fill.store', [$world, $map]), [
            'fill' => $file,
        ])->assertOk()->assertJson(['ok' => true]);

        $map->refresh();
        $expectedPath = 'worlds/'.$world->id.'/maps/'.$map->id.'/map_fill.png';
        $this->assertSame($expectedPath, $map->map_fill_path);
        Storage::disk('public')->assertExists($map->map_fill_path);
        $this->assertSame($png, Storage::disk('public')->get($map->map_fill_path));
    }

    public function test_owner_can_save_lines_without_changing_stored_fill(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);
        $map = WorldMap::query()->create([
            'world_id' => $world->id,
            'title' => 'Карта',
            'width' => 3000,
            'height' => 3000,
            'map_drawing_lines' => [],
            'map_fill_path' => null,
        ]);
        $path = 'worlds/'.$world->id.'/maps/'.$map->id.'/map_fill.png';
        Storage::disk('public')->makeDirectory(\dirname($path));
        Storage::disk('public')->put($path, 'saved-fill');
        $map->map_fill_path = $path;
        $map->save();

        $this->actingAs($user)->putJson(route('worlds.maps.canvas.update', [$world, $map]), [
            'lines' => [
                [
                    'points' => [0, 0, 50, 50],
                    'stroke' => 'rgba(52, 48, 42, 0.92)',
                    'dash' => null,
                ],
            ],
        ])->assertOk();

        $map->refresh();
        $this->assertSame($path, $map->map_fill_path);
        Storage::disk('public')->assertExists($path);
        $this->assertSame('saved-fill', Storage::disk('public')->get($path));
    }

    public function test_non_owner_cannot_save_map_canvas(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $owner->id,
            'name' => 'W',
            'onoff' => true,
        ]);
        $map = WorldMap::query()->create([
            'world_id' => $world->id,
            'title' => 'Карта',
            'width' => 3000,
            'height' => 3000,
            'map_drawing_lines' => [],
            'map_fill_path' => null,
        ]);

        $this->actingAs($other)->putJson(route('worlds.maps.canvas.update', [$world, $map]), [
            'lines' => [],
        ])->assertForbidden();
    }

    public function test_non_owner_cannot_upload_map_fill(): void
    {
        Storage::fake('public');

        $owner = User::factory()->create();
        $other = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $owner->id,
            'name' => 'W',
            'onoff' => true,
        ]);
        $map = WorldMap::query()->create([
            'world_id' => $world->id,
            'title' => 'Карта',
            'width' => 3000,
            'height' => 3000,
            'map_drawing_lines' => [],
            'map_fill_path' => null,
        ]);
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
        $file = UploadedFile::fake()->createWithContent('map_fill.png', $png);

        $this->actingAs($other)->post(route('worlds.maps.fill.store', [$world, $map]), [
            'fill' => $file,
        ])->assertForbidden();
    }
}
