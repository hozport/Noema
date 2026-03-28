<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Профиль пользователя: отображаемое имя, биография, аватар.
     */
    public function show(): View
    {
        return view('account.profile', [
            'user' => auth()->user(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'display_name' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:5000'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ]);

        $user->display_name = $validated['display_name'] ?? null;
        $user->bio = $validated['bio'] ?? null;

        if ($request->hasFile('avatar')) {
            $dir = $user->getUploadsPath('profile');
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            if ($user->avatar_path) {
                $old = $user->getUploadsPath($user->avatar_path);
                if (is_file($old)) {
                    @unlink($old);
                }
            }
            $extension = $request->file('avatar')->getClientOriginalExtension() ?: 'jpg';
            $filename = 'avatar.'.$extension;
            $request->file('avatar')->move($dir, $filename);
            $user->avatar_path = 'profile/'.$filename;
        }

        $user->save();

        return redirect()->route('account.profile')->with('success', 'Профиль сохранён.');
    }
}
