<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use App\Models\Worlds\World;
use App\Models\Worlds\WorldMap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Покрытие модуля «Карты»: CRUD, доступ, журнал, смена размера и заливки.
 */
class MapsModuleFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_map_and_redirects_to_editor(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);

        $response = $this->actingAs($user)->post(route('worlds.maps.store', $world), [
            'title' => 'Новая карта',
            'width' => 2000,
            'height' => 1800,
        ]);

        $map = WorldMap::query()->where('world_id', $world->id)->first();
        $this->assertNotNull($map);
        $this->assertSame('Новая карта', $map->title);
        $this->assertSame(2000, $map->width);
        $this->assertSame(1800, $map->height);

        $response->assertRedirect(route('worlds.maps.show', [$world, $map]));
    }

    public function test_map_store_validation_requires_title_and_valid_side(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);

        $this->actingAs($user)->post(route('worlds.maps.store', $world), [
            'title' => '',
            'width' => 2000,
            'height' => 2000,
        ])->assertSessionHasErrors(['title']);

        $this->actingAs($user)->post(route('worlds.maps.store', $world), [
            'title' => 'X',
            'width' => WorldMap::MIN_SIDE - 1,
            'height' => 2000,
        ])->assertSessionHasErrors(['width']);
    }

    public function test_owner_can_update_single_map_settings(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);
        $map = WorldMap::query()->create([
            'world_id' => $world->id,
            'title' => 'Было',
            'width' => 3000,
            'height' => 3000,
            'map_drawing_lines' => [],
            'map_fill_path' => null,
        ]);

        $this->actingAs($user)->put(route('worlds.maps.update', [$world, $map]), [
            'title' => 'Стало',
            'width' => 2800,
            'height' => 2600,
        ])->assertRedirect(route('worlds.maps.show', [$world, $map]));

        $map->refresh();
        $this->assertSame('Стало', $map->title);
        $this->assertSame(2800, $map->width);
        $this->assertSame(2600, $map->height);
    }

    public function test_map_resize_clears_fill_png_on_disk(): void
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
            'title' => 'К',
            'width' => 3000,
            'height' => 3000,
            'map_drawing_lines' => [],
            'map_fill_path' => null,
        ]);

        $path = 'worlds/'.$world->id.'/maps/'.$map->id.'/map_fill.png';
        Storage::disk('public')->makeDirectory(\dirname($path));
        Storage::disk('public')->put($path, 'fake');
        $map->map_fill_path = $path;
        $map->save();

        $this->assertTrue(Storage::disk('public')->exists($path));

        $this->actingAs($user)->put(route('worlds.maps.update', [$world, $map]), [
            'title' => 'К',
            'width' => 2800,
            'height' => 3000,
        ])->assertRedirect(route('worlds.maps.show', [$world, $map]));

        $map->refresh();
        $this->assertNull($map->map_fill_path);
        $this->assertFalse(Storage::disk('public')->exists($path));
    }

    public function test_owner_can_delete_map_and_redirects_to_index(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);
        $map = WorldMap::query()->create([
            'world_id' => $world->id,
            'title' => 'Удалить',
            'width' => 2000,
            'height' => 2000,
            'map_drawing_lines' => [],
            'map_fill_path' => null,
        ]);
        $mapId = $map->id;

        $this->actingAs($user)
            ->delete(route('worlds.maps.destroy', [$world, $map]))
            ->assertRedirect(route('worlds.maps.index', $world));

        $this->assertNull(WorldMap::query()->find($mapId));
    }

    public function test_non_owner_cannot_access_maps_index(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $owner->id,
            'name' => 'W',
            'onoff' => true,
        ]);

        $this->actingAs($other)->get(route('worlds.maps.index', $world))->assertForbidden();
    }

    public function test_guest_cannot_create_map(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);

        $this->post(route('worlds.maps.store', $world), [
            'title' => 'X',
            'width' => 2000,
            'height' => 2000,
        ])->assertRedirect();
    }

    public function test_non_owner_cannot_mutate_map(): void
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
            'title' => 'M',
            'width' => 2000,
            'height' => 2000,
            'map_drawing_lines' => [],
            'map_fill_path' => null,
        ]);

        $this->actingAs($other)->put(route('worlds.maps.update', [$world, $map]), [
            'title' => 'Взлом',
            'width' => 2000,
            'height' => 2000,
        ])->assertForbidden();

        $this->actingAs($other)->delete(route('worlds.maps.destroy', [$world, $map]))->assertForbidden();
    }

    public function test_maps_routes_return_404_when_world_hidden(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => false,
        ]);

        $this->actingAs($user)->get(route('worlds.maps.index', $world))->assertNotFound();
    }

    public function test_map_editor_returns_404_when_map_belongs_to_another_world(): void
    {
        $user = User::factory()->create();
        $worldA = World::query()->create([
            'user_id' => $user->id,
            'name' => 'A',
            'onoff' => true,
        ]);
        $worldB = World::query()->create([
            'user_id' => $user->id,
            'name' => 'B',
            'onoff' => true,
        ]);
        $mapOnA = WorldMap::query()->create([
            'world_id' => $worldA->id,
            'title' => 'На A',
            'width' => 2000,
            'height' => 2000,
            'map_drawing_lines' => [],
            'map_fill_path' => null,
        ]);

        $this->actingAs($user)
            ->get(route('worlds.maps.show', [$worldB, $mapOnA]))
            ->assertNotFound();
    }

    public function test_maps_module_activity_lists_only_map_actions_and_clear_works(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);
        ActivityLog::record($user, $world, 'world.updated', 'Событие не из карт', $world);

        $this->actingAs($user)->post(route('worlds.maps.store', $world), [
            'title' => 'ЖурналТест',
            'width' => 2000,
            'height' => 2000,
        ])->assertRedirect();

        $html = $this->actingAs($user)->get(route('maps.module.activity', $world))->assertOk()->getContent();
        $this->assertStringContainsString('ЖурналТест', $html);
        $this->assertStringNotContainsString('Событие не из карт', $html);

        $this->assertGreaterThan(0, ActivityLog::query()->where('world_id', $world->id)->where('action', 'like', 'map.%')->count());

        $this->actingAs($user)
            ->from(route('maps.module.activity', $world))
            ->delete(route('maps.module.activity.clear', $world))
            ->assertRedirect(route('maps.module.activity', $world));

        $this->assertSame(0, ActivityLog::query()->where('world_id', $world->id)->where('action', 'like', 'map.%')->count());
        $this->assertSame(1, ActivityLog::query()->where('world_id', $world->id)->where('action', 'world.updated')->count());
    }

    public function test_single_map_activity_is_scoped_to_that_map_and_clear_works(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);
        $mapA = WorldMap::query()->create([
            'world_id' => $world->id,
            'title' => 'Карта А',
            'width' => 2000,
            'height' => 2000,
            'map_drawing_lines' => [],
            'map_fill_path' => null,
        ]);
        $mapB = WorldMap::query()->create([
            'world_id' => $world->id,
            'title' => 'Карта Б',
            'width' => 2000,
            'height' => 2000,
            'map_drawing_lines' => [],
            'map_fill_path' => null,
        ]);

        ActivityLog::record($user, $world, 'map.updated', 'Правки только Б.', $mapB);
        ActivityLog::record($user, $world, 'map.updated', 'Правки только А.', $mapA);

        $html = $this->actingAs($user)
            ->get(route('worlds.maps.activity', [$world, $mapA]))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('Правки только А.', $html);
        $this->assertStringNotContainsString('Правки только Б.', $html);

        $this->actingAs($user)
            ->from(route('worlds.maps.activity', [$world, $mapA]))
            ->delete(route('worlds.maps.activity.clear', [$world, $mapA]))
            ->assertRedirect(route('worlds.maps.activity', [$world, $mapA]));

        $this->assertSame(
            0,
            ActivityLog::query()
                ->where('world_id', $world->id)
                ->where('subject_type', WorldMap::class)
                ->where('subject_id', $mapA->id)
                ->count()
        );
        $this->assertSame(1, ActivityLog::query()->where('world_id', $world->id)->where('subject_id', $mapB->id)->count());
    }
}
