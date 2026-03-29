<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11pt; color: #111; }
        h1 { font-size: 18pt; margin-bottom: 0.25em; }
        .type { color: #444; margin-bottom: 1em; font-size: 11pt; }
        h2 { font-size: 12pt; margin: 1em 0 0.35em; page-break-after: avoid; }
        p { margin: 0.35em 0; }
        .muted { color: #555; font-size: 10pt; }
        ul { margin: 0.25em 0 0.5em 1.2em; padding: 0; }
        .hero-img { max-width: 220px; max-height: 220px; object-fit: contain; border: 1px solid #ccc; margin: 0.5em 0; display: block; }
    </style>
</head>
<body>
    <p class="muted">Фракции: {{ $world->name }}</p>
    <h1>{{ $faction->name }}</h1>
    <p class="type">{{ $faction->typeLabel() }}</p>

    @if ($faction->pdfImageDataUri())
        <img src="{{ $faction->pdfImageDataUri() }}" alt="" class="hero-img">
    @endif

    @if (filled($faction->short_description))
        <h2>Краткое описание</h2>
        <p style="white-space: pre-wrap;">{{ $faction->short_description }}</p>
    @endif

    @if (filled($faction->full_description))
        <h2>Полное описание</h2>
        <p style="white-space: pre-wrap;">{{ $faction->full_description }}</p>
    @endif

    @if ($faction->factionEvents->isNotEmpty())
        <h2>События фракции</h2>
        <ul>
            @foreach ($faction->factionEvents as $e)
                @php
                    $y = $e->epoch_year !== null ? (string) $e->epoch_year : '…';
                    if ($e->year_end !== null && $e->year_end !== $e->epoch_year) {
                        $y .= '–' . $e->year_end;
                    }
                @endphp
                <li><strong>{{ $e->title }}</strong> ({{ $y }})</li>
            @endforeach
        </ul>
    @endif

    @if (filled($faction->geographic_stub))
        <h2>Географические объекты</h2>
        <p style="white-space: pre-wrap;">{{ $faction->geographic_stub }}</p>
    @endif

    @if ($faction->members->isNotEmpty())
        <h2>Члены фракции</h2>
        <ul>
            @foreach ($faction->members->sortBy('name') as $m)
                <li>{{ $m->name }}</li>
            @endforeach
        </ul>
    @endif

    @if ($faction->relatedFactions->isNotEmpty())
        <h2>Связанные фракции</h2>
        <ul>
            @foreach ($faction->relatedFactions->sortBy('name') as $r)
                <li>{{ $r->name }}</li>
            @endforeach
        </ul>
    @endif

    @if ($faction->enemyFactions->isNotEmpty())
        <h2>Вражеские фракции</h2>
        <ul>
            @foreach ($faction->enemyFactions->sortBy('name') as $e)
                <li>{{ $e->name }}</li>
            @endforeach
        </ul>
    @endif
</body>
</html>
