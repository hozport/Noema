<?php

namespace App\Http\Controllers\Faction;

use App\Http\Controllers\Controller;
use App\Markup\NoemaMarkupValidator;
use App\Models\ActivityLog;
use App\Models\Biography\Biography;
use App\Models\Faction\Faction;
use App\Models\Timeline\TimelineLine;
use App\Models\Worlds\World;
use App\Support\FactionType;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FactionProfileController extends Controller
{
    public function show(World $world, Faction $faction)
    {
        $this->authorizeWorld($world);

        if ($faction->world_id !== $world->id) {
            abort(404);
        }

        $faction->load([
            'members',
            'relatedFactions',
            'enemyFactions',
            'world.user',
            'factionEvents',
        ]);

        $allBiographiesForForm = $world->biographies()->orderBy('name')->get();
        $allFactionsForForm = $world->factions()->where('id', '!=', $faction->id)->orderBy('name')->get();

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

        $factionEventsPayload = $faction->factionEvents->map(function ($e) {
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

        $factionTimelineLineId = TimelineLine::query()
            ->where('world_id', $world->id)
            ->where('source_faction_id', $faction->id)
            ->value('id');

        return view('factions.show', compact(
            'world',
            'faction',
            'allBiographiesForForm',
            'allFactionsForForm',
            'timelineLines',
            'factionEventsPayload',
            'factionTimelineLineId'
        ));
    }

    public function store(Request $request, World $world)
    {
        $this->authorizeWorld($world);

        $validated = $this->validateFaction($request, $world, null);

        $faction = $world->factions()->create([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'type_custom' => $validated['type'] === FactionType::OTHER ? ($validated['type_custom'] ?? null) : null,
            'short_description' => $validated['short_description'] ?? null,
            'full_description' => $validated['full_description'] ?? null,
            'geographic_stub' => $validated['geographic_stub'] ?? null,
        ]);

        if ($request->hasFile('image')) {
            $faction->image_path = $request->file('image')->store("factions/{$world->id}", 'public');
            $faction->save();
        }

        $this->syncRelations($faction, $validated);

        ActivityLog::record($request->user(), $world, 'faction.created', 'Создана фракция «'.$faction->name.'».', $faction);

        return redirect()
            ->route('factions.show', [$world, $faction])
            ->with('success', 'Фракция создана.');
    }

    public function update(Request $request, World $world, Faction $faction)
    {
        $this->authorizeWorld($world);

        if ($faction->world_id !== $world->id) {
            abort(404);
        }

        $validated = $this->validateFaction($request, $world, $faction);

        $faction->fill([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'type_custom' => $validated['type'] === FactionType::OTHER ? ($validated['type_custom'] ?? null) : null,
            'short_description' => $validated['short_description'] ?? null,
            'full_description' => $validated['full_description'] ?? null,
            'geographic_stub' => $validated['geographic_stub'] ?? null,
        ]);

        if ($request->hasFile('image')) {
            $this->deletePublicPath($faction->image_path);
            $faction->image_path = $request->file('image')->store("factions/{$world->id}", 'public');
        }

        $faction->save();

        $this->syncRelations($faction, $validated);

        ActivityLog::record($request->user(), $world, 'faction.updated', 'Обновлена фракция «'.$faction->name.'».', $faction);

        return redirect()
            ->route('factions.show', [$world, $faction])
            ->with('success', 'Изменения сохранены.');
    }

    public function destroy(Request $request, World $world, Faction $faction)
    {
        $this->authorizeWorld($world);

        if ($faction->world_id !== $world->id) {
            abort(404);
        }

        $this->deletePublicPath($faction->image_path);
        $name = $faction->name;
        ActivityLog::record($request->user(), $world, 'faction.deleted', 'Удалена фракция «'.$name.'».', $faction);

        $faction->delete();

        return redirect()
            ->route('factions.index', $world)
            ->with('success', 'Фракция удалена.');
    }

    public function pdf(World $world, Faction $faction)
    {
        $this->authorizeWorld($world);

        if ($faction->world_id !== $world->id) {
            abort(404);
        }

        $faction->load([
            'members',
            'relatedFactions',
            'enemyFactions',
            'factionEvents',
        ]);

        $html = view('factions.faction-pdf', compact('world', 'faction'))->render();

        $options = new Options;
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $slug = Str::slug(Str::ascii($faction->name));
        if ($slug === '') {
            $slug = 'faction-'.$faction->id;
        }
        $filename = $slug.'.pdf';

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateFaction(Request $request, World $world, ?Faction $self): array
    {
        $selfId = $self?->id;

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in(FactionType::keys())],
            'type_custom' => [
                Rule::requiredIf(fn () => (string) $request->input('type') === FactionType::OTHER),
                'nullable',
                'string',
                'max:255',
            ],
            'image' => ['nullable', 'image', 'max:12288'],
            'short_description' => ['nullable', 'string'],
            'full_description' => ['nullable', 'string'],
            'geographic_stub' => ['nullable', 'string'],
            'member_ids' => ['nullable', 'array'],
            'member_ids.*' => [
                'integer',
                Rule::exists('biographies', 'id')->where('world_id', $world->id),
            ],
            'related_ids' => ['nullable', 'array'],
            'related_ids.*' => [
                'integer',
                Rule::exists('factions', 'id')->where('world_id', $world->id),
            ],
            'enemy_ids' => ['nullable', 'array'],
            'enemy_ids.*' => [
                'integer',
                Rule::exists('factions', 'id')->where('world_id', $world->id),
            ],
        ];

        $validated = $request->validate($rules);

        if (($validated['type'] ?? '') === FactionType::OTHER) {
            $validated['type_custom'] = trim((string) ($validated['type_custom'] ?? ''));
            if ($validated['type_custom'] === '') {
                throw ValidationException::withMessages([
                    'type_custom' => 'Укажите свой вариант типа.',
                ]);
            }
        } else {
            $validated['type_custom'] = null;
        }

        // member_ids — это id биографий; не фильтровать по id фракции (иначе совпадение id=1 и id=1 снимает членство).
        $validated['member_ids'] = $this->filterPivotIds($validated['member_ids'] ?? [], null);
        $validated['related_ids'] = $this->filterPivotIds($validated['related_ids'] ?? [], $selfId);
        $validated['enemy_ids'] = $this->filterPivotIds($validated['enemy_ids'] ?? [], $selfId);

        foreach (['short_description', 'full_description'] as $field) {
            $raw = $validated[$field] ?? null;
            if ($raw !== null && $raw !== '') {
                $markupErrors = NoemaMarkupValidator::validate($raw);
                if ($markupErrors !== []) {
                    throw ValidationException::withMessages([
                        $field => implode(' ', $markupErrors),
                    ]);
                }
            }
        }

        return $validated;
    }

    private function filterPivotIds(array $ids, ?int $selfId): array
    {
        $ids = array_unique(array_map('intval', $ids));
        if ($selfId !== null) {
            $ids = array_values(array_filter($ids, fn ($id) => $id !== $selfId));
        }

        return $ids;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function syncRelations(Faction $faction, array $validated): void
    {
        $memberIds = array_values(array_unique(array_map('intval', $validated['member_ids'] ?? [])));
        $oldMemberIds = DB::table('faction_biography')
            ->where('faction_id', $faction->id)
            ->pluck('biography_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $faction->members()->sync($memberIds);
        $faction->relatedFactions()->sync($validated['related_ids'] ?? []);
        $faction->enemyFactions()->sync($validated['enemy_ids'] ?? []);

        $fkColumn = FactionType::biographyForeignKeyColumnForDedicatedType($faction->type);
        if ($fkColumn === null) {
            return;
        }

        $removedIds = array_values(array_diff($oldMemberIds, $memberIds));
        foreach ($removedIds as $bid) {
            $bio = Biography::query()->where('world_id', $faction->world_id)->find($bid);
            if ($bio === null) {
                continue;
            }
            if ($bio->{$fkColumn} !== null && (int) $bio->{$fkColumn} === (int) $faction->id) {
                $bio->{$fkColumn} = null;
                $bio->save();
            }
        }

        foreach ($memberIds as $bid) {
            $bio = Biography::query()->where('world_id', $faction->world_id)->find($bid);
            if ($bio === null) {
                continue;
            }
            $prev = $bio->{$fkColumn};
            if ($prev !== null && (int) $prev !== (int) $faction->id) {
                $bio->membershipFactions()->detach((int) $prev);
            }
            $bio->{$fkColumn} = $faction->id;
            $bio->save();
        }
    }

    private function deletePublicPath(?string $path): void
    {
        if ($path === null || $path === '') {
            return;
        }
        if (str_starts_with($path, 'factions/')) {
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
