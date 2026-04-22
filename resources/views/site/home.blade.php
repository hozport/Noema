@extends('site.layout')

@section('title', 'Noema — система проектирования миров')

@section('content')
    <section id="hero" class="w-full border-b border-base-300 bg-gradient-to-br from-base-100 via-base-100 to-base-300/25">
        <div class="max-w-[1344px] mx-auto px-4 sm:px-6 py-12 sm:py-16 lg:py-20">
            <div class="grid lg:grid-cols-2 gap-10 lg:gap-14 items-start lg:items-center">
                <div class="order-2 lg:order-1 max-w-xl">
                    <h1 class="font-display text-3xl sm:text-4xl lg:text-5xl font-semibold text-base-content leading-tight tracking-tight">
                        Система проектирования Миров
                    </h1>
                    <p class="mt-4 text-base sm:text-lg text-base-content/75 leading-relaxed">
                        Для авторов, сценаристов, геймдизайнеров и просто людей с бесконечной фантазией.
                    </p>

                    <div class="mt-8 p-6 bg-base-200/80 border border-base-300 rounded-none shadow-lg max-w-md">
                        <h2 class="text-sm font-medium text-base-content/60 uppercase tracking-wider mb-4">Вход</h2>
                        <form method="POST" action="{{ url('/login') }}" class="flex flex-col gap-4">
                            @csrf
                            <div class="form-control">
                                <label for="landing-email" class="label py-1">
                                    <span class="label-text text-sm text-base-content/70">Логин</span>
                                </label>
                                <input
                                    type="text"
                                    id="landing-email"
                                    name="email"
                                    value="{{ old('email') }}"
                                    required
                                    autocomplete="username"
                                    class="input input-bordered input-sm w-full h-9 bg-base-100 border-base-300 text-base-content placeholder:opacity-50 rounded-none"
                                    placeholder="email@example.com"
                                >
                                @error('email')
                                    <p class="text-error text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="form-control">
                                <label for="landing-password" class="label py-1">
                                    <span class="label-text text-sm text-base-content/70">Пароль</span>
                                </label>
                                <input
                                    type="password"
                                    id="landing-password"
                                    name="password"
                                    required
                                    autocomplete="current-password"
                                    class="input input-bordered input-sm w-full h-9 bg-base-100 border-base-300 text-base-content placeholder:opacity-50 rounded-none"
                                    placeholder="••••••••"
                                >
                            </div>
                            <label class="label cursor-pointer justify-start gap-2 py-1">
                                <input type="checkbox" name="remember" class="checkbox checkbox-sm checkbox-primary rounded-none">
                                <span class="label-text text-sm text-base-content/60">Запомнить меня</span>
                            </label>
                            <button type="submit" class="btn btn-primary rounded-none h-10 mt-1">
                                Войти
                            </button>
                        </form>
                    </div>
                </div>
                <div class="order-1 lg:order-2 w-full min-h-[min(52vh,520px)] flex items-center justify-center p-8 text-center border border-dashed border-base-300/60 bg-base-200/40 bg-gradient-to-br from-base-200/80 to-base-300/20">
                    <p class="text-base-content/45 text-sm max-w-xs leading-relaxed">
                        Здесь появятся скриншоты интерфейса — полноширинное изображение или коллаж можно будет подставить позже.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section id="features" class="max-w-[1344px] w-full mx-auto px-4 sm:px-6 py-16 sm:py-20">
        <h2 class="font-display text-2xl sm:text-3xl font-semibold text-base-content text-center mb-12 sm:mb-16">
            Что умеет Noema?
        </h2>

        <div class="space-y-16 sm:space-y-20">
            <div class="grid md:grid-cols-2 gap-8 md:gap-12 items-center">
                <div>
                    <h3 class="font-display text-xl sm:text-2xl font-semibold text-base-content mb-3">Таймлайн</h3>
                    <p class="text-base-content/75 leading-relaxed">
                        <span class="underline underline-offset-2">Визуальная хронология:</span> создавай Отрезки времени, наполняй их Событиями, переноси на временную шкалу Биографии и истории Фракций.
                    </p>
                    <p class="text-base-content/75 leading-relaxed mt-3">
                        Хронология твоего Мира всегда под рукой!
                    </p>
                </div>
                <div class="rounded-none border border-base-300 bg-base-100 shadow-sm overflow-hidden">
                    <div class="bg-base-100 box-border" style="padding: 25px;">
                        <img
                            src="{{ asset('images/utp0.jpg') }}"
                            alt="Интерфейс модуля «Таймлайн»: отрезки, события и выкладка с биографий и фракций"
                            class="w-full h-auto object-cover object-top block max-h-[min(28rem,75vh)]"
                            loading="lazy"
                            decoding="async"
                        >
                    </div>
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-8 md:gap-12 items-center">
                <div class="md:order-1 order-2 rounded-none border border-base-300 bg-base-100 shadow-sm overflow-hidden">
                    <div class="bg-base-100 box-border" style="padding: 25px;">
                        <img
                            src="{{ asset('images/utp1.jpg') }}"
                            alt="Интерфейс модуля «Карточки»: карточный метод и оформление текста"
                            class="w-full h-auto object-cover object-top block max-h-[min(28rem,75vh)]"
                            loading="lazy"
                            decoding="async"
                        >
                    </div>
                </div>
                <div class="md:order-2 order-1">
                    <h3 class="font-display text-xl sm:text-2xl font-semibold text-base-content mb-3">Карточки</h3>
                    <p class="text-base-content/75 leading-relaxed">
                        <span class="underline underline-offset-2">Карточный метод:</span> пиши Истории карточным методом, иди от общего к частному, создавай Циклы, разделяй Карточки, форматируй текст с помощью теговой системы, делай ссылки на другие разделы.
                    </p>
                    <p class="text-base-content/75 leading-relaxed mt-3">
                        Между прочим, Набоков писал именно так!
                    </p>
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-8 md:gap-12 items-center">
                <div>
                    <h3 class="font-display text-xl sm:text-2xl font-semibold text-base-content mb-3">Связи</h3>
                    <p class="text-base-content/75 leading-relaxed">
                        <span class="underline underline-offset-2">Доски связей:</span> создавай Взаимосвязи между объектами различных разделов системы, визуализируй загадки, создавай генеалогические деревья, круги знакомств, политическую обстановку.
                    </p>
                    <p class="text-base-content/75 leading-relaxed mt-3">
                        Все детективы в кино делают такие доски!
                    </p>
                </div>
                <div class="min-h-[200px] rounded-none border border-dashed border-base-300/50 bg-gradient-to-br from-base-200/60 to-base-300/15" role="img" aria-hidden="true"></div>
            </div>

            <div class="grid md:grid-cols-2 gap-8 md:gap-12 items-center">
                <div class="min-h-[200px] rounded-none border border-dashed border-base-300/50 bg-gradient-to-br from-base-200/60 to-base-300/15 md:order-1 order-2" role="img" aria-hidden="true"></div>
                <div class="md:order-2 order-1">
                    <h3 class="font-display text-xl sm:text-2xl font-semibold text-base-content mb-3">Карты</h3>
                    <p class="text-base-content/75 leading-relaxed">
                        <span class="underline underline-offset-2">Карты местности:</span> создавай карты в одном из трёх доступных сеттингов (фэнтези, современность, научная фантастика), создавай Объекты на карте из готовых пресетов или загрузи свои спрайты, рисуй рельеф, используй политическую линзу для отрисовки границ государств.
                    </p>
                    <p class="text-base-content/75 leading-relaxed mt-3">
                        Теперь ты не забудешь, куда герой шёл, и сколько рек ему пришлось преодолеть!
                    </p>
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-8 md:gap-12 items-center">
                <div>
                    <h3 class="font-display text-xl sm:text-2xl font-semibold text-base-content mb-3">Фракции</h3>
                    <p class="text-base-content/75 leading-relaxed">
                        <span class="underline underline-offset-2">Группируй людей:</span> расы, народы, политические течения, торговые ассоциации, корпорации, в общем, любые объединения людей и их сложные взаимосвязи.
                    </p>
                    <p class="text-base-content/75 leading-relaxed mt-3">
                        Теперь ты не забудешь, кто кому - друг, а кто - нет!
                    </p>
                </div>
                <div class="min-h-[200px] rounded-none border border-dashed border-base-300/50 bg-gradient-to-br from-base-200/60 to-base-300/15" role="img" aria-hidden="true"></div>
            </div>

            <div class="grid md:grid-cols-2 gap-8 md:gap-12 items-center">
                <div class="md:order-1 order-2 rounded-none border border-base-300 bg-base-100 shadow-sm overflow-hidden">
                    <div class="bg-base-100 box-border" style="padding: 25px;">
                        <img
                            src="{{ asset('images/utp6.jpg') }}"
                            alt="Интерфейс модуля «Биографии»: профиль и события"
                            class="w-full h-auto object-cover object-top block max-h-[min(28rem,75vh)]"
                            loading="lazy"
                            decoding="async"
                        >
                    </div>
                </div>
                <div class="md:order-2 order-1">
                    <h3 class="font-display text-xl sm:text-2xl font-semibold text-base-content mb-3">Биографии</h3>
                    <p class="text-base-content/75 leading-relaxed">
                        <span class="underline underline-offset-2">Библии персонажей:</span> создавай биографические заметки о всех персонажах твоего Мира, экспортируй их жизни в Таймлайны, прописывай родственные и дружеские связи, отношение к фракциям, списки врагов.
                    </p>
                    <p class="text-base-content/75 leading-relaxed mt-3">
                        Теперь ты не забудешь, кто есть кто!
                    </p>
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-8 md:gap-12 items-center">
                <div>
                    <h3 class="font-display text-xl sm:text-2xl font-semibold text-base-content mb-3">Бестиарий</h3>
                    <p class="text-base-content/75 leading-relaxed">
                        <span class="underline underline-offset-2">Справочник существ:</span> насели свой Мир невообразимыми существами, пропиши связи - кто кого ест, кто сколько живёт, кто где обитает.
                    </p>
                    <p class="text-base-content/75 leading-relaxed mt-3">
                        И кого же встретит герой по пути через эту пустыню?
                    </p>
                </div>
                <div class="rounded-none border border-base-300 bg-base-100 shadow-sm overflow-hidden">
                    <div class="bg-base-100 box-border" style="padding: 25px;">
                        <img
                            src="{{ asset('images/utp7.jpg') }}"
                            alt="Интерфейс модуля «Бестиарий»: существа и карточки"
                            class="w-full h-auto object-cover object-top block max-h-[min(28rem,75vh)]"
                            loading="lazy"
                            decoding="async"
                        >
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="about" class="border-t border-base-300 bg-base-200/30">
        <div class="max-w-[1344px] mx-auto px-4 sm:px-6 py-16 sm:py-20">
            <h2 class="font-display text-2xl sm:text-3xl font-semibold text-base-content text-center mb-8">
                Для кого создана Noema?
            </h2>
            <div class="max-w-3xl mx-auto text-base sm:text-lg text-base-content/80 leading-relaxed space-y-4">
                <p>
                    Noema рассчитана на тех, кому нужно держать большой вымышленный мир в порядке: писателям и сценаристам,
                    авторам настольных и видеоигр, гейммастерам и всем, кто системно собирает лор, а не теряет его в заметках.
                </p>
                <p>
                    Инструменты связаны между собой: одна и та же сущность может участвовать в таймлайне, карточках истории,
                    бестиарии и биографиях — без дублирования смысла в разных файлах.
                </p>
            </div>
        </div>
    </section>

    <section id="roadmap" class="max-w-[1344px] w-full mx-auto px-4 sm:px-6 py-12 text-center">
        <p class="text-base-content/60 text-sm max-w-xl mx-auto">
            Дорожная карта и обновления сервиса — на отдельной странице.
        </p>
        <a href="{{ route('site.roadmap') }}" class="inline-block mt-4 text-sm link link-hover text-primary">Открыть дорожную карту</a>
    </section>

    <section id="documentation" class="border-t border-base-300 py-12 sm:py-16">
        <div class="max-w-[1344px] mx-auto px-4 sm:px-6 flex flex-col sm:flex-row flex-wrap items-center justify-center gap-4">
            <a href="{{ route('site.documentation') }}" class="btn btn-outline border-base-300 rounded-none px-10 min-h-12 text-base">
                Документация
            </a>
            <a href="{{ route('site.register') }}" class="btn btn-primary rounded-none px-10 min-h-12 text-base">
                {{ __('site.nav.register') }}
            </a>
        </div>
    </section>
@endsection
