<?php

namespace Tests\Feature;

use App\Models\Biography\Biography;
use App\Models\Faction\Faction;
use App\Models\User;
use App\Models\Worlds\World;
use App\Support\FactionType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FactionsModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_factions_index_requires_auth(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);

        $this->get(route('factions.index', $world))->assertRedirect();
    }

    public function test_owner_can_create_faction_and_see_show(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);

        $this->actingAs($user);

        $this->post(route('factions.store', $world), [
            'name' => 'Северный союз',
            'type' => FactionType::ALLIANCE,
            'short_description' => 'Кратко',
            'full_description' => 'Полностью',
            'geographic_stub' => 'Пока карты нет',
            'member_ids' => [],
            'related_ids' => [],
            'enemy_ids' => [],
        ])->assertRedirect();

        $faction = Faction::query()->where('world_id', $world->id)->first();
        $this->assertNotNull($faction);
        $this->assertSame('Северный союз', $faction->name);

        $this->get(route('factions.show', [$world, $faction]))->assertOk()->assertSee('Северный союз', false);
    }

    public function test_biography_race_other_creates_race_faction(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);

        $this->actingAs($user);

        $this->post(route('biographies.store', $world), [
            'name' => 'Герой',
            'race_faction_id' => 'other',
            'race_other_name' => 'Кастомная раса',
            'gender' => null,
            'short_description' => null,
            'full_description' => null,
            'relative_ids' => [],
            'friend_ids' => [],
            'enemy_ids' => [],
            'faction_membership_ids' => [],
        ])->assertRedirect();

        $faction = Faction::query()->where('world_id', $world->id)->where('type', FactionType::RACE)->first();
        $this->assertNotNull($faction);
        $this->assertSame('Кастомная раса', $faction->name);
    }

    public function test_faction_country_members_overwrites_biography_country_and_clears_previous_pivot(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);

        $countryA = Faction::query()->create([
            'world_id' => $world->id,
            'name' => 'Рвачей',
            'type' => FactionType::COUNTRY,
            'type_custom' => null,
            'short_description' => null,
            'full_description' => null,
            'geographic_stub' => null,
        ]);
        $countryB = Faction::query()->create([
            'world_id' => $world->id,
            'name' => 'Жым',
            'type' => FactionType::COUNTRY,
            'type_custom' => null,
            'short_description' => null,
            'full_description' => null,
            'geographic_stub' => null,
        ]);

        $bio = Biography::query()->create([
            'world_id' => $world->id,
            'name' => 'Герой',
            'country_faction_id' => $countryA->id,
            'race_faction_id' => null,
            'people_faction_id' => null,
            'gender' => null,
            'birth_year' => null,
            'birth_month' => null,
            'birth_day' => null,
            'death_year' => null,
            'death_month' => null,
            'death_day' => null,
            'short_description' => null,
            'full_description' => null,
        ]);
        $bio->membershipFactions()->sync([$countryA->id]);

        $this->actingAs($user);

        $this->put(route('factions.update', [$world, $countryB]), [
            'name' => 'Жым',
            'type' => FactionType::COUNTRY,
            'short_description' => null,
            'full_description' => null,
            'geographic_stub' => null,
            'member_ids' => [$bio->id],
            'related_ids' => [],
            'enemy_ids' => [],
        ])->assertRedirect();

        $bio->refresh();
        $this->assertSame((int) $countryB->id, (int) $bio->country_faction_id);
        $this->assertTrue($bio->membershipFactions()->where('factions.id', $countryB->id)->exists());
        $this->assertFalse($bio->membershipFactions()->where('factions.id', $countryA->id)->exists());
    }

    public function test_faction_type_change_migrates_biography_fk_to_new_dedicated_column(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);

        $faction = Faction::query()->create([
            'world_id' => $world->id,
            'name' => 'Край',
            'type' => FactionType::COUNTRY,
            'type_custom' => null,
            'short_description' => null,
            'full_description' => null,
            'geographic_stub' => null,
        ]);

        $bio = Biography::query()->create([
            'world_id' => $world->id,
            'name' => 'Персонаж',
            'country_faction_id' => $faction->id,
            'race_faction_id' => null,
            'people_faction_id' => null,
            'gender' => null,
            'birth_year' => null,
            'birth_month' => null,
            'birth_day' => null,
            'death_year' => null,
            'death_month' => null,
            'death_day' => null,
            'short_description' => null,
            'full_description' => null,
        ]);
        $bio->membershipFactions()->sync([$faction->id]);

        $this->actingAs($user);

        $response = $this->put(route('factions.update', [$world, $faction]), [
            'name' => 'Край',
            'type' => FactionType::RACE,
            'short_description' => null,
            'full_description' => null,
            'geographic_stub' => null,
            'member_ids' => [$bio->id],
            'related_ids' => [],
            'enemy_ids' => [],
        ]);
        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $bio->refresh();
        $this->assertNull($bio->country_faction_id);
        $this->assertSame((int) $faction->id, (int) $bio->race_faction_id);
    }

    public function test_faction_type_change_migration_runs_on_model_save_without_controller(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);

        $faction = Faction::query()->create([
            'world_id' => $world->id,
            'name' => 'Край',
            'type' => FactionType::COUNTRY,
            'type_custom' => null,
            'short_description' => null,
            'full_description' => null,
            'geographic_stub' => null,
        ]);

        $bio = Biography::query()->create([
            'world_id' => $world->id,
            'name' => 'Персонаж',
            'country_faction_id' => $faction->id,
            'race_faction_id' => null,
            'people_faction_id' => null,
            'gender' => null,
            'birth_year' => null,
            'birth_month' => null,
            'birth_day' => null,
            'death_year' => null,
            'death_month' => null,
            'death_day' => null,
            'short_description' => null,
            'full_description' => null,
        ]);
        $bio->membershipFactions()->sync([$faction->id]);

        $faction->type = FactionType::RACE;
        $faction->save();

        $bio->refresh();
        $this->assertNull($bio->country_faction_id);
        $this->assertSame((int) $faction->id, (int) $bio->race_faction_id);
    }
}
