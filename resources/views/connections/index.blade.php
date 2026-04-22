<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.flash-toast-critical-css')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Связи — {{ $world->name }} — Noema</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=cormorant-garamond:400,500,600,700|instrument-sans:400,500,600" rel="stylesheet">
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    <style>
        .card-block-container { display: flex; flex-wrap: wrap; gap: 1rem; }
        .card-block-container .card-block {
            width: 200px !important; min-width: 200px !important; max-width: 200px !important;
            min-height: 120px !important; flex-shrink: 0 !important;
            border-radius: 0 !important;
        }
    </style>
</head>
<body class="min-h-screen bg-base-100 flex flex-col">
    @include('site.partials.header')

    <main class="flex-1 p-6 max-w-[1344px] w-full mx-auto">
        <x-noema-page-head>
            <x-slot name="title">
                <h1 class="text-[1.875rem] font-semibold text-base-content leading-tight min-w-0" style="font-family: 'Cormorant Garamond', Georgia, serif;">Связи</h1>
            </x-slot>
            <x-slot name="center">
                <button type="button" class="btn btn-primary btn-sm btn-square shrink-0 rounded-none" onclick="document.getElementById('addConnectionBoardModal').showModal()" title="Новая доска" aria-label="Новая доска">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                </button>
            </x-slot>
            <x-slot name="actions">
                <a href="{{ route('worlds.dashboard', $world) }}" class="btn btn-ghost btn-square" title="Назад в дашборд" aria-label="Назад в дашборд">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M19 12H5M12 19l-7-7 7-7"/>
                    </svg>
                </a>
                @include('partials.activity-log-button', ['world' => $world, 'connectionsModuleJournal' => true])
            </x-slot>
            <x-slot name="below">
                <p class="text-base-content/60 mb-6 max-w-2xl">{{ $world->name }} — доски для связей между сущностями мира (как на детективной стене).</p>
            </x-slot>
        </x-noema-page-head>

        @if (session('success'))
            <p class="text-success mb-4" role="alert" data-auto-dismiss>{{ session('success') }}</p>
        @endif

        @if ($boards->isNotEmpty())
            <div class="card-block-container">
                @foreach ($boards as $board)
                    <a href="{{ route('worlds.connections.show', [$world, $board]) }}" class="card card-block bg-base-200 border border-base-300 hover:border-primary/30 transition-colors flex flex-col items-center justify-center gap-1 p-6 text-center">
                        <h2 class="text-lg font-semibold text-base-content">{{ $board->name }}</h2>
                    </a>
                @endforeach
            </div>
        @else
            <p class="text-base-content/60 mb-6">Пока нет досок. Создайте первую.</p>
        @endif
    </main>

    <dialog id="addConnectionBoardModal" class="modal" aria-labelledby="add-board-heading">
        <div class="modal-box rounded-none max-w-md">
            <form method="POST" action="{{ route('worlds.connections.store', $world) }}">
                @csrf
                <h2 id="add-board-heading" class="text-xl font-semibold mb-4">Новая доска</h2>
                <label for="newBoardName" class="block text-sm text-base-content/70 mb-1">Название</label>
                <input type="text" id="newBoardName" name="name" value="{{ old('name') }}" required placeholder="Название доски" maxlength="255"
                    class="input input-bordered w-full rounded-none bg-base-200 border-base-300 py-3 @error('name') input-error @enderror">
                @error('name')
                    <p class="text-error text-sm mt-2">{{ $message }}</p>
                @enderror
                <div class="modal-action mt-6">
                    <button type="button" class="btn btn-ghost rounded-none" onclick="document.getElementById('addConnectionBoardModal').close()">Отмена</button>
                    <button type="submit" class="btn btn-primary rounded-none">Создать</button>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop"><button type="submit" class="cursor-default" aria-label="Закрыть">close</button></form>
    </dialog>

    @include('site.partials.footer')
    @if ($errors->has('name'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.getElementById('addConnectionBoardModal')?.showModal();
            });
        </script>
    @endif
</body>
</html>
