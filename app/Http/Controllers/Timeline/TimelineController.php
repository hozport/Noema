<?php

namespace App\Http\Controllers\Timeline;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Biography\BiographyEvent;
use App\Models\Faction\FactionEvent;
use App\Models\Timeline\TimelineEvent;
use App\Models\Timeline\TimelineLine;
use App\Models\Worlds\World;
use App\Services\WorldReferencePointSyncService;
use App\Support\TimelinePdfSupport;
use App\Support\TimelineVisualBuilder;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class TimelineController extends Controller
{
    public function show(Request $request, World $world)
    {
        if ($world->user_id !== $request->user()->id) {
            abort(403);
        }

        if (! $world->onoff) {
            abort(404);
        }

        $visual = TimelineVisualBuilder::build($world);
        $timelineLines = $world->timelineLines()->orderBy('is_main')->orderBy('sort_order')->orderBy('id')->get();

        $orderedLines = $timelineLines->values();
        $lineReorderMeta = $this->buildLineReorderMeta($orderedLines);

        $timelineEventsForJs = TimelineEvent::query()
            ->whereHas('line', fn ($q) => $q->where('world_id', $world->id))
            ->orderBy('id')
            ->get(['id', 'timeline_line_id', 'title', 'epoch_year', 'month', 'day', 'breaks_line']);

        $biographies = $world->biographies()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($b) => ['id' => $b->id, 'name' => $b->name])
            ->values()
            ->all();

        $factions = $world->factions()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($f) => ['id' => $f->id, 'name' => $f->name])
            ->values()
            ->all();

        $biographyEventsByBiography = [];
        $biographyEventLookup = [];
        foreach (BiographyEvent::query()
            ->whereHas('biography', fn ($q) => $q->where('world_id', $world->id))
            ->with('biography:id,name')
            ->orderBy('id')
            ->get() as $be) {
            if ($be->isOnTimeline() || $be->epoch_year === null) {
                continue;
            }
            $row = [
                'id' => $be->id,
                'label' => Str::limit($be->title, 200),
                'epoch_year' => (int) $be->epoch_year,
                'month' => (int) $be->month,
                'day' => (int) $be->day,
                'title' => $be->title,
                'breaks_line' => (bool) $be->breaks_line,
            ];
            $bid = (string) $be->biography_id;
            $biographyEventsByBiography[$bid][] = $row;
            $biographyEventLookup[(string) $be->id] = $row;
        }

        $factionEventsByFaction = [];
        $factionEventLookup = [];
        foreach (FactionEvent::query()
            ->whereHas('faction', fn ($q) => $q->where('world_id', $world->id))
            ->with('faction:id,name')
            ->orderBy('id')
            ->get() as $fe) {
            if ($fe->isOnTimeline() || $fe->epoch_year === null) {
                continue;
            }
            $row = [
                'id' => $fe->id,
                'label' => Str::limit($fe->title, 200),
                'epoch_year' => (int) $fe->epoch_year,
                'month' => (int) $fe->month,
                'day' => (int) $fe->day,
                'title' => $fe->title,
                'breaks_line' => (bool) $fe->breaks_line,
            ];
            $fid = (string) $fe->faction_id;
            $factionEventsByFaction[$fid][] = $row;
            $factionEventLookup[(string) $fe->id] = $row;
        }

        $timelineEventSourceOptions = [
            'biographies' => $biographies,
            'factions' => $factions,
            'biography_events_by_biography' => $biographyEventsByBiography,
            'faction_events_by_faction' => $factionEventsByFaction,
            'biography_event_lookup' => $biographyEventLookup,
            'faction_event_lookup' => $factionEventLookup,
        ];

        return view('timeline.show', compact(
            'world',
            'visual',
            'timelineLines',
            'timelineEventsForJs',
            'lineReorderMeta',
            'timelineEventSourceOptions'
        ));
    }

    /**
     * Экспорт таймлайна в PDF: заголовок, мир, основная линия и дополнительные линии со списками событий.
     *
     * @param  Request  $request  HTTP-запрос
     * @param  World  $world  Мир
     */
    public function pdf(Request $request, World $world): Response
    {
        if ($world->user_id !== $request->user()->id) {
            abort(403);
        }

        if (! $world->onoff) {
            abort(404);
        }

        $lines = $world->timelineLines()
            ->with([
                'events' => function ($q): void {
                    $q->orderBy('epoch_year')->orderBy('month')->orderBy('day')->orderBy('id');
                },
                'events.source',
            ])
            ->orderByDesc('is_main')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $mainLine = $lines->firstWhere('is_main', true);
        $mainEvents = $mainLine !== null ? $mainLine->events : collect();

        $mapRow = static function (TimelineEvent $e): array {
            return [
                'date' => TimelinePdfSupport::formatEventDate($e),
                'title' => $e->title,
                'description' => TimelinePdfSupport::eventDescription($e),
            ];
        };

        $mainRows = $mainEvents->map($mapRow)->values()->all();

        $secondarySections = $lines->where('is_main', false)->values()->map(function ($line) use ($mapRow) {
            return [
                'name' => $line->name,
                'rows' => $line->events->map($mapRow)->values()->all(),
            ];
        })->all();

        $html = view('timeline.timeline-pdf', compact('world', 'mainRows', 'secondarySections'))->render();

        $options = new Options;
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $slug = Str::slug(Str::ascii($world->name));
        if ($slug === '') {
            $slug = 'world-'.$world->id;
        }
        $filename = 'timeline-'.$slug.'.pdf';

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * Настройки таймлайна с экрана холста: подпись инициирующего события и ограничение правой границы шкалы.
     *
     * Обновляет `worlds.reference_point`, `worlds.timeline_max_year` и синхронизирует маркер на основной линии.
     *
     * @param  Request  $request  Поля reference_point, timeline_max_year
     * @param  World  $world  Мир
     * @param  WorldReferencePointSyncService  $referencePointSync  Синхронизация маркера года 0
     */
    public function updateWorldReference(Request $request, World $world, WorldReferencePointSyncService $referencePointSync): JsonResponse|RedirectResponse
    {
        if ($world->user_id !== $request->user()->id) {
            abort(403);
        }

        if (! $world->onoff) {
            abort(404);
        }

        $validated = $request->validate([
            'reference_point' => ['nullable', 'string', 'max:255'],
            'timeline_max_year' => ['nullable', 'integer', 'min:0'],
        ]);

        $world->reference_point = $validated['reference_point'] ?? null;
        if (array_key_exists('timeline_max_year', $validated)) {
            $world->timeline_max_year = $validated['timeline_max_year'];
        }
        $world->save();

        $referencePointSync->sync($world);

        ActivityLog::record($request->user(), $world, 'world.updated', 'Обновлены настройки таймлайна на холсте.', $world);

        if ($request->wantsJson()) {
            $world->refresh();
            $visual = TimelineVisualBuilder::build($world);
            $timelineLines = $world->timelineLines()->orderBy('is_main')->orderBy('sort_order')->orderBy('id')->get();
            $orderedLines = $timelineLines->values();
            $lineReorderMeta = $this->buildLineReorderMeta($orderedLines);
            $canvasHtml = view('timeline.partials.canvas-export-root', [
                'visual' => $visual,
                'lineReorderMeta' => $lineReorderMeta,
            ])->render();

            return response()->json([
                'message' => 'Параметры сохранены.',
                'axis' => [
                    'tMin' => $visual['tMin'],
                    'tMax' => $visual['tMax'],
                    'canvasWidth' => $visual['canvasWidth'],
                    'eventYearMin' => $visual['eventYearMin'],
                    'eventYearMax' => $visual['eventYearMax'],
                ],
                'canvas_html' => $canvasHtml,
            ]);
        }

        return redirect()
            ->route('worlds.timeline', $world)
            ->with('success', 'Параметры сохранены.');
    }

    /**
     * Мета для UI перестановки дорожек (стрелки вверх/вниз) по упорядоченному списку линий.
     *
     * @param  Collection<int, TimelineLine>  $orderedLines
     * @return array<int, array{can_up: bool, can_down: bool}>
     */
    protected function buildLineReorderMeta(Collection $orderedLines): array
    {
        $lineReorderMeta = [];
        foreach ($orderedLines as $i => $l) {
            if ($l->is_main) {
                continue;
            }
            $prev = $i > 0 ? $orderedLines->get($i - 1) : null;
            $next = $i < $orderedLines->count() - 1 ? $orderedLines->get($i + 1) : null;
            $lineReorderMeta[$l->id] = [
                'can_up' => $prev !== null && ! $prev->is_main,
                'can_down' => $next !== null && ! $next->is_main,
            ];
        }

        return $lineReorderMeta;
    }
}
