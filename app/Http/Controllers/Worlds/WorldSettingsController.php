<?php

namespace App\Http\Controllers\Worlds;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Worlds\World;
use App\Services\WorldReferencePointSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class WorldSettingsController extends Controller
{
    public function __construct(
        private readonly WorldReferencePointSyncService $referencePointSync
    ) {}

    public function update(Request $request, World $world): RedirectResponse
    {
        if ($world->user_id !== $request->user()->id) {
            abort(403);
        }

        if (! $world->onoff) {
            abort(404);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'annotation' => ['nullable', 'string', 'max:1000'],
            'reference_point' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'max:2048'],
            'remove_image' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return redirect()->route('worlds.dashboard', $world)
                ->withErrors($validator)
                ->withInput()
                ->with('open_world_settings', true);
        }

        $validated = $validator->validated();

        $world->name = $validated['name'];
        $world->annotation = $validated['annotation'] ?? null;
        $world->reference_point = $validated['reference_point'] ?? null;

        $removeImage = $request->boolean('remove_image');

        if ($request->hasFile('image')) {
            $world->deleteImageFile();
            $user = $request->user();
            $worldsDir = $user->getUploadsPath('worlds');
            if (! is_dir($worldsDir)) {
                mkdir($worldsDir, 0755, true);
            }
            $extension = $request->file('image')->getClientOriginalExtension() ?: 'jpg';
            $filename = Str::random(20).'.'.$extension;
            $request->file('image')->move($worldsDir, $filename);
            $world->image_path = $filename;
        } elseif ($removeImage) {
            $world->deleteImageFile();
            $world->image_path = null;
        }

        $world->save();

        $this->referencePointSync->sync($world);

        ActivityLog::record($request->user(), $world, 'world.updated', 'Обновлены настройки мира «'.$world->name.'».', $world);

        return redirect()->route('worlds.dashboard', $world)
            ->with('success', 'Параметры мира сохранены.');
    }
}
