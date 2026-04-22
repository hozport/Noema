{{--
  Единая верхняя строка страниц мира: заголовок (слева) | центр (создание сущностей и др.) | действия (справа).

  В одной строке сетки только основной заголовок (слот title) и кнопки. Подзаголовки, мета и пояснения — в слоте below (полная ширина под строкой).
  Фильтры, поиск, select и переключатели списка — не в этом компоненте: блок сразу под </x-noema-page-head> (см. .cursor/rules/noema-page-head-ui.mdc).

  Порядок в правом блоке: Назад (если есть) → выгрузка (PDF и т.п.) → настройки/редактирование → удаление → журнал.
  Центр по умолчанию пустой — задайте слот center только где нужны кнопки создания (как на таймлайне и в истории карточек).

  По умолчанию блок отстоит от соседних по вертикали на 15px (wrapperClass my-[15px]); при необходимости передайте свой wrapperClass.
--}}
@props([
    'wrapperClass' => 'my-[15px]',
])
<div {{ $attributes->merge(['class' => 'min-w-0 '.$wrapperClass]) }}>
    <div class="grid grid-cols-1 gap-y-4 md:grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] md:items-center md:gap-x-4 md:gap-y-0">
        <div class="min-w-0 md:justify-self-start self-center">
            {{ $title }}
        </div>
        <div class="flex w-full justify-center gap-2 md:w-auto md:justify-self-center flex-wrap self-center">
            {{ $center ?? '' }}
        </div>
        <div class="flex flex-wrap items-center gap-1 justify-end md:justify-self-end self-center">
            {{ $actions }}
        </div>
    </div>
    @isset($below)
        <div class="min-w-0 mt-1">
            {{ $below }}
        </div>
    @endisset
</div>
