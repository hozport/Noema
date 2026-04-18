<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Worlds\World;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MapsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_maps_page_requires_auth(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);

        $this->get(route('worlds.maps', $world))->assertRedirect();
    }

    public function test_owner_can_open_maps_page(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);

        $this->actingAs($user);

        $this->get(route('worlds.maps', $world))
            ->assertOk()
            ->assertSee('Карты', false)
            ->assertSee('map-stage-mount', false)
            ->assertSee('/sprites', false)
            ->assertSee('map-page-meta', false)
            ->assertSee('mapSprites', false)
            ->assertSee('mapsCanvasSaveUrl', false);
    }

    public function test_maps_page_meta_includes_root_relative_map_fill_url(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);

        $path = 'worlds/'.$world->id.'/map_fill.png';
        Storage::disk('public')->makeDirectory('worlds/'.$world->id);
        Storage::disk('public')->put($path, 'fake-png');
        $world->map_fill_path = $path;
        $world->save();

        $html = $this->actingAs($user)->get(route('worlds.maps', $world))->assertOk()->getContent();

        preg_match('#id="map-page-meta">(.+?)</script>#s', $html, $m);
        $this->assertArrayHasKey(1, $m);
        $meta = json_decode($m[1], true);
        $this->assertIsArray($meta);
        $this->assertArrayHasKey('mapFillUrl', $meta);
        $this->assertIsString($meta['mapFillUrl']);
        $this->assertStringStartsWith('/storage/'.$path, $meta['mapFillUrl']);
    }
}
