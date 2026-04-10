<?php

namespace Tests\Feature;

use App\Models\Cards\Card;
use App\Models\Cards\Story;
use App\Models\User;
use App\Models\Worlds\World;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CardsCardEditPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_card_edit_page_ok_when_story_uses_page_display_mode(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);
        $story = Story::query()->create([
            'world_id' => $world->id,
            'name' => 'История',
            'card_display_mode' => Story::CARD_DISPLAY_PAGE,
        ]);
        $card = Card::query()->create([
            'story_id' => $story->id,
            'title' => 'К1',
            'number' => 1,
            'content' => 'Текст',
        ]);

        $this->actingAs($user)
            ->get(route('cards.card.edit', [$world, $story, $card]))
            ->assertOk()
            ->assertSee('К1', false)
            ->assertSee('Текст', false);
    }

    public function test_card_edit_redirects_to_story_when_display_mode_is_modal(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);
        $story = Story::query()->create([
            'world_id' => $world->id,
            'name' => 'История',
            'card_display_mode' => Story::CARD_DISPLAY_MODAL,
        ]);
        $card = Card::query()->create([
            'story_id' => $story->id,
            'title' => 'К1',
            'number' => 1,
            'content' => '',
        ]);

        $this->actingAs($user)
            ->get(route('cards.card.edit', [$world, $story, $card]))
            ->assertRedirect(route('cards.show', [$world, $story]));
    }

    public function test_card_edit_returns_404_when_card_belongs_to_another_story(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);
        $storyA = Story::query()->create([
            'world_id' => $world->id,
            'name' => 'A',
            'card_display_mode' => Story::CARD_DISPLAY_PAGE,
        ]);
        $storyB = Story::query()->create([
            'world_id' => $world->id,
            'name' => 'B',
            'card_display_mode' => Story::CARD_DISPLAY_PAGE,
        ]);
        $card = Card::query()->create([
            'story_id' => $storyB->id,
            'title' => 'X',
            'number' => 1,
            'content' => '',
        ]);

        $this->actingAs($user)
            ->get(route('cards.card.edit', [$world, $storyA, $card]))
            ->assertNotFound();
    }

    public function test_card_update_from_edit_page_redirects_to_edit_with_success_and_saves_pin(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);
        $story = Story::query()->create([
            'world_id' => $world->id,
            'name' => 'История',
            'card_display_mode' => Story::CARD_DISPLAY_PAGE,
        ]);
        $card = Card::query()->create([
            'story_id' => $story->id,
            'title' => 'К1',
            'number' => 1,
            'content' => 'Параграф',
            'is_highlighted' => false,
        ]);

        $this->actingAs($user)
            ->put(route('cards.update', [$world, $card]), [
                'title' => 'К1',
                'content' => 'Параграф',
                'is_highlighted' => '1',
            ])
            ->assertRedirect(route('cards.card.edit', [$world, $story, $card]))
            ->assertSessionHas('success', 'Карточка сохранена.');

        $card->refresh();
        $this->assertTrue($card->is_highlighted);
    }

    public function test_card_delete_with_redirect_to_story_goes_to_story_show(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);
        $story = Story::query()->create([
            'world_id' => $world->id,
            'name' => 'История',
            'card_display_mode' => Story::CARD_DISPLAY_PAGE,
        ]);
        $card = Card::query()->create([
            'story_id' => $story->id,
            'title' => 'К1',
            'number' => 1,
            'content' => '',
        ]);

        $this->actingAs($user)
            ->delete(route('cards.destroy', [$world, $card]), [
                'redirect_to_story' => '1',
            ])
            ->assertRedirect(route('cards.show', [$world, $story]));

        $this->assertDatabaseMissing('cards', ['id' => $card->id]);
    }
}
