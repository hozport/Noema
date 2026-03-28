<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
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
}
