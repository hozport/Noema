<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11pt; color: #111; }
        h1 { font-size: 18pt; margin-bottom: 0.25em; }
        .sci { font-style: italic; color: #444; margin-bottom: 1em; }
        h2 { font-size: 12pt; margin: 1em 0 0.35em; page-break-after: avoid; }
        p { margin: 0.35em 0; }
        .muted { color: #555; font-size: 10pt; }
        ul { margin: 0.25em 0 0.5em 1.2em; padding: 0; }
        .hero-img { max-width: 220px; max-height: 220px; width: auto; height: auto; object-fit: contain; border: 1px solid #ccc; margin: 0.5em 0; display: block; }
        .gallery { margin: 0.5em 0 1em; }
        .gallery img { width: 120px; height: 120px; object-fit: cover; border: 1px solid #ccc; margin: 0 0.35em 0.35em 0; vertical-align: top; }
    </style>
</head>
<body>
    <p class="muted">Бестиарий: {{ $world->name }}</p>
    <h1>{{ $creature->name }}</h1>
    @if (filled($creature->scientific_name))
        <p class="sci">{{ $creature->scientific_name }}</p>
    @endif

    @if ($creature->pdfImageDataUri())
        <img src="{{ $creature->pdfImageDataUri() }}" alt="" class="hero-img">
    @endif

    @if (filled($creature->species_kind))
        <p><strong>Вид:</strong> {{ $creature->species_kind }}</p>
    @endif

    @if (filled($creature->height_text) || filled($creature->weight_text) || filled($creature->lifespan_text))
        <p>
            @if (filled($creature->height_text))<strong>Рост:</strong> {{ $creature->height_text }}@endif
            @if (filled($creature->height_text) && (filled($creature->weight_text) || filled($creature->lifespan_text))) — @endif
            @if (filled($creature->weight_text))<strong>Вес:</strong> {{ $creature->weight_text }}@endif
            @if (filled($creature->weight_text) && filled($creature->lifespan_text)) — @endif
            @if (filled($creature->lifespan_text))<strong>Жизнь:</strong> {{ $creature->lifespan_text }}@endif
        </p>
    @endif

    @if (filled($creature->short_description))
        <h2>Краткое описание</h2>
        <p style="white-space: pre-wrap;">{{ $creature->short_description }}</p>
    @endif

    @if (filled($creature->full_description))
        <h2>Полное описание</h2>
        <p style="white-space: pre-wrap;">{{ $creature->full_description }}</p>
    @endif

    @if (filled($creature->habitat_text))
        <h2>Ореол обитания</h2>
        <p style="white-space: pre-wrap;">{{ $creature->habitat_text }}</p>
    @endif

    @if ($creature->relatedCreatures->isNotEmpty())
        <h2>Родственные существа</h2>
        <ul>
            @foreach ($creature->relatedCreatures->sortBy('name') as $rel)
                <li>{{ $rel->name }}</li>
            @endforeach
        </ul>
    @endif

    @if ($creature->foodCreatures->isNotEmpty() || (is_array($creature->food_custom) && count($creature->food_custom ?? []) > 0))
        <h2>Пища</h2>
        @if ($creature->foodCreatures->isNotEmpty())
            <p>Звери: {{ $creature->foodCreatures->sortBy('name')->pluck('name')->join(', ') }}</p>
        @endif
        @if (is_array($creature->food_custom) && count($creature->food_custom ?? []) > 0)
            <ul>
                @foreach ($creature->food_custom as $line)
                    <li>{{ $line }}</li>
                @endforeach
            </ul>
        @endif
    @endif

    @if ($creature->galleryImages->isNotEmpty())
        <h2>Галерея</h2>
        <div class="gallery">
            @foreach ($creature->galleryImages as $g)
                @if ($g->pdfImageDataUri())
                    <img src="{{ $g->pdfImageDataUri() }}" alt="">
                @endif
            @endforeach
        </div>
    @endif
</body>
</html>
