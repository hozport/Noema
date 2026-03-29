<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; color: #111; }
        h1 { font-size: 18pt; margin-bottom: 0.35em; }
        .meta { color: #444; margin-bottom: 1em; }
        table.list { width: 100%; border-collapse: collapse; margin: 0.25em 0 0.75em; }
        table.list td { vertical-align: top; padding: 0.25em 0.4em 0.4em 0; border-bottom: 1px solid #e5e5e5; }
        table.list td.thumb { width: 52px; }
        table.list img { width: 44px; height: 44px; object-fit: cover; display: block; border: 1px solid #ccc; }
    </style>
</head>
<body>
    <h1>Биографии — {{ $world->name }}</h1>
    <p class="meta"><strong>Всего:</strong> {{ $biographies->count() }}</p>

    @if ($biographies->isEmpty())
        <p>В модуле пока нет биографий.</p>
    @else
        <table class="list">
            @foreach ($biographies->sortBy(fn ($b) => mb_strtolower($b->name, 'UTF-8')) as $bio)
                <tr>
                    <td class="thumb">
                        @if ($bio->pdfImageDataUri())
                            <img src="{{ $bio->pdfImageDataUri() }}" alt="">
                        @endif
                    </td>
                    <td>
                        <strong>{{ $bio->name }}</strong>
                        — <em>{{ $bio->lifeYearsLabel() }}</em>
                        @if (filled($bio->raceLabel()))
                            <span> — {{ $bio->raceLabel() }}</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </table>
    @endif
</body>
</html>
