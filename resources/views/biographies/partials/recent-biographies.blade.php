@if ($recentBiographies->isNotEmpty())
    <section class="mb-6" aria-labelledby="recent-biographies-heading">
        <h2 id="recent-biographies-heading" class="text-sm font-medium text-base-content/70 mb-3">Последние добавленные</h2>
        <div class="flex flex-wrap gap-4 justify-start">
            @foreach ($recentBiographies as $bio)
                <article class="card biography-card bg-base-200 border border-base-300 shadow-none">
                    <figure class="px-0 pt-0 pb-0">
                        @if ($bio->imageUrl())
                            <img src="{{ $bio->imageUrl() }}" alt="{{ $bio->name }}" class="biography-card-img rounded-none">
                        @else
                            <div class="biography-card-img flex items-center justify-center text-base-content/30 rounded-none" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            </div>
                        @endif
                    </figure>
                    <div class="card-body px-4 pt-3 pb-4 gap-1 items-stretch text-center">
                        <h3 class="card-title text-lg font-semibold justify-center leading-tight">{{ $bio->name }}</h3>
                        <p class="text-sm text-base-content/50">{{ $bio->lifeYearsLabel() }}</p>
                        <a href="{{ route('biographies.show', [$world, $bio]) }}" class="btn btn-primary btn-sm rounded-none mt-2">Подробнее</a>
                    </div>
                </article>
            @endforeach
        </div>
    </section>
@endif
