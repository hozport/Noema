{{-- Ожидает: $world, $recentCreatures (коллекция Creature) --}}
@if ($recentCreatures->isNotEmpty())
    <section class="mb-6" aria-labelledby="recent-creatures-heading">
        <h2 id="recent-creatures-heading" class="text-sm font-medium text-base-content/70 mb-3">Последние добавленные</h2>
        <div class="flex flex-wrap gap-4 justify-start">
            @foreach ($recentCreatures as $rc)
                <article class="card bestiary-creature-card bg-base-200 border border-base-300 shadow-none">
                    <figure class="px-0 pt-0 pb-0">
                        @if ($rc->imageUrl())
                            <img src="{{ $rc->imageUrl() }}" alt="{{ $rc->name }}" class="bestiary-creature-img rounded-none">
                        @else
                            <div class="bestiary-creature-img flex items-center justify-center text-base-content/30 rounded-none" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M4 16l4.586-4.586a2 2 0 0 1 2.828 0L16 16m-2-2l1.586-1.586a2 2 0 0 1 2.828 0L20 14"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M22 19H2"/></svg>
                            </div>
                        @endif
                    </figure>
                    <div class="card-body px-4 pt-3 pb-4 gap-1 items-stretch text-center">
                        <h3 class="card-title text-lg font-semibold justify-center leading-tight">{{ $rc->name }}</h3>
                        @if (filled($rc->scientific_name))
                            <p class="text-sm text-base-content/50">{{ $rc->scientific_name }}</p>
                        @endif
                        <a href="{{ route('bestiary.creatures.show', [$world, $rc]) }}" class="btn btn-primary btn-sm rounded-none mt-2">Подробнее</a>
                    </div>
                </article>
            @endforeach
        </div>
    </section>
@endif
