<?php

namespace Tests\Feature;

use App\Models\Biography\Biography;
use App\Models\Biography\BiographyEvent;
use App\Models\Timeline\TimelineEvent;
use App\Models\Timeline\TimelineLine;
use App\Models\User;
use App\Models\Worlds\World;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BiographyTimelineEditSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_timeline_edit_updates_linked_biography_event_dates(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);

        $biography = Biography::query()->create([
            'world_id' => $world->id,
            'name' => 'Hero',
        ]);

        $be = BiographyEvent::query()->create([
            'biography_id' => $biography->id,
            'title' => 'Fact',
            'epoch_year' => 10,
            'year_end' => null,
            'month' => 2,
            'day' => 3,
            'body' => null,
            'breaks_line' => false,
        ]);

        $line = TimelineLine::query()->create([
            'world_id' => $world->id,
            'name' => 'Hero line',
            'start_year' => 0,
            'end_year' => null,
            'extends_to_canvas_end' => true,
            'color' => '#457B9D',
            'is_main' => false,
            'sort_order' => 0,
        ]);

        $te = TimelineEvent::query()->create([
            'timeline_line_id' => $line->id,
            'title' => 'Fact',
            'epoch_year' => 10,
            'month' => 2,
            'day' => 3,
            'breaks_line' => false,
            'source_type' => BiographyEvent::class,
            'source_id' => $be->id,
        ]);
        $te->biographies()->syncWithoutDetaching([(int) $biography->id]);

        $response = $this->actingAs($user)->from(route('worlds.timeline', $world))->put(
            route('timeline.events.update', [$world, $te]),
            [
                'title' => 'Новый заголовок',
                'epoch_year' => 99,
                'month' => 5,
                'day' => 7,
                'breaks_line' => false,
            ],
        );

        $response->assertRedirect(route('worlds.timeline', $world));

        $be->refresh();
        $this->assertSame('Новый заголовок', $be->title);
        $this->assertSame(99, $be->epoch_year);
        $this->assertSame(5, $be->month);
        $this->assertSame(7, $be->day);
    }

    public function test_biography_edit_updates_linked_timeline_event_title_and_dates(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);

        $biography = Biography::query()->create([
            'world_id' => $world->id,
            'name' => 'Hero',
        ]);

        $be = BiographyEvent::query()->create([
            'biography_id' => $biography->id,
            'title' => 'Fact',
            'epoch_year' => 10,
            'year_end' => null,
            'month' => 2,
            'day' => 3,
            'body' => null,
            'breaks_line' => false,
        ]);

        $line = TimelineLine::query()->create([
            'world_id' => $world->id,
            'name' => 'Hero line',
            'start_year' => 0,
            'end_year' => null,
            'extends_to_canvas_end' => true,
            'color' => '#457B9D',
            'is_main' => false,
            'sort_order' => 0,
        ]);

        $te = TimelineEvent::query()->create([
            'timeline_line_id' => $line->id,
            'title' => 'Fact',
            'epoch_year' => 10,
            'month' => 2,
            'day' => 3,
            'breaks_line' => false,
            'source_type' => BiographyEvent::class,
            'source_id' => $be->id,
        ]);
        $te->biographies()->syncWithoutDetaching([(int) $biography->id]);

        $this->actingAs($user)->putJson(route('biographies.events.update', [$world, $biography, $be]), [
            'title' => 'Из биографии',
            'epoch_year' => 50,
            'year_end' => null,
            'month' => 4,
            'day' => 8,
            'body' => null,
            'breaks_line' => false,
        ])->assertOk();

        $te->refresh();
        $this->assertSame('Из биографии', $te->title);
        $this->assertSame(50, $te->epoch_year);
        $this->assertSame(4, $te->month);
        $this->assertSame(8, $te->day);
    }

    public function test_deleting_biography_event_also_deletes_linked_timeline_event(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);

        $biography = Biography::query()->create([
            'world_id' => $world->id,
            'name' => 'Hero',
        ]);

        $be = BiographyEvent::query()->create([
            'biography_id' => $biography->id,
            'title' => 'Fact',
            'epoch_year' => 10,
            'year_end' => null,
            'month' => 2,
            'day' => 3,
            'body' => null,
            'breaks_line' => false,
        ]);

        $line = TimelineLine::query()->create([
            'world_id' => $world->id,
            'name' => 'Hero line',
            'start_year' => 0,
            'end_year' => null,
            'extends_to_canvas_end' => true,
            'color' => '#457B9D',
            'is_main' => false,
            'sort_order' => 0,
        ]);

        $te = TimelineEvent::query()->create([
            'timeline_line_id' => $line->id,
            'title' => 'Fact',
            'epoch_year' => 10,
            'month' => 2,
            'day' => 3,
            'breaks_line' => false,
            'source_type' => BiographyEvent::class,
            'source_id' => $be->id,
        ]);
        $te->biographies()->syncWithoutDetaching([(int) $biography->id]);

        $tid = $te->id;

        $this->actingAs($user)->deleteJson(route('biographies.events.destroy', [$world, $biography, $be]))->assertOk();

        $this->assertDatabaseMissing('biography_events', ['id' => $be->id]);
        $this->assertDatabaseMissing('timeline_events', ['id' => $tid]);
    }

    public function test_deleting_timeline_event_keeps_biography_event(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);

        $biography = Biography::query()->create([
            'world_id' => $world->id,
            'name' => 'Hero',
        ]);

        $be = BiographyEvent::query()->create([
            'biography_id' => $biography->id,
            'title' => 'Fact',
            'epoch_year' => 10,
            'year_end' => null,
            'month' => 2,
            'day' => 3,
            'body' => null,
            'breaks_line' => false,
        ]);

        $line = TimelineLine::query()->create([
            'world_id' => $world->id,
            'name' => 'Hero line',
            'start_year' => 0,
            'end_year' => null,
            'extends_to_canvas_end' => true,
            'color' => '#457B9D',
            'is_main' => false,
            'sort_order' => 0,
        ]);

        $te = TimelineEvent::query()->create([
            'timeline_line_id' => $line->id,
            'title' => 'Fact',
            'epoch_year' => 10,
            'month' => 2,
            'day' => 3,
            'breaks_line' => false,
            'source_type' => BiographyEvent::class,
            'source_id' => $be->id,
        ]);
        $te->biographies()->syncWithoutDetaching([(int) $biography->id]);

        $this->actingAs($user)->from(route('worlds.timeline', $world))->delete(
            route('timeline.events.destroy', [$world, $te]),
        )->assertRedirect(route('worlds.timeline', $world));

        $this->assertDatabaseMissing('timeline_events', ['id' => $te->id]);
        $this->assertDatabaseHas('biography_events', ['id' => $be->id, 'title' => 'Fact']);
    }
}
