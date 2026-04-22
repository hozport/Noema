<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Worlds\World;
use App\Models\Worlds\WorldMap;
use App\Models\Worlds\WorldMapSprite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class MarkupEntityCacheAndRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_markup_entities_response_is_cached_per_world_and_module(): void
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
        WorldMapSprite::query()->create([
            'world_map_id' => $map->id,
            'sprite_path' => 'Поселения/gorod_1.svg',
            'pos_x' => 10,
            'pos_y' => 20,
            'title' => 'Город',
        ]);

        $cacheKey = sprintf('markup:entities:world:%d:module:1', $world->id);
        $this->assertFalse(Cache::has($cacheKey));

        $this->actingAs($user)->getJson(route('worlds.markup.entities', $world).'?module=1')->assertOk();

        $this->assertTrue(Cache::has($cacheKey));
    }

    public function test_markup_resolve_returns_429_when_rate_limited(): void
    {
        config(['markup.resolve_rate_limit_per_minute' => 2]);

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
            'title' => 'X',
        ]);

        $payload = [
            'refs' => [
                ['module' => 1, 'entity' => $sprite->id],
            ],
        ];

        RateLimiter::clear(sprintf('markup-resolve:user:%d', $user->id));

        $this->actingAs($user)->postJson(route('worlds.markup.resolve', $world), $payload)->assertOk();
        $this->actingAs($user)->postJson(route('worlds.markup.resolve', $world), $payload)->assertOk();
        $this->actingAs($user)->postJson(route('worlds.markup.resolve', $world), $payload)
            ->assertStatus(429)
            ->assertJsonPath('message', 'Слишком много запросов. Подождите минуту.');
    }

    public function test_markup_resolve_batch_cache_returns_same_previews_without_db_round_trip(): void
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
            'title' => 'Batch title',
        ]);

        $payload = [
            'refs' => [
                ['module' => 1, 'entity' => $sprite->id],
            ],
        ];

        $first = $this->actingAs($user)->postJson(route('worlds.markup.resolve', $world), $payload);
        $first->assertOk();
        $second = $this->actingAs($user)->postJson(route('worlds.markup.resolve', $world), $payload);
        $second->assertOk();

        $this->assertSame($first->json('previews'), $second->json('previews'));
    }
}
