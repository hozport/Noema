<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class TeamController extends Controller
{
    /**
     * Команда: доступ помощников и прав (в разработке).
     */
    public function show(): View
    {
        return view('account.team');
    }
}
