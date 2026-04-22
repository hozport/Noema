<?php

namespace Tests\Unit;

use App\Models\Biography\Biography;
use App\Models\User;
use App\Models\Worlds\World;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Порядок событий биографии (orderByRaw совместим с PostgreSQL и SQLite)
 */
class BiographyEventsOrderQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_biography_events_relation_query_executes_without_error(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);

        $bio = Biography::query()->create([
            'world_id' => $world->id,
            'name' => 'Герой',
        ]);

        $bio->biographyEvents()->create([
            'title' => 'Рождение',
            'epoch_year' => 1000,
            'month' => 1,
            'day' => 1,
            'body' => null,
        ]);

        $rows = $bio->biographyEvents()->get();

        $this->assertCount(1, $rows);
        $this->assertSame('Рождение', $rows->first()->title);
    }
}
