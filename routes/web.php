<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Cards\CardController;
use App\Http\Controllers\Cards\StoryController;
use App\Http\Controllers\Worlds\CreateWorldController;
use App\Http\Controllers\Worlds\WorldDashboardController;
use App\Http\Controllers\Worlds\WorldsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('worlds.index') : redirect()->route('login');
})->name('home');

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/worlds', [WorldsController::class, 'index'])->name('worlds.index');
    Route::get('/worlds/create', [CreateWorldController::class, 'create'])->name('worlds.create');
    Route::post('/worlds', [CreateWorldController::class, 'store'])->name('worlds.store');
    Route::delete('/worlds/{world}', [WorldsController::class, 'destroy'])->name('worlds.destroy');
    Route::get('/worlds/{world}', [WorldDashboardController::class, 'show'])->name('worlds.dashboard');

    Route::get('/worlds/{world}/cards', [StoryController::class, 'index'])->name('cards.index');
    Route::post('/worlds/{world}/cards/stories', [StoryController::class, 'store'])->name('cards.stories.store');
    Route::get('/worlds/{world}/cards/stories/{story}', [StoryController::class, 'show'])->name('cards.show');
    Route::put('/worlds/{world}/cards/stories/{story}', [StoryController::class, 'update'])->name('cards.stories.update');
    Route::get('/worlds/{world}/cards/stories/{story}/pdf', [StoryController::class, 'pdf'])->name('cards.stories.pdf');
    Route::post('/worlds/{world}/cards/stories/{story}/reorder', [CardController::class, 'reorder'])->name('cards.reorder');
    Route::put('/worlds/{world}/cards/{card}', [CardController::class, 'update'])->name('cards.update');
    Route::delete('/worlds/{world}/cards/{card}', [CardController::class, 'destroy'])->name('cards.destroy');
    Route::post('/worlds/{world}/cards/{card}/highlight', [CardController::class, 'highlight'])->name('cards.highlight');
    Route::post('/worlds/{world}/cards/{card}/decompose', [CardController::class, 'decompose'])->name('cards.decompose');
});
