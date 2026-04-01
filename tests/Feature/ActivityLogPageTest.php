<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use App\Models\Worlds\World;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_activity_page_loads_and_lists_entries(): void
    {
        $user = User::factory()->create();
        ActivityLog::record($user, null, 'account.profile.updated', 'Обновлён профиль.', $user);

        $response = $this->actingAs($user)->get(route('account.activity'));

        $response->assertOk();
        $response->assertSee('Обновлён профиль.', false);
    }

    public function test_world_activity_page_loads_for_owner(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'Мир W',
            'onoff' => true,
        ]);
        ActivityLog::record($user, $world, 'world.updated', 'Сохранены настройки мира.', $world);

        $response = $this->actingAs($user)->get(route('worlds.activity', $world));

        $response->assertOk();
        $response->assertSee('Сохранены настройки мира.', false);
    }

    public function test_world_activity_forbidden_for_other_user(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $owner->id,
            'name' => 'Мир W',
            'onoff' => true,
        ]);

        $this->actingAs($other)->get(route('worlds.activity', $world))->assertForbidden();
    }
}
