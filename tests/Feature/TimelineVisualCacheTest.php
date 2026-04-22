<?php

namespace Tests\Feature;

use App\Models\Timeline\TimelineEvent;
use App\Models\Timeline\TimelineLine;
use App\Models\User;
use App\Models\Worlds\World;
use App\Services\TimelineBootstrapService;
use App\Support\TimelineVisualCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class TimelineVisualCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_timeline_page_stores_visual_payload_in_cache(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);
        TimelineBootstrapService::bootstrap($world);

        $key = TimelineVisualCache::cacheKey($world->id);
        $this->assertFalse(Cache::has($key));

        $this->actingAs($user)->get(route('worlds.timeline', $world))->assertOk();

        $this->assertTrue(Cache::has($key));
        $payload = Cache::get($key);
        $this->assertIsArray($payload);
        $this->assertArrayHasKey('tracks', $payload);
        $this->assertNotEmpty($payload['tracks']);
    }

    public function test_timeline_visual_cache_invalidates_when_line_updated(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);
        TimelineBootstrapService::bootstrap($world);

        $this->actingAs($user)->get(route('worlds.timeline', $world))->assertOk();

        $key = TimelineVisualCache::cacheKey($world->id);
        $this->assertTrue(Cache::has($key));

        $line = TimelineLine::query()->where('world_id', $world->id)->where('is_main', true)->first();
        $this->assertNotNull($line);
        $line->update(['name' => 'Renamed main']);

        $this->assertFalse(Cache::has($key));
    }

    public function test_timeline_visual_cache_invalidates_when_event_saved(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);
        TimelineBootstrapService::bootstrap($world);

        $line = TimelineLine::query()->create([
            'world_id' => $world->id,
            'name' => 'Side',
            'start_year' => 0,
            'end_year' => null,
            'extends_to_canvas_end' => true,
            'color' => '#445566',
            'is_main' => false,
            'sort_order' => 1,
        ]);

        $this->actingAs($user)->get(route('worlds.timeline', $world))->assertOk();

        $key = TimelineVisualCache::cacheKey($world->id);
        $this->assertTrue(Cache::has($key));

        TimelineEvent::query()->create([
            'timeline_line_id' => $line->id,
            'title' => 'E',
            'epoch_year' => 10,
            'month' => 1,
            'day' => 1,
            'breaks_line' => false,
        ]);

        $this->assertFalse(Cache::has($key));
    }

    public function test_timeline_visual_cache_invalidates_when_world_reference_fields_change(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
            'reference_point' => null,
            'timeline_max_year' => null,
        ]);
        TimelineBootstrapService::bootstrap($world);

        $this->actingAs($user)->get(route('worlds.timeline', $world))->assertOk();

        $key = TimelineVisualCache::cacheKey($world->id);
        $this->assertTrue(Cache::has($key));

        $world->update(['reference_point' => 'Новая точка']);

        $this->assertFalse(Cache::has($key));
    }
}
