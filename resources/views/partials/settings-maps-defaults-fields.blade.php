{{-- Общий блок полей «размеры карты по умолчанию» — аккаунт и модуль «Карты» в мире. --}}
@props([
    'widthDefault',
    'heightDefault',
    'idPrefix' => 'maps-default',
    'hint' => null,
])
@php
    $min = \App\Models\Worlds\WorldMap::MIN_SIDE;
    $max = \App\Models\Worlds\WorldMap::MAX_SIDE;
@endphp
<div class="border border-base-300/80 bg-base-100/40 p-4 space-y-4">
    <p class="text-xs font-medium text-base-content/60 uppercase tracking-wide">Размеры по умолчанию</p>
    @if ($hint !== null && $hint !== '')
        <p class="text-sm text-base-content/70">{{ $hint }}</p>
    @endif
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="form-control w-full">
            <label class="label py-1" for="{{ $idPrefix }}-width"><span class="label-text">Ширина (px)</span></label>
            <input type="number" id="{{ $idPrefix }}-width" name="maps_default_width" value="{{ old('maps_default_width', $widthDefault) }}" required min="{{ $min }}" max="{{ $max }}" step="1"
                class="input input-bordered w-full rounded-none bg-base-100 border-base-300 tabular-nums @error('maps_default_width') input-error @enderror">
            @error('maps_default_width')
                <p class="text-error text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>
        <div class="form-control w-full">
            <label class="label py-1" for="{{ $idPrefix }}-height"><span class="label-text">Высота (px)</span></label>
            <input type="number" id="{{ $idPrefix }}-height" name="maps_default_height" value="{{ old('maps_default_height', $heightDefault) }}" required min="{{ $min }}" max="{{ $max }}" step="1"
                class="input input-bordered w-full rounded-none bg-base-100 border-base-300 tabular-nums @error('maps_default_height') input-error @enderror">
            @error('maps_default_height')
                <p class="text-error text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>
    </div>
    <p class="text-xs text-base-content/50">{{ $min }}…{{ $max }} px по каждой стороне.</p>
</div>
