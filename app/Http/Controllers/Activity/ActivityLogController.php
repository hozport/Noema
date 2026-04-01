<?php

namespace App\Http\Controllers\Activity;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
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
}
