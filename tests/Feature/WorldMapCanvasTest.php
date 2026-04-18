<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Worlds\World;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WorldMapCanvasTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_save_map_canvas(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);

        $this->putJson(route('worlds.maps.canvas.update', $world), [
            'lines' => [],
            'fill_png_base64' => null,
        ])->assertUnauthorized();
    }

    public function test_owner_can_save_map_canvas_lines_and_clear_fill(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);
        $path = 'worlds/'.$world->id.'/map_fill.png';
        Storage::disk('public')->makeDirectory('worlds/'.$world->id);
        Storage::disk('public')->put($path, 'old');
        $world->map_fill_path = $path;
        $world->save();

        $this->actingAs($user)->putJson(route('worlds.maps.canvas.update', $world), [
            'lines' => [
                [
                    'points' => [0, 0, 100, 200],
                    'stroke' => 'rgba(52, 48, 42, 0.92)',
                    'dash' => null,
                ],
            ],
            'fill_png_base64' => null,
        ])->assertOk()->assertJson(['ok' => true]);

        $world->refresh();
        $this->assertCount(1, $world->map_drawing_lines ?? []);
        $this->assertSame('rgba(52, 48, 42, 0.92)', $world->map_drawing_lines[0]['stroke']);
        $this->assertNull($world->map_fill_path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_owner_can_save_map_fill_png(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);

        $binary = random_bytes(64);
        $png = base64_encode($binary);

        $this->actingAs($user)->putJson(route('worlds.maps.canvas.update', $world), [
            'lines' => [],
            'fill_png_base64' => $png,
        ])->assertOk();

        $world->refresh();
        $this->assertSame('worlds/'.$world->id.'/map_fill.png', $world->map_fill_path);
        Storage::disk('public')->assertExists($world->map_fill_path);
        $this->assertSame($binary, Storage::disk('public')->get($world->map_fill_path));
    }

    public function test_owner_can_save_lines_without_changing_stored_fill(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);
        $path = 'worlds/'.$world->id.'/map_fill.png';
        Storage::disk('public')->makeDirectory('worlds/'.$world->id);
        Storage::disk('public')->put($path, 'saved-fill');
        $world->map_fill_path = $path;
        $world->save();

        $this->actingAs($user)->putJson(route('worlds.maps.canvas.update', $world), [
            'lines' => [
                [
                    'points' => [0, 0, 50, 50],
                    'stroke' => 'rgba(52, 48, 42, 0.92)',
                    'dash' => null,
                ],
            ],
        ])->assertOk();

        $world->refresh();
        $this->assertSame($path, $world->map_fill_path);
        Storage::disk('public')->assertExists($path);
        $this->assertSame('saved-fill', Storage::disk('public')->get($path));
    }

    public function test_non_owner_cannot_save_map_canvas(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $owner->id,
            'name' => 'W',
            'onoff' => true,
        ]);

        $this->actingAs($other)->putJson(route('worlds.maps.canvas.update', $world), [
            'lines' => [],
            'fill_png_base64' => null,
        ])->assertForbidden();
    }
}
