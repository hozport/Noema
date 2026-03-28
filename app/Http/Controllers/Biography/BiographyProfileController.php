<?php

namespace App\Http\Controllers\Biography;

use App\Http\Controllers\Controller;
use App\Models\Biography\Biography;
use App\Models\Timeline\TimelineLine;
use App\Models\Worlds\World;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BiographyProfileController extends Controller
{
    public function show(World $world, Biography $biography)
    {
        $this->authorizeWorld($world);

        if ($biography->world_id !== $world->id) {
            abort(404);
        }

        $biography->load(['relatives', 'friends', 'enemies', 'world.user', 'biographyEvents']);

        $allBiographiesForForm = $world->biographies()->orderBy('name')->get();

        $timelineLines = $world->timelineLines()
            ->orderBy('is_main')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(function ($l) use ($world) {
                return [
                    'id' => $l->id,
                    'label' => $l->name,
                    'description' => $l->is_main
                        ? ($world->reference_point
                            ? 'Точка отсчёта: '.$world->reference_point
                            : 'Основная линия мира')
                        : 'Пользовательская линия',
                    'is_main' => $l->is_main,
                ];
            })
            ->values()
            ->all();

        $biographyEventsPayload = $biography->biographyEvents->map(function ($e) {
            return [
                'id' => $e->id,
                'title' => $e->title,
                'year' => $e->epoch_year,
                'year_end' => $e->year_end,
                'month' => $e->month,
                'day' => $e->day,
                'body' => $e->body,
                'breaks_line' => (bool) $e->breaks_line,
                'on_timeline' => $e->isOnTimeline(),
            ];
        })->values()->all();

        $biographyTimelineLineId = TimelineLine::query()
            ->where('world_id', $world->id)
            ->where('source_biography_id', $biography->id)
            ->value('id');

        return view('biographies.show', compact('world', 'biography', 'allBiographiesForForm', 'timelineLines', 'biographyEventsPayload', 'biographyTimelineLineId'));
    }

    public function store(Request $request, World $world)
    {
        $this->authorizeWorld($world);

        $validated = $this->validateBiography($request, $world, null);

        $biography = $world->biographies()->create([
            'name' => $validated['name'],
            'race' => $validated['race'] ?? null,
            'birth_year' => $validated['birth_year'] ?? null,
            'birth_month' => $validated['birth_month'] ?? null,
            'birth_day' => $validated['birth_day'] ?? null,
            'death_year' => $validated['death_year'] ?? null,
            'death_month' => $validated['death_month'] ?? null,
            'death_day' => $validated['death_day'] ?? null,
            'short_description' => $validated['short_description'] ?? null,
            'full_description' => $validated['full_description'] ?? null,
        ]);

        if ($request->hasFile('image')) {
            $biography->image_path = $request->file('image')->store("biographies/{$world->id}", 'public');
            $biography->save();
        }

        $this->syncRelations($biography, $validated);

        return redirect()
            ->route('biographies.show', [$world, $biography])
            ->with('success', 'Биография создана.');
    }

    public function update(Request $request, World $world, Biography $biography)
    {
        $this->authorizeWorld($world);

        if ($biography->world_id !== $world->id) {
            abort(404);
        }

        $validated = $this->validateBiography($request, $world, $biography);

        $biography->fill([
            'name' => $validated['name'],
            'race' => $validated['race'] ?? null,
            'birth_year' => $validated['birth_year'] ?? null,
            'birth_month' => $validated['birth_month'] ?? null,
            'birth_day' => $validated['birth_day'] ?? null,
            'death_year' => $validated['death_year'] ?? null,
            'death_month' => $validated['death_month'] ?? null,
            'death_day' => $validated['death_day'] ?? null,
            'short_description' => $validated['short_description'] ?? null,
            'full_description' => $validated['full_description'] ?? null,
        ]);

        if ($request->hasFile('image')) {
            $this->deletePublicPath($biography->image_path);
            $biography->image_path = $request->file('image')->store("biographies/{$world->id}", 'public');
        }

        $biography->save();

        $this->syncRelations($biography, $validated);

        return redirect()
            ->route('biographies.show', [$world, $biography])
            ->with('success', 'Изменения сохранены.');
    }

    public function pdf(World $world, Biography $biography)
    {
        $this->authorizeWorld($world);

        if ($biography->world_id !== $world->id) {
            abort(404);
        }

        $biography->load(['relatives', 'friends', 'enemies']);

        $html = view('biographies.biography-pdf', compact('world', 'biography'))->render();

        $options = new Options;
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $slug = Str::slug(Str::ascii($biography->name));
        if ($slug === '') {
            $slug = 'biography-'.$biography->id;
        }
        $filename = $slug.'.pdf';

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    private function validateBiography(Request $request, World $world, ?Biography $self): array
    {
        $this->mergeBiographyDateInputs($request);

        $selfId = $self?->id;

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'race' => ['nullable', 'string', 'max:255'],
            'birth_year' => ['nullable', 'integer', 'min:0', 'max:2147483647'],
            'birth_month' => ['nullable', 'integer', 'min:1', 'max:100'],
            'birth_day' => ['nullable', 'integer', 'min:1', 'max:100'],
            'death_year' => ['nullable', 'integer', 'min:0', 'max:2147483647'],
            'death_month' => ['nullable', 'integer', 'min:1', 'max:100'],
            'death_day' => ['nullable', 'integer', 'min:1', 'max:100'],
            'image' => ['nullable', 'image', 'max:12288'],
            'short_description' => ['nullable', 'string'],
            'full_description' => ['nullable', 'string'],
            'relative_ids' => ['nullable', 'array'],
            'relative_ids.*' => [
                'integer',
                Rule::exists('biographies', 'id')->where('world_id', $world->id),
            ],
            'friend_ids' => ['nullable', 'array'],
            'friend_ids.*' => [
                'integer',
                Rule::exists('biographies', 'id')->where('world_id', $world->id),
            ],
            'enemy_ids' => ['nullable', 'array'],
            'enemy_ids.*' => [
                'integer',
                Rule::exists('biographies', 'id')->where('world_id', $world->id),
            ],
        ];

        $validated = $request->validate($rules);

        $validated['relative_ids'] = $this->filterPivotIds($validated['relative_ids'] ?? [], $selfId);
        $validated['friend_ids'] = $this->filterPivotIds($validated['friend_ids'] ?? [], $selfId);
        $validated['enemy_ids'] = $this->filterPivotIds($validated['enemy_ids'] ?? [], $selfId);

        $this->validateBiographyBirthDeathConsistency($validated);
        $this->assertDeathAfterBirth($validated);

        return $validated;
    }

    private function mergeBiographyDateInputs(Request $request): void
    {
        foreach (['birth_year', 'birth_month', 'birth_day', 'death_year', 'death_month', 'death_day'] as $key) {
            $v = $request->input($key);
            if ($v === '' || $v === null) {
                $request->merge([$key => null]);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function validateBiographyBirthDeathConsistency(array $data): void
    {
        $messages = [];
        foreach (['birth', 'death'] as $prefix) {
            $y = $data["{$prefix}_year"] ?? null;
            $m = $data["{$prefix}_month"] ?? null;
            $d = $data["{$prefix}_day"] ?? null;
            if ($m !== null && $y === null) {
                $messages["{$prefix}_month"] = 'Укажите год или уберите месяц.';
            }
            if ($d !== null && $m === null) {
                $messages["{$prefix}_day"] = 'Укажите месяц или уберите день.';
            }
        }
        if ($messages !== []) {
            throw ValidationException::withMessages($messages);
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function assertDeathAfterBirth(array $validated): void
    {
        $by = $validated['birth_year'] ?? null;
        $dy = $validated['death_year'] ?? null;
        if ($by === null || $dy === null) {
            return;
        }
        if ((int) $dy > (int) $by) {
            return;
        }
        if ((int) $dy < (int) $by) {
            throw ValidationException::withMessages([
                'death_year' => 'Год смерти не может быть раньше года рождения.',
            ]);
        }
        $bm = $validated['birth_month'] ?? null;
        $bd = $validated['birth_day'] ?? null;
        $dm = $validated['death_month'] ?? null;
        $dd = $validated['death_day'] ?? null;
        if ($bm === null || $bd === null || $dm === null || $dd === null) {
            return;
        }
        $bMonth = (int) $bm;
        $bDay = (int) $bd;
        $dMonth = (int) $dm;
        $dDay = (int) $dd;
        if ($dMonth < $bMonth || ($dMonth === $bMonth && $dDay < $bDay)) {
            throw ValidationException::withMessages([
                'death_day' => 'Дата смерти не может быть раньше даты рождения.',
            ]);
        }
    }

    private function filterPivotIds(array $ids, ?int $selfId): array
    {
        $ids = array_unique(array_map('intval', $ids));
        if ($selfId !== null) {
            $ids = array_values(array_filter($ids, fn ($id) => $id !== $selfId));
        }

        return $ids;
    }

    private function syncRelations(Biography $biography, array $validated): void
    {
        $biography->relatives()->sync($validated['relative_ids'] ?? []);
        $biography->friends()->sync($validated['friend_ids'] ?? []);
        $biography->enemies()->sync($validated['enemy_ids'] ?? []);
    }

    private function deletePublicPath(?string $path): void
    {
        if ($path === null || $path === '') {
            return;
        }
        if (str_starts_with($path, 'biographies/')) {
            Storage::disk('public')->delete($path);
        }
    }

    private function authorizeWorld(World $world): void
    {
        if ($world->user_id !== auth()->id()) {
            abort(403);
        }
        if (! $world->onoff) {
            abort(404);
        }
    }
}
