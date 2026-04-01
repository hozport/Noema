<?php

namespace App\Http\Controllers\Worlds;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Worlds\World;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConnectionsController extends Controller
{
    public function index(Request $request, World $world): View
    {
        $this->authorizeWorld($request, $world);

        $boards = $world->connectionBoards()->orderByDesc('updated_at')->orderByDesc('id')->get();

        return view('connections.index', compact('world', 'boards'));
    }

    public function store(Request $request, World $world): RedirectResponse
    {
        $this->authorizeWorld($request, $world);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $board = $world->connectionBoards()->create([
            'name' => trim($validated['name']),
        ]);

        ActivityLog::record($request->user(), $world, 'connections.board.created', 'Создана доска связей «'.$board->name.'».', $board);

        return redirect()->route('worlds.connections.show', [$world, $board]);
    }

    private function authorizeWorld(Request $request, World $world): void
    {
        if ($world->user_id !== $request->user()->id) {
            abort(403);
        }

        if (! $world->onoff) {
            abort(404);
        }
    }
}
