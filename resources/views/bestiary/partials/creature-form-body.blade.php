@php
    $c = $creature ?? null;
    $candidates = $c !== null ? $allCreatures->where('id', '!=', $c->id) : $allCreatures;
    $relatedSelected = old('related_ids', $c?->relatedCreatures->pluck('id')->all() ?? []);
    $foodSelected = old('food_creature_ids', $c?->foodCreatures->pluck('id')->all() ?? []);
    if (! is_array($relatedSelected)) {
        $relatedSelected = [];
    }
    if (! is_array($foodSelected)) {
        $foodSelected = [];
    }
@endphp

<label class="block text-sm text-base-content/70 mb-1" for="cf-name-{{ $formSuffix }}">Название</label>
<input type="text" name="name" id="cf-name-{{ $formSuffix }}" value="{{ old('name', $c->name ?? '') }}" required maxlength="255"
    class="input input-bordered w-full rounded-none bg-base-200 border-base-300 @error('name') input-error @enderror">
@error('name')
    <p class="text-error text-sm mt-1">{{ $message }}</p>
@enderror

<label class="block text-sm text-base-content/70 mb-1 mt-4" for="cf-sci-{{ $formSuffix }}">Научное название</label>
<input type="text" name="scientific_name" id="cf-sci-{{ $formSuffix }}" value="{{ old('scientific_name', $c->scientific_name ?? '') }}" maxlength="255"
    class="input input-bordered w-full rounded-none bg-base-200 border-base-300 @error('scientific_name') input-error @enderror">
@error('scientific_name')
    <p class="text-error text-sm mt-1">{{ $message }}</p>
@enderror

<label class="block text-sm text-base-content/70 mb-1 mt-4" for="cf-species-{{ $formSuffix }}">Вид</label>
<input type="text" name="species_kind" id="cf-species-{{ $formSuffix }}" value="{{ old('species_kind', $c->species_kind ?? '') }}" maxlength="255" list="species-kind-datalist-{{ $formSuffix }}"
    class="input input-bordered w-full rounded-none bg-base-200 border-base-300 @error('species_kind') input-error @enderror" placeholder="Выберите из списка или введите новый">
<datalist id="species-kind-datalist-{{ $formSuffix }}">
    @foreach ($speciesSuggestions as $s)
        <option value="{{ $s }}"></option>
    @endforeach
</datalist>
<p class="text-xs text-base-content/50 mt-1">Варианты из других зверей этого мира; можно ввести свой.</p>
@error('species_kind')
    <p class="text-error text-sm mt-1">{{ $message }}</p>
@enderror

<label class="block text-sm text-base-content/70 mb-1 mt-4" for="cf-img-{{ $formSuffix }}">Главное изображение</label>
<input type="file" name="image" id="cf-img-{{ $formSuffix }}" accept="image/*"
    class="file-input file-input-bordered w-full rounded-none bg-base-200 border-base-300 @error('image') file-input-error @enderror">
@error('image')
    <p class="text-error text-sm mt-1">{{ $message }}</p>
@enderror

<div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-4">
    <div>
        <label class="block text-sm text-base-content/70 mb-1" for="cf-h-{{ $formSuffix }}">Рост</label>
        <input type="text" name="height_text" id="cf-h-{{ $formSuffix }}" value="{{ old('height_text', $c->height_text ?? '') }}" maxlength="120" placeholder="10–15 см"
            class="input input-bordered w-full rounded-none bg-base-200 border-base-300 @error('height_text') input-error @enderror">
        @error('height_text')
            <p class="text-error text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>
    <div>
        <label class="block text-sm text-base-content/70 mb-1" for="cf-w-{{ $formSuffix }}">Вес</label>
        <input type="text" name="weight_text" id="cf-w-{{ $formSuffix }}" value="{{ old('weight_text', $c->weight_text ?? '') }}" maxlength="120" placeholder="1–2 кг"
            class="input input-bordered w-full rounded-none bg-base-200 border-base-300 @error('weight_text') input-error @enderror">
        @error('weight_text')
            <p class="text-error text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>
    <div>
        <label class="block text-sm text-base-content/70 mb-1" for="cf-life-{{ $formSuffix }}">Продолжительность жизни</label>
        <input type="text" name="lifespan_text" id="cf-life-{{ $formSuffix }}" value="{{ old('lifespan_text', $c->lifespan_text ?? '') }}" maxlength="120" placeholder="2–3 года"
            class="input input-bordered w-full rounded-none bg-base-200 border-base-300 @error('lifespan_text') input-error @enderror">
        @error('lifespan_text')
            <p class="text-error text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>
</div>

@include('partials.noema-markup-field', [
    'name' => 'short_description',
    'baseId' => 'cf-short-'.$formSuffix,
    'label' => 'Краткое описание',
    'value' => old('short_description', $c->short_description ?? ''),
    'mtClass' => 'mt-4',
])
@include('partials.noema-markup-field', [
    'name' => 'full_description',
    'baseId' => 'cf-full-'.$formSuffix,
    'label' => 'Полное описание',
    'value' => old('full_description', $c->full_description ?? ''),
    'mtClass' => 'mt-4',
])

<label class="block text-sm text-base-content/70 mb-1 mt-4" for="cf-hab-{{ $formSuffix }}">Ореол обитания</label>
<textarea name="habitat_text" id="cf-hab-{{ $formSuffix }}" rows="3" placeholder="Позже можно будет выбрать объекты карты"
    class="textarea textarea-bordered w-full rounded-none bg-base-200 border-base-300 @error('habitat_text') textarea-error @enderror">{{ old('habitat_text', $c->habitat_text ?? '') }}</textarea>
@error('habitat_text')
    <p class="text-error text-sm mt-1">{{ $message }}</p>
@enderror

<p class="text-sm text-base-content/70 mt-4 mb-1">Родственные существа</p>
<div class="max-h-40 overflow-y-auto border border-base-300 rounded-none bg-base-200/50 p-2 space-y-1">
    @forelse ($candidates as $oc)
        <label class="flex items-center gap-2 cursor-pointer text-sm">
            <input type="checkbox" name="related_ids[]" value="{{ $oc->id }}" class="checkbox checkbox-sm rounded-none"
                @checked(in_array($oc->id, $relatedSelected, true))>
            <span>{{ $oc->name }}</span>
        </label>
    @empty
        <p class="text-base-content/50 text-sm">Нет других зверей в мире.</p>
    @endforelse
</div>
@error('related_ids')
    <p class="text-error text-sm mt-1">{{ $message }}</p>
@enderror

<p class="text-sm text-base-content/70 mt-4 mb-1">Пища (другие звери)</p>
<div class="max-h-40 overflow-y-auto border border-base-300 rounded-none bg-base-200/50 p-2 space-y-1">
    @forelse ($candidates as $oc)
        <label class="flex items-center gap-2 cursor-pointer text-sm">
            <input type="checkbox" name="food_creature_ids[]" value="{{ $oc->id }}" class="checkbox checkbox-sm rounded-none"
                @checked(in_array($oc->id, $foodSelected, true))>
            <span>{{ $oc->name }}</span>
        </label>
    @empty
        <p class="text-base-content/50 text-sm">Нет других зверей в мире.</p>
    @endforelse
</div>
@error('food_creature_ids')
    <p class="text-error text-sm mt-1">{{ $message }}</p>
@enderror

<label class="block text-sm text-base-content/70 mb-1 mt-4" for="cf-food-custom-{{ $formSuffix }}">Пища (свой вариант, по одному пункту в строке)</label>
<textarea name="food_custom_text" id="cf-food-custom-{{ $formSuffix }}" rows="3" placeholder="Трава&#10;Насекомые"
    class="textarea textarea-bordered w-full rounded-none bg-base-200 border-base-300 @error('food_custom_text') textarea-error @enderror">{{ old('food_custom_text', $c?->foodCustomLines() ?? '') }}</textarea>
@error('food_custom_text')
    <p class="text-error text-sm mt-1">{{ $message }}</p>
@enderror

<label class="block text-sm text-base-content/70 mb-1 mt-4" for="cf-gallery-{{ $formSuffix }}">Галерея</label>
<input type="file" name="gallery[]" id="cf-gallery-{{ $formSuffix }}" accept="image/*" multiple
    class="file-input file-input-bordered w-full rounded-none bg-base-200 border-base-300 @error('gallery.*') file-input-error @enderror">
@error('gallery.*')
    <p class="text-error text-sm mt-1">{{ $message }}</p>
@enderror

@if ($c !== null && $c->galleryImages->isNotEmpty())
    <p class="text-sm text-base-content/70 mt-4 mb-1">Удалить из галереи</p>
    <div class="flex flex-wrap gap-3">
        @foreach ($c->galleryImages as $g)
            <label class="flex flex-col items-center gap-1 cursor-pointer">
                <img src="{{ $g->url() }}" alt="" class="w-20 h-20 object-cover border border-base-300">
                <span class="text-xs"><input type="checkbox" name="remove_gallery_ids[]" value="{{ $g->id }}" class="checkbox checkbox-xs"> удалить</span>
            </label>
        @endforeach
    </div>
@endif
