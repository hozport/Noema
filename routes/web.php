<?php

use App\Http\Controllers\Account\NoemaSettingsController;
use App\Http\Controllers\Account\ProfileController;
use App\Http\Controllers\Account\TeamController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Bestiary\BestiaryController;
use App\Http\Controllers\Bestiary\BestiaryCreatureController;
use App\Http\Controllers\Biography\BiographiesController;
use App\Http\Controllers\Biography\BiographyEventController;
use App\Http\Controllers\Biography\BiographyProfileController;
use App\Http\Controllers\Biography\BiographyTimelineController;
use App\Http\Controllers\Cards\CardController;
use App\Http\Controllers\Cards\StoryController;
use App\Http\Controllers\Timeline\TimelineController;
use App\Http\Controllers\Timeline\TimelineEventController;
use App\Http\Controllers\Timeline\TimelineLineController;
use App\Http\Controllers\Worlds\ConnectionsBoardController;
use App\Http\Controllers\Worlds\CreateWorldController;
use App\Http\Controllers\Worlds\WorldDashboardController;
use App\Http\Controllers\Worlds\WorldMapsController;
use App\Http\Controllers\Worlds\WorldsController;
use App\Http\Controllers\Worlds\WorldSettingsController;
use Illuminate\Support\Facades\Route;

require __DIR__.'/site.php';

Route::get('/login', function () {
    return auth()->check()
        ? redirect()->route('worlds.index')
        : redirect()->route('site.home');
})->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::prefix('account')->name('account.')->group(function () {
        Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::get('/team', [TeamController::class, 'show'])->name('team');
        Route::get('/settings', [NoemaSettingsController::class, 'show'])->name('settings');
    });

    Route::get('/worlds', [WorldsController::class, 'index'])->name('worlds.index');
    Route::get('/worlds/create', [CreateWorldController::class, 'create'])->name('worlds.create');
    Route::post('/worlds', [CreateWorldController::class, 'store'])->name('worlds.store');
    Route::delete('/worlds/{world}', [WorldsController::class, 'destroy'])->name('worlds.destroy');
    Route::get('/worlds/{world}', [WorldDashboardController::class, 'show'])->name('worlds.dashboard');
    Route::put('/worlds/{world}', [WorldSettingsController::class, 'update'])->name('worlds.update');
    Route::get('/worlds/{world}/connections', [ConnectionsBoardController::class, 'show'])->name('worlds.connections');
    Route::get('/worlds/{world}/maps', [WorldMapsController::class, 'show'])->name('worlds.maps');
    Route::get('/worlds/{world}/timeline', [TimelineController::class, 'show'])->name('worlds.timeline');
    Route::post('/worlds/{world}/timeline/lines', [TimelineLineController::class, 'store'])->name('timeline.lines.store');
    Route::put('/worlds/{world}/timeline/lines/{line}', [TimelineLineController::class, 'update'])->name('timeline.lines.update');
    Route::delete('/worlds/{world}/timeline/lines/{line}', [TimelineLineController::class, 'destroy'])->name('timeline.lines.destroy');
    Route::post('/worlds/{world}/timeline/events', [TimelineEventController::class, 'store'])->name('timeline.events.store');
    Route::put('/worlds/{world}/timeline/events/{timelineEvent}', [TimelineEventController::class, 'update'])->name('timeline.events.update');
    Route::delete('/worlds/{world}/timeline/events/{timelineEvent}', [TimelineEventController::class, 'destroy'])->name('timeline.events.destroy');

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

    Route::get('/worlds/{world}/bestiary', [BestiaryController::class, 'index'])->name('bestiary.index');
    Route::get('/worlds/{world}/bestiary/pdf', [BestiaryController::class, 'pdf'])->name('bestiary.pdf');
    Route::post('/worlds/{world}/bestiary/creatures', [BestiaryCreatureController::class, 'store'])->name('bestiary.creatures.store');
    Route::put('/worlds/{world}/bestiary/creatures/{creature}', [BestiaryCreatureController::class, 'update'])->name('bestiary.creatures.update');
    Route::get('/worlds/{world}/bestiary/creatures/{creature}/pdf', [BestiaryCreatureController::class, 'pdf'])->name('bestiary.creatures.pdf');
    Route::get('/worlds/{world}/bestiary/creatures/{creature}', [BestiaryCreatureController::class, 'show'])->name('bestiary.creatures.show');

    Route::get('/worlds/{world}/biographies/pdf', [BiographiesController::class, 'pdf'])->name('biographies.pdf');
    Route::get('/worlds/{world}/biographies', [BiographiesController::class, 'index'])->name('biographies.index');
    Route::post('/worlds/{world}/biographies', [BiographyProfileController::class, 'store'])->name('biographies.store');
    Route::post('/worlds/{world}/biographies/{biography}/events', [BiographyEventController::class, 'store'])->name('biographies.events.store');
    Route::put('/worlds/{world}/biographies/{biography}/events/{biographyEvent}', [BiographyEventController::class, 'update'])->name('biographies.events.update');
    Route::delete('/worlds/{world}/biographies/{biography}/events/{biographyEvent}', [BiographyEventController::class, 'destroy'])->name('biographies.events.destroy');
    Route::post('/worlds/{world}/biographies/{biography}/timeline/create-line', [BiographyTimelineController::class, 'createLine'])->name('biographies.timeline.create-line');
    Route::delete('/worlds/{world}/biographies/{biography}/timeline/line', [BiographyTimelineController::class, 'removeLine'])->name('biographies.timeline.remove-line');
    Route::post('/worlds/{world}/biographies/{biography}/timeline/push-event', [BiographyTimelineController::class, 'pushEvent'])->name('biographies.timeline.push-event');
    Route::get('/worlds/{world}/biographies/{biography}/pdf', [BiographyProfileController::class, 'pdf'])->name('biography.pdf');
    Route::get('/worlds/{world}/biographies/{biography}', [BiographyProfileController::class, 'show'])->name('biographies.show');
    Route::put('/worlds/{world}/biographies/{biography}', [BiographyProfileController::class, 'update'])->name('biographies.update');
});
