{{-- $faction: ?Faction, $world, $allBiographies, $allFactions, $formSuffix --}}
@php
    $suffix = $formSuffix;
    $bioOthers = $allBiographies;
    if (isset($faction) && $faction) {
        $bioOthers = $allBiographies->where('id', '!=', $faction->id);
    }
    $factionOthers = $allFactions;
    if (isset($faction) && $faction) {
        $factionOthers = $allFactions->where('id', '!=', $faction->id);
    }
    $oldMember = old('member_ids', isset($faction) && $faction ? $faction->members->pluck('id')->all() : []);
    $oldRelated = old('related_ids', isset($faction) && $faction ? $faction->relatedFactions->pluck('id')->all() : []);
    $oldEnemy = old('enemy_ids', isset($faction) && $faction ? $faction->enemyFactions->pluck('id')->all() : []);
    $typeVal = old('type', isset($faction) && $faction ? $faction->type : \App\Support\FactionType::ORGANIZATION);
@endphp

<label for="faction-name-{{ $suffix }}" class="block text-sm text-base-content/70 mb-1">Название</label>
<input type="text" name="name" id="faction-name-{{ $suffix }}" value="{{ old('name', optional($faction)->name) }}" required maxlength="255"
    class="input input-bordered w-full rounded-none bg-base-200 border-base-300 @error('name') input-error @enderror">
@error('name')<p class="text-error text-sm mt-1">{{ $message }}</p>@enderror

<label for="faction-type-{{ $suffix }}" class="block text-sm text-base-content/70 mb-1 mt-4">Тип фракции</label>
<select name="type" id="faction-type-{{ $suffix }}" data-faction-type-select="{{ $suffix }}"
    class="select select-bordered w-full rounded-none bg-base-200 border-base-300 @error('type') select-error @enderror">
    @foreach (\App\Support\FactionType::labels() as $key => $label)
        <option value="{{ $key }}" @selected($typeVal === $key)>{{ $label }}</option>
    @endforeach
</select>
@error('type')<p class="text-error text-sm mt-1">{{ $message }}</p>@enderror

<div id="faction-type-other-wrap-{{ $suffix }}" class="mt-2 @if ($typeVal !== \App\Support\FactionType::OTHER) hidden @endif">
    <label for="faction-type-custom-{{ $suffix }}" class="block text-sm text-base-content/70 mb-1">Свой вариант типа</label>
    <input type="text" name="type_custom" id="faction-type-custom-{{ $suffix }}" value="{{ old('type_custom', optional($faction)->type_custom) }}" maxlength="255"
        class="input input-bordered w-full rounded-none bg-base-200 border-base-300 @error('type_custom') input-error @enderror">
    @error('type_custom')<p class="text-error text-sm mt-1">{{ $message }}</p>@enderror
</div>

<label for="faction-image-{{ $suffix }}" class="block text-sm text-base-content/70 mb-1 mt-4">Изображение</label>
<input type="file" name="image" id="faction-image-{{ $suffix }}" accept="image/*"
    class="file-input file-input-bordered w-full rounded-none bg-base-200 border-base-300 @error('image') input-error @enderror">
@error('image')<p class="text-error text-sm mt-1">{{ $message }}</p>@enderror

<label for="faction-short-{{ $suffix }}" class="block text-sm text-base-content/70 mb-1 mt-4">Краткое описание</label>
<textarea name="short_description" id="faction-short-{{ $suffix }}" rows="4"
    class="textarea textarea-bordered w-full rounded-none bg-base-200 border-base-300 resize-y min-h-[5rem] @error('short_description') textarea-error @enderror">{{ old('short_description', optional($faction)->short_description) }}</textarea>
@error('short_description')<p class="text-error text-sm mt-1">{{ $message }}</p>@enderror

<label for="faction-full-{{ $suffix }}" class="block text-sm text-base-content/70 mb-1 mt-4">Полное описание</label>
<textarea name="full_description" id="faction-full-{{ $suffix }}" rows="8"
    class="textarea textarea-bordered w-full rounded-none bg-base-200 border-base-300 resize-y min-h-[10rem] @error('full_description') textarea-error @enderror">{{ old('full_description', optional($faction)->full_description) }}</textarea>
@error('full_description')<p class="text-error text-sm mt-1">{{ $message }}</p>@enderror

<label for="faction-geo-{{ $suffix }}" class="block text-sm text-base-content/70 mb-1 mt-4">Географические объекты</label>
<textarea name="geographic_stub" id="faction-geo-{{ $suffix }}" rows="2" placeholder="Позже здесь будет выбор с карт; пока можно оставить заметку."
    class="textarea textarea-bordered w-full rounded-none bg-base-200 border-base-300 @error('geographic_stub') textarea-error @enderror">{{ old('geographic_stub', optional($faction)->geographic_stub) }}</textarea>
@error('geographic_stub')<p class="text-error text-sm mt-1">{{ $message }}</p>@enderror

<label for="faction-members-{{ $suffix }}" class="block text-sm text-base-content/70 mb-1 mt-6">Члены фракции</label>
<select name="member_ids[]" id="faction-members-{{ $suffix }}" multiple size="6"
    class="select select-bordered w-full rounded-none bg-base-200 border-base-300 max-h-[8lh] overflow-y-auto @error('member_ids') select-error @enderror">
    @foreach ($bioOthers as $b)
        <option value="{{ $b->id }}" @selected(in_array($b->id, (array) $oldMember, true))>{{ $b->name }}</option>
    @endforeach
</select>
<p class="text-xs text-base-content/50 mt-1">Биографии персонажей этого мира.</p>
@error('member_ids')<p class="text-error text-sm mt-1">{{ $message }}</p>@enderror

<label for="faction-related-{{ $suffix }}" class="block text-sm text-base-content/70 mb-1 mt-4">Связанные фракции</label>
<select name="related_ids[]" id="faction-related-{{ $suffix }}" multiple size="5"
    class="select select-bordered w-full rounded-none bg-base-200 border-base-300 max-h-[7lh] overflow-y-auto @error('related_ids') select-error @enderror">
    @foreach ($factionOthers as $f)
        <option value="{{ $f->id }}" @selected(in_array($f->id, (array) $oldRelated, true))>{{ $f->name }}</option>
    @endforeach
</select>
@error('related_ids')<p class="text-error text-sm mt-1">{{ $message }}</p>@enderror

<label for="faction-enemy-{{ $suffix }}" class="block text-sm text-base-content/70 mb-1 mt-4">Вражеские фракции</label>
<select name="enemy_ids[]" id="faction-enemy-{{ $suffix }}" multiple size="5"
    class="select select-bordered w-full rounded-none bg-base-200 border-base-300 max-h-[7lh] overflow-y-auto @error('enemy_ids') select-error @enderror">
    @foreach ($factionOthers as $f)
        <option value="{{ $f->id }}" @selected(in_array($f->id, (array) $oldEnemy, true))>{{ $f->name }}</option>
    @endforeach
</select>
@error('enemy_ids')<p class="text-error text-sm mt-1">{{ $message }}</p>@enderror
