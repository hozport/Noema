<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Noema — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=cormorant-garamond:400,500,600,700|instrument-sans:400,500,600" rel="stylesheet">
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="min-h-screen bg-base-100 flex flex-col items-center p-6" x-data>
    <main class="flex-1 w-full max-w-xs flex flex-col items-center justify-center">
        {{-- Logo: крупно, по центру --}}
        <div class="text-center mb-16">
            <p class="font-display text-xl sm:text-2xl tracking-[0.35em] text-base-content/60 uppercase mb-1">GENEFIS MEDIA's</p>
            <h1 class="font-display text-6xl sm:text-7xl font-semibold tracking-wide text-base-content">NOEMA</h1>
        </div>

        {{-- Форма: небольшой размер --}}
        <div class="w-full">
            <form method="POST" action="{{ url('/login') }}" class="flex flex-col gap-4">
                @csrf

                <div class="form-control">
                    <label for="email" class="label py-1">
                        <span class="label-text text-sm text-base-content/70">Логин</span>
                    </label>
                    <input
                        type="text"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="username"
                        class="input input-bordered input-sm w-full h-9 bg-base-200 border-base-300 text-base-content placeholder:opacity-50"
                        placeholder="test@test.test"
                    >
                    @error('email')
                        <p class="text-error text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-control">
                    <label for="password" class="label py-1">
                        <span class="label-text text-sm text-base-content/70">Пароль</span>
                    </label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        autocomplete="current-password"
                        class="input input-bordered input-sm w-full h-9 bg-base-200 border-base-300 text-base-content placeholder:opacity-50"
                        placeholder="••••••••"
                    >
                </div>

                <label class="label cursor-pointer justify-start gap-2 py-1">
                    <input type="checkbox" name="remember" class="checkbox checkbox-sm checkbox-primary">
                    <span class="label-text text-sm text-base-content/60">Запомнить меня</span>
                </label>

                <button type="submit" class="btn btn-primary btn-sm h-9 mt-1">
                    Войти
                </button>
            </form>
        </div>
    </main>
    <footer class="py-4 text-center text-sm text-base-content/50">
        &copy; GENEFIS MEDIA, {{ date('Y') }}
    </footer>
</body>
</html>
