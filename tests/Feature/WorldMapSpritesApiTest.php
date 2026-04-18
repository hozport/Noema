<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Worlds\World;
use App\Models\Worlds\WorldMapSprite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorldMapSpritesApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_store_map_sprite(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);

        $response = $this->actingAs($user)->postJson(route('worlds.maps.sprites.store', $world), [
            'sprite_path' => 'Поселения/gorod_1.svg',
            'pos_x' => 100.5,
            'pos_y' => 200.25,
        ]);

        $response->assertCreated()
            ->assertJsonFragment([
                'sprite_path' => 'Поселения/gorod_1.svg',
            ]);

        $this->assertDatabaseHas('world_map_sprites', [
            'world_id' => $world->id,
            'sprite_path' => 'Поселения/gorod_1.svg',
        ]);
    }

    public function test_owner_can_update_map_sprite_title_and_description_without_position(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);

        $sprite = WorldMapSprite::query()->create([
            'world_id' => $world->id,
            'sprite_path' => 'Поселения/gorod_1.svg',
            'pos_x' => 10,
            'pos_y' => 20,
        ]);

        $this->actingAs($user)->putJson(route('worlds.maps.sprites.update', [$world, $sprite]), [
            'title' => 'Городок',
            'description' => "Строка один.\nСтрока два.",
        ])->assertOk()
            ->assertJsonFragment([
                'title' => 'Городок',
                'description' => "Строка один.\nСтрока два.",
            ]);

        $this->assertDatabaseHas('world_map_sprites', [
            'id' => $sprite->id,
            'title' => 'Городок',
            'pos_x' => '10.0000',
            'pos_y' => '20.0000',
        ]);
    }

    public function test_owner_can_update_map_sprite_position(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);

        $sprite = WorldMapSprite::query()->create([
            'world_id' => $world->id,
            'sprite_path' => 'Поселения/gorod_1.svg',
            'pos_x' => 10,
            'pos_y' => 20,
        ]);

        $this->actingAs($user)->putJson(route('worlds.maps.sprites.update', [$world, $sprite]), [
            'pos_x' => 150,
            'pos_y' => 250,
        ])->assertOk()
            ->assertJsonFragment([
                'pos_x' => 150,
                'pos_y' => 250,
            ]);

        $this->assertDatabaseHas('world_map_sprites', [
            'id' => $sprite->id,
            'pos_x' => '150.0000',
            'pos_y' => '250.0000',
        ]);
    }

    public function test_owner_can_delete_map_sprite(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);

        $sprite = WorldMapSprite::query()->create([
            'world_id' => $world->id,
            'sprite_path' => 'Поселения/gorod_1.svg',
            'pos_x' => 10,
            'pos_y' => 20,
        ]);

        $this->actingAs($user)
            ->deleteJson(route('worlds.maps.sprites.destroy', [$world, $sprite]))
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertDatabaseMissing('world_map_sprites', ['id' => $sprite->id]);
    }

    public function test_store_rejects_invalid_sprite_path(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);

        $this->actingAs($user)->postJson(route('worlds.maps.sprites.store', $world), [
            'sprite_path' => '../.env',
            'pos_x' => 0,
            'pos_y' => 0,
        ])->assertStatus(422);

        $this->actingAs($user)->postJson(route('worlds.maps.sprites.store', $world), [
            'sprite_path' => 'НетТакойПапки/x.svg',
            'pos_x' => 0,
            'pos_y' => 0,
        ])->assertStatus(422);
    }

    public function test_other_user_cannot_store_sprite(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $owner->id,
            'name' => 'W',
            'onoff' => true,
        ]);

        $this->actingAs($other)->postJson(route('worlds.maps.sprites.store', $world), [
            'sprite_path' => 'Поселения/gorod_1.svg',
            'pos_x' => 0,
            'pos_y' => 0,
        ])->assertForbidden();
    }

    public function test_cannot_update_sprite_from_another_world(): void
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

        $sprite = WorldMapSprite::query()->create([
            'world_id' => $worldA->id,
            'sprite_path' => 'Поселения/gorod_1.svg',
            'pos_x' => 1,
            'pos_y' => 2,
        ]);

        $this->actingAs($user)->putJson(route('worlds.maps.sprites.update', [$worldB, $sprite]), [
            'pos_x' => 99,
            'pos_y' => 99,
        ])->assertNotFound();
    }

    public function test_cannot_delete_sprite_from_another_world(): void
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

        $sprite = WorldMapSprite::query()->create([
            'world_id' => $worldA->id,
            'sprite_path' => 'Поселения/gorod_1.svg',
            'pos_x' => 1,
            'pos_y' => 2,
        ]);

        $this->actingAs($user)
            ->deleteJson(route('worlds.maps.sprites.destroy', [$worldB, $sprite]))
            ->assertNotFound();

        $this->assertDatabaseHas('world_map_sprites', ['id' => $sprite->id]);
    }
}
