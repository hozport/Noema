<?php

namespace App\Http\Controllers\Cards;

use App\Http\Controllers\Controller;
use App\Markup\NoemaMarkupValidator;
use App\Models\ActivityLog;
use App\Models\Cards\Card;
use App\Models\Cards\Story;
use App\Models\Worlds\World;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CardController extends Controller
{
    public function edit(World $world, Story $story, Card $card)
    {
        $this->authorizeCard($world, $card);

        if ((int) $card->story_id !== (int) $story->id) {
            abort(404);
        }

        if (($story->card_display_mode ?? Story::CARD_DISPLAY_MODAL) !== Story::CARD_DISPLAY_PAGE) {
            return redirect()->route('cards.show', [$world, $story]);
        }

        $card->loadMissing('story');

        return view('cards.card-edit', [
            'world' => $world,
            'story' => $story,
            'card' => $card,
        ]);
    }

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
            'is_highlighted' => ['sometimes', Rule::in(['0', '1'])],
        ]);

        $highlightTouched = $request->has('is_highlighted');
        $wantHighlight = $highlightTouched && (($validated['is_highlighted'] ?? '0') === '1');

        $updates = [];

        if ($request->exists('title')) {
            $updates['title'] = isset($validated['title']) && trim((string) $validated['title']) !== ''
                ? trim($validated['title'])
                : null;
        }

        if ($request->exists('content')) {
            $raw = $validated['content'] ?? null;
            if ($raw !== null && $raw !== '') {
                $markupErrors = NoemaMarkupValidator::validate($raw, $world);
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

        if ($highlightTouched) {
            $story = $card->story;
            $wasHighlighted = (bool) $card->is_highlighted;

            if ($wantHighlight) {
                $story->cards()->update(['is_highlighted' => false]);
                $card->update(['is_highlighted' => true]);
            } else {
                $card->update(['is_highlighted' => false]);
            }

            $card->refresh();

            if ($wasHighlighted !== (bool) $card->is_highlighted) {
                ActivityLog::record($request->user(), $world, 'card.highlight.updated', 'Изменена подсветка карточки в истории «'.$story->name.'».', $card);
            }
        }

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'title' => $card->title]);
        }

        if ($request->has('is_highlighted')) {
            $story = $card->story;

            return redirect()
                ->route('cards.card.edit', [$world, $story, $card])
                ->with('success', 'Карточка сохранена.');
        }

        return redirect()->back();
    }

    /**
     * Создаёт в истории несколько пустых карточек в конце списка.
     *
     * @param  Request  $request  Поле count (1…100)
     * @param  World  $world  Мир
     * @param  Story  $story  История
     */
    public function bulkCreate(Request $request, World $world, Story $story): RedirectResponse
    {
        $this->authorizeStory($world, $story);

        $validated = $request->validate([
            'count' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $count = (int) $validated['count'];

        DB::transaction(function () use ($story, $count): void {
            $base = (int) $story->cards()->max('number');
            for ($i = 0; $i < $count; $i++) {
                $story->cards()->create([
                    'title' => null,
                    'content' => null,
                    'number' => $base + $i + 1,
                ]);
            }
            $story->renumberCards();
        });

        ActivityLog::record(
            $request->user(),
            $world,
            'cards.bulk_created',
            'Добавлено '.$count.' пустых карточек в историю «'.$story->name.'».',
            $story
        );

        $msg = $count === 1 ? 'Создана 1 карточка.' : 'Создано карточек: '.$count.'.';

        return redirect()
            ->route('cards.show', [$world, $story])
            ->with('success', $msg)
            ->with('story_cards_scroll_end', true);
    }

    /**
     * Последовательно декомпозирует все карточки истории, у которых ≥2 абзацев (та же логика, что у одной карточки).
     *
     * @param  World  $world  Мир
     * @param  Story  $story  История
     */
    public function decomposeAll(Request $request, World $world, Story $story): RedirectResponse
    {
        $this->authorizeStory($world, $story);

        $changed = false;

        DB::transaction(function () use ($story, &$changed): void {
            $guard = 0;
            while ($guard++ < 5000) {
                $target = $story->cards()
                    ->orderBy('number')
                    ->orderBy('id')
                    ->get()
                    ->first(fn (Card $c) => count($c->getParagraphs()) >= 2);
                if ($target === null) {
                    break;
                }
                $this->performCardDecomposition($story, $target);
                $changed = true;
            }
        });

        if (! $changed) {
            return redirect()
                ->back()
                ->with('error', 'Нет карточек с двумя и более абзацами для декомпозиции.');
        }

        ActivityLog::record(
            auth()->user(),
            $world,
            'cards.decompose_all',
            'Декомпозиция всех карточек по абзацам в истории «'.$story->name.'».',
            $story
        );

        return redirect()
            ->route('cards.show', [$world, $story])
            ->with('success', 'Карточки декомпозированы по абзацам.')
            ->with('story_cards_scroll_end', true);
    }

    public function decompose(Request $request, World $world, Card $card)
    {
        $this->authorizeCard($world, $card);

        $story = $card->story;

        $paragraphs = $card->getParagraphs();

        if (count($paragraphs) < 2) {
            return redirect()->back()->with('error', 'Нужно как минимум 2 абзаца для декомпозиции.');
        }

        $this->performCardDecomposition($story, $card);

        ActivityLog::record(auth()->user(), $world, 'card.decomposed', 'Декомпозиция карточки в истории «'.$story->name.'».', $story);

        if ($request->boolean('redirect_to_story')) {
            return redirect()->route('cards.show', [$world, $story])->with('success', 'Карточка декомпозирована.');
        }

        return redirect()->back()->with('success', 'Карточка декомпозирована.');
    }

    /**
     * Одна карточка → несколько карточек по абзацам (содержимое `Card::getParagraphs()`).
     *
     * @param  Story  $story  История
     * @param  Card  $card  Карточка (будет удалена)
     */
    private function performCardDecomposition(Story $story, Card $card): void
    {
        $paragraphs = $card->getParagraphs();
        if (count($paragraphs) < 2) {
            return;
        }

        $slot = $card->number;

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
    }

    public function destroy(Request $request, World $world, Card $card)
    {
        $this->authorizeCard($world, $card);

        $story = $card->story;
        ActivityLog::record(auth()->user(), $world, 'card.deleted', 'Удалена карточка в истории «'.$story->name.'».', $card);

        $card->delete();
        $story->renumberCards();

        if ($request->boolean('redirect_to_story')) {
            return redirect()->route('cards.show', [$world, $story])->with('success', 'Карточка удалена.');
        }

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
