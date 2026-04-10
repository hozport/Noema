<?php

namespace App\Http\Controllers\Bestiary;

use App\Http\Controllers\Controller;
use App\Markup\NoemaMarkupValidator;
use App\Models\ActivityLog;
use App\Models\Bestiary\Creature;
use App\Models\Bestiary\CreatureGallery;
use App\Models\Worlds\World;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BestiaryCreatureController extends Controller
{
    public function show(World $world, Creature $creature)
    {
        $this->authorizeWorld($world);

        if ($creature->world_id !== $world->id) {
            abort(404);
        }

        $creature->load(['relatedCreatures', 'foodCreatures', 'galleryImages', 'world.user']);

        $speciesSuggestions = $this->speciesSuggestions($world);
        $allCreaturesForForm = $world->creatures()->orderBy('name')->get();

        return view('bestiary.creature-show', compact('world', 'creature', 'speciesSuggestions', 'allCreaturesForForm'));
    }

    public function store(Request $request, World $world)
    {
        $this->authorizeWorld($world);

        $validated = $this->validateCreature($request, $world, null);

        $creature = DB::transaction(function () use ($request, $world, $validated) {
            $creature = $world->creatures()->create([
                'name' => $validated['name'],
                'scientific_name' => $validated['scientific_name'] ?? null,
                'species_kind' => $validated['species_kind'] ?? null,
                'height_text' => $validated['height_text'] ?? null,
                'weight_text' => $validated['weight_text'] ?? null,
                'lifespan_text' => $validated['lifespan_text'] ?? null,
                'short_description' => $validated['short_description'] ?? null,
                'full_description' => $validated['full_description'] ?? null,
                'habitat_text' => $validated['habitat_text'] ?? null,
                'food_custom' => $this->parseFoodCustom($request->input('food_custom_text')),
            ]);

            if ($request->hasFile('image')) {
                $creature->image_path = $request->file('image')->store("creatures/{$world->id}", 'public');
                $creature->save();
            }

            $this->syncRelated($creature, $validated['related_ids'] ?? []);
            $this->syncFood($creature, $validated['food_creature_ids'] ?? []);

            $this->storeGalleryUploads($request, $world, $creature);

            return $creature;
        });

        ActivityLog::record($request->user(), $world, 'bestiary.creature.created', 'В бестиарий добавлено существо «'.$creature->name.'».', $creature);

        return redirect()
            ->route('bestiary.creatures.show', [$world, $creature])
            ->with('success', 'Существо создано.');
    }

    public function update(Request $request, World $world, Creature $creature)
    {
        $this->authorizeWorld($world);

        if ($creature->world_id !== $world->id) {
            abort(404);
        }

        $validated = $this->validateCreature($request, $world, $creature);

        DB::transaction(function () use ($request, $world, $creature, $validated) {
            $creature->fill([
                'name' => $validated['name'],
                'scientific_name' => $validated['scientific_name'] ?? null,
                'species_kind' => $validated['species_kind'] ?? null,
                'height_text' => $validated['height_text'] ?? null,
                'weight_text' => $validated['weight_text'] ?? null,
                'lifespan_text' => $validated['lifespan_text'] ?? null,
                'short_description' => $validated['short_description'] ?? null,
                'full_description' => $validated['full_description'] ?? null,
                'habitat_text' => $validated['habitat_text'] ?? null,
                'food_custom' => $this->parseFoodCustom($request->input('food_custom_text')),
            ]);

            if ($request->hasFile('image')) {
                $this->deletePublicPath($creature->image_path);
                $creature->image_path = $request->file('image')->store("creatures/{$world->id}", 'public');
            }

            $creature->save();

            $this->syncRelated($creature, $validated['related_ids'] ?? []);
            $this->syncFood($creature, $validated['food_creature_ids'] ?? []);

            $removeIds = $validated['remove_gallery_ids'] ?? [];
            if ($removeIds !== []) {
                $toRemove = $creature->galleryImages()->whereIn('id', $removeIds)->get();
                foreach ($toRemove as $g) {
                    $this->deletePublicPath($g->path);
                    $g->delete();
                }
            }

            $this->storeGalleryUploads($request, $world, $creature);
        });

        ActivityLog::record($request->user(), $world, 'bestiary.creature.updated', 'Обновлено существо «'.$creature->name.'».', $creature);

        return redirect()
            ->route('bestiary.creatures.show', [$world, $creature])
            ->with('success', 'Изменения сохранены.');
    }

    public function destroy(Request $request, World $world, Creature $creature)
    {
        $this->authorizeWorld($world);

        if ($creature->world_id !== $world->id) {
            abort(404);
        }

        foreach ($creature->galleryImages as $g) {
            $this->deletePublicPath($g->path);
        }
        $this->deletePublicPath($creature->image_path);
        $name = $creature->name;
        ActivityLog::record($request->user(), $world, 'bestiary.creature.deleted', 'Удалено существо «'.$name.'».', $creature);

        $creature->delete();

        return redirect()
            ->route('bestiary.index', $world)
            ->with('success', 'Существо удалено.');
    }

    public function pdf(World $world, Creature $creature)
    {
        $this->authorizeWorld($world);

        if ($creature->world_id !== $world->id) {
            abort(404);
        }

        $creature->load(['relatedCreatures', 'foodCreatures', 'galleryImages']);

        $html = view('bestiary.creature-pdf', compact('world', 'creature'))->render();

        $options = new Options;
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $slug = Str::slug(Str::ascii($creature->name));
        if ($slug === '') {
            $slug = 'creature-'.$creature->id;
        }
        $filename = $slug.'.pdf';

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    private function speciesSuggestions(World $world): Collection
    {
        return $world->creatures()
            ->whereNotNull('species_kind')
            ->where('species_kind', '!=', '')
            ->distinct()
            ->orderBy('species_kind')
            ->pluck('species_kind')
            ->values();
    }

    private function validateCreature(Request $request, World $world, ?Creature $creature): array
    {
        $creatureId = $creature?->id;

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'scientific_name' => ['nullable', 'string', 'max:255'],
            'species_kind' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'max:12288'],
            'height_text' => ['nullable', 'string', 'max:120'],
            'weight_text' => ['nullable', 'string', 'max:120'],
            'lifespan_text' => ['nullable', 'string', 'max:120'],
            'short_description' => ['nullable', 'string'],
            'full_description' => ['nullable', 'string'],
            'habitat_text' => ['nullable', 'string'],
            'food_custom_text' => ['nullable', 'string'],
            'related_ids' => ['nullable', 'array'],
            'related_ids.*' => [
                'integer',
                Rule::exists('creatures', 'id')->where('world_id', $world->id),
            ],
            'food_creature_ids' => ['nullable', 'array'],
            'food_creature_ids.*' => [
                'integer',
                Rule::exists('creatures', 'id')->where('world_id', $world->id),
            ],
            'gallery' => ['nullable', 'array'],
            'gallery.*' => ['image', 'max:12288'],
        ];

        if ($creature !== null) {
            $rules['remove_gallery_ids'] = ['nullable', 'array'];
            $rules['remove_gallery_ids.*'] = [
                'integer',
                Rule::exists('creature_gallery', 'id')->where(
                    fn ($q) => $q->where('creature_id', $creature->id)
                ),
            ];
        }

        $validated = $request->validate($rules);

        $validated['related_ids'] = $this->filterPivotIds($validated['related_ids'] ?? [], $creatureId);
        $validated['food_creature_ids'] = $this->filterPivotIds($validated['food_creature_ids'] ?? [], $creatureId);

        if ($creature !== null) {
            $validated['remove_gallery_ids'] = $validated['remove_gallery_ids'] ?? [];
        } else {
            $validated['remove_gallery_ids'] = [];
        }

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

    /** Убирает самосвязи и дубликаты. */
    private function filterPivotIds(array $ids, ?int $selfId): array
    {
        $ids = array_unique(array_map('intval', $ids));
        if ($selfId !== null) {
            $ids = array_values(array_filter($ids, fn ($id) => $id !== $selfId));
        }

        return $ids;
    }

    private function parseFoodCustom(?string $raw): ?array
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }
        $lines = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $raw))));
        if ($lines === []) {
            return null;
        }

        return $lines;
    }

    private function syncRelated(Creature $creature, array $ids): void
    {
        $creature->relatedCreatures()->sync($ids);
    }

    private function syncFood(Creature $creature, array $ids): void
    {
        $creature->foodCreatures()->sync($ids);
    }

    private function storeGalleryUploads(Request $request, World $world, Creature $creature): void
    {
        $files = $request->file('gallery', []);
        if ($files === []) {
            return;
        }
        $maxOrder = (int) $creature->galleryImages()->max('sort_order');
        foreach ($files as $i => $file) {
            if ($file === null) {
                continue;
            }
            $path = $file->store("creatures/{$world->id}/{$creature->id}/gallery", 'public');
            CreatureGallery::create([
                'creature_id' => $creature->id,
                'path' => $path,
                'sort_order' => $maxOrder + $i + 1,
            ]);
        }
    }

    private function deletePublicPath(?string $path): void
    {
        if ($path === null || $path === '') {
            return;
        }
        if (str_starts_with($path, 'creatures/')) {
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
