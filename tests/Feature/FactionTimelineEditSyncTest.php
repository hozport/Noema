<?php

namespace Tests\Feature;

use App\Models\Faction\Faction;
use App\Models\Faction\FactionEvent;
use App\Models\Timeline\TimelineEvent;
use App\Models\Timeline\TimelineLine;
use App\Models\User;
use App\Models\Worlds\World;
use App\Services\TimelineBootstrapService;
use App\Support\FactionType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FactionTimelineEditSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_timeline_edit_updates_linked_faction_event_dates(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);
        TimelineBootstrapService::bootstrap($world);

        $faction = Faction::query()->create([
            'world_id' => $world->id,
            'name' => 'Guild',
            'type' => FactionType::OTHER,
        ]);

        $fe = FactionEvent::query()->create([
            'faction_id' => $faction->id,
            'title' => 'Pact',
            'epoch_year' => 10,
            'year_end' => null,
            'month' => 2,
            'day' => 3,
            'body' => null,
            'breaks_line' => false,
        ]);

        $line = TimelineLine::query()->create([
            'world_id' => $world->id,
            'name' => 'Guild line',
            'start_year' => 0,
            'end_year' => null,
            'extends_to_canvas_end' => true,
            'color' => '#457B9D',
            'is_main' => false,
            'sort_order' => 1,
        ]);

        $te = TimelineEvent::query()->create([
            'timeline_line_id' => $line->id,
            'title' => 'Pact',
            'epoch_year' => 10,
            'month' => 2,
            'day' => 3,
            'breaks_line' => false,
            'source_type' => FactionEvent::class,
            'source_id' => $fe->id,
        ]);
        $te->factions()->syncWithoutDetaching([(int) $faction->id]);

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

        $fe->refresh();
        $this->assertSame('Новый заголовок', $fe->title);
        $this->assertSame(99, $fe->epoch_year);
        $this->assertSame(5, $fe->month);
        $this->assertSame(7, $fe->day);
    }

    public function test_faction_edit_updates_linked_timeline_event_title_and_dates(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);
        TimelineBootstrapService::bootstrap($world);

        $faction = Faction::query()->create([
            'world_id' => $world->id,
            'name' => 'Guild',
            'type' => FactionType::OTHER,
        ]);

        $fe = FactionEvent::query()->create([
            'faction_id' => $faction->id,
            'title' => 'Pact',
            'epoch_year' => 10,
            'year_end' => null,
            'month' => 2,
            'day' => 3,
            'body' => null,
            'breaks_line' => false,
        ]);

        $line = TimelineLine::query()->create([
            'world_id' => $world->id,
            'name' => 'Guild line',
            'start_year' => 0,
            'end_year' => null,
            'extends_to_canvas_end' => true,
            'color' => '#457B9D',
            'is_main' => false,
            'sort_order' => 1,
        ]);

        $te = TimelineEvent::query()->create([
            'timeline_line_id' => $line->id,
            'title' => 'Pact',
            'epoch_year' => 10,
            'month' => 2,
            'day' => 3,
            'breaks_line' => false,
            'source_type' => FactionEvent::class,
            'source_id' => $fe->id,
        ]);
        $te->factions()->syncWithoutDetaching([(int) $faction->id]);

        $this->actingAs($user)->putJson(route('factions.events.update', [$world, $faction, $fe]), [
            'title' => 'Из фракции',
            'epoch_year' => 50,
            'year_end' => null,
            'month' => 4,
            'day' => 8,
            'body' => null,
            'breaks_line' => false,
        ])->assertOk();

        $te->refresh();
        $this->assertStringContainsString('Из фракции', $te->title);
        $this->assertSame(50, $te->epoch_year);
        $this->assertSame(4, $te->month);
        $this->assertSame(8, $te->day);
    }

    public function test_deleting_faction_event_also_deletes_linked_timeline_event(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);
        TimelineBootstrapService::bootstrap($world);

        $faction = Faction::query()->create([
            'world_id' => $world->id,
            'name' => 'Guild',
            'type' => FactionType::OTHER,
        ]);

        $fe = FactionEvent::query()->create([
            'faction_id' => $faction->id,
            'title' => 'Pact',
            'epoch_year' => 10,
            'year_end' => null,
            'month' => 2,
            'day' => 3,
            'body' => null,
            'breaks_line' => false,
        ]);

        $line = TimelineLine::query()->create([
            'world_id' => $world->id,
            'name' => 'Guild line',
            'start_year' => 0,
            'end_year' => null,
            'extends_to_canvas_end' => true,
            'color' => '#457B9D',
            'is_main' => false,
            'sort_order' => 1,
        ]);

        $te = TimelineEvent::query()->create([
            'timeline_line_id' => $line->id,
            'title' => 'Pact',
            'epoch_year' => 10,
            'month' => 2,
            'day' => 3,
            'breaks_line' => false,
            'source_type' => FactionEvent::class,
            'source_id' => $fe->id,
        ]);
        $te->factions()->syncWithoutDetaching([(int) $faction->id]);

        $tid = $te->id;

        $this->actingAs($user)->deleteJson(route('factions.events.destroy', [$world, $faction, $fe]))->assertOk();

        $this->assertDatabaseMissing('faction_events', ['id' => $fe->id]);
        $this->assertDatabaseMissing('timeline_events', ['id' => $tid]);
    }

    public function test_deleting_timeline_event_keeps_faction_event(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);
        TimelineBootstrapService::bootstrap($world);

        $faction = Faction::query()->create([
            'world_id' => $world->id,
            'name' => 'Guild',
            'type' => FactionType::OTHER,
        ]);

        $fe = FactionEvent::query()->create([
            'faction_id' => $faction->id,
            'title' => 'Pact',
            'epoch_year' => 10,
            'year_end' => null,
            'month' => 2,
            'day' => 3,
            'body' => null,
            'breaks_line' => false,
        ]);

        $line = TimelineLine::query()->create([
            'world_id' => $world->id,
            'name' => 'Guild line',
            'start_year' => 0,
            'end_year' => null,
            'extends_to_canvas_end' => true,
            'color' => '#457B9D',
            'is_main' => false,
            'sort_order' => 1,
        ]);

        $te = TimelineEvent::query()->create([
            'timeline_line_id' => $line->id,
            'title' => 'Pact',
            'epoch_year' => 10,
            'month' => 2,
            'day' => 3,
            'breaks_line' => false,
            'source_type' => FactionEvent::class,
            'source_id' => $fe->id,
        ]);
        $te->factions()->syncWithoutDetaching([(int) $faction->id]);

        $this->actingAs($user)->from(route('worlds.timeline', $world))->delete(
            route('timeline.events.destroy', [$world, $te]),
        )->assertRedirect(route('worlds.timeline', $world));

        $this->assertDatabaseMissing('timeline_events', ['id' => $te->id]);
        $this->assertDatabaseHas('faction_events', ['id' => $fe->id, 'title' => 'Pact']);
    }
}
