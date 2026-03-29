<?php

namespace Tests\Feature;

use App\Models\Cards\Card;
use App\Models\Cards\Story;
use App\Models\Timeline\TimelineEvent;
use App\Models\Timeline\TimelineLine;
use App\Models\User;
use App\Models\Worlds\ConnectionBoard;
use App\Models\Worlds\World;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConnectionsBoardApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_timeline_lines_json_requires_auth(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);

        $this->getJson(route('worlds.connections.data.timeline-lines', $world))
            ->assertUnauthorized();
    }

    public function test_timeline_lines_returns_lines_for_owner(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);

        TimelineLine::query()->create([
            'world_id' => $world->id,
            'name' => 'Main',
            'start_year' => 0,
            'end_year' => null,
            'extends_to_canvas_end' => true,
            'color' => '#000000',
            'is_main' => true,
            'sort_order' => 0,
        ]);

        $response = $this->actingAs($user)->getJson(route('worlds.connections.data.timeline-lines', $world));

        $response->assertOk();
        $response->assertJsonPath('lines.0.name', 'Main');
    }

    public function test_post_story_card_node_persists(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);

        $board = ConnectionBoard::query()->create([
            'world_id' => $world->id,
            'name' => 'Доска',
        ]);

        $story = Story::query()->create([
            'world_id' => $world->id,
            'name' => 'S',
        ]);

        $card = Card::query()->create([
            'story_id' => $story->id,
            'title' => 'C1',
            'number' => 1,
            'content' => '',
        ]);

        $response = $this->actingAs($user)->postJson(route('worlds.connections.nodes.store', [$world, $board]), [
            'kind' => 'story_card',
            'entity_id' => $card->id,
            'meta' => ['story_id' => $story->id],
            'x' => 10,
            'y' => 20,
        ]);

        $response->assertOk();
        $response->assertJsonPath('node.kind', 'story_card');
        $response->assertJsonPath('node.entity_id', $card->id);
        $this->assertDatabaseHas('connection_board_nodes', [
            'connection_board_id' => $board->id,
            'kind' => 'story_card',
            'entity_id' => $card->id,
            'x' => 10,
            'y' => 20,
        ]);
    }

    public function test_post_timeline_event_node_persists(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);

        $board = ConnectionBoard::query()->create([
            'world_id' => $world->id,
            'name' => 'Доска',
        ]);

        $line = TimelineLine::query()->create([
            'world_id' => $world->id,
            'name' => 'L',
            'start_year' => 0,
            'end_year' => null,
            'extends_to_canvas_end' => true,
            'color' => '#000000',
            'is_main' => false,
            'sort_order' => 0,
        ]);

        $event = TimelineEvent::query()->create([
            'timeline_line_id' => $line->id,
            'title' => 'E',
            'epoch_year' => 1,
            'month' => 1,
            'day' => 1,
            'breaks_line' => false,
            'source_type' => null,
            'source_id' => null,
        ]);

        $response = $this->actingAs($user)->postJson(route('worlds.connections.nodes.store', [$world, $board]), [
            'kind' => 'timeline_event',
            'entity_id' => $event->id,
            'meta' => null,
            'x' => 5,
            'y' => 6,
        ]);

        $response->assertOk();
        $response->assertJsonPath('node.kind', 'timeline_event');
        $this->assertDatabaseHas('connection_board_nodes', [
            'connection_board_id' => $board->id,
            'kind' => 'timeline_event',
            'entity_id' => $event->id,
        ]);
    }
}
