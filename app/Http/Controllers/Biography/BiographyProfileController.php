<?php

namespace App\Http\Controllers\Biography;

use App\Http\Controllers\Controller;
use App\Models\Biography\Biography;
use App\Models\Faction\Faction;
use App\Models\Timeline\TimelineLine;
use App\Models\Worlds\World;
use App\Support\BiographyKinship;
use App\Support\FactionType;
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

        $biography->load([
            'relatives',
            'friends',
            'enemies',
            'world.user',
            'biographyEvents',
            'raceFaction',
            'peopleFaction',
            'countryFaction',
            'membershipFactions',
        ]);
        $socialMembershipFactions = $biography->socialMembershipFactions();

        $allBiographiesForForm = $world->biographies()->orderBy('name')->get();
        $raceFactions = $world->factions()->where('type', FactionType::RACE)->orderBy('name')->get();
        $peopleFactions = $world->factions()->where('type', FactionType::PEOPLE)->orderBy('name')->get();
        $countryFactions = $world->factions()->where('type', FactionType::COUNTRY)->orderBy('name')->get();
        $membershipFactions = $world->factions()
            ->whereNotIn('type', FactionType::biographyDedicatedTypes())
            ->orderBy('name')
            ->get();

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

        return view('biographies.show', compact(
            'world',
            'biography',
            'allBiographiesForForm',
            'raceFactions',
            'peopleFactions',
            'countryFactions',
            'membershipFactions',
            'socialMembershipFactions',
            'timelineLines',
            'biographyEventsPayload',
            'biographyTimelineLineId'
        ));
    }

    public function store(Request $request, World $world)
    {
        $this->authorizeWorld($world);

        $validated = $this->validateBiography($request, $world, null);
        $raceFactionId = $this->resolveTypedFactionForBiography($request, $world, 'race_faction_id', 'race_other_name', FactionType::RACE);
        $peopleFactionId = $this->resolveTypedFactionForBiography($request, $world, 'people_faction_id', 'people_other_name', FactionType::PEOPLE);
        $countryFactionId = $this->resolveTypedFactionForBiography($request, $world, 'country_faction_id', 'country_other_name', FactionType::COUNTRY);

        $biography = $world->biographies()->create([
            'name' => $validated['name'],
            'race_faction_id' => $raceFactionId,
            'people_faction_id' => $peopleFactionId,
            'country_faction_id' => $countryFactionId,
            'gender' => $validated['gender'] ?? null,
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
        $raceFactionId = $this->resolveTypedFactionForBiography($request, $world, 'race_faction_id', 'race_other_name', FactionType::RACE);
        $peopleFactionId = $this->resolveTypedFactionForBiography($request, $world, 'people_faction_id', 'people_other_name', FactionType::PEOPLE);
        $countryFactionId = $this->resolveTypedFactionForBiography($request, $world, 'country_faction_id', 'country_other_name', FactionType::COUNTRY);

        $biography->fill([
            'name' => $validated['name'],
            'race_faction_id' => $raceFactionId,
            'people_faction_id' => $peopleFactionId,
            'country_faction_id' => $countryFactionId,
            'gender' => $validated['gender'] ?? null,
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

    public function destroy(Request $request, World $world, Biography $biography)
    {
        $this->authorizeWorld($world);

        if ($biography->world_id !== $world->id) {
            abort(404);
        }

        $this->deletePublicPath($biography->image_path);
        $biography->delete();

        return redirect()
            ->route('biographies.index', $world)
            ->with('success', 'Биография удалена.');
    }

    public function pdf(World $world, Biography $biography)
    {
        $this->authorizeWorld($world);

        if ($biography->world_id !== $world->id) {
            abort(404);
        }

        $biography->load(['relatives', 'friends', 'enemies', 'raceFaction', 'peopleFaction', 'countryFaction', 'membershipFactions']);
        $socialMembershipFactions = $biography->socialMembershipFactions();

        $html = view('biographies.biography-pdf', compact('world', 'biography', 'socialMembershipFactions'))->render();

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
            'race_faction_id' => ['nullable'],
            'race_other_name' => ['nullable', 'string', 'max:255'],
            'people_faction_id' => ['nullable'],
            'people_other_name' => ['nullable', 'string', 'max:255'],
            'country_faction_id' => ['nullable'],
            'country_other_name' => ['nullable', 'string', 'max:255'],
            'faction_membership_ids' => ['nullable', 'array'],
            'faction_membership_ids.*' => [
                'integer',
                Rule::exists('factions', 'id')->where(function ($q) use ($world) {
                    $q->where('world_id', $world->id)
                        ->whereNotIn('type', FactionType::biographyDedicatedTypes());
                }),
            ],
            'gender' => ['nullable', Rule::in(['m', 'f'])],
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
            'relative_kinship' => ['nullable', 'array'],
            'relative_kinship.*' => ['nullable', 'string', 'max:32'],
            'relative_kinship_custom' => ['nullable', 'array'],
            'relative_kinship_custom.*' => ['nullable', 'string', 'max:255'],
        ];

        $validated = $request->validate($rules);

        $validated['relative_ids'] = $this->filterPivotIds($validated['relative_ids'] ?? [], $selfId);
        $validated['friend_ids'] = $this->filterPivotIds($validated['friend_ids'] ?? [], $selfId);
        $validated['enemy_ids'] = $this->filterPivotIds($validated['enemy_ids'] ?? [], $selfId);
        $membershipIds = array_values(array_unique(array_map('intval', $validated['faction_membership_ids'] ?? [])));
        $validated['faction_membership_ids'] = array_values(array_filter($membershipIds, function (int $id) use ($world): bool {
            $type = Faction::query()->where('world_id', $world->id)->where('id', $id)->value('type');

            return $type !== null && ! in_array($type, FactionType::biographyDedicatedTypes(), true);
        }));

        $this->validateRelativeKinship($validated);

        $this->validateBiographyBirthDeathConsistency($validated);
        $this->assertDeathAfterBirth($validated);

        return $validated;
    }

    private function resolveTypedFactionForBiography(
        Request $request,
        World $world,
        string $selectKey,
        string $otherKey,
        string $factionType
    ): ?int {
        $rf = $request->input($selectKey);
        if ($rf === 'other') {
            $other = trim((string) $request->input($otherKey, ''));
            if ($other === '') {
                throw ValidationException::withMessages([
                    $otherKey => $this->typedFactionOtherRequiredMessage($factionType),
                ]);
            }

            return $this->ensureTypedFaction($world, $other, $factionType)->id;
        }
        if ($rf === '' || $rf === null) {
            return null;
        }
        $id = (int) $rf;
        $exists = Faction::query()
            ->where('world_id', $world->id)
            ->where('type', $factionType)
            ->where('id', $id)
            ->exists();
        if (! $exists) {
            throw ValidationException::withMessages([
                $selectKey => 'Выберите значение из списка.',
            ]);
        }

        return $id;
    }

    private function typedFactionOtherRequiredMessage(string $factionType): string
    {
        return match ($factionType) {
            FactionType::RACE => 'Введите название расы.',
            FactionType::PEOPLE => 'Введите название народа.',
            FactionType::COUNTRY => 'Введите название страны.',
            default => 'Введите название.',
        };
    }

    private function ensureTypedFaction(World $world, string $name, string $factionType): Faction
    {
        $name = Str::limit(trim($name), 255);
        $existing = Faction::query()
            ->where('world_id', $world->id)
            ->where('type', $factionType)
            ->where('name', $name)
            ->first();
        if ($existing) {
            return $existing;
        }

        return Faction::query()->create([
            'world_id' => $world->id,
            'name' => $name,
            'type' => $factionType,
            'type_custom' => null,
            'short_description' => null,
            'full_description' => null,
            'geographic_stub' => null,
        ]);
    }

    private function mergeBiographyDateInputs(Request $request): void
    {
        foreach (['birth_year', 'birth_month', 'birth_day', 'death_year', 'death_month', 'death_day'] as $key) {
            $v = $request->input($key);
            if ($v === '' || $v === null) {
                $request->merge([$key => null]);
            }
        }
        $g = $request->input('gender');
        if ($g === '' || $g === null) {
            $request->merge(['gender' => null]);
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

    private function validateRelativeKinship(array $validated): void
    {
        $messages = [];
        foreach ($validated['relative_ids'] ?? [] as $rid) {
            $rid = (int) $rid;
            $kin = $this->kinshipInput($validated, $rid);
            if ($kin === null || $kin === '') {
                $messages['relative_kinship.'.$rid] = 'Укажите степень родства для каждого выбранного родственника.';

                continue;
            }
            if (! in_array($kin, BiographyKinship::presetKeys(), true)) {
                $messages['relative_kinship.'.$rid] = 'Некорректная степень родства.';

                continue;
            }
            if ($kin === BiographyKinship::CUSTOM) {
                $custom = trim((string) $this->kinshipCustomInput($validated, $rid));
                if ($custom === '') {
                    $messages['relative_kinship_custom.'.$rid] = 'Введите свой вариант степени родства.';
                }
            }
        }
        if ($messages !== []) {
            throw ValidationException::withMessages($messages);
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function kinshipInput(array $validated, int $rid): ?string
    {
        $map = $validated['relative_kinship'] ?? [];
        if (array_key_exists($rid, $map)) {
            return $map[$rid] === null || $map[$rid] === '' ? null : (string) $map[$rid];
        }
        if (array_key_exists((string) $rid, $map)) {
            $v = $map[(string) $rid];

            return $v === null || $v === '' ? null : (string) $v;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function kinshipCustomInput(array $validated, int $rid): ?string
    {
        $map = $validated['relative_kinship_custom'] ?? [];
        if (array_key_exists($rid, $map)) {
            return $map[$rid] !== null ? (string) $map[$rid] : null;
        }
        if (array_key_exists((string) $rid, $map)) {
            $v = $map[(string) $rid];

            return $v !== null ? (string) $v : null;
        }

        return null;
    }

    private function syncRelations(Biography $biography, array $validated): void
    {
        $syncRelatives = [];
        foreach ($validated['relative_ids'] ?? [] as $rid) {
            $rid = (int) $rid;
            $kin = $this->kinshipInput($validated, $rid) ?? '';
            $kcRaw = $this->kinshipCustomInput($validated, $rid);
            $kc = is_string($kcRaw) ? trim($kcRaw) : '';
            if ($kin === BiographyKinship::CUSTOM) {
                $syncRelatives[$rid] = [
                    'kinship' => BiographyKinship::CUSTOM,
                    'kinship_custom' => $kc !== '' ? Str::limit($kc, 255) : null,
                ];
            } else {
                $syncRelatives[$rid] = [
                    'kinship' => $kin,
                    'kinship_custom' => null,
                ];
            }
        }
        $biography->relatives()->sync($syncRelatives);
        $biography->friends()->sync($validated['friend_ids'] ?? []);
        $biography->enemies()->sync($validated['enemy_ids'] ?? []);
        $biography->membershipFactions()->sync($this->mergedMembershipFactionIds($biography, $validated));
    }

    /**
     * В pivot входят «социальные» фракции из формы плюс раса / народ / страна из отдельных полей
     * (по одной на тип — конфликтующие связи в pivot затираются через sync).
     *
     * @param  array<string, mixed>  $validated
     * @return list<int>
     */
    private function mergedMembershipFactionIds(Biography $biography, array $validated): array
    {
        $social = array_values(array_unique(array_map('intval', $validated['faction_membership_ids'] ?? [])));
        $fromFk = array_filter([
            $biography->race_faction_id,
            $biography->people_faction_id,
            $biography->country_faction_id,
        ], fn ($v) => $v !== null && (int) $v !== 0);

        return array_values(array_unique(array_merge($social, $fromFk)));
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
