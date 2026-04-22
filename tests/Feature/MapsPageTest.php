<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Worlds\World;
use App\Models\Worlds\WorldMap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MapsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_maps_index_requires_auth(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);

        $this->get(route('worlds.maps.index', $world))->assertRedirect();
    }

    public function test_owner_can_open_maps_index(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);

        $this->actingAs($user);

        $this->get(route('worlds.maps.index', $world))
            ->assertOk()
            ->assertSee('Карты', false);
    }

    public function test_maps_index_renders_fill_preview_url_for_maps_with_png(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);

        $map = $world->maps()->create([
            'title' => 'M',
            'width' => 2000,
            'height' => 2000,
            'map_drawing_lines' => [],
            'map_fill_path' => null,
        ]);

        $path = 'worlds/'.$world->id.'/maps/'.$map->id.'/map_fill.png';
        Storage::disk('public')->put($path, 'fake-png');
        $map->map_fill_path = $path;
        $map->save();

        $html = $this->actingAs($user)
            ->get(route('worlds.maps.index', $world))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(
            '/worlds/'.$world->id.'/maps/'.$map->id.'/fill.png',
            $html
        );
    }

    public function test_owner_can_open_map_editor_and_meta_includes_fill_url(): void
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
        Storage::disk('public')->put($path, 'fake-png');
        $map->map_fill_path = $path;
        $map->save();

        $html = $this->actingAs($user)->get(route('worlds.maps.show', [$world, $map]))->assertOk()->getContent();

        $this->assertStringContainsString('map-stage-mount', $html);
        preg_match('#id="map-page-meta">(.+?)</script>#s', $html, $m);
        $this->assertArrayHasKey(1, $m);
        $meta = json_decode($m[1], true);
        $this->assertIsArray($meta);
        $this->assertArrayHasKey('mapFillUrl', $meta);
        $this->assertIsString($meta['mapFillUrl']);
        $this->assertStringContainsString('/worlds/'.$world->id.'/maps/'.$map->id.'/fill.png', $meta['mapFillUrl']);
        $this->assertSame(3000, $meta['mapWidth']);
        $this->assertSame(3000, $meta['mapHeight']);
    }

    public function test_maps_index_shows_default_dimensions_and_owner_can_update_module_settings(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);

        $this->actingAs($user);

        $html = $this->get(route('worlds.maps.index', $world))->assertOk()->getContent();
        $this->assertStringContainsString('id="maps-module-width"', $html);
        $this->assertStringContainsString('value="2000"', $html);

        $this->put(route('worlds.maps.settings.update', $world), [
            'maps_default_width' => 2200,
            'maps_default_height' => 2100,
        ])->assertRedirect(route('worlds.maps.index', $world));

        $world->refresh();
        $this->assertSame(2200, $world->maps_default_width);
        $this->assertSame(2100, $world->maps_default_height);

        $html2 = $this->get(route('worlds.maps.index', $world))->assertOk()->getContent();
        $this->assertStringContainsString('value="2200"', $html2);
        $this->assertStringContainsString('value="2100"', $html2);
    }

    public function test_maps_module_settings_validation_rejects_out_of_range(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);

        $this->actingAs($user)->put(route('worlds.maps.settings.update', $world), [
            'maps_default_width' => 400,
            'maps_default_height' => 2000,
        ])->assertSessionHasErrors(['maps_default_width']);
    }
}
