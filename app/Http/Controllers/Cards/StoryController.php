<?php

namespace App\Http\Controllers\Cards;

use App\Http\Controllers\Controller;
use App\Models\Cards\Story;
use App\Models\Worlds\World;
use Illuminate\Http\Request;

class StoryController extends Controller
{
    private const INITIAL_CARD_TITLES = ['Завязка', 'Развитие', 'Кульминация', 'Развязка'];

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
        ]);

        $story = $world->stories()->create([
            'name' => $validated['name'],
        ]);

        foreach (self::INITIAL_CARD_TITLES as $i => $title) {
            $story->cards()->create([
                'title' => $title,
                'content' => null,
                'position' => $i + 1,
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

    private function authorizeWorld(World $world): void
    {
        if ($world->user_id !== auth()->id()) {
            abort(403);
        }
    }
}
