<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11pt; color: #111; }
        h1 { font-size: 16pt; margin: 0 0 1em; border-bottom: 1px solid #ccc; padding-bottom: 0.35em; }
        .card { margin-bottom: 1.25em; page-break-inside: avoid; }
        .card h2 { font-size: 12pt; margin: 0 0 0.35em; color: #1a1a1a; }
        .card .num { color: #666; font-size: 9pt; margin-bottom: 0.25em; }
        .card .body { white-space: pre-wrap; line-height: 1.45; }
        .empty { color: #888; font-style: italic; }
    </style>
</head>
<body>
    <h1>{{ $story->name }}</h1>
    @if (filled($story->cycle))
        <p style="font-size: 10pt; color: #444; margin: -0.5em 0 1em;">Цикл: {{ $story->cycle }}</p>
    @endif
    @foreach ($story->cards as $card)
        <div class="card">
            <div class="num">№ {{ $card->number }}</div>
            <h2>{{ $card->displayTitle() }}</h2>
            @if (filled($card->content))
                <div class="body">{{ $card->content }}</div>
            @else
                <p class="empty">(нет текста)</p>
            @endif
        </div>
    @endforeach
</body>
</html>
