<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11pt; color: #111; }
        h1 { font-size: 18pt; margin: 0 0 1em; }
        /* Отступ сверху у каждого раздела, чтобы заголовки не слипались с текстом выше */
        h2 { font-size: 12pt; margin: 2.35em 0 0.65em; }
        h2:first-of-type { margin-top: 1.85em; }
        .world-name { font-size: 13pt; font-weight: 700; margin: 0.35em 0 0.25em; }
        .world-desc { margin: 0.35em 0 0.75em; white-space: pre-wrap; }
        .event-block { margin: 0.5em 0 0.65em; }
        .event-date { font-weight: 600; color: #333; }
        .event-title { font-weight: 600; }
        .event-desc { margin: 0.25em 0 0 0.5em; padding-left: 0.5em; border-left: 2px solid #ccc; color: #333; white-space: pre-wrap; }
        .muted { color: #555; font-size: 10pt; }
    </style>
</head>
<body>
    <h1>Таймлайн</h1>

    <p class="world-name">{{ $world->name }}</p>
    @if (filled($world->annotation))
        <p class="world-desc">{{ $world->annotation }}</p>
    @endif

    <h2>Основная временная линия</h2>
    @forelse ($mainRows as $row)
        <div class="event-block">
            <p class="event-line"><span class="event-date">{{ $row['date'] }}</span> — <span class="event-title">{{ $row['title'] }}</span></p>
            @if (filled($row['description']))
                <p class="event-desc">{{ $row['description'] }}</p>
            @endif
        </div>
    @empty
        <p class="muted">Нет событий на основной линии.</p>
    @endforelse

    @foreach ($secondarySections as $section)
        <h2>{{ $section['name'] }}</h2>
        @forelse ($section['rows'] as $row)
            <div class="event-block">
                <p class="event-line"><span class="event-date">{{ $row['date'] }}</span> — <span class="event-title">{{ $row['title'] }}</span></p>
                @if (filled($row['description']))
                    <p class="event-desc">{{ $row['description'] }}</p>
                @endif
            </div>
        @empty
            <p class="muted">Нет событий на этой линии.</p>
        @endforelse
    @endforeach
</body>
</html>
