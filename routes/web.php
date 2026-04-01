<?php

use App\Http\Controllers\Account\NoemaSettingsController;
use App\Http\Controllers\Account\ProfileController;
use App\Http\Controllers\Account\TeamController;
use App\Http\Controllers\Account\WorldsListDisplayController;
use App\Http\Controllers\Activity\ActivityLogController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Bestiary\BestiaryController;
use App\Http\Controllers\Bestiary\BestiaryCreatureController;
use App\Http\Controllers\Biography\BiographiesController;
use App\Http\Controllers\Biography\BiographyEventController;
use App\Http\Controllers\Biography\BiographyProfileController;
use App\Http\Controllers\Biography\BiographyTimelineController;
use App\Http\Controllers\Cards\CardController;
use App\Http\Controllers\Cards\StoryController;
use App\Http\Controllers\Faction\FactionEventController;
use App\Http\Controllers\Faction\FactionProfileController;
use App\Http\Controllers\Faction\FactionsController;
use App\Http\Controllers\Faction\FactionTimelineController;
use App\Http\Controllers\Timeline\TimelineClearController;
use App\Http\Controllers\Timeline\TimelineController;
use App\Http\Controllers\Timeline\TimelineEventController;
use App\Http\Controllers\Timeline\TimelineLineController;
use App\Http\Controllers\Worlds\ConnectionsBoardController;
use App\Http\Controllers\Worlds\ConnectionsController;
use App\Http\Controllers\Worlds\CreateWorldController;
use App\Http\Controllers\Worlds\WorldDashboardController;
use App\Http\Controllers\Worlds\WorldMapsController;
use App\Http\Controllers\Worlds\WorldsController;
use App\Http\Controllers\Worlds\WorldSettingsController;
use App\Http\Controllers\Markup\MarkupEntityController;
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
        Route::get('/activity', [ActivityLogController::class, 'account'])->name('activity');
        Route::put('/worlds-display', [WorldsListDisplayController::class, 'update'])->name('worlds-display.update');
        Route::get('/team', [TeamController::class, 'show'])->name('team');
        Route::get('/settings', [NoemaSettingsController::class, 'show'])->name('settings');
    });

    Route::get('/worlds', [WorldsController::class, 'index'])->name('worlds.index');
    Route::get('/worlds/create', [CreateWorldController::class, 'create'])->name('worlds.create');
    Route::post('/worlds', [CreateWorldController::class, 'store'])->name('worlds.store');
    Route::delete('/worlds/{world}', [WorldsController::class, 'destroy'])->name('worlds.destroy');
    Route::get('/worlds/{world}', [WorldDashboardController::class, 'show'])->name('worlds.dashboard');
    Route::get('/worlds/{world}/activity/timeline', [ActivityLogController::class, 'worldTimeline'])->name('worlds.activity.timeline');
    Route::get('/worlds/{world}/activity', [ActivityLogController::class, 'world'])->name('worlds.activity');
    Route::get('/worlds/{world}/markup/entities', [MarkupEntityController::class, 'entities'])->name('worlds.markup.entities');
    Route::post('/worlds/{world}/markup/resolve', [MarkupEntityController::class, 'resolve'])->name('worlds.markup.resolve');
    Route::put('/worlds/{world}', [WorldSettingsController::class, 'update'])->name('worlds.update');
    Route::get('/worlds/{world}/connections/timeline-lines', [ConnectionsBoardController::class, 'timelineLines'])->name('worlds.connections.data.timeline-lines');
    Route::get('/worlds/{world}/connections/timeline-lines/{line}/events', [ConnectionsBoardController::class, 'timelineLineEvents'])->name('worlds.connections.data.timeline-line-events');
    Route::get('/worlds/{world}/connections/stories', [ConnectionsBoardController::class, 'stories'])->name('worlds.connections.data.stories');
    Route::get('/worlds/{world}/connections/stories/{story}/cards', [ConnectionsBoardController::class, 'storyCards'])->name('worlds.connections.data.story-cards');
    Route::get('/worlds/{world}/connections/creatures', [ConnectionsBoardController::class, 'creatures'])->name('worlds.connections.data.creatures');
    Route::get('/worlds/{world}/connections/biographies', [ConnectionsBoardController::class, 'biographies'])->name('worlds.connections.data.biographies');
    Route::get('/worlds/{world}/connections', [ConnectionsController::class, 'index'])->name('worlds.connections');
    Route::post('/worlds/{world}/connections', [ConnectionsController::class, 'store'])->name('worlds.connections.store');
    Route::get('/worlds/{world}/connections/{connectionBoard}', [ConnectionsBoardController::class, 'show'])->name('worlds.connections.show');
    Route::post('/worlds/{world}/connections/{connectionBoard}/nodes', [ConnectionsBoardController::class, 'nodesStore'])->name('worlds.connections.nodes.store');
    Route::put('/worlds/{world}/connections/{connectionBoard}/nodes/{node}', [ConnectionsBoardController::class, 'nodesUpdate'])->name('worlds.connections.nodes.update');
    Route::delete('/worlds/{world}/connections/{connectionBoard}/nodes/{node}', [ConnectionsBoardController::class, 'nodesDestroy'])->name('worlds.connections.nodes.destroy');
    Route::post('/worlds/{world}/connections/{connectionBoard}/edges', [ConnectionsBoardController::class, 'edgesStore'])->name('worlds.connections.edges.store');
    Route::delete('/worlds/{world}/connections/{connectionBoard}/edges/{edge}', [ConnectionsBoardController::class, 'edgesDestroy'])->name('worlds.connections.edges.destroy');
    Route::get('/worlds/{world}/maps', [WorldMapsController::class, 'show'])->name('worlds.maps');
    Route::post('/worlds/{world}/maps/sprites', [WorldMapsController::class, 'storeSprite'])->name('worlds.maps.sprites.store');
    Route::put('/worlds/{world}/maps/sprites/{worldMapSprite}', [WorldMapsController::class, 'updateSprite'])->name('worlds.maps.sprites.update');
    Route::delete('/worlds/{world}/maps/sprites/{worldMapSprite}', [WorldMapsController::class, 'destroySprite'])->name('worlds.maps.sprites.destroy');
    Route::get('/worlds/{world}/timeline', [TimelineController::class, 'show'])->name('worlds.timeline');
    Route::post('/worlds/{world}/timeline/clear', [TimelineClearController::class, 'store'])->name('worlds.timeline.clear');
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
    Route::delete('/worlds/{world}/bestiary/creatures/{creature}', [BestiaryCreatureController::class, 'destroy'])->name('bestiary.creatures.destroy');
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
    Route::delete('/worlds/{world}/biographies/{biography}', [BiographyProfileController::class, 'destroy'])->name('biographies.destroy');
    Route::get('/worlds/{world}/biographies/{biography}', [BiographyProfileController::class, 'show'])->name('biographies.show');
    Route::put('/worlds/{world}/biographies/{biography}', [BiographyProfileController::class, 'update'])->name('biographies.update');

    Route::get('/worlds/{world}/factions', [FactionsController::class, 'index'])->name('factions.index');
    Route::get('/worlds/{world}/factions/pdf', [FactionsController::class, 'pdf'])->name('factions.index.pdf');
    Route::post('/worlds/{world}/factions', [FactionProfileController::class, 'store'])->name('factions.store');
    Route::post('/worlds/{world}/factions/{faction}/events', [FactionEventController::class, 'store'])->name('factions.events.store');
    Route::put('/worlds/{world}/factions/{faction}/events/{factionEvent}', [FactionEventController::class, 'update'])->name('factions.events.update');
    Route::delete('/worlds/{world}/factions/{faction}/events/{factionEvent}', [FactionEventController::class, 'destroy'])->name('factions.events.destroy');
    Route::post('/worlds/{world}/factions/{faction}/timeline/create-line', [FactionTimelineController::class, 'createLine'])->name('factions.timeline.create-line');
    Route::delete('/worlds/{world}/factions/{faction}/timeline/line', [FactionTimelineController::class, 'removeLine'])->name('factions.timeline.remove-line');
    Route::post('/worlds/{world}/factions/{faction}/timeline/push-event', [FactionTimelineController::class, 'pushEvent'])->name('factions.timeline.push-event');
    Route::get('/worlds/{world}/factions/{faction}/pdf', [FactionProfileController::class, 'pdf'])->name('factions.pdf');
    Route::get('/worlds/{world}/factions/{faction}', [FactionProfileController::class, 'show'])->name('factions.show');
    Route::put('/worlds/{world}/factions/{faction}', [FactionProfileController::class, 'update'])->name('factions.update');
    Route::delete('/worlds/{world}/factions/{faction}', [FactionProfileController::class, 'destroy'])->name('factions.destroy');
});
