<?php

namespace Tests\Unit;

use App\Models\Biography\Biography;
use App\Models\Biography\BiographyEvent;
use App\Models\Timeline\TimelineEvent;
use App\Models\Timeline\TimelineLine;
use App\Models\User;
use App\Models\Worlds\World;
use App\Services\TimelineBootstrapService;
use App\Support\TimelinePdfSupport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimelinePdfSupportTest extends TestCase
{
    use RefreshDatabase;

    public function test_format_event_date_matches_visual_pattern(): void
    {
        $e = new TimelineEvent([
            'epoch_year' => 42,
            'month' => 3,
            'day' => 5,
        ]);

        $this->assertSame('05.03.42', TimelinePdfSupport::formatEventDate($e));
    }

    public function test_event_description_returns_null_without_source(): void
    {
        $e = new TimelineEvent([
            'title' => 'X',
            'epoch_year' => 1,
            'month' => 1,
            'day' => 1,
            'source_type' => null,
            'source_id' => null,
        ]);

        $this->assertNull(TimelinePdfSupport::eventDescription($e));
    }

    public function test_event_description_strips_html_from_biography_event_body(): void
    {
        $user = User::factory()->create();
        $world = World::query()->create([
            'user_id' => $user->id,
            'name' => 'W',
            'onoff' => true,
        ]);
        TimelineBootstrapService::bootstrap($world);

        $bio = Biography::query()->create([
            'world_id' => $world->id,
            'name' => 'Hero',
        ]);

        $be = BiographyEvent::query()->create([
            'biography_id' => $bio->id,
            'title' => 'Fact',
            'epoch_year' => 10,
            'month' => 2,
            'day' => 3,
            'body' => '<p>Описание <strong>важное</strong></p>',
        ]);

        $main = TimelineLine::query()->where('world_id', $world->id)->where('is_main', true)->firstOrFail();
        $te = TimelineEvent::query()->create([
            'timeline_line_id' => $main->id,
            'title' => 'На таймлайне',
            'epoch_year' => 10,
            'month' => 2,
            'day' => 3,
            'breaks_line' => false,
            'source_type' => BiographyEvent::class,
            'source_id' => $be->id,
        ]);

        $plain = TimelinePdfSupport::eventDescription($te);
        $this->assertIsString($plain);
        $this->assertStringContainsString('Описание', $plain);
        $this->assertStringContainsString('важное', $plain);
        $this->assertStringNotContainsString('<p>', $plain);
    }
}
