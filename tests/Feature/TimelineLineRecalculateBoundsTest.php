<?php

namespace Tests\Feature;

use App\Models\Timeline\TimelineEvent;
use App\Models\Timeline\TimelineLine;
use App\Models\User;
use App\Models\Worlds\World;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimelineLineRecalculateBoundsTest extends TestCase
{
    use RefreshDatabase;

    public function test_secondary_line_end_year_not_shrunk_when_only_event_is_at_period_start(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'Test world',
            'onoff' => true,
        ]);

        $line = TimelineLine::query()->create([
            'world_id' => $world->id,
            'name' => 'Test line',
            'start_year' => 0,
            'end_year' => 400,
            'color' => '#ff0000',
            'is_main' => false,
            'extends_to_canvas_end' => false,
            'sort_order' => 0,
        ]);

        TimelineEvent::query()->create([
            'timeline_line_id' => $line->id,
            'title' => 'Start',
            'epoch_year' => 0,
            'month' => 1,
            'day' => 1,
            'breaks_line' => false,
        ]);

        $line->refresh();
        $line->recalculateBoundsFromEvents();
        $line->refresh();

        $this->assertSame(0, $line->start_year);
        $this->assertSame(400, $line->end_year);
    }

    public function test_secondary_line_end_year_expands_when_event_is_after_period_end(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'Test world',
            'onoff' => true,
        ]);

        $line = TimelineLine::query()->create([
            'world_id' => $world->id,
            'name' => 'Test line',
            'start_year' => 0,
            'end_year' => 400,
            'color' => '#ff0000',
            'is_main' => false,
            'extends_to_canvas_end' => false,
            'sort_order' => 0,
        ]);

        TimelineEvent::query()->create([
            'timeline_line_id' => $line->id,
            'title' => 'Late',
            'epoch_year' => 500,
            'month' => 1,
            'day' => 1,
            'breaks_line' => false,
        ]);

        $line->refresh();
        $line->recalculateBoundsFromEvents();
        $line->refresh();

        $this->assertSame(0, $line->start_year);
        $this->assertSame(500, $line->end_year);
    }
}
