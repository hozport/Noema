<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WorldsListDisplayController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'worlds_list_sort' => ['required', 'string', 'in:'.implode(',', [
                User::WORLDS_SORT_ALPHABET,
                User::WORLDS_SORT_CREATED_AT,
                User::WORLDS_SORT_UPDATED_AT,
            ])],
        ]);

        $request->user()->update([
            'worlds_list_sort' => $validated['worlds_list_sort'],
        ]);

        return redirect()->route('worlds.index')->with('success', 'Порядок списка миров сохранён.');
    }
}
