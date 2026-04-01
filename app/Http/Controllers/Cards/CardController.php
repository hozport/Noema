<?php

namespace App\Http\Controllers\Cards;

use App\Http\Controllers\Controller;
use App\Markup\NoemaMarkupValidator;
use App\Models\ActivityLog;
use App\Models\Cards\Card;
use App\Models\Cards\Story;
use App\Models\Worlds\World;
use Illuminate\Http\Request;

class CardController extends Controller
{
    public function reorder(Request $request, World $world, Story $story)
    {
        $this->authorizeStory($world, $story);

        $validated = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer', 'exists:cards,id'],
        ]);

        $expected = $story->cards()->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();
        $sent = collect($validated['order'])->map(fn ($id) => (int) $id)->sort()->values()->all();

        if ($expected !== $sent) {
            abort(422, 'Неверный набор карточек.');
        }

        foreach ($validated['order'] as $index => $cardId) {
            Card::where('id', $cardId)->where('story_id', $story->id)->update(['number' => $index + 1]);
        }

        ActivityLog::record($request->user(), $world, 'cards.reordered', 'Изменён порядок карточек в истории «'.$story->name.'».', $story);

        return response()->json(['ok' => true]);
    }

    public function update(Request $request, World $world, Card $card)
    {
        $this->authorizeCard($world, $card);

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
        ]);

        $updates = [];

        if ($request->exists('title')) {
            $updates['title'] = isset($validated['title']) && trim((string) $validated['title']) !== ''
                ? trim($validated['title'])
                : null;
        }

        if ($request->exists('content')) {
            $raw = $validated['content'] ?? null;
            if ($raw !== null && $raw !== '') {
                $markupErrors = NoemaMarkupValidator::validate($raw);
                if ($markupErrors !== []) {
                    $message = implode(' ', $markupErrors);
                    if ($request->expectsJson()) {
                        return response()->json([
                            'message' => $message,
                            'errors' => ['content' => $markupErrors],
                        ], 422);
                    }

                    return redirect()->back()
                        ->withErrors(['content' => $message])
                        ->withInput();
                }
            }
            $updates['content'] = $raw ?: null;
        }

        if ($updates !== []) {
            $card->update($updates);
            ActivityLog::record($request->user(), $world, 'card.updated', 'Обновлена карточка в истории «'.$card->story->name.'».', $card);
        }

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'title' => $card->title]);
        }

        return redirect()->back();
    }

    public function decompose(World $world, Card $card)
    {
        $this->authorizeCard($world, $card);

        $paragraphs = $card->getParagraphs();

        if (count($paragraphs) < 2) {
            return redirect()->back()->with('error', 'Нужно как минимум 2 абзаца для декомпозиции.');
        }

        $slot = $card->number;
        $story = $card->story;

        $story->cards()
            ->where('number', '>', $slot)
            ->increment('number', count($paragraphs) - 1);

        $card->delete();

        foreach ($paragraphs as $i => $paragraph) {
            $story->cards()->create([
                'title' => null,
                'content' => $paragraph,
                'number' => $slot + $i,
            ]);
        }

        $story->renumberCards();

        ActivityLog::record(auth()->user(), $world, 'card.decomposed', 'Декомпозиция карточки в истории «'.$story->name.'».', $story);

        return redirect()->back()->with('success', 'Карточка декомпозирована.');
    }

    public function destroy(World $world, Card $card)
    {
        $this->authorizeCard($world, $card);

        $story = $card->story;
        ActivityLog::record(auth()->user(), $world, 'card.deleted', 'Удалена карточка в истории «'.$story->name.'».', $card);

        $card->delete();
        $story->renumberCards();

        return redirect()->back()->with('success', 'Карточка удалена.');
    }

    public function highlight(World $world, Card $card)
    {
        $this->authorizeCard($world, $card);

        $story = $card->story;

        if ($card->is_highlighted) {
            $card->update(['is_highlighted' => false]);
        } else {
            $story->cards()->update(['is_highlighted' => false]);
            $card->update(['is_highlighted' => true]);
        }

        $highlightedId = $story->cards()->where('is_highlighted', true)->value('id');

        ActivityLog::record(auth()->user(), $world, 'card.highlight.updated', 'Изменена подсветка карточки в истории «'.$story->name.'».', $card);

        if (request()->expectsJson()) {
            return response()->json([
                'ok' => true,
                'highlighted_card_id' => $highlightedId,
            ]);
        }

        return redirect()->back();
    }

    private function authorizeStory(World $world, Story $story): void
    {
        if ($world->user_id !== auth()->id()) {
            abort(403);
        }
        if (! $world->onoff) {
            abort(404);
        }
        if ($story->world_id !== $world->id) {
            abort(404);
        }
    }

    private function authorizeCard(World $world, Card $card): void
    {
        if ($world->user_id !== auth()->id()) {
            abort(403);
        }
        if (! $world->onoff) {
            abort(404);
        }
        if ($card->story->world_id !== $world->id) {
            abort(404);
        }
    }
}
