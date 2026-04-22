<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Worlds\World;
use App\Models\Worlds\WorldMap;
use App\Models\Worlds\WorldMapSprite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarkupMapObjectEntitiesTest extends TestCase
{
    use RefreshDatabase;

    public function test_markup_entities_module_one_lists_world_map_sprites(): void
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

        $a = WorldMapSprite::query()->create([
            'world_map_id' => $map->id,
            'sprite_path' => 'Поселения/gorod_1.svg',
            'pos_x' => 10,
            'pos_y' => 20,
            'title' => 'Столица',
        ]);
        $b = WorldMapSprite::query()->create([
            'world_map_id' => $map->id,
            'sprite_path' => 'Поселения/gorod_2.svg',
            'pos_x' => 30,
            'pos_y' => 40,
            'title' => null,
        ]);
        $c = WorldMapSprite::query()->create([
            'world_map_id' => $map->id,
            'sprite_path' => 'Поселения/gorod_3.svg',
            'pos_x' => 50,
            'pos_y' => 60,
            'title' => 'объект на карте #'.$b->id,
        ]);

        $response = $this->actingAs($user)->getJson(route('worlds.markup.entities', $world).'?module=1');

        $response->assertOk();
        $response->assertJsonPath('items.0.id', $a->id);
        $response->assertJsonPath('items.0.label', 'Столица');
        $response->assertJsonCount(1, 'items');
    }

    public function test_markup_resolve_includes_map_object_preview(): void
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

        $sprite = WorldMapSprite::query()->create([
            'world_map_id' => $map->id,
            'sprite_path' => 'Поселения/gorod_1.svg',
            'pos_x' => 0,
            'pos_y' => 0,
            'title' => 'Город X',
            'description' => 'Описание точки',
        ]);

        $response = $this->actingAs($user)->postJson(route('worlds.markup.resolve', $world), [
            'refs' => [
                ['module' => 1, 'entity' => $sprite->id],
            ],
        ]);

        $response->assertOk();
        $key = '1:'.$sprite->id;
        $response->assertJsonPath("previews.$key.title", 'Город X');
        $response->assertJsonPath("previews.$key.description", 'Описание точки');
        $this->assertNotNull($response->json("previews.$key.image_url"));
    }
}
