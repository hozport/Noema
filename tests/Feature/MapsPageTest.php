<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Worlds\World;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->assertSee('mapSprites', false);
    }
}
