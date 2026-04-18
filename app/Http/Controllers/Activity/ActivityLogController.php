<?php

namespace App\Http\Controllers\Activity;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Bestiary\Creature;
use App\Models\Biography\Biography;
use App\Models\Biography\BiographyEvent;
use App\Models\Cards\Card;
use App\Models\Cards\Story;
use App\Models\Faction\Faction;
use App\Models\Faction\FactionEvent;
use App\Models\Timeline\TimelineLine;
use App\Models\Worlds\ConnectionBoard;
use App\Models\Worlds\ConnectionBoardEdge;
use App\Models\Worlds\ConnectionBoardNode;
use App\Models\Worlds\World;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Просмотр и очистка журналов активности по областям: аккаунт, мир, модули и отдельные сущности.
 *
 * Каждый метод списка строит тот же фильтр, что и соответствующий метод очистки, чтобы «очистить журнал»
 * удалял ровно те записи, которые видны на странице.
 */
class ActivityLogController extends Controller
{
    public function account(Request $request): View
    {
        $logs = ActivityLog::query()
            ->where('owner_user_id', $request->user()->id)
            ->with(['actor', 'world'])
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('activity.index', [
            'scope' => 'account',
            'world' => null,
            'logs' => $logs,
        ]);
    }

    public function world(Request $request, World $world): View
    {
        if ($world->user_id !== $request->user()->id) {
            abort(403);
        }

        if (! $world->onoff) {
            abort(404);
        }

        $logs = ActivityLog::query()
            ->where('world_id', $world->id)
            ->with('actor')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('activity.index', [
            'scope' => 'world',
            'world' => $world,
            'logs' => $logs,
        ]);
    }

    public function cardsModule(Request $request, World $world): View
    {
        if ($world->user_id !== $request->user()->id) {
            abort(403);
        }

        if (! $world->onoff) {
            abort(404);
        }

        $logs = ActivityLog::query()
            ->where('world_id', $world->id)
            ->where(function ($q) {
                $q->where('subject_type', Story::class)
                    ->orWhere('subject_type', Card::class);
            })
            ->with('actor')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('activity.index', [
            'scope' => 'cards_module',
            'world' => $world,
            'logs' => $logs,
        ]);
    }

    /**
     * Журнал только действий, связанных с таймлайном (линии, события, выкладка из биографий/фракций).
     *
     * @param  Request  $request  HTTP-запрос
     * @param  World  $world  Мир
     */
    public function worldTimeline(Request $request, World $world): View
    {
        if ($world->user_id !== $request->user()->id) {
            abort(403);
        }

        if (! $world->onoff) {
            abort(404);
        }

        $logs = ActivityLog::query()
            ->where('world_id', $world->id)
            ->where(function ($q) {
                $q->where('action', 'like', 'timeline.%')
                    ->orWhere('action', 'like', 'biography.timeline.%')
                    ->orWhere('action', 'like', 'faction.timeline.%');
            })
            ->with('actor')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('activity.index', [
            'scope' => 'world_timeline',
            'world' => $world,
            'logs' => $logs,
        ]);
    }

    /**
     * Журнал только действий, связанных с данной историей и её карточками.
     *
     * @param  Request  $request  HTTP-запрос
     * @param  World  $world  Мир
     * @param  Story  $story  История
     */
    public function story(Request $request, World $world, Story $story): View
    {
        if ($world->user_id !== $request->user()->id) {
            abort(403);
        }

        if (! $world->onoff) {
            abort(404);
        }

        if ($story->world_id !== $world->id) {
            abort(404);
        }

        $storyId = $story->id;

        $logs = ActivityLog::query()
            ->where('world_id', $world->id)
            ->where(function ($q) use ($storyId) {
                $q->where(function ($q2) use ($storyId) {
                    $q2->where('subject_type', Story::class)
                        ->where('subject_id', $storyId);
                })->orWhere(function ($q2) use ($storyId) {
                    $q2->where('subject_type', Card::class)
                        ->whereExists(function ($sub) use ($storyId) {
                            $sub->selectRaw('1')
                                ->from('cards')
                                ->whereColumn('cards.id', 'activity_logs.subject_id')
                                ->where('cards.story_id', $storyId);
                        });
                });
            })
            ->with('actor')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('activity.index', [
            'scope' => 'story',
            'world' => $world,
            'story' => $story,
            'logs' => $logs,
        ]);
    }

    /**
     * Журнал только действий, у которых в качестве субъекта указана эта карточка.
     *
     * @param  Request  $request  HTTP-запрос
     * @param  World  $world  Мир
     * @param  Story  $story  История
     * @param  Card  $card  Карточка
     */
    public function card(Request $request, World $world, Story $story, Card $card): View
    {
        if ($world->user_id !== $request->user()->id) {
            abort(403);
        }

        if (! $world->onoff) {
            abort(404);
        }

        if ($story->world_id !== $world->id || (int) $card->story_id !== (int) $story->id) {
            abort(404);
        }

        $logs = ActivityLog::query()
            ->where('world_id', $world->id)
            ->where('subject_type', Card::class)
            ->where('subject_id', $card->id)
            ->with('actor')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('activity.index', [
            'scope' => 'card',
            'world' => $world,
            'story' => $story,
            'card' => $card,
            'logs' => $logs,
        ]);
    }

    /**
     * Журнал модуля «Биографии»: все действия с префиксом действия biography.*.
     *
     * @param  Request  $request  HTTP-запрос
     * @param  World  $world  Мир
     */
    public function biographiesModule(Request $request, World $world): View
    {
        $this->assertWorldOwnerAndVisible($request, $world);

        $logs = ActivityLog::query()
            ->where('world_id', $world->id)
            ->where('action', 'like', 'biography.%')
            ->with('actor')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('activity.index', [
            'scope' => 'biographies_module',
            'world' => $world,
            'logs' => $logs,
        ]);
    }

    /**
     * Журнал одной биографии: профиль, события и линии таймлайна, порождённые этой биографией.
     *
     * @param  Request  $request  HTTP-запрос
     * @param  World  $world  Мир
     * @param  Biography  $biography  Биография
     */
    public function biography(Request $request, World $world, Biography $biography): View
    {
        $this->assertWorldOwnerAndVisible($request, $world);
        $this->assertBiographyInWorld($world, $biography);

        $logs = $this->biographyActivityQuery($world, $biography)
            ->with('actor')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('activity.index', [
            'scope' => 'biography',
            'world' => $world,
            'biography' => $biography,
            'logs' => $logs,
        ]);
    }

    /**
     * Журнал модуля «Фракции»: действия с префиксом faction.*.
     *
     * @param  Request  $request  HTTP-запрос
     * @param  World  $world  Мир
     */
    public function factionsModule(Request $request, World $world): View
    {
        $this->assertWorldOwnerAndVisible($request, $world);

        $logs = ActivityLog::query()
            ->where('world_id', $world->id)
            ->where('action', 'like', 'faction.%')
            ->with('actor')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('activity.index', [
            'scope' => 'factions_module',
            'world' => $world,
            'logs' => $logs,
        ]);
    }

    /**
     * Журнал одной фракции: профиль, события и линии таймлайна, связанные с фракцией.
     *
     * @param  Request  $request  HTTP-запрос
     * @param  World  $world  Мир
     * @param  Faction  $faction  Фракция
     */
    public function faction(Request $request, World $world, Faction $faction): View
    {
        $this->assertWorldOwnerAndVisible($request, $world);
        $this->assertFactionInWorld($world, $faction);

        $logs = $this->factionActivityQuery($world, $faction)
            ->with('actor')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('activity.index', [
            'scope' => 'faction',
            'world' => $world,
            'faction' => $faction,
            'logs' => $logs,
        ]);
    }

    /**
     * Журнал модуля «Бестиарий»: действия с префиксом bestiary.*.
     *
     * @param  Request  $request  HTTP-запрос
     * @param  World  $world  Мир
     */
    public function bestiaryModule(Request $request, World $world): View
    {
        $this->assertWorldOwnerAndVisible($request, $world);

        $logs = ActivityLog::query()
            ->where('world_id', $world->id)
            ->where('action', 'like', 'bestiary.%')
            ->with('actor')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('activity.index', [
            'scope' => 'bestiary_module',
            'world' => $world,
            'logs' => $logs,
        ]);
    }

    /**
     * Журнал существа бестиария: записи, где объектом изменения является это существо.
     *
     * @param  Request  $request  HTTP-запрос
     * @param  World  $world  Мир
     * @param  Creature  $creature  Существо
     */
    public function creature(Request $request, World $world, Creature $creature): View
    {
        $this->assertWorldOwnerAndVisible($request, $world);
        $this->assertCreatureInWorld($world, $creature);

        $logs = ActivityLog::query()
            ->where('world_id', $world->id)
            ->where('subject_type', Creature::class)
            ->where('subject_id', $creature->id)
            ->with('actor')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('activity.index', [
            'scope' => 'creature',
            'world' => $world,
            'creature' => $creature,
            'logs' => $logs,
        ]);
    }

    /**
     * Журнал модуля «Связи»: действия с префиксом connections.*.
     *
     * @param  Request  $request  HTTP-запрос
     * @param  World  $world  Мир
     */
    public function connectionsModule(Request $request, World $world): View
    {
        $this->assertWorldOwnerAndVisible($request, $world);

        $logs = ActivityLog::query()
            ->where('world_id', $world->id)
            ->where('action', 'like', 'connections.%')
            ->with('actor')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('activity.index', [
            'scope' => 'connections_module',
            'world' => $world,
            'logs' => $logs,
        ]);
    }

    /**
     * Журнал одной доски связей: создание доски, блоки и рёбра на ней.
     *
     * @param  Request  $request  HTTP-запрос
     * @param  World  $world  Мир
     * @param  ConnectionBoard  $connectionBoard  Доска
     */
    public function connectionBoard(Request $request, World $world, ConnectionBoard $connectionBoard): View
    {
        $this->assertWorldOwnerAndVisible($request, $world);
        $this->assertConnectionBoardInWorld($world, $connectionBoard);

        $logs = $this->connectionBoardActivityQuery($world, $connectionBoard)
            ->with('actor')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('activity.index', [
            'scope' => 'connection_board',
            'world' => $world,
            'connectionBoard' => $connectionBoard,
            'logs' => $logs,
        ]);
    }

    /**
     * Журнал модуля «Карты»: действия с префиксом map.* (спрайты на карте мира).
     *
     * @param  Request  $request  HTTP-запрос
     * @param  World  $world  Мир
     */
    public function mapsModule(Request $request, World $world): View
    {
        $this->assertWorldOwnerAndVisible($request, $world);

        $logs = ActivityLog::query()
            ->where('world_id', $world->id)
            ->where('action', 'like', 'map.%')
            ->with('actor')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('activity.index', [
            'scope' => 'maps_module',
            'world' => $world,
            'logs' => $logs,
        ]);
    }

    /**
     * Удаляет все записи общего журнала аккаунта.
     *
     * @param  Request  $request  HTTP-запрос
     */
    public function clearAccount(Request $request): RedirectResponse
    {
        ActivityLog::query()->where('owner_user_id', $request->user()->id)->delete();

        return redirect()
            ->route('account.activity')
            ->with('success', 'Журнал очищен.');
    }

    /**
     * Удаляет все записи журнала мира.
     *
     * @param  Request  $request  HTTP-запрос
     * @param  World  $world  Мир
     */
    public function clearWorld(Request $request, World $world): RedirectResponse
    {
        if ($world->user_id !== $request->user()->id) {
            abort(403);
        }
        if (! $world->onoff) {
            abort(404);
        }

        ActivityLog::query()->where('world_id', $world->id)->delete();

        return redirect()
            ->route('worlds.activity', $world)
            ->with('success', 'Журнал очищен.');
    }

    /**
     * Удаляет записи журнала модуля «Карточки» для мира.
     *
     * @param  Request  $request  HTTP-запрос
     * @param  World  $world  Мир
     */
    public function clearCardsModule(Request $request, World $world): RedirectResponse
    {
        if ($world->user_id !== $request->user()->id) {
            abort(403);
        }
        if (! $world->onoff) {
            abort(404);
        }

        ActivityLog::query()
            ->where('world_id', $world->id)
            ->where(function ($q) {
                $q->where('subject_type', Story::class)
                    ->orWhere('subject_type', Card::class);
            })
            ->delete();

        return redirect()
            ->route('cards.module.activity', $world)
            ->with('success', 'Журнал очищен.');
    }

    /**
     * Удаляет записи журнала таймлайна для мира.
     *
     * @param  Request  $request  HTTP-запрос
     * @param  World  $world  Мир
     */
    public function clearWorldTimeline(Request $request, World $world): RedirectResponse
    {
        if ($world->user_id !== $request->user()->id) {
            abort(403);
        }
        if (! $world->onoff) {
            abort(404);
        }

        ActivityLog::query()
            ->where('world_id', $world->id)
            ->where(function ($q) {
                $q->where('action', 'like', 'timeline.%')
                    ->orWhere('action', 'like', 'biography.timeline.%')
                    ->orWhere('action', 'like', 'faction.timeline.%');
            })
            ->delete();

        return redirect()
            ->route('worlds.activity.timeline', $world)
            ->with('success', 'Журнал очищен.');
    }

    /**
     * Удаляет записи журнала истории и её карточек.
     *
     * @param  Request  $request  HTTP-запрос
     * @param  World  $world  Мир
     * @param  Story  $story  История
     */
    public function clearStory(Request $request, World $world, Story $story): RedirectResponse
    {
        if ($world->user_id !== $request->user()->id) {
            abort(403);
        }
        if (! $world->onoff) {
            abort(404);
        }
        if ($story->world_id !== $world->id) {
            abort(404);
        }

        $storyId = $story->id;

        ActivityLog::query()
            ->where('world_id', $world->id)
            ->where(function ($q) use ($storyId) {
                $q->where(function ($q2) use ($storyId) {
                    $q2->where('subject_type', Story::class)
                        ->where('subject_id', $storyId);
                })->orWhere(function ($q2) use ($storyId) {
                    $q2->where('subject_type', Card::class)
                        ->whereExists(function ($sub) use ($storyId) {
                            $sub->selectRaw('1')
                                ->from('cards')
                                ->whereColumn('cards.id', 'activity_logs.subject_id')
                                ->where('cards.story_id', $storyId);
                        });
                });
            })
            ->delete();

        return redirect()
            ->route('cards.stories.activity', [$world, $story])
            ->with('success', 'Журнал очищен.');
    }

    /**
     * Удаляет записи журнала, относящиеся к карточке.
     *
     * @param  Request  $request  HTTP-запрос
     * @param  World  $world  Мир
     * @param  Story  $story  История
     * @param  Card  $card  Карточка
     */
    public function clearCard(Request $request, World $world, Story $story, Card $card): RedirectResponse
    {
        if ($world->user_id !== $request->user()->id) {
            abort(403);
        }
        if (! $world->onoff) {
            abort(404);
        }
        if ($story->world_id !== $world->id || (int) $card->story_id !== (int) $story->id) {
            abort(404);
        }

        ActivityLog::query()
            ->where('world_id', $world->id)
            ->where('subject_type', Card::class)
            ->where('subject_id', $card->id)
            ->delete();

        return redirect()
            ->route('cards.card.activity', [$world, $story, $card])
            ->with('success', 'Журнал очищен.');
    }

    /**
     * Очищает журнал модуля «Биографии» (префикс biography.*).
     *
     * @param  Request  $request  HTTP-запрос
     * @param  World  $world  Мир
     */
    public function clearBiographiesModule(Request $request, World $world): RedirectResponse
    {
        $this->assertWorldOwnerAndVisible($request, $world);

        ActivityLog::query()
            ->where('world_id', $world->id)
            ->where('action', 'like', 'biography.%')
            ->delete();

        return redirect()
            ->route('biographies.module.activity', $world)
            ->with('success', 'Журнал очищен.');
    }

    /**
     * Очищает журнал одной биографии (тот же фильтр, что и biography()).
     *
     * @param  Request  $request  HTTP-запрос
     * @param  World  $world  Мир
     * @param  Biography  $biography  Биография
     */
    public function clearBiography(Request $request, World $world, Biography $biography): RedirectResponse
    {
        $this->assertWorldOwnerAndVisible($request, $world);
        $this->assertBiographyInWorld($world, $biography);

        $this->biographyActivityQuery($world, $biography)->delete();

        return redirect()
            ->route('biography.activity', [$world, $biography])
            ->with('success', 'Журнал очищен.');
    }

    /**
     * Очищает журнал модуля «Фракции».
     *
     * @param  Request  $request  HTTP-запрос
     * @param  World  $world  Мир
     */
    public function clearFactionsModule(Request $request, World $world): RedirectResponse
    {
        $this->assertWorldOwnerAndVisible($request, $world);

        ActivityLog::query()
            ->where('world_id', $world->id)
            ->where('action', 'like', 'faction.%')
            ->delete();

        return redirect()
            ->route('factions.module.activity', $world)
            ->with('success', 'Журнал очищен.');
    }

    /**
     * Очищает журнал одной фракции.
     *
     * @param  Request  $request  HTTP-запрос
     * @param  World  $world  Мир
     * @param  Faction  $faction  Фракция
     */
    public function clearFaction(Request $request, World $world, Faction $faction): RedirectResponse
    {
        $this->assertWorldOwnerAndVisible($request, $world);
        $this->assertFactionInWorld($world, $faction);

        $this->factionActivityQuery($world, $faction)->delete();

        return redirect()
            ->route('faction.activity', [$world, $faction])
            ->with('success', 'Журнал очищен.');
    }

    /**
     * Очищает журнал модуля «Бестиарий».
     *
     * @param  Request  $request  HTTP-запрос
     * @param  World  $world  Мир
     */
    public function clearBestiaryModule(Request $request, World $world): RedirectResponse
    {
        $this->assertWorldOwnerAndVisible($request, $world);

        ActivityLog::query()
            ->where('world_id', $world->id)
            ->where('action', 'like', 'bestiary.%')
            ->delete();

        return redirect()
            ->route('bestiary.module.activity', $world)
            ->with('success', 'Журнал очищен.');
    }

    /**
     * Очищает журнал существа бестиария.
     *
     * @param  Request  $request  HTTP-запрос
     * @param  World  $world  Мир
     * @param  Creature  $creature  Существо
     */
    public function clearCreature(Request $request, World $world, Creature $creature): RedirectResponse
    {
        $this->assertWorldOwnerAndVisible($request, $world);
        $this->assertCreatureInWorld($world, $creature);

        ActivityLog::query()
            ->where('world_id', $world->id)
            ->where('subject_type', Creature::class)
            ->where('subject_id', $creature->id)
            ->delete();

        return redirect()
            ->route('bestiary.creature.activity', [$world, $creature])
            ->with('success', 'Журнал очищен.');
    }

    /**
     * Очищает журнал модуля «Связи».
     *
     * @param  Request  $request  HTTP-запрос
     * @param  World  $world  Мир
     */
    public function clearConnectionsModule(Request $request, World $world): RedirectResponse
    {
        $this->assertWorldOwnerAndVisible($request, $world);

        ActivityLog::query()
            ->where('world_id', $world->id)
            ->where('action', 'like', 'connections.%')
            ->delete();

        return redirect()
            ->route('connections.module.activity', $world)
            ->with('success', 'Журнал очищен.');
    }

    /**
     * Очищает журнал доски связей.
     *
     * @param  Request  $request  HTTP-запрос
     * @param  World  $world  Мир
     * @param  ConnectionBoard  $connectionBoard  Доска
     */
    public function clearConnectionBoard(Request $request, World $world, ConnectionBoard $connectionBoard): RedirectResponse
    {
        $this->assertWorldOwnerAndVisible($request, $world);
        $this->assertConnectionBoardInWorld($world, $connectionBoard);

        $this->connectionBoardActivityQuery($world, $connectionBoard)->delete();

        return redirect()
            ->route('connections.board.activity', [$world, $connectionBoard])
            ->with('success', 'Журнал очищен.');
    }

    /**
     * Очищает журнал модуля «Карты».
     *
     * @param  Request  $request  HTTP-запрос
     * @param  World  $world  Мир
     */
    public function clearMapsModule(Request $request, World $world): RedirectResponse
    {
        $this->assertWorldOwnerAndVisible($request, $world);

        ActivityLog::query()
            ->where('world_id', $world->id)
            ->where('action', 'like', 'map.%')
            ->delete();

        return redirect()
            ->route('maps.module.activity', $world)
            ->with('success', 'Журнал очищен.');
    }

    /**
     * Проверяет, что мир принадлежит пользователю и не скрыт.
     *
     * @param  Request  $request  HTTP-запрос
     * @param  World  $world  Мир
     */
    private function assertWorldOwnerAndVisible(Request $request, World $world): void
    {
        if ($world->user_id !== $request->user()->id) {
            abort(403);
        }
        if (! $world->onoff) {
            abort(404);
        }
    }

    /**
     * Проверяет принадлежность биографии миру.
     *
     * @param  World  $world  Мир
     * @param  Biography  $biography  Биография
     */
    private function assertBiographyInWorld(World $world, Biography $biography): void
    {
        if ((int) $biography->world_id !== (int) $world->id) {
            abort(404);
        }
    }

    /**
     * Проверяет принадлежность фракции миру.
     *
     * @param  World  $world  Мир
     * @param  Faction  $faction  Фракция
     */
    private function assertFactionInWorld(World $world, Faction $faction): void
    {
        if ((int) $faction->world_id !== (int) $world->id) {
            abort(404);
        }
    }

    /**
     * Проверяет принадлежность существа миру.
     *
     * @param  World  $world  Мир
     * @param  Creature  $creature  Существо
     */
    private function assertCreatureInWorld(World $world, Creature $creature): void
    {
        if ((int) $creature->world_id !== (int) $world->id) {
            abort(404);
        }
    }

    /**
     * Проверяет принадлежность доски связей миру.
     *
     * @param  World  $world  Мир
     * @param  ConnectionBoard  $connectionBoard  Доска
     */
    private function assertConnectionBoardInWorld(World $world, ConnectionBoard $connectionBoard): void
    {
        if ((int) $connectionBoard->world_id !== (int) $world->id) {
            abort(404);
        }
    }

    /**
     * Базовый запрос журнала одной биографии (без сортировки и пагинации).
     *
     * @param  World  $world  Мир
     * @param  Biography  $biography  Биография
     * @return Builder<ActivityLog>
     */
    private function biographyActivityQuery(World $world, Biography $biography): Builder
    {
        $bioId = $biography->id;

        return ActivityLog::query()
            ->where('world_id', $world->id)
            ->where(function ($q) use ($bioId) {
                $q->where(function ($q2) use ($bioId) {
                    $q2->where('subject_type', Biography::class)
                        ->where('subject_id', $bioId);
                })->orWhere(function ($q2) use ($bioId) {
                    $q2->where('subject_type', BiographyEvent::class)
                        ->whereExists(function ($sub) use ($bioId) {
                            $sub->selectRaw('1')
                                ->from('biography_events')
                                ->whereColumn('biography_events.id', 'activity_logs.subject_id')
                                ->where('biography_events.biography_id', $bioId);
                        });
                })->orWhere(function ($q2) use ($bioId) {
                    $q2->where('subject_type', TimelineLine::class)
                        ->whereExists(function ($sub) use ($bioId) {
                            $sub->selectRaw('1')
                                ->from('timeline_lines')
                                ->whereColumn('timeline_lines.id', 'activity_logs.subject_id')
                                ->where('timeline_lines.source_biography_id', $bioId);
                        });
                });
            });
    }

    /**
     * Базовый запрос журнала одной фракции.
     *
     * @param  World  $world  Мир
     * @param  Faction  $faction  Фракция
     * @return Builder<ActivityLog>
     */
    private function factionActivityQuery(World $world, Faction $faction): Builder
    {
        $factionId = $faction->id;

        return ActivityLog::query()
            ->where('world_id', $world->id)
            ->where(function ($q) use ($factionId) {
                $q->where(function ($q2) use ($factionId) {
                    $q2->where('subject_type', Faction::class)
                        ->where('subject_id', $factionId);
                })->orWhere(function ($q2) use ($factionId) {
                    $q2->where('subject_type', FactionEvent::class)
                        ->whereExists(function ($sub) use ($factionId) {
                            $sub->selectRaw('1')
                                ->from('faction_events')
                                ->whereColumn('faction_events.id', 'activity_logs.subject_id')
                                ->where('faction_events.faction_id', $factionId);
                        });
                })->orWhere(function ($q2) use ($factionId) {
                    $q2->where('subject_type', TimelineLine::class)
                        ->whereExists(function ($sub) use ($factionId) {
                            $sub->selectRaw('1')
                                ->from('timeline_lines')
                                ->whereColumn('timeline_lines.id', 'activity_logs.subject_id')
                                ->where('timeline_lines.source_faction_id', $factionId);
                        });
                });
            });
    }

    /**
     * Базовый запрос журнала доски связей.
     *
     * @param  World  $world  Мир
     * @param  ConnectionBoard  $connectionBoard  Доска
     * @return Builder<ActivityLog>
     */
    private function connectionBoardActivityQuery(World $world, ConnectionBoard $connectionBoard): Builder
    {
        $boardId = $connectionBoard->id;

        return ActivityLog::query()
            ->where('world_id', $world->id)
            ->where(function ($q) use ($boardId) {
                $q->where(function ($q2) use ($boardId) {
                    $q2->where('subject_type', ConnectionBoard::class)
                        ->where('subject_id', $boardId);
                })->orWhere(function ($q2) use ($boardId) {
                    $q2->where('subject_type', ConnectionBoardNode::class)
                        ->whereExists(function ($sub) use ($boardId) {
                            $sub->selectRaw('1')
                                ->from('connection_board_nodes')
                                ->whereColumn('connection_board_nodes.id', 'activity_logs.subject_id')
                                ->where('connection_board_nodes.connection_board_id', $boardId);
                        });
                })->orWhere(function ($q2) use ($boardId) {
                    $q2->where('subject_type', ConnectionBoardEdge::class)
                        ->whereExists(function ($sub) use ($boardId) {
                            $sub->selectRaw('1')
                                ->from('connection_board_edges')
                                ->whereColumn('connection_board_edges.id', 'activity_logs.subject_id')
                                ->where('connection_board_edges.connection_board_id', $boardId);
                        });
                });
            });
    }
}
