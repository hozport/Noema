<?php

namespace App\Http\Controllers\Cards;

use App\Http\Controllers\Controller;
use App\Models\Cards\Story;
use App\Models\Worlds\World;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class StoryController extends Controller
{
    private const INITIAL_CARD_COUNT = 4;

    public function index(World $world)
    {
        $this->authorizeWorld($world);

        $stories = $world->stories()->orderBy('created_at', 'desc')->get();

        return view('cards.index', compact('world', 'stories'));
    }

    public function store(Request $request, World $world)
    {
        $this->authorizeWorld($world);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'synopsis' => ['nullable', 'string'],
        ]);

        $story = $world->stories()->create([
            'name' => $validated['name'],
            'synopsis' => isset($validated['synopsis']) && trim((string) $validated['synopsis']) !== ''
                ? trim($validated['synopsis'])
                : null,
        ]);

        for ($i = 0; $i < self::INITIAL_CARD_COUNT; $i++) {
            $story->cards()->create([
                'title' => null,
                'content' => null,
                'number' => $i + 1,
            ]);
        }

        return redirect()->route('cards.show', [$world, $story]);
    }

    public function show(World $world, Story $story)
    {
        $this->authorizeWorld($world);

        if ($story->world_id !== $world->id) {
            abort(404);
        }

        $story->load('cards');

        return view('cards.show', compact('world', 'story'));
    }

    public function update(Request $request, World $world, Story $story)
    {
        $this->authorizeWorld($world);

        if ($story->world_id !== $world->id) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'synopsis' => ['nullable', 'string'],
        ]);

        $story->update([
            'name' => $validated['name'],
            'synopsis' => isset($validated['synopsis']) && trim((string) $validated['synopsis']) !== ''
                ? trim($validated['synopsis'])
                : null,
        ]);

        return redirect()->route('cards.show', [$world, $story])->with('success', 'Настройки истории сохранены.');
    }

    public function pdf(World $world, Story $story)
    {
        $this->authorizeWorld($world);

        if ($story->world_id !== $world->id) {
            abort(404);
        }

        $story->load(['cards' => fn ($q) => $q->orderBy('number')]);

        $html = view('cards.story-pdf', compact('story'))->render();

        $options = new Options;
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $slug = Str::slug(Str::ascii($story->name));
        if ($slug === '') {
            $slug = 'story-'.$story->id;
        }
        $filename = $slug.'-cards.pdf';

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
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
