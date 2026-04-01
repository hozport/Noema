<?php

namespace App\Http\Controllers\Worlds;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Models\Worlds\World;
use Illuminate\Http\Request;

class WorldsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $sort = $user->worlds_list_sort ?? User::WORLDS_SORT_UPDATED_AT;

        $query = $user->worlds()->active()->with([]);

        match ($sort) {
            User::WORLDS_SORT_ALPHABET => $query->orderBy('name'),
            User::WORLDS_SORT_CREATED_AT => $query->orderByDesc('created_at'),
            User::WORLDS_SORT_UPDATED_AT => $query->orderByDesc('updated_at'),
            default => $query->orderByDesc('updated_at'),
        };

        $worlds = $query->get();

        return view('worlds.index', [
            'worlds' => $worlds,
            'worldsListSort' => $sort,
        ]);
    }

    public function destroy(Request $request, World $world)
    {
        if ($world->user_id !== $request->user()->id) {
            abort(403);
        }

        if (! $world->onoff) {
            abort(404);
        }

        $name = $world->name;
        ActivityLog::record($request->user(), $world, 'world.hidden', 'Мир «'.$name.'» скрыт из списка.', $world);

        $world->update(['onoff' => false]);

        return redirect()->route('worlds.index')->with('success', 'Мир скрыт из списка.');
    }
}
