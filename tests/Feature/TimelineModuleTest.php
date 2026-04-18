<?php

namespace Tests\Feature;

use App\Models\Timeline\TimelineEvent;
use App\Models\Timeline\TimelineLine;
use App\Models\User;
use App\Models\Worlds\World;
use App\Services\TimelineBootstrapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimelineModuleTest extends TestCase
{
    use RefreshDatabase;

    private function createWorldWithTimeline(User $user): World
    {
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);
        TimelineBootstrapService::bootstrap($world);

        return $world;
    }

    public function test_owner_can_open_timeline_page(): void
    {
        $user = User::factory()->create();
        $world = $this->createWorldWithTimeline($user);

        $this->actingAs($user)->get(route('worlds.timeline', $world))->assertOk();
    }

    public function test_owner_can_download_timeline_pdf(): void
    {
        $user = User::factory()->create();
        $world = $this->createWorldWithTimeline($user);

        $response = $this->actingAs($user)->get(route('worlds.timeline.pdf', $world));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent() ?: '');
    }

    public function test_non_owner_cannot_download_timeline_pdf(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $world = $this->createWorldWithTimeline($owner);

        $this->actingAs($other)->get(route('worlds.timeline.pdf', $world))->assertForbidden();
    }

    public function test_non_owner_cannot_open_timeline(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $world = $this->createWorldWithTimeline($owner);

        $this->actingAs($other)->get(route('worlds.timeline', $world))->assertForbidden();
    }

    public function test_store_line_creates_secondary_line(): void
    {
        $user = User::factory()->create();
        $world = $this->createWorldWithTimeline($user);

        $this->actingAs($user)->post(route('timeline.lines.store', $world), [
            'name' => 'Вторая',
            'start_year' => 0,
            'end_year' => null,
            'color' => '#112233',
        ])->assertRedirect(route('worlds.timeline', $world));

        $this->assertDatabaseHas('timeline_lines', [
            'world_id' => $world->id,
            'name' => 'Вторая',
            'is_main' => false,
        ]);
    }

    public function test_store_event_on_line_and_update_and_delete(): void
    {
        $user = User::factory()->create();
        $world = $this->createWorldWithTimeline($user);

        $line = TimelineLine::query()->create([
            'world_id' => $world->id,
            'name' => 'Side',
            'start_year' => 0,
            'end_year' => null,
            'extends_to_canvas_end' => true,
            'color' => '#445566',
            'is_main' => false,
            'sort_order' => 1,
        ]);

        $this->actingAs($user)->post(route('timeline.events.store', $world), [
            'timeline_line_id' => $line->id,
            'title' => 'Событие',
            'epoch_year' => 42,
            'month' => 3,
            'day' => 15,
            'breaks_line' => false,
        ])->assertRedirect(route('worlds.timeline', $world));

        $event = TimelineEvent::query()->where('timeline_line_id', $line->id)->first();
        $this->assertNotNull($event);
        $this->assertSame('Событие', $event->title);

        $this->actingAs($user)->put(route('timeline.events.update', [$world, $event]), [
            'title' => 'Обновлено',
            'epoch_year' => 43,
            'month' => 4,
            'day' => 16,
            'breaks_line' => false,
        ])->assertRedirect(route('worlds.timeline', $world));

        $event->refresh();
        $this->assertSame('Обновлено', $event->title);
        $this->assertSame(43, $event->epoch_year);

        $this->actingAs($user)->from(route('worlds.timeline', $world))->delete(
            route('timeline.events.destroy', [$world, $event]),
        )->assertRedirect(route('worlds.timeline', $world));

        $this->assertDatabaseMissing('timeline_events', ['id' => $event->id]);
    }

    public function test_destroy_secondary_line(): void
    {
        $user = User::factory()->create();
        $world = $this->createWorldWithTimeline($user);

        $line = TimelineLine::query()->create([
            'world_id' => $world->id,
            'name' => 'Удалить',
            'start_year' => 0,
            'end_year' => null,
            'extends_to_canvas_end' => true,
            'color' => '#778899',
            'is_main' => false,
            'sort_order' => 1,
        ]);

        $this->actingAs($user)->from(route('worlds.timeline', $world))->delete(
            route('timeline.lines.destroy', [$world, $line]),
        )->assertRedirect(route('worlds.timeline', $world));

        $this->assertDatabaseMissing('timeline_lines', ['id' => $line->id]);
    }

    public function test_clear_timeline_removes_secondary_lines_and_non_reference_events(): void
    {
        $user = User::factory()->create();
        $world = $this->createWorldWithTimeline($user);

        $secondary = TimelineLine::query()->create([
            'world_id' => $world->id,
            'name' => 'Extra',
            'start_year' => 0,
            'end_year' => null,
            'extends_to_canvas_end' => true,
            'color' => '#AABBCC',
            'is_main' => false,
            'sort_order' => 1,
        ]);

        $main = TimelineLine::query()->where('world_id', $world->id)->where('is_main', true)->firstOrFail();
        TimelineEvent::query()->create([
            'timeline_line_id' => $main->id,
            'title' => 'Временное',
            'epoch_year' => 5,
            'month' => 1,
            'day' => 1,
            'breaks_line' => false,
            'is_reference_marker' => false,
        ]);

        $this->actingAs($user)->post(route('worlds.timeline.clear', $world))->assertRedirect(route('worlds.timeline', $world));

        $this->assertDatabaseMissing('timeline_lines', ['id' => $secondary->id]);
        $this->assertDatabaseMissing('timeline_events', ['title' => 'Временное']);
        $this->assertDatabaseHas('timeline_lines', ['id' => $main->id, 'is_main' => true]);
    }

    public function test_move_line_swaps_sort_order(): void
    {
        $user = User::factory()->create();
        $world = $this->createWorldWithTimeline($user);

        $a = TimelineLine::query()->create([
            'world_id' => $world->id,
            'name' => 'A',
            'start_year' => 0,
            'end_year' => null,
            'extends_to_canvas_end' => true,
            'color' => '#111111',
            'is_main' => false,
            'sort_order' => 1,
        ]);
        $b = TimelineLine::query()->create([
            'world_id' => $world->id,
            'name' => 'B',
            'start_year' => 0,
            'end_year' => null,
            'extends_to_canvas_end' => true,
            'color' => '#222222',
            'is_main' => false,
            'sort_order' => 2,
        ]);

        $sortA = (int) $a->sort_order;
        $sortB = (int) $b->sort_order;

        $this->actingAs($user)
            ->postJson(route('timeline.lines.move', [$world, $b]), ['direction' => 'up'])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $a->refresh();
        $b->refresh();
        $this->assertSame($sortB, $a->sort_order);
        $this->assertSame($sortA, $b->sort_order);
    }

    public function test_non_owner_cannot_clear_timeline(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $world = $this->createWorldWithTimeline($owner);

        $this->actingAs($other)->post(route('worlds.timeline.clear', $world))->assertForbidden();
    }
}
