<?php

namespace App\Http\Controllers\Cards;

use App\Http\Controllers\Controller;
use App\Models\Cards\Card;
use App\Models\Worlds\World;
use Illuminate\Http\Request;

class CardController extends Controller
{
    public function update(Request $request, World $world, Card $card)
    {
        $this->authorizeCard($world, $card);

        $validated = $request->validate([
            'content' => ['nullable', 'string'],
        ]);

        $card->update([
            'content' => $validated['content'] ?: null,
        ]);

        return redirect()->back();
    }

    public function decompose(World $world, Card $card)
    {
        $this->authorizeCard($world, $card);

        $paragraphs = $card->getParagraphs();

        if (count($paragraphs) < 2) {
            return redirect()->back()->with('error', 'Нужно как минимум 2 абзаца для декомпозиции.');
        }

        $position = $card->position;

        $card->story->cards()
            ->where('position', '>', $position)
            ->increment('position', count($paragraphs) - 1);

        $card->delete();

        foreach ($paragraphs as $i => $paragraph) {
            $card->story->cards()->create([
                'title' => 'Карточка ' . ($position + $i),
                'content' => $paragraph,
                'position' => $position + $i,
            ]);
        }

        return redirect()->back()->with('success', 'Карточка декомпозирована.');
    }

    private function authorizeCard(World $world, Card $card): void
    {
        if ($world->user_id !== auth()->id()) {
            abort(403);
        }
        if ($card->story->world_id !== $world->id) {
            abort(404);
        }
    }
}
