<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11pt; color: #111; }
        h1 { font-size: 18pt; margin-bottom: 0.25em; }
        .muted { color: #555; font-size: 10pt; }
        .years { color: #444; margin-bottom: 0.75em; font-style: italic; }
        h2 { font-size: 12pt; margin: 1em 0 0.35em; }
        p { margin: 0.35em 0; }
        ul { margin: 0.25em 0 0.5em 1.2em; padding: 0; }
        .hero-img { max-width: 220px; max-height: 220px; object-fit: contain; border: 1px solid #ccc; margin: 0.5em 0; display: block; }
    </style>
</head>
<body>
    <p class="muted">Биографии: {{ $world->name }}</p>
    <h1>{{ $biography->name }}</h1>
    <p class="years">{{ $biography->lifeYearsLabel() }}@if (($meta = $biography->bioHeaderMetaLine()) !== null) · {{ $meta }}@endif</p>

    @if ($biography->pdfImageDataUri())
        <img src="{{ $biography->pdfImageDataUri() }}" alt="" class="hero-img">
    @endif

    @if (filled($biography->short_description))
        <h2>Краткое описание</h2>
        <p style="white-space: pre-wrap;">{{ $biography->short_description }}</p>
    @endif

    @if (filled($biography->full_description))
        <h2>Полное описание</h2>
        <p style="white-space: pre-wrap;">{{ $biography->full_description }}</p>
    @endif

    @if ($biography->relatives->isNotEmpty())
        <h2>Родственные связи</h2>
        <ul>
            @foreach ($biography->relatives->sortBy('name') as $r)
                <li>
                    @php
                        $kl = \App\Support\BiographyKinship::displayLabel($r->pivot->kinship ?? null, $r->pivot->kinship_custom ?? null);
                    @endphp
                    @if ($kl !== '')
                        {{ $kl }}:
                    @endif
                    {{ $r->name }}
                </li>
            @endforeach
        </ul>
    @endif

    @if ($biography->friends->isNotEmpty())
        <h2>Друзья</h2>
        <ul>
            @foreach ($biography->friends->sortBy('name') as $f)
                <li>{{ $f->name }}</li>
            @endforeach
        </ul>
    @endif

    @if ($biography->enemies->isNotEmpty())
        <h2>Враги</h2>
        <ul>
            @foreach ($biography->enemies->sortBy('name') as $e)
                <li>{{ $e->name }}</li>
            @endforeach
        </ul>
    @endif

    @if ($socialMembershipFactions->isNotEmpty())
        <h2>Состоит в фракции</h2>
        @foreach ($socialMembershipFactions as $mf)
            <p><strong>{{ $mf->name }}</strong>@if (filled($mf->short_description)) — {{ $mf->short_description }}@endif</p>
        @endforeach
    @endif
</body>
</html>
