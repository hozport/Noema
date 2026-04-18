@php
    $journalTitle = $journalTitle ?? 'Журнал изменений';
    if (! empty($card) && isset($world, $story) && $world && $story) {
        $href = route('cards.card.activity', [$world, $story, $card]);
    } elseif (! empty($story) && isset($world) && $world && empty($card)) {
        $href = route('cards.stories.activity', [$world, $story]);
    } elseif (! empty($biography) && isset($world) && $world) {
        $href = route('biography.activity', [$world, $biography]);
    } elseif (! empty($faction) && isset($world) && $world) {
        $href = route('faction.activity', [$world, $faction]);
    } elseif (! empty($creature) && isset($world) && $world) {
        $href = route('bestiary.creature.activity', [$world, $creature]);
    } elseif (! empty($connectionBoard) && isset($world) && $world) {
        $href = route('connections.board.activity', [$world, $connectionBoard]);
    } elseif (! empty($timelineJournal) && isset($world) && $world) {
        $href = route('worlds.activity.timeline', $world);
    } elseif (! empty($cardsModuleJournal) && isset($world) && $world) {
        $href = route('cards.module.activity', $world);
    } elseif (! empty($biographiesModuleJournal) && isset($world) && $world) {
        $href = route('biographies.module.activity', $world);
    } elseif (! empty($factionsModuleJournal) && isset($world) && $world) {
        $href = route('factions.module.activity', $world);
    } elseif (! empty($bestiaryModuleJournal) && isset($world) && $world) {
        $href = route('bestiary.module.activity', $world);
    } elseif (! empty($connectionsModuleJournal) && isset($world) && $world) {
        $href = route('connections.module.activity', $world);
    } elseif (! empty($mapsModuleJournal) && isset($world) && $world) {
        $href = route('maps.module.activity', $world);
    } elseif (isset($world) && $world) {
        $href = route('worlds.activity', $world);
    } else {
        $href = route('account.activity');
    }
@endphp
<a href="{{ $href }}" class="btn btn-ghost btn-square shrink-0" title="{{ $journalTitle }}" aria-label="{{ $journalTitle }}">
    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M8 6h13"/>
        <path d="M8 12h13"/>
        <path d="M8 18h13"/>
        <path d="M3 6h.01"/>
        <path d="M3 12h.01"/>
        <path d="M3 18h.01"/>
    </svg>
</a>
