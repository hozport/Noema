{{-- $world, $biography — шапка под именем: годы, раса/народ/страна (ссылки на фракции), пол --}}
@php
    $biography->loadMissing(['raceFaction', 'peopleFaction', 'countryFaction']);
@endphp
<p class="text-base-content/60 text-lg mt-1">
    <span>{{ $biography->lifeYearsLabel() }}</span>
    @if ($biography->raceFaction)
        <span class="text-base-content/35 mx-1" aria-hidden="true">·</span>
        <a href="{{ route('factions.show', [$world, $biography->raceFaction]) }}" class="link link-hover">{{ $biography->raceFaction->name }}</a>
    @endif
    @if ($biography->peopleFaction)
        <span class="text-base-content/35 mx-1" aria-hidden="true">·</span>
        <a href="{{ route('factions.show', [$world, $biography->peopleFaction]) }}" class="link link-hover">{{ $biography->peopleFaction->name }}</a>
    @endif
    @if ($biography->countryFaction)
        <span class="text-base-content/35 mx-1" aria-hidden="true">·</span>
        <a href="{{ route('factions.show', [$world, $biography->countryFaction]) }}" class="link link-hover">{{ $biography->countryFaction->name }}</a>
    @endif
    @if (($g = $biography->genderLabel()) !== null)
        <span class="text-base-content/35 mx-1" aria-hidden="true">·</span>
        <span>{{ $g }}</span>
    @endif
</p>
