<?php

namespace App\Http\Controllers\Worlds;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WorldsController extends Controller
{
    public function index(Request $request)
    {
        $worlds = $request->user()->worlds()->latest()->get();

        return view('worlds.index', compact('worlds'));
    }
}
