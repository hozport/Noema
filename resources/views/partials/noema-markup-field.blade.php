{{--
  $name, $baseId, $label, $value — обязательны; $mtClass — отступ сверху блока (по умолчанию mt-4)
--}}
@php
    $mtClass = $mtClass ?? 'mt-4';
@endphp
@include('partials.noema-markup-shared-styles')

<div data-noema-markup-field data-noema-markup-base="{{ $baseId }}">
    <div class="flex flex-wrap items-center justify-between gap-2 mb-1 {{ $mtClass }}">
        <label for="{{ $baseId }}-view" class="block text-sm text-base-content/70 shrink-0">{{ $label }}</label>
        <div class="dropdown dropdown-end">
            <button type="button" tabindex="0" class="btn btn-ghost btn-xs btn-square rounded-none min-h-0 h-7 w-7 shrink-0 border border-base-300 text-base-content/70 hover:text-base-content" aria-label="Справка по разметке" title="Справка">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            </button>
            <div tabindex="0" class="dropdown-content bg-base-200 border border-base-300 rounded-none z-[120] w-[min(calc(100vw-2rem),22rem)] max-h-[min(70vh,20rem)] overflow-y-auto shadow-lg p-3 mt-1 text-left text-xs text-base-content/90 leading-snug">
                <p class="mb-3 last:mb-0">Клик по тексту — редактирование. После выделения — панель форматирования; правый клик — меню в точке курсора. Ctrl/Cmd+B/I/U, зачёркивание — Ctrl/Cmd+Shift+S. Теги: <code class="text-[0.8rem]">[b][/b]</code> <code class="text-[0.8rem]">[i][/i]</code> <code class="text-[0.8rem]">[u][/u]</code> <code class="text-[0.8rem]">[s][/s]</code>, ссылка <code class="text-[0.8rem]">[link module=M entity=E]…[/link]</code>. Экранирование <code class="text-[0.8rem]">\</code>.</p>
                <p class="mb-0">Перенос строки внутри тегов не допускается.</p>
            </div>
        </div>
    </div>
    <input type="hidden" name="{{ $name }}" id="{{ $baseId }}-hidden" value="{{ old($name, $value) }}" autocomplete="off">
    <div id="{{ $baseId }}-view-wrap">
        <div id="{{ $baseId }}-view" class="noema-markup-view textarea textarea-bordered w-full rounded-none bg-base-200 border-base-300 min-h-[8rem] max-h-[22rem] overflow-auto p-3 text-sm leading-relaxed cursor-pointer whitespace-pre-wrap" tabindex="-1" role="region" aria-label="{{ $label }}"></div>
    </div>
    <div id="{{ $baseId }}-edit-wrap" class="hidden mt-2">
        <div id="{{ $baseId }}-cm-host" class="rounded-none border border-base-300 bg-base-200 overflow-hidden min-h-[12rem]"></div>
        <div class="flex flex-wrap items-center gap-4 mt-2">
            <button type="button" id="{{ $baseId }}-preview-toggle" class="btn btn-link btn-sm rounded-none px-0 min-h-0 h-auto font-normal underline text-base-content/80 hover:text-base-content">Просмотр</button>
        </div>
    </div>
    @error($name)
        <p class="text-error text-sm mt-1">{{ $message }}</p>
    @enderror
</div>

<dialog id="{{ $baseId }}-preview-dialog" class="noema-markup-preview-dialog" aria-labelledby="{{ $baseId }}-preview-heading">
    <div class="noema-markup-preview-viewport">
        <div class="noema-markup-preview-scrim" data-markup-preview-close tabindex="-1" aria-hidden="true"></div>
        <div class="noema-markup-preview-panel max-w-lg" onclick="event.stopPropagation()">
            <h2 id="{{ $baseId }}-preview-heading" class="text-lg font-semibold mb-3 pr-8">Просмотр разметки</h2>
            <div id="{{ $baseId }}-preview-body" class="noema-markup-view textarea textarea-bordered w-full rounded-none bg-base-200 border-base-300 min-h-[8rem] max-h-[min(60vh,28rem)] overflow-auto p-3 text-sm leading-relaxed whitespace-pre-wrap"></div>
            <div class="mt-4 flex flex-row-reverse">
                <button type="button" class="btn btn-primary rounded-none" data-markup-preview-close>Закрыть</button>
            </div>
            <button type="button" class="btn btn-ghost btn-sm btn-circle absolute top-2 right-2" data-markup-preview-close aria-label="Закрыть" title="Закрыть">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
            </button>
        </div>
    </div>
</dialog>
