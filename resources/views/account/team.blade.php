@extends('layouts.noema-app')

@section('title', 'Команда — Noema')

@section('content')
    <div class="mb-6">
        <a href="{{ route('worlds.index') }}" class="link link-hover text-base-content/70 text-sm">← Назад к мирам</a>
    </div>

    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-8 max-w-3xl">
        <div>
            <h1 class="font-display text-3xl md:text-4xl text-base-content font-semibold tracking-tight mb-2">Команда</h1>
            <p class="text-base text-base-content/75 leading-relaxed">
                Доступ помощникам и советчикам с настройкой прав. Список участников и сохранение появятся позже.
            </p>
        </div>
        <button type="button" class="btn btn-primary rounded-none shrink-0" onclick="teamAddMemberModal.showModal()">
            Добавить
        </button>
    </div>

    <p class="text-sm text-base-content/50 max-w-2xl">Пока нет приглашённых участников.</p>

    <dialog id="teamAddMemberModal" class="modal modal-middle">
        <div class="modal-box rounded-none max-w-md w-[90vw] p-6 md:p-8 border border-base-300 bg-base-200 shadow-2xl">
            <h2 class="font-display text-xl font-semibold text-base-content mb-1">Новый участник</h2>
            <p class="text-sm text-base-content/60 mb-6">Только интерфейс — данные не сохраняются.</p>
            <form id="teamAddMemberForm" class="space-y-4">
                <div class="form-control">
                    <label class="label py-1"><span class="label-text">Имя</span></label>
                    <input type="text" name="name" autocomplete="name"
                        class="input input-bordered w-full rounded-none bg-base-100 border-base-300"
                        placeholder="Имя или позывной">
                </div>
                <div class="form-control">
                    <label class="label py-1"><span class="label-text">E-mail</span></label>
                    <input type="email" name="email" autocomplete="email"
                        class="input input-bordered w-full rounded-none bg-base-100 border-base-300"
                        placeholder="email@example.com">
                </div>
                <div class="form-control">
                    <label class="label py-1"><span class="label-text">Роль</span></label>
                    <select name="role" class="select select-bordered w-full rounded-none bg-base-100 border-base-300">
                        <option value="observer">Наблюдатель</option>
                        <option value="editor">Редактор</option>
                        <option value="creator">Создатель</option>
                    </select>
                </div>
                <div class="modal-action flex flex-wrap gap-2 justify-end pt-4">
                    <button type="button" class="btn btn-ghost rounded-none" onclick="teamAddMemberModal.close()">Отмена</button>
                    <button type="submit" class="btn btn-primary rounded-none">Готово</button>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop"><button type="submit" class="cursor-default opacity-0 absolute inset-0 w-full h-full" aria-label="Закрыть">close</button></form>
    </dialog>
@endsection

@push('styles')
    <style>
        dialog.modal:not([open]) { display: none !important; }
        dialog.modal[open] {
            position: fixed !important; top: 0 !important; left: 0 !important; right: 0 !important; bottom: 0 !important;
            width: 100vw !important; height: 100vh !important; margin: 0 !important; padding: 1rem !important;
            display: flex !important; align-items: center !important; justify-content: center !important;
            z-index: 999 !important; overflow-y: auto !important;
        }
        dialog.modal[open]::backdrop { background: rgba(0,0,0,0.6); }
        dialog.modal[open] .modal-backdrop {
            position: absolute !important; inset: 0 !important; z-index: -1 !important;
        }
    </style>
@endpush

@push('scripts')
    <script>
        const teamAddMemberModal = document.getElementById('teamAddMemberModal');
        const teamAddMemberForm = document.getElementById('teamAddMemberForm');
        teamAddMemberForm?.addEventListener('submit', function (e) {
            e.preventDefault();
            teamAddMemberModal?.close();
        });
        teamAddMemberModal?.addEventListener('close', function () {
            teamAddMemberForm?.reset();
        });
    </script>
@endpush
