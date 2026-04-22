<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Worlds\World;
use App\Models\Worlds\WorldMap;
use App\Models\Worlds\WorldMapSprite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MapsDatabaseConnectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_world_map_models_use_default_database_connection(): void
    {
        $this->assertNull((new WorldMap)->getConnectionName());
        $this->assertNull((new WorldMapSprite)->getConnectionName());
    }

    public function test_world_map_create_persists_on_sqlite_in_tests(): void
    {
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

        $this->assertSame(1, DB::table('world_maps')->where('world_id', $world->id)->count());
        $this->assertSame($map->id, (int) DB::table('world_maps')->where('world_id', $world->id)->value('id'));
    }
}
