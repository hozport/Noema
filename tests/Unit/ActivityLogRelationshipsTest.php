<?php

namespace Tests\Unit;

use App\Models\ActivityLog;
use App\Models\User;
use App\Models\Worlds\World;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Связи журнала активности с пользователем и миром на одном подключении
 */
class ActivityLogRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    public function test_actor_owner_and_world_relations_resolve(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);

        $log = ActivityLog::record($user, $world, 'world.test', 'Событие.', $world);

        $log->load(['actor', 'owner', 'world']);

        $this->assertTrue($log->actor->is($user));
        $this->assertTrue($log->owner->is($user));
        $this->assertTrue($log->world->is($world));
    }
}
