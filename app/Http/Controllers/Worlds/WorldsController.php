<?php

namespace App\Http\Controllers\Worlds;

use App\Http\Controllers\Controller;
use App\Models\Worlds\World;
use Illuminate\Http\Request;

class WorldsController extends Controller
{
    public function index(Request $request)
    {
        $worlds = $request->user()->worlds()->active()->latest()->get();

        return view('worlds.index', compact('worlds'));
    }

    public function destroy(Request $request, World $world)
    {
        if ($world->user_id !== $request->user()->id) {
            abort(403);
        }

        if (! $world->onoff) {
            abort(404);
        }

        $world->update(['onoff' => false]);

        return redirect()->route('worlds.index')->with('success', 'Мир скрыт из списка.');
    }
}
