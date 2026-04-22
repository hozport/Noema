<?php

namespace Tests\Feature;

use App\Models\Cards\Card;
use App\Models\Cards\Story;
use App\Models\User;
use App\Models\Worlds\World;
use App\Models\Worlds\WorldMap;
use App\Models\Worlds\WorldMapSprite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Покрытие HTTP-слоя модуля «Карточки»: список историй, CRUD истории, PDF, порядок карточек,
 * правка/подсветка/удаление/декомпозиция карточек.
 */
class CardsModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_cards_index(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);

        $this->get(route('cards.index', $world))->assertRedirect();
    }

    public function test_cards_index_forbidden_for_non_owner(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $owner->id,
            'name' => 'W',
            'onoff' => true,
        ]);

        $this->actingAs($other)->get(route('cards.index', $world))->assertForbidden();
    }

    public function test_cards_index_not_found_when_world_hidden(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => false,
        ]);

        $this->actingAs($user)->get(route('cards.index', $world))->assertNotFound();
    }

    public function test_cards_index_shows_stories_and_respects_cycle_filter(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);
        Story::query()->create([
            'world_id' => $world->id,
            'name' => 'Цикл А',
            'cycle' => 'main',
        ]);
        Story::query()->create([
            'world_id' => $world->id,
            'name' => 'Другой',
            'cycle' => 'side',
        ]);

        $this->actingAs($user)
            ->get(route('cards.index', ['world' => $world, 'cycle' => 'main']))
            ->assertOk()
            ->assertSee('Цикл А', false)
            ->assertDontSee('Другой', false);
    }

    public function test_story_store_creates_history_with_four_seed_cards_and_logs(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);

        $response = $this->actingAs($user)->post(route('cards.stories.store', $world), [
            'name' => 'Новая арка',
            'cycle' => ' I ',
            'synopsis' => ' Описание ',
        ]);

        $story = Story::query()->where('world_id', $world->id)->first();
        $this->assertNotNull($story);
        $response->assertRedirect(route('cards.show', [$world, $story]));

        $this->assertSame('Новая арка', $story->name);
        $this->assertSame('I', $story->cycle);
        $this->assertSame('Описание', $story->synopsis);
        $this->assertSame(4, $story->cards()->count());
        $titles = $story->cards()->orderBy('number')->pluck('title')->all();
        $this->assertSame(['Вступление', 'Развитие', 'Кульминация', 'Развязка'], $titles);

        $this->assertDatabaseHas('activity_logs', [
            'world_id' => $world->id,
            'action' => 'story.created',
        ]);
    }

    public function test_story_store_validation_requires_name(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);

        $this->actingAs($user)
            ->post(route('cards.stories.store', $world), [])
            ->assertSessionHasErrors('name');
    }

    public function test_story_show_not_found_when_story_belongs_to_another_world(): void
    {
        $user = User::factory()->create();
        $worldA = World::query()->create([
            'user_id' => $user->id,
            'name' => 'A',
            'onoff' => true,
        ]);
        $worldB = World::query()->create([
            'user_id' => $user->id,
            'name' => 'B',
            'onoff' => true,
        ]);
        $story = Story::query()->create([
            'world_id' => $worldA->id,
            'name' => 'История',
        ]);

        $this->actingAs($user)
            ->get(route('cards.show', [$worldB, $story]))
            ->assertNotFound();
    }

    public function test_story_update_saves_fields_and_writes_activity_log(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);
        $story = Story::query()->create([
            'world_id' => $world->id,
            'name' => 'Имя',
            'card_display_mode' => Story::CARD_DISPLAY_MODAL,
        ]);

        $this->actingAs($user)
            ->put(route('cards.stories.update', [$world, $story]), [
                'name' => 'Новое имя',
                'cycle' => '',
                'synopsis' => '',
                'card_display_mode' => Story::CARD_DISPLAY_PAGE,
            ])
            ->assertRedirect(route('cards.show', [$world, $story]))
            ->assertSessionHas('success');

        $story->refresh();
        $this->assertSame('Новое имя', $story->name);
        $this->assertNull($story->cycle);
        $this->assertSame(Story::CARD_DISPLAY_PAGE, $story->card_display_mode);

        $this->assertDatabaseHas('activity_logs', [
            'world_id' => $world->id,
            'action' => 'story.updated',
        ]);
    }

    public function test_story_update_rejects_invalid_card_display_mode(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);
        $story = Story::query()->create([
            'world_id' => $world->id,
            'name' => 'Имя',
        ]);

        $this->actingAs($user)
            ->put(route('cards.stories.update', [$world, $story]), [
                'name' => 'Имя',
                'card_display_mode' => 'invalid',
            ])
            ->assertSessionHasErrors('card_display_mode');
    }

    public function test_story_pdf_returns_pdf_attachment(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);
        $story = Story::query()->create([
            'world_id' => $world->id,
            'name' => 'PDF История',
        ]);
        Card::query()->create([
            'story_id' => $story->id,
            'title' => 'К1',
            'number' => 1,
            'content' => 'Текст',
        ]);

        $response = $this->actingAs($user)->get(route('cards.stories.pdf', [$world, $story]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition') ?? '');
    }

    public function test_reorder_updates_card_numbers_when_order_is_complete_permutation(): void
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
        $c1 = Card::query()->create(['story_id' => $story->id, 'title' => 'a', 'number' => 1, 'content' => null]);
        $c2 = Card::query()->create(['story_id' => $story->id, 'title' => 'b', 'number' => 2, 'content' => null]);

        $this->actingAs($user)
            ->postJson(route('cards.reorder', [$world, $story]), [
                'order' => [$c2->id, $c1->id],
            ])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $c1->refresh();
        $c2->refresh();
        $this->assertSame(2, $c1->number);
        $this->assertSame(1, $c2->number);

        $this->assertDatabaseHas('activity_logs', [
            'world_id' => $world->id,
            'action' => 'cards.reordered',
        ]);
    }

    public function test_reorder_returns_422_when_order_set_mismatch(): void
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
        $c1 = Card::query()->create(['story_id' => $story->id, 'title' => 'a', 'number' => 1, 'content' => null]);
        Card::query()->create(['story_id' => $story->id, 'title' => 'b', 'number' => 2, 'content' => null]);

        $this->actingAs($user)
            ->postJson(route('cards.reorder', [$world, $story]), [
                'order' => [$c1->id],
            ])
            ->assertStatus(422);
    }

    public function test_card_update_json_returns_ok_and_title(): void
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
        $card = Card::query()->create([
            'story_id' => $story->id,
            'title' => 'Old',
            'number' => 1,
            'content' => null,
        ]);

        $this->actingAs($user)
            ->putJson(route('cards.update', [$world, $card]), [
                'title' => 'Новый',
                'content' => 'Строка',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('title', 'Новый');
    }

    public function test_card_update_json_returns_422_on_invalid_noema_markup(): void
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
        $card = Card::query()->create([
            'story_id' => $story->id,
            'title' => 'K',
            'number' => 1,
            'content' => null,
        ]);

        $this->actingAs($user)
            ->putJson(route('cards.update', [$world, $card]), [
                'content' => "[b]a\nb[/b]",
            ])
            ->assertStatus(422)
            ->assertJsonStructure(['message', 'errors' => ['content']]);
    }

    public function test_card_update_json_returns_422_when_map_object_link_has_no_valid_title(): void
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
        $card = Card::query()->create([
            'story_id' => $story->id,
            'title' => 'K',
            'number' => 1,
            'content' => null,
        ]);
        $wm = WorldMap::query()->create([
            'world_id' => $world->id,
            'title' => 'Карта',
            'width' => 3000,
            'height' => 3000,
            'map_drawing_lines' => [],
            'map_fill_path' => null,
        ]);
        $sprite = WorldMapSprite::query()->create([
            'world_map_id' => $wm->id,
            'sprite_path' => 'Поселения/x.svg',
            'pos_x' => 0,
            'pos_y' => 0,
            'title' => null,
        ]);

        $this->actingAs($user)
            ->putJson(route('cards.update', [$world, $card]), [
                'content' => '[link module=1 entity='.$sprite->id.']текст[/link]',
            ])
            ->assertStatus(422)
            ->assertJsonStructure(['message', 'errors' => ['content']]);
    }

    public function test_card_decompose_fails_with_flash_error_when_less_than_two_paragraphs(): void
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
        $card = Card::query()->create([
            'story_id' => $story->id,
            'title' => null,
            'number' => 1,
            'content' => 'Один абзац',
        ]);

        $this->actingAs($user)
            ->from(route('cards.show', [$world, $story]))
            ->post(route('cards.decompose', [$world, $card]))
            ->assertRedirect(route('cards.show', [$world, $story]))
            ->assertSessionHas('error');
    }

    public function test_decompose_all_redirects_back_with_error_when_no_splittable_cards(): void
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
            'number' => 1,
            'content' => 'Один',
        ]);

        $this->actingAs($user)
            ->from(route('cards.show', [$world, $story]))
            ->post(route('cards.stories.decompose-all', [$world, $story]))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_decompose_with_redirect_to_story_goes_to_story_show(): void
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
        $card = Card::query()->create([
            'story_id' => $story->id,
            'title' => null,
            'number' => 1,
            'content' => "A\n\nB",
        ]);

        $this->actingAs($user)
            ->post(route('cards.decompose', [$world, $card]), [
                'redirect_to_story' => '1',
            ])
            ->assertRedirect(route('cards.show', [$world, $story]))
            ->assertSessionHas('success');

        $this->assertSame(2, $story->cards()->count());
    }

    public function test_highlight_json_returns_highlighted_card_id(): void
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
        $c1 = Card::query()->create([
            'story_id' => $story->id,
            'title' => '1',
            'number' => 1,
            'content' => null,
            'is_highlighted' => false,
        ]);
        $c2 = Card::query()->create([
            'story_id' => $story->id,
            'title' => '2',
            'number' => 2,
            'content' => null,
            'is_highlighted' => false,
        ]);

        $this->actingAs($user)
            ->postJson(route('cards.highlight', [$world, $c2]), [])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('highlighted_card_id', $c2->id);

        $c1->refresh();
        $c2->refresh();
        $this->assertFalse($c1->is_highlighted);
        $this->assertTrue($c2->is_highlighted);

        $this->actingAs($user)
            ->postJson(route('cards.highlight', [$world, $c2]), [])
            ->assertOk()
            ->assertJsonPath('highlighted_card_id', null);
    }

    public function test_bulk_create_validation_rejects_count_out_of_range(): void
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

        $this->actingAs($user)
            ->post(route('cards.stories.cards.bulk-create', [$world, $story]), [
                'count' => 0,
            ])
            ->assertSessionHasErrors('count');

        $this->actingAs($user)
            ->post(route('cards.stories.cards.bulk-create', [$world, $story]), [
                'count' => 101,
            ])
            ->assertSessionHasErrors('count');
    }

    public function test_card_destroy_without_redirect_redirects_back(): void
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
            'card_display_mode' => Story::CARD_DISPLAY_PAGE,
        ]);
        $card = Card::query()->create([
            'story_id' => $story->id,
            'title' => 'К1',
            'number' => 1,
            'content' => '',
        ]);

        $this->actingAs($user)
            ->from(route('cards.card.edit', [$world, $story, $card]))
            ->delete(route('cards.destroy', [$world, $card]))
            ->assertRedirect(route('cards.card.edit', [$world, $story, $card]))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('cards', ['id' => $card->id]);
    }
}
