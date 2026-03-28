<?php

namespace App\Http\Controllers\Worlds;

use App\Http\Controllers\Controller;
use App\Services\TimelineBootstrapService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CreateWorldController extends Controller
{
    public function create()
    {
        return view('worlds.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'reference_point' => ['nullable', 'string', 'max:255'],
            'annotation' => ['nullable', 'string', 'max:1000'],
            'image' => ['nullable', 'image', 'max:2048'],
        ]);

        $user = $request->user();
        $imagePath = null;

        if ($request->hasFile('image')) {
            $worldsDir = $user->getUploadsPath('worlds');
            if (! is_dir($worldsDir)) {
                mkdir($worldsDir, 0755, true);
            }
            $extension = $request->file('image')->getClientOriginalExtension();
            $filename = Str::random(20).'.'.$extension;
            $request->file('image')->move($worldsDir, $filename);
            $imagePath = $filename;
        }

        $world = $user->worlds()->create([
            'name' => $validated['name'],
            'reference_point' => $validated['reference_point'] ?? null,
            'annotation' => $validated['annotation'] ?? null,
            'image_path' => $imagePath,
        ]);

        TimelineBootstrapService::bootstrap($world);

        return redirect()->route('worlds.dashboard', $world);
    }
}
