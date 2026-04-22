<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WorldsListDisplayController extends Controller
{
    /**
     * Сохраняет порядок карточек на странице «Мои миры» (доступно и с /account/settings).
     *
     * @param  Request  $request  HTTP-запрос
     */
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

        return back()->with('success', 'Порядок списка миров сохранён.');
    }
}
