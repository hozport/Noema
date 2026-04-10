<?php

namespace Tests\Feature;

use App\Models\Cards\Card;
use App\Models\Cards\Story;
use App\Models\User;
use App\Models\Worlds\ConnectionBoard;
use App\Models\Worlds\ConnectionBoardNode;
use App\Models\Worlds\World;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CardsStoryDestroyTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_delete_story_and_story_card_nodes_on_boards(): void
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
            'name' => 'История',
        ]);

        $card = Card::query()->create([
            'story_id' => $story->id,
            'title' => 'C1',
            'number' => 1,
            'content' => '',
        ]);

        $node = ConnectionBoardNode::query()->create([
            'connection_board_id' => $board->id,
            'kind' => 'story_card',
            'entity_id' => $card->id,
            'meta' => ['story_id' => $story->id],
            'x' => 1,
            'y' => 2,
        ]);

        $this->actingAs($user)
            ->delete(route('cards.stories.destroy', [$world, $story]))
            ->assertRedirect(route('cards.index', $world));

        $this->assertDatabaseMissing('stories', ['id' => $story->id]);
        $this->assertDatabaseMissing('cards', ['id' => $card->id]);
        $this->assertDatabaseMissing('connection_board_nodes', ['id' => $node->id]);
        $this->assertDatabaseHas('connection_boards', ['id' => $board->id]);
    }
}
