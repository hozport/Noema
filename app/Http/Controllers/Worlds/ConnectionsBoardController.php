<?php

namespace App\Http\Controllers\Worlds;

use App\Http\Controllers\Controller;
use App\Models\Bestiary\Creature;
use App\Models\Biography\Biography;
use App\Models\Cards\Card;
use App\Models\Cards\Story;
use App\Models\Timeline\TimelineEvent;
use App\Models\Timeline\TimelineLine;
use App\Models\Worlds\ConnectionBoard;
use App\Models\Worlds\ConnectionBoardEdge;
use App\Models\Worlds\ConnectionBoardNode;
use App\Models\Worlds\World;
use App\Support\ConnectionBoardNodeKind;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ConnectionsBoardController extends Controller
{
    public function show(Request $request, World $world, ConnectionBoard $connectionBoard): View
    {
        $this->authorizeWorld($request, $world);
        $this->ensureBoardInWorld($connectionBoard, $world);

        $nodes = ConnectionBoardNode::query()
            ->where('connection_board_id', $connectionBoard->id)
            ->orderBy('id')
            ->get();

        $nodesPayload = $this->serializeNodes($nodes, $world);

        $edges = ConnectionBoardEdge::query()
            ->where('connection_board_id', $connectionBoard->id)
            ->orderBy('id')
            ->get();

        $edgesPayload = $edges->map(fn (ConnectionBoardEdge $e) => [
            'id' => $e->id,
            'from_node_id' => $e->from_node_id,
            'to_node_id' => $e->to_node_id,
        ])->values()->all();

        $urls = [
            'base' => url("/worlds/{$world->id}/connections/{$connectionBoard->id}"),
        ];

        return view('connections.board', compact('world', 'connectionBoard', 'nodesPayload', 'edgesPayload', 'urls'));
    }

    public function timelineLines(Request $request, World $world): JsonResponse
    {
        $this->authorizeWorld($request, $world);

        $lines = TimelineLine::query()
            ->where('world_id', $world->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'name', 'color']);

        return response()->json(['lines' => $lines]);
    }

    public function timelineLineEvents(Request $request, World $world, TimelineLine $line): JsonResponse
    {
        $this->authorizeWorld($request, $world);

        if ($line->world_id !== $world->id) {
            abort(404);
        }

        $events = TimelineEvent::query()
            ->where('timeline_line_id', $line->id)
            ->orderBy('epoch_year')
            ->orderBy('month')
            ->orderBy('day')
            ->orderBy('id')
            ->get(['id', 'title', 'epoch_year', 'month', 'day']);

        return response()->json(['events' => $events]);
    }

    public function stories(Request $request, World $world): JsonResponse
    {
        $this->authorizeWorld($request, $world);

        $stories = Story::query()
            ->where('world_id', $world->id)
            ->orderBy('name')
            ->orderBy('id')
            ->get(['id', 'name']);

        return response()->json(['stories' => $stories]);
    }

    public function storyCards(Request $request, World $world, Story $story): JsonResponse
    {
        $this->authorizeWorld($request, $world);

        if ($story->world_id !== $world->id) {
            abort(404);
        }

        $cards = Card::query()
            ->where('story_id', $story->id)
            ->orderBy('number')
            ->orderBy('id')
            ->get(['id', 'story_id', 'title', 'number']);

        $payload = $cards->map(fn (Card $c) => [
            'id' => $c->id,
            'label' => $c->displayTitle(),
        ]);

        return response()->json(['cards' => $payload]);
    }

    public function creatures(Request $request, World $world): JsonResponse
    {
        $this->authorizeWorld($request, $world);

        $rows = Creature::query()
            ->where('world_id', $world->id)
            ->orderBy('name')
            ->orderBy('id')
            ->get(['id', 'name']);

        return response()->json(['creatures' => $rows]);
    }

    public function biographies(Request $request, World $world): JsonResponse
    {
        $this->authorizeWorld($request, $world);

        $rows = Biography::query()
            ->where('world_id', $world->id)
            ->orderBy('name')
            ->orderBy('id')
            ->get(['id', 'name']);

        return response()->json(['biographies' => $rows]);
    }

    public function nodesStore(Request $request, World $world, ConnectionBoard $connectionBoard): JsonResponse
    {
        $this->authorizeWorld($request, $world);
        $this->ensureBoardInWorld($connectionBoard, $world);

        $data = $this->validateNodePayload($request);

        $this->assertEntityBelongsToWorld($world, $data['kind'], $data['entity_id'], $data['meta'] ?? []);

        $node = ConnectionBoardNode::create([
            'connection_board_id' => $connectionBoard->id,
            'kind' => $data['kind'],
            'entity_id' => $data['entity_id'],
            'meta' => $data['meta'] ?? null,
            'x' => $data['x'],
            'y' => $data['y'],
        ]);

        return response()->json([
            'node' => $this->serializeNode($node, $world),
        ]);
    }

    public function nodesUpdate(Request $request, World $world, ConnectionBoard $connectionBoard, ConnectionBoardNode $node): JsonResponse
    {
        $this->authorizeWorld($request, $world);
        $this->ensureBoardInWorld($connectionBoard, $world);

        if ($node->connection_board_id !== $connectionBoard->id) {
            abort(404);
        }

        $validated = $request->validate([
            'x' => ['required', 'integer'],
            'y' => ['required', 'integer'],
        ]);

        $node->update([
            'x' => $validated['x'],
            'y' => $validated['y'],
        ]);

        return response()->json([
            'node' => $this->serializeNode($node->fresh(), $world),
        ]);
    }

    public function nodesDestroy(Request $request, World $world, ConnectionBoard $connectionBoard, ConnectionBoardNode $node): JsonResponse
    {
        $this->authorizeWorld($request, $world);
        $this->ensureBoardInWorld($connectionBoard, $world);

        if ($node->connection_board_id !== $connectionBoard->id) {
            abort(404);
        }

        $node->delete();

        return response()->json(['ok' => true]);
    }

    public function edgesStore(Request $request, World $world, ConnectionBoard $connectionBoard): JsonResponse
    {
        $this->authorizeWorld($request, $world);
        $this->ensureBoardInWorld($connectionBoard, $world);

        $data = $request->validate([
            'from_node_id' => ['required', 'integer'],
            'to_node_id' => ['required', 'integer'],
        ]);

        $a = (int) $data['from_node_id'];
        $b = (int) $data['to_node_id'];

        if ($a === $b) {
            abort(422, 'Нельзя связать узел с самим собой.');
        }

        $from = min($a, $b);
        $to = max($a, $b);

        $existsA = ConnectionBoardNode::query()
            ->where('connection_board_id', $connectionBoard->id)
            ->whereKey($from)
            ->exists();
        $existsB = ConnectionBoardNode::query()
            ->where('connection_board_id', $connectionBoard->id)
            ->whereKey($to)
            ->exists();

        if (! $existsA || ! $existsB) {
            abort(422, 'Оба узла должны быть на этой доске.');
        }

        $edge = ConnectionBoardEdge::firstOrCreate(
            [
                'connection_board_id' => $connectionBoard->id,
                'from_node_id' => $from,
                'to_node_id' => $to,
            ],
            ['meta' => null],
        );

        return response()->json([
            'edge' => [
                'id' => $edge->id,
                'from_node_id' => $edge->from_node_id,
                'to_node_id' => $edge->to_node_id,
            ],
        ]);
    }

    public function edgesDestroy(Request $request, World $world, ConnectionBoard $connectionBoard, ConnectionBoardEdge $edge): JsonResponse
    {
        $this->authorizeWorld($request, $world);
        $this->ensureBoardInWorld($connectionBoard, $world);

        if ($edge->connection_board_id !== $connectionBoard->id) {
            abort(404);
        }

        $edge->delete();

        return response()->json(['ok' => true]);
    }

    private function ensureBoardInWorld(ConnectionBoard $board, World $world): void
    {
        if ((int) $board->world_id !== (int) $world->id) {
            abort(404);
        }
    }

    private function authorizeWorld(Request $request, World $world): void
    {
        if ($world->user_id !== $request->user()->id) {
            abort(403);
        }

        if (! $world->onoff) {
            abort(404);
        }
    }

    /**
     * @param  array<string, mixed>|null  $meta
     */
    private function validateNodePayload(Request $request): array
    {
        return $request->validate([
            'kind' => ['required', 'string', Rule::in(ConnectionBoardNodeKind::all())],
            'entity_id' => ['nullable', 'integer'],
            'meta' => ['nullable', 'array'],
            'x' => ['required', 'integer'],
            'y' => ['required', 'integer'],
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $meta
     */
    private function assertEntityBelongsToWorld(World $world, string $kind, ?int $entityId, ?array $meta): void
    {
        match ($kind) {
            ConnectionBoardNodeKind::TIMELINE_EVENT => $this->assertTimelineEvent($world, $entityId),
            ConnectionBoardNodeKind::STORY_CARD => $this->assertStoryCard($world, $entityId, $meta ?? []),
            ConnectionBoardNodeKind::MAP_PLACEHOLDER => null,
            ConnectionBoardNodeKind::CREATURE => $this->assertCreature($world, $entityId),
            ConnectionBoardNodeKind::BIOGRAPHY => $this->assertBiography($world, $entityId),
            default => abort(422, 'Неизвестный тип узла.'),
        };
    }

    private function assertTimelineEvent(World $world, ?int $entityId): void
    {
        if ($entityId === null || $entityId < 1) {
            abort(422, 'Укажите событие.');
        }

        $event = TimelineEvent::query()->with('line')->find($entityId);
        if (! $event || ! $event->line || $event->line->world_id !== $world->id) {
            abort(422, 'Событие не найдено в этом мире.');
        }
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function assertStoryCard(World $world, ?int $entityId, array $meta): void
    {
        if ($entityId === null || $entityId < 1) {
            abort(422, 'Укажите карточку.');
        }
        $storyId = isset($meta['story_id']) ? (int) $meta['story_id'] : 0;
        if ($storyId < 1) {
            abort(422, 'Укажите историю карточки.');
        }

        $story = Story::query()->where('world_id', $world->id)->whereKey($storyId)->first();
        if (! $story) {
            abort(422, 'История не найдена.');
        }

        $card = Card::query()->whereKey($entityId)->where('story_id', $story->id)->first();
        if (! $card) {
            abort(422, 'Карточка не найдена в этой истории.');
        }
    }

    private function assertCreature(World $world, ?int $entityId): void
    {
        if ($entityId === null || $entityId < 1) {
            abort(422, 'Укажите существо.');
        }
        $exists = Creature::query()->where('world_id', $world->id)->whereKey($entityId)->exists();
        if (! $exists) {
            abort(422, 'Существо не найдено.');
        }
    }

    private function assertBiography(World $world, ?int $entityId): void
    {
        if ($entityId === null || $entityId < 1) {
            abort(422, 'Укажите личность.');
        }
        $exists = Biography::query()->where('world_id', $world->id)->whereKey($entityId)->exists();
        if (! $exists) {
            abort(422, 'Биография не найдена.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeNode(ConnectionBoardNode $node, World $world): array
    {
        return $this->serializeNodes(collect([$node]), $world)[0] ?? [
            'id' => $node->id,
            'kind' => $node->kind,
            'entity_id' => $node->entity_id,
            'meta' => $node->meta ?? [],
            'x' => $node->x,
            'y' => $node->y,
            'label' => '…',
            'subtitle' => null,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function serializeNodes(Collection $nodes, World $world): array
    {
        if ($nodes->isEmpty()) {
            return [];
        }

        $byKind = $nodes->groupBy('kind');

        $timelineEventIds = $byKind->get(ConnectionBoardNodeKind::TIMELINE_EVENT, collect())
            ->pluck('entity_id')->filter()->unique()->values()->all();
        $events = TimelineEvent::query()
            ->with('line')
            ->whereIn('id', $timelineEventIds)
            ->get()
            ->keyBy('id');

        $cardNodes = $byKind->get(ConnectionBoardNodeKind::STORY_CARD, collect());
        $cardIds = $cardNodes->pluck('entity_id')->filter()->unique()->values()->all();
        $cards = Card::query()->with('story')->whereIn('id', $cardIds)->get()->keyBy('id');

        $creatureIds = $byKind->get(ConnectionBoardNodeKind::CREATURE, collect())
            ->pluck('entity_id')->filter()->unique()->values()->all();
        $creatures = Creature::query()->where('world_id', $world->id)->whereIn('id', $creatureIds)->get()->keyBy('id');

        $bioIds = $byKind->get(ConnectionBoardNodeKind::BIOGRAPHY, collect())
            ->pluck('entity_id')->filter()->unique()->values()->all();
        $bios = Biography::query()->where('world_id', $world->id)->whereIn('id', $bioIds)->get()->keyBy('id');

        return $nodes->map(function (ConnectionBoardNode $n) use ($events, $cards, $creatures, $bios) {
            $label = 'Узел';
            $subtitle = null;

            if ($n->kind === ConnectionBoardNodeKind::TIMELINE_EVENT) {
                $e = $events->get($n->entity_id);
                $label = $e?->title ? (string) $e->title : 'Событие';
                $subtitle = $e?->line?->name;
            } elseif ($n->kind === ConnectionBoardNodeKind::STORY_CARD) {
                $c = $cards->get($n->entity_id);
                $label = $c ? $c->displayTitle() : 'Карточка';
                $subtitle = $c?->story?->name;
            } elseif ($n->kind === ConnectionBoardNodeKind::MAP_PLACEHOLDER) {
                $label = 'Объект карты';
                $subtitle = 'Заглушка';
            } elseif ($n->kind === ConnectionBoardNodeKind::CREATURE) {
                $cr = $creatures->get($n->entity_id);
                $label = $cr?->name ? (string) $cr->name : 'Существо';
            } elseif ($n->kind === ConnectionBoardNodeKind::BIOGRAPHY) {
                $b = $bios->get($n->entity_id);
                $label = $b?->name ? (string) $b->name : 'Личность';
            }

            return [
                'id' => $n->id,
                'kind' => $n->kind,
                'entity_id' => $n->entity_id,
                'meta' => $n->meta ?? [],
                'x' => $n->x,
                'y' => $n->y,
                'label' => $label,
                'subtitle' => $subtitle,
            ];
        })->values()->all();
    }
}
