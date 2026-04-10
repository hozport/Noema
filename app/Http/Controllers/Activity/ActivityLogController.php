<?php

namespace App\Http\Controllers\Activity;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Cards\Card;
use App\Models\Cards\Story;
use App\Models\Worlds\World;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function account(Request $request): View
    {
        $logs = ActivityLog::query()
            ->where('owner_user_id', $request->user()->id)
            ->with(['actor', 'world'])
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('activity.index', [
            'scope' => 'account',
            'world' => null,
            'logs' => $logs,
        ]);
    }

    public function world(Request $request, World $world): View
    {
        if ($world->user_id !== $request->user()->id) {
            abort(403);
        }

        if (! $world->onoff) {
            abort(404);
        }

        $logs = ActivityLog::query()
            ->where('world_id', $world->id)
            ->with('actor')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('activity.index', [
            'scope' => 'world',
            'world' => $world,
            'logs' => $logs,
        ]);
    }

    public function cardsModule(Request $request, World $world): View
    {
        if ($world->user_id !== $request->user()->id) {
            abort(403);
        }

        if (! $world->onoff) {
            abort(404);
        }

        $logs = ActivityLog::query()
            ->where('world_id', $world->id)
            ->where(function ($q) {
                $q->where('subject_type', Story::class)
                    ->orWhere('subject_type', Card::class);
            })
            ->with('actor')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('activity.index', [
            'scope' => 'cards_module',
            'world' => $world,
            'logs' => $logs,
        ]);
    }

    /**
     * Журнал только действий, связанных с таймлайном (линии, события, выкладка из биографий/фракций).
     */
    public function worldTimeline(Request $request, World $world): View
    {
        if ($world->user_id !== $request->user()->id) {
            abort(403);
        }

        if (! $world->onoff) {
            abort(404);
        }

        $logs = ActivityLog::query()
            ->where('world_id', $world->id)
            ->where(function ($q) {
                $q->where('action', 'like', 'timeline.%')
                    ->orWhere('action', 'like', 'biography.timeline.%')
                    ->orWhere('action', 'like', 'faction.timeline.%');
            })
            ->with('actor')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('activity.index', [
            'scope' => 'world_timeline',
            'world' => $world,
            'logs' => $logs,
        ]);
    }

    /**
     * Журнал только действий, связанных с данной историей и её карточками.
     */
    public function story(Request $request, World $world, Story $story): View
    {
        if ($world->user_id !== $request->user()->id) {
            abort(403);
        }

        if (! $world->onoff) {
            abort(404);
        }

        if ($story->world_id !== $world->id) {
            abort(404);
        }

        $storyId = $story->id;

        $logs = ActivityLog::query()
            ->where('world_id', $world->id)
            ->where(function ($q) use ($storyId) {
                $q->where(function ($q2) use ($storyId) {
                    $q2->where('subject_type', Story::class)
                        ->where('subject_id', $storyId);
                })->orWhere(function ($q2) use ($storyId) {
                    $q2->where('subject_type', Card::class)
                        ->whereExists(function ($sub) use ($storyId) {
                            $sub->selectRaw('1')
                                ->from('cards')
                                ->whereColumn('cards.id', 'activity_logs.subject_id')
                                ->where('cards.story_id', $storyId);
                        });
                });
            })
            ->with('actor')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('activity.index', [
            'scope' => 'story',
            'world' => $world,
            'story' => $story,
            'logs' => $logs,
        ]);
    }

    /**
     * Журнал только действий, у которых в качестве субъекта указана эта карточка.
     */
    public function card(Request $request, World $world, Story $story, Card $card): View
    {
        if ($world->user_id !== $request->user()->id) {
            abort(403);
        }

        if (! $world->onoff) {
            abort(404);
        }

        if ($story->world_id !== $world->id || (int) $card->story_id !== (int) $story->id) {
            abort(404);
        }

        $logs = ActivityLog::query()
            ->where('world_id', $world->id)
            ->where('subject_type', Card::class)
            ->where('subject_id', $card->id)
            ->with('actor')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('activity.index', [
            'scope' => 'card',
            'world' => $world,
            'story' => $story,
            'card' => $card,
            'logs' => $logs,
        ]);
    }
}
