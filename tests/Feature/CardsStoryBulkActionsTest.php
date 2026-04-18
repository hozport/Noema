<?php

namespace Tests\Feature;

use App\Models\Cards\Card;
use App\Models\Cards\Story;
use App\Models\User;
use App\Models\Worlds\World;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CardsStoryBulkActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_create_adds_empty_cards_at_end(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);
        $story = Story::query()->create([
            'world_id' => $world->id,
            'name' => 'S',
        ]);
        Card::query()->create([
            'story_id' => $story->id,
            'title' => null,
            'content' => 'a',
            'number' => 1,
        ]);

        $this->actingAs($user)->post(route('cards.stories.cards.bulk-create', [$world, $story]), [
            'count' => 3,
        ])->assertRedirect(route('cards.show', [$world, $story]));

        $this->assertSame(4, $story->cards()->count());
        $numbers = $story->cards()->orderBy('number')->pluck('number')->all();
        $this->assertSame([1, 2, 3, 4], $numbers);
        $newCards = $story->cards()->where('number', '>', 1)->orderBy('number')->get();
        $this->assertCount(3, $newCards);
        foreach ($newCards as $c) {
            $this->assertNull($c->content);
        }
    }

    public function test_decompose_all_splits_multi_paragraph_cards(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);
        $story = Story::query()->create([
            'world_id' => $world->id,
            'name' => 'S',
        ]);
        Card::query()->create([
            'story_id' => $story->id,
            'title' => null,
            'content' => "One\n\nTwo",
            'number' => 1,
        ]);

        $this->actingAs($user)->post(route('cards.stories.decompose-all', [$world, $story]))
            ->assertRedirect(route('cards.show', [$world, $story]));

        $story->refresh();
        $this->assertSame(2, $story->cards()->count());
    }
}
