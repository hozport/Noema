<?php

namespace App\Support;

final class ConnectionBoardNodeKind
{
    public const TIMELINE_EVENT = 'timeline_event';

    public const STORY_CARD = 'story_card';

    public const MAP_PLACEHOLDER = 'map_placeholder';

    public const CREATURE = 'creature';

    public const BIOGRAPHY = 'biography';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::TIMELINE_EVENT,
            self::STORY_CARD,
            self::MAP_PLACEHOLDER,
            self::CREATURE,
            self::BIOGRAPHY,
        ];
    }
}
