<?php

namespace Tests\Feature;

use App\Models\Bestiary\Creature;
use App\Models\Biography\Biography;
use App\Models\Faction\Faction;
use App\Models\User;
use App\Models\Worlds\World;
use App\Support\FactionType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EncyclopediaSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_download_factions_list_pdf(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);
        Faction::query()->create([
            'world_id' => $world->id,
            'name' => 'Союз',
            'type' => FactionType::ALLIANCE,
            'type_custom' => null,
            'short_description' => null,
            'full_description' => null,
            'geographic_stub' => null,
        ]);

        $this->actingAs($user);

        $response = $this->get(route('factions.index.pdf', $world));
        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_owner_can_download_single_faction_pdf(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);
        $faction = Faction::query()->create([
            'world_id' => $world->id,
            'name' => 'Гильдия',
            'type' => FactionType::ORGANIZATION,
            'type_custom' => null,
            'short_description' => null,
            'full_description' => null,
            'geographic_stub' => null,
        ]);

        $this->actingAs($user);

        $response = $this->get(route('factions.pdf', [$world, $faction]));
        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_owner_can_delete_biography(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);
        $bio = Biography::query()->create([
            'world_id' => $world->id,
            'name' => 'Герой',
            'race_faction_id' => null,
            'people_faction_id' => null,
            'country_faction_id' => null,
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

        $this->actingAs($user);

        $this->delete(route('biographies.destroy', [$world, $bio]))
            ->assertRedirect(route('biographies.index', $world));

        $this->assertDatabaseMissing('biographies', ['id' => $bio->id]);
    }

    public function test_owner_can_delete_creature(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);
        $creature = Creature::query()->create([
            'world_id' => $world->id,
            'name' => 'Зверь',
            'scientific_name' => null,
            'species_kind' => null,
            'image_path' => null,
            'height_text' => null,
            'weight_text' => null,
            'lifespan_text' => null,
            'short_description' => null,
            'full_description' => null,
            'habitat_text' => null,
            'food_custom' => null,
        ]);

        $this->actingAs($user);

        $this->delete(route('bestiary.creatures.destroy', [$world, $creature]))
            ->assertRedirect(route('bestiary.index', $world));

        $this->assertDatabaseMissing('creatures', ['id' => $creature->id]);
    }
}
