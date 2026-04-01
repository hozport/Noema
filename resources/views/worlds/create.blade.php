<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Создать Мир — Noema</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=cormorant-garamond:400,500,600,700|instrument-sans:400,500,600" rel="stylesheet">
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    <style>
        .create-form input,
        .create-form textarea {
            font-family: inherit;
        }
        .create-form input::placeholder,
        .create-form input::-webkit-input-placeholder,
        .create-form input::-moz-placeholder,
        .create-form input:-ms-input-placeholder {
            color: rgba(255,255,255,0.35) !important;
            font-weight: 400;
            opacity: 1;
        }
        .create-form input:hover,
        .create-form input:focus,
        .create-form textarea:hover,
        .create-form textarea:focus,
        .create-form input:active {
            background: transparent !important;
        }
    </style>
</head>
<body class="min-h-screen bg-base-100 flex flex-col">
    @include('site.partials.header')

    <main style="flex: 1; display: flex; align-items: center; justify-content: center; padding: 4rem 3rem 6rem;">
        <div style="width: 60%; max-width: 900px; min-width: 320px;">
            <div class="flex justify-end mb-4">
                <a href="{{ route('worlds.index') }}" class="btn btn-ghost btn-square shrink-0" title="Назад к мирам" aria-label="Назад к мирам">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M19 12H5M12 19l-7-7 7-7"/>
                    </svg>
                </a>
            </div>
            <form method="POST" action="{{ route('worlds.store') }}" enctype="multipart/form-data" class="create-form" style="display: flex; flex-direction: column; gap: 4rem;">
                @csrf

                <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="{{ old('name') }}"
                        required
                        autofocus
                        placeholder="Введите название Мира"
                        style="width: 100%; padding: 1rem 0; font-size: 26px; background: transparent; color: inherit; border: none; border-bottom: 1px solid rgba(255,255,255,0.2); outline: none;"
                    >
                    @error('name')
                        <p class="text-error text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                    <input
                        type="text"
                        id="reference_point"
                        name="reference_point"
                        value="{{ old('reference_point') }}"
                        placeholder="Введите точку отсчёта (к примеру, Большой Взрыв, Сотворение мира и пр.)"
                        style="width: 100%; padding: 1rem 0; font-size: 26px; background: transparent; color: inherit; border: none; border-bottom: 1px solid rgba(255,255,255,0.2); outline: none;"
                    >
                    @error('reference_point')
                        <p class="text-error text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                    <textarea
                        id="annotation"
                        name="annotation"
                        rows="4"
                        placeholder="Введите краткий синопсис истории."
                        style="width: 100%; padding: 1rem 0 1rem 1rem; font-size: 1rem; font-family: inherit; background: transparent; color: inherit; border: none; border-left: 1px solid rgba(255,255,255,0.2); outline: none; resize: none;"
                    >{{ old('annotation') }}</textarea>
                    @error('annotation')
                        <p class="text-error text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                    <label for="image" style="font-size: 1rem;">
                        Изображение мира
                        <span style="opacity: 0.6; font-weight: normal;">необязательно</span>
                    </label>
                    <input
                        type="file"
                        id="image"
                        name="image"
                        accept="image/*"
                        style="width: 100%; padding: 1rem 0; font-size: 1rem; background: transparent; color: inherit; border: none; border-bottom: 1px solid rgba(255,255,255,0.2); outline: none;"
                    >
                    @error('image')
                        <p class="text-error text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div style="display: flex; justify-content: center; padding-top: 2rem;">
                    <button type="submit" class="btn btn-primary min-h-0 normal-case" style="padding: 1.25rem 3rem; border-radius: 0;">
                        Далее
                    </button>
                </div>
            </form>
        </div>
    </main>
    @include('site.partials.footer')
    {{-- Стили в конце body, чтобы переопределить Tailwind/DaisyUI --}}
    <style>
        .create-form textarea::placeholder,
        .create-form textarea::-webkit-input-placeholder,
        .create-form textarea::-moz-placeholder,
        .create-form textarea:-ms-input-placeholder {
            color: rgba(255,255,255,0.35) !important;
            font-weight: 400 !important;
            opacity: 1 !important;
        }
    </style>
</body>
</html>
