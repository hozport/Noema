<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Worlds\World;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimelineWorldReferenceUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_update_reference_point_from_timeline_route(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'Test world',
            'onoff' => true,
            'reference_point' => null,
        ]);

        $response = $this->actingAs($user)->put(route('worlds.timeline.world-reference.update', $world), [
            'reference_point' => 'Падение звезды',
        ]);

        $response->assertRedirect(route('worlds.timeline', $world));
        $response->assertSessionHas('success');

        $world->refresh();
        $this->assertSame('Падение звезды', $world->reference_point);
    }

    public function test_non_owner_cannot_update_reference_point(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $owner->id,
            'name' => 'Test world',
            'onoff' => true,
        ]);

        $this->actingAs($other)->put(route('worlds.timeline.world-reference.update', $world), [
            'reference_point' => 'X',
        ])->assertForbidden();
    }

    public function test_reference_point_must_not_exceed_255_chars(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'Test world',
            'onoff' => true,
        ]);

        $this->actingAs($user)->put(route('worlds.timeline.world-reference.update', $world), [
            'reference_point' => str_repeat('a', 256),
        ])->assertSessionHasErrors('reference_point');
    }

    public function test_owner_can_update_timeline_max_year_from_timeline_route(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'Test world',
            'onoff' => true,
            'timeline_max_year' => null,
        ]);

        $response = $this->actingAs($user)->put(route('worlds.timeline.world-reference.update', $world), [
            'reference_point' => null,
            'timeline_max_year' => 1200,
        ]);

        $response->assertRedirect(route('worlds.timeline', $world));

        $world->refresh();
        $this->assertSame(1200, $world->timeline_max_year);
    }

    public function test_world_reference_update_returns_json_when_requested(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'Test world',
            'onoff' => true,
            'reference_point' => null,
            'timeline_max_year' => null,
        ]);

        $response = $this->actingAs($user)
            ->withHeaders([
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->put(route('worlds.timeline.world-reference.update', $world), [
                'reference_point' => 'Новая подпись',
                'timeline_max_year' => 1400,
            ]);

        $response->assertOk();
        $response->assertJsonPath('message', 'Параметры сохранены.');
        $response->assertJsonStructure([
            'message',
            'axis' => ['tMin', 'tMax', 'canvasWidth', 'eventYearMin', 'eventYearMax'],
            'canvas_html',
        ]);
        $this->assertStringContainsString('timeline-jpg-export-root', $response->json('canvas_html'));

        $world->refresh();
        $this->assertSame('Новая подпись', $world->reference_point);
        $this->assertSame(1400, $world->timeline_max_year);
    }
}
