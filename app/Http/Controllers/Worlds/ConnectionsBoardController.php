<?php

namespace App\Http\Controllers\Worlds;

use App\Http\Controllers\Controller;
use App\Models\Worlds\World;
use Illuminate\Http\Request;

class ConnectionsBoardController extends Controller
{
    /**
     * Доска связей: единое полотно сущностей мира и связей между ними (в разработке).
     */
    public function show(Request $request, World $world)
    {
        if ($world->user_id !== $request->user()->id) {
            abort(403);
        }

        if (! $world->onoff) {
            abort(404);
        }

        return view('connections.board', compact('world'));
    }
}
