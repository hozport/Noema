{{-- Порядок карточек на странице «Мои миры» — общий блок для списка миров и /account/settings. --}}
@props([
    'currentSort',
    'selectId' => 'worlds-list-sort',
    'selectLabel' => 'Последовательность',
])
@php
    $u = \App\Models\User::class;
@endphp
<div class="form-control w-full">
    <label class="label py-1" for="{{ $selectId }}"><span class="label-text">{{ $selectLabel }}</span></label>
    <select id="{{ $selectId }}" name="worlds_list_sort" class="select select-bordered w-full rounded-none bg-base-100 border-base-300 @error('worlds_list_sort') select-error @enderror">
        <option value="{{ $u::WORLDS_SORT_ALPHABET }}" @selected($currentSort === $u::WORLDS_SORT_ALPHABET)>По алфавиту</option>
        <option value="{{ $u::WORLDS_SORT_CREATED_AT }}" @selected($currentSort === $u::WORLDS_SORT_CREATED_AT)>По дате создания</option>
        <option value="{{ $u::WORLDS_SORT_UPDATED_AT }}" @selected($currentSort === $u::WORLDS_SORT_UPDATED_AT)>По дате последнего обновления</option>
    </select>
    @error('worlds_list_sort')
        <p class="text-error text-sm mt-1">{{ $message }}</p>
    @enderror
</div>
