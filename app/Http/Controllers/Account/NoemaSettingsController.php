<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Worlds\WorldMap;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NoemaSettingsController extends Controller
{
    /**
     * Общие настройки интерфейса Noema (в разработке).
     */
    public function show(): View
    {
        return view('account.settings');
    }

    /**
     * Сохранение размеров новой карты по умолчанию на уровне аккаунта (новые миры и подсказка).
     *
     * @param  Request  $request  HTTP-запрос
     */
    public function updateMapsDefaults(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'maps_default_width' => ['required', 'integer', 'min:'.WorldMap::MIN_SIDE, 'max:'.WorldMap::MAX_SIDE],
            'maps_default_height' => ['required', 'integer', 'min:'.WorldMap::MIN_SIDE, 'max:'.WorldMap::MAX_SIDE],
        ]);

        $user = $request->user();
        $user->maps_default_width = (int) $validated['maps_default_width'];
        $user->maps_default_height = (int) $validated['maps_default_height'];
        $user->save();

        return redirect()
            ->route('account.settings')
            ->with('success', 'Размеры карт по умолчанию сохранены.');
    }
}
