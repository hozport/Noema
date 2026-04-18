<?php

namespace Tests\Unit;

use App\Models\Timeline\TimelineEvent;
use App\Models\Timeline\TimelineLine;
use App\Models\User;
use App\Models\Worlds\World;
use App\Services\TimelineBootstrapService;
use App\Support\TimelineVisualBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimelineVisualBuilderBreaksTest extends TestCase
{
    use RefreshDatabase;

    public function test_earliest_break_year_truncates_line_when_multiple_flags_set(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);
        TimelineBootstrapService::bootstrap($world);

        $secondary = TimelineLine::query()->create([
            'world_id' => $world->id,
            'name' => 'Secondary',
            'start_year' => 0,
            'end_year' => null,
            'extends_to_canvas_end' => true,
            'color' => '#457B9D',
            'is_main' => false,
            'sort_order' => 1,
        ]);

        TimelineEvent::query()->create([
            'timeline_line_id' => $secondary->id,
            'title' => 'Late break',
            'epoch_year' => 100,
            'month' => 1,
            'day' => 1,
            'breaks_line' => true,
        ]);
        TimelineEvent::query()->create([
            'timeline_line_id' => $secondary->id,
            'title' => 'Early break',
            'epoch_year' => 50,
            'month' => 1,
            'day' => 1,
            'breaks_line' => true,
        ]);

        $world->refresh();
        $visual = TimelineVisualBuilder::build($world);
        $track = collect($visual['tracks'])->firstWhere('id', $secondary->id);

        $this->assertNotNull($track);
        $this->assertSame(50, $track['lineToYear']);
    }

    public function test_single_break_year_truncates_line(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);
        TimelineBootstrapService::bootstrap($world);

        $secondary = TimelineLine::query()->create([
            'world_id' => $world->id,
            'name' => 'Secondary',
            'start_year' => 0,
            'end_year' => null,
            'extends_to_canvas_end' => true,
            'color' => '#457B9D',
            'is_main' => false,
            'sort_order' => 1,
        ]);

        TimelineEvent::query()->create([
            'timeline_line_id' => $secondary->id,
            'title' => 'Only break',
            'epoch_year' => 120,
            'month' => 1,
            'day' => 1,
            'breaks_line' => true,
        ]);

        $world->refresh();
        $visual = TimelineVisualBuilder::build($world);
        $track = collect($visual['tracks'])->firstWhere('id', $secondary->id);

        $this->assertNotNull($track);
        $this->assertSame(120, $track['lineToYear']);
    }
}
