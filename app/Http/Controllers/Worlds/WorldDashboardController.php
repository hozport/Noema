<?php

namespace App\Http\Controllers\Worlds;

use App\Http\Controllers\Controller;
use App\Models\Worlds\World;
use Illuminate\Http\Request;

class WorldDashboardController extends Controller
{
    public function show(Request $request, World $world)
    {
        if ($world->user_id !== $request->user()->id) {
            abort(403);
        }

        return view('dashboard.show', compact('world'));
    }
}
