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
        .type { color: #555; font-size: 9pt; }
    </style>
</head>
<body>
    <h1>Фракции — {{ $world->name }}</h1>
    <p class="meta"><strong>Всего:</strong> {{ $factions->count() }}</p>

    @if ($factions->isEmpty())
        <p>В модуле пока нет фракций.</p>
    @else
        <table class="list">
            @foreach ($factions->sortBy(fn ($f) => mb_strtolower($f->name, 'UTF-8')) as $f)
                <tr>
                    <td class="thumb">
                        @if ($f->pdfImageDataUri())
                            <img src="{{ $f->pdfImageDataUri() }}" alt="">
                        @endif
                    </td>
                    <td>
                        <strong>{{ $f->name }}</strong>
                        <span class="type"> — {{ $f->typeLabel() }}</span>
                    </td>
                </tr>
            @endforeach
        </table>
    @endif
</body>
</html>
