<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; color: #111; }
        h1 { font-size: 18pt; margin-bottom: 0.35em; }
        h2 { font-size: 13pt; margin-top: 1em; margin-bottom: 0.45em; page-break-after: avoid; }
        .meta { color: #444; margin-bottom: 0.75em; line-height: 1.45; }
        .total { margin: 0.5em 0 1em; font-size: 10pt; color: #333; }
        table.list { width: 100%; border-collapse: collapse; margin: 0.25em 0 0.75em; }
        table.list td { vertical-align: top; padding: 0.25em 0.4em 0.4em 0; border-bottom: 1px solid #e5e5e5; }
        table.list td.thumb { width: 52px; }
        table.list img { width: 44px; height: 44px; object-fit: cover; display: block; border: 1px solid #ccc; }
    </style>
</head>
<body>
    <h1>Бестиарий — {{ $world->name }}</h1>
    <p class="meta">
        В интерфейсе бестиария два независимых указателя: кириллица (А–Я) и латиница (A–Z).
        Существо попадает в группу только по первой букве названия в соответствующей раскладке
        (русское «А» и латинское «A» — разные буквы).
    </p>
    <p class="total"><strong>Всего существ в бестиарии:</strong> {{ $creatures->count() }}</p>

    <h2>Все существа</h2>
    @if ($creatures->isEmpty())
        <p>В бестиарии пока нет существ.</p>
    @else
        <table class="list">
            @foreach ($creatures->sortBy(fn ($c) => mb_strtolower($c->name, 'UTF-8')) as $creature)
                <tr>
                    <td class="thumb">
                        @if ($creature->pdfImageDataUri())
                            <img src="{{ $creature->pdfImageDataUri() }}" alt="">
                        @endif
                    </td>
                    <td>
                        <strong>{{ $creature->name }}</strong>
                        @if (filled($creature->scientific_name))
                            — <em>{{ $creature->scientific_name }}</em>
                        @endif
                    </td>
                </tr>
            @endforeach
        </table>
    @endif
</body>
</html>
