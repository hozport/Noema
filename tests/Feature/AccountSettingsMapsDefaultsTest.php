<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Worlds\World;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountSettingsMapsDefaultsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_account_settings_page(): void
    {
        $this->get(route('account.settings'))->assertRedirect();
    }

    public function test_authenticated_user_can_open_account_settings_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('account.settings'))
            ->assertOk()
            ->assertSee('Настройки', false)
            ->assertSee('Размеры по умолчанию', false)
            ->assertSee('Последовательность', false);
    }

    public function test_guest_cannot_update_account_maps_defaults(): void
    {
        $this->put(route('account.settings.maps-defaults.update'), [
            'maps_default_width' => 2200,
            'maps_default_height' => 2100,
        ])->assertRedirect();
    }

    public function test_user_can_update_account_maps_defaults(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('account.settings.maps-defaults.update'), [
            'maps_default_width' => 2200,
            'maps_default_height' => 2100,
        ])->assertRedirect(route('account.settings'));

        $user->refresh();
        $this->assertSame(2200, $user->maps_default_width);
        $this->assertSame(2100, $user->maps_default_height);
    }

    public function test_new_world_inherits_user_maps_defaults(): void
    {
        $user = User::factory()->create([
            'maps_default_width' => 2400,
            'maps_default_height' => 2300,
        ]);

        $this->actingAs($user)->post(route('worlds.store'), [
            'name' => 'Test World',
        ])->assertRedirect();

        $world = World::query()->where('name', 'Test World')->first();
        $this->assertNotNull($world);
        $this->assertSame(2400, $world->maps_default_width);
        $this->assertSame(2300, $world->maps_default_height);
    }

    public function test_worlds_list_sort_can_be_saved_from_account_settings(): void
    {
        $user = User::factory()->create([
            'worlds_list_sort' => User::WORLDS_SORT_ALPHABET,
        ]);

        $this->actingAs($user)
            ->from(route('account.settings'))
            ->put(route('account.worlds-display.update'), [
                'worlds_list_sort' => User::WORLDS_SORT_CREATED_AT,
            ])
            ->assertRedirect(route('account.settings'));

        $user->refresh();
        $this->assertSame(User::WORLDS_SORT_CREATED_AT, $user->worlds_list_sort);
    }

    public function test_worlds_list_sort_save_from_worlds_index_redirects_back(): void
    {
        $user = User::factory()->create([
            'worlds_list_sort' => User::WORLDS_SORT_ALPHABET,
        ]);

        $this->actingAs($user)
            ->from(route('worlds.index'))
            ->put(route('account.worlds-display.update'), [
                'worlds_list_sort' => User::WORLDS_SORT_UPDATED_AT,
            ])
            ->assertRedirect(route('worlds.index'));

        $user->refresh();
        $this->assertSame(User::WORLDS_SORT_UPDATED_AT, $user->worlds_list_sort);
    }
}
