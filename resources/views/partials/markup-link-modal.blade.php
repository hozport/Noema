{{-- Одно на страницу: общее для всех полей Noema-разметки --}}
<dialog id="linkEntityModal" class="noema-markup-preview-dialog" aria-labelledby="link-entity-heading">
    <div class="noema-markup-preview-viewport">
        <div class="noema-markup-preview-scrim" data-link-modal-close tabindex="-1" aria-hidden="true"></div>
        <div class="noema-markup-preview-panel max-w-md relative" onclick="event.stopPropagation()">
            <h2 id="link-entity-heading" class="text-lg font-semibold mb-3 pr-8">Ссылка на сущность</h2>
            <label for="linkModuleSelect" class="block text-sm text-base-content/70 mb-1">Модуль</label>
            <select id="linkModuleSelect" class="select select-bordered w-full rounded-none bg-base-200 border-base-300 mb-3"></select>
            <label for="linkEntitySelect" class="block text-sm text-base-content/70 mb-1">Сущность</label>
            <select id="linkEntitySelect" class="select select-bordered w-full rounded-none bg-base-200 border-base-300 mb-4"></select>
            <div class="flex flex-row-reverse gap-2 justify-end">
                <button type="button" id="linkModalConfirm" class="btn btn-primary rounded-none">Вставить</button>
                <button type="button" id="linkModalCancel" class="btn btn-ghost rounded-none" data-link-modal-close>Отмена</button>
            </div>
            <button type="button" class="btn btn-ghost btn-sm btn-circle absolute top-2 right-2" data-link-modal-close aria-label="Закрыть" title="Закрыть">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
            </button>
        </div>
    </div>
</dialog>
