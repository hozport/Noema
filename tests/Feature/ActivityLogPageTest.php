<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Biography\Biography;
use App\Models\Cards\Card;
use App\Models\Cards\Story;
use App\Models\User;
use App\Models\Worlds\World;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_activity_page_loads_and_lists_entries(): void
    {
        $user = User::factory()->create();
        ActivityLog::record($user, null, 'account.profile.updated', 'Обновлён профиль.', $user);

        $response = $this->actingAs($user)->get(route('account.activity'));

        $response->assertOk();
        $response->assertSee('Обновлён профиль.', false);
    }

    public function test_world_activity_page_loads_for_owner(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'Мир W',
            'onoff' => true,
        ]);
        ActivityLog::record($user, $world, 'world.updated', 'Сохранены настройки мира.', $world);

        $response = $this->actingAs($user)->get(route('worlds.activity', $world));

        $response->assertOk();
        $response->assertSee('Сохранены настройки мира.', false);
    }

    public function test_world_activity_forbidden_for_other_user(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $owner->id,
            'name' => 'Мир W',
            'onoff' => true,
        ]);

        $this->actingAs($other)->get(route('worlds.activity', $world))->assertForbidden();
    }

    public function test_world_activity_clear_deletes_logs_for_world(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'Мир W',
            'onoff' => true,
        ]);
        ActivityLog::record($user, $world, 'world.updated', 'Событие', $world);

        $this->assertSame(1, ActivityLog::query()->where('world_id', $world->id)->count());

        $this->actingAs($user)
            ->from(route('worlds.activity', $world))
            ->delete(route('worlds.activity.clear', $world))
            ->assertRedirect(route('worlds.activity', $world));

        $this->assertSame(0, ActivityLog::query()->where('world_id', $world->id)->count());
    }

    public function test_story_activity_lists_only_that_story_and_its_cards(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'Мир W',
            'onoff' => true,
        ]);
        $storyA = Story::query()->create([
            'world_id' => $world->id,
            'name' => 'История А',
        ]);
        $storyB = Story::query()->create([
            'world_id' => $world->id,
            'name' => 'История Б',
        ]);
        $cardA = Card::query()->create([
            'story_id' => $storyA->id,
            'title' => 'К1',
            'number' => 1,
            'content' => null,
        ]);

        ActivityLog::record($user, $world, 'story.updated', 'Запись по истории А', $storyA);
        ActivityLog::record($user, $world, 'story.updated', 'Запись по истории Б', $storyB);
        ActivityLog::record($user, $world, 'card.updated', 'Запись по карточке А', $cardA);
        ActivityLog::record($user, $world, 'world.updated', 'Настройки мира', $world);

        $response = $this->actingAs($user)->get(route('cards.stories.activity', [$world, $storyA]));

        $response->assertOk();
        $response->assertSee('Запись по истории А', false);
        $response->assertSee('Запись по карточке А', false);
        $response->assertDontSee('Запись по истории Б', false);
        $response->assertDontSee('Настройки мира', false);
    }

    public function test_card_activity_lists_only_logs_where_subject_is_that_card(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'Мир W',
            'onoff' => true,
        ]);
        $story = Story::query()->create([
            'world_id' => $world->id,
            'name' => 'История',
        ]);
        $cardA = Card::query()->create([
            'story_id' => $story->id,
            'title' => 'Карточка А',
            'number' => 1,
            'content' => null,
        ]);
        $cardB = Card::query()->create([
            'story_id' => $story->id,
            'title' => 'Карточка Б',
            'number' => 2,
            'content' => null,
        ]);

        ActivityLog::record($user, $world, 'card.updated', 'Правка карточки А', $cardA);
        ActivityLog::record($user, $world, 'card.updated', 'Правка карточки Б', $cardB);
        ActivityLog::record($user, $world, 'story.updated', 'Правка истории', $story);

        $response = $this->actingAs($user)->get(route('cards.card.activity', [$world, $story, $cardA]));

        $response->assertOk();
        $response->assertSee('Правка карточки А', false);
        $response->assertDontSee('Правка карточки Б', false);
        $response->assertDontSee('Правка истории', false);
    }

    public function test_cards_module_activity_filters_logs_and_back_link_points_to_cards_index(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'Мир W',
            'onoff' => true,
        ]);
        $story = Story::query()->create([
            'world_id' => $world->id,
            'name' => 'История',
        ]);
        $card = Card::query()->create([
            'story_id' => $story->id,
            'title' => 'К1',
            'number' => 1,
            'content' => null,
        ]);

        ActivityLog::record($user, $world, 'story.updated', 'Событие истории', $story);
        ActivityLog::record($user, $world, 'card.updated', 'Событие карточки', $card);
        ActivityLog::record($user, $world, 'world.updated', 'Настройки мира', $world);

        $response = $this->actingAs($user)->get(route('cards.module.activity', $world));

        $response->assertOk();
        $response->assertSee('Событие истории', false);
        $response->assertSee('Событие карточки', false);
        $response->assertDontSee('Настройки мира', false);
        $response->assertSee(route('cards.index', $world), false);
    }

    public function test_biographies_module_activity_filters_by_biography_actions(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'Мир W',
            'onoff' => true,
        ]);
        $bio = Biography::query()->create([
            'world_id' => $world->id,
            'name' => 'Герой',
        ]);

        ActivityLog::record($user, $world, 'biography.updated', 'Правка биографии', $bio);
        ActivityLog::record($user, $world, 'world.updated', 'Настройки мира', $world);

        $response = $this->actingAs($user)->get(route('biographies.module.activity', $world));

        $response->assertOk();
        $response->assertSee('Правка биографии', false);
        $response->assertDontSee('Настройки мира', false);
        $response->assertSee(route('biographies.index', $world), false);
    }

    public function test_biography_entity_activity_lists_only_that_biography(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'Мир W',
            'onoff' => true,
        ]);
        $bioA = Biography::query()->create([
            'world_id' => $world->id,
            'name' => 'А',
        ]);
        $bioB = Biography::query()->create([
            'world_id' => $world->id,
            'name' => 'Б',
        ]);

        ActivityLog::record($user, $world, 'biography.updated', 'Событие А', $bioA);
        ActivityLog::record($user, $world, 'biography.updated', 'Событие Б', $bioB);

        $response = $this->actingAs($user)->get(route('biography.activity', [$world, $bioA]));

        $response->assertOk();
        $response->assertSee('Событие А', false);
        $response->assertDontSee('Событие Б', false);
    }
}
