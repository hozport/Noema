<?php

namespace App\Http\Controllers\Worlds;

use App\Http\Controllers\Controller;
use App\Models\Worlds\World;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorldMapsController extends Controller
{
    /**
     * Карты мира: географическое полотно и привязки (в разработке).
     */
    public function show(Request $request, World $world): View
    {
        if ($world->user_id !== $request->user()->id) {
            abort(403);
        }

        if (! $world->onoff) {
            abort(404);
        }

        return view('maps.show', compact('world'));
    }
}
