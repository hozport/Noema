<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Worlds\World;
use App\Services\TimelineBootstrapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimelinePdfExportTest extends TestCase
{
    use RefreshDatabase;

    private function createWorldWithTimeline(User $user): World
    {
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);
        TimelineBootstrapService::bootstrap($world);

        return $world;
    }

    public function test_guest_is_redirected_from_timeline_pdf(): void
    {
        $user = User::factory()->create();
        $world = $this->createWorldWithTimeline($user);

        $this->get(route('worlds.timeline.pdf', $world))->assertRedirect();
    }

    public function test_owner_gets_not_found_for_hidden_world_timeline_pdf(): void
    {
        $user = User::factory()->create();
        $world = $this->createWorldWithTimeline($user);
        $world->update(['onoff' => false]);

        $this->actingAs($user)->get(route('worlds.timeline.pdf', $world))->assertNotFound();
    }

    public function test_timeline_pdf_blade_contains_section_headings_and_rows(): void
    {
        $world = new World([
            'name' => 'Мир Альфа',
            'annotation' => "Кратко о сеттинге.\nВторая строка.",
        ]);

        $html = view('timeline.timeline-pdf', [
            'world' => $world,
            'mainRows' => [
                ['date' => '01.01.0', 'title' => 'Нулевое событие', 'description' => null],
                ['date' => '15.03.100', 'title' => 'Другое', 'description' => 'Подпись'],
            ],
            'secondarySections' => [
                [
                    'name' => 'Побочная линия',
                    'rows' => [
                        ['date' => '02.02.50', 'title' => 'Факт', 'description' => null],
                    ],
                ],
            ],
        ])->render();

        $this->assertStringContainsString('Таймлайн', $html);
        $this->assertStringContainsString('Мир Альфа', $html);
        $this->assertStringContainsString('Кратко о сеттинге', $html);
        $this->assertStringContainsString('Основная временная линия', $html);
        $this->assertStringContainsString('Нулевое событие', $html);
        $this->assertStringContainsString('Побочная линия', $html);
        $this->assertStringContainsString('Факт', $html);
    }
}
