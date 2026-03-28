{{-- $biography: ?Biography, $world, $allBiographies: Collection, $formSuffix: string --}}
@php
    $suffix = $formSuffix;
    $others = isset($biography) && $biography
        ? $allBiographies->where('id', '!=', $biography->id)
        : $allBiographies;
    $oldRel = old('relative_ids', isset($biography) && $biography ? $biography->relatives->pluck('id')->all() : []);
    $oldFr = old('friend_ids', isset($biography) && $biography ? $biography->friends->pluck('id')->all() : []);
    $oldEn = old('enemy_ids', isset($biography) && $biography ? $biography->enemies->pluck('id')->all() : []);
@endphp

<label for="bio-name-{{ $suffix }}" class="block text-sm text-base-content/70 mb-1">Имя</label>
<input type="text" name="name" id="bio-name-{{ $suffix }}" value="{{ old('name', optional($biography)->name) }}" required maxlength="255"
    class="input input-bordered w-full rounded-none bg-base-200 border-base-300 @error('name') input-error @enderror">
@error('name')<p class="text-error text-sm mt-1">{{ $message }}</p>@enderror

<label for="bio-race-{{ $suffix }}" class="block text-sm text-base-content/70 mb-1 mt-4">Раса</label>
<input type="text" name="race" id="bio-race-{{ $suffix }}" value="{{ old('race', optional($biography)->race) }}" maxlength="255"
    class="input input-bordered w-full rounded-none bg-base-200 border-base-300 @error('race') input-error @enderror">
@error('race')<p class="text-error text-sm mt-1">{{ $message }}</p>@enderror

<p class="text-xs text-base-content/50 mt-4 mb-2">Рождение и смерть по <strong>шкале вашего мира</strong> (ввод числами, без календаря устройства). Достаточно года; месяц и день — по желанию.</p>
<div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-1">
    <div>
        <label for="bio-birth-year-{{ $suffix }}" class="block text-sm text-base-content/70 mb-1">Год рождения</label>
        <input type="number" name="birth_year" id="bio-birth-year-{{ $suffix }}" value="{{ old('birth_year', optional($biography)->birth_year) }}" min="0" step="1"
            class="input input-bordered input-sm w-full rounded-none bg-base-200 border-base-300 @error('birth_year') input-error @enderror"
            placeholder="—" autocomplete="off">
        @error('birth_year')<p class="text-error text-sm mt-1">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="bio-birth-month-{{ $suffix }}" class="block text-sm text-base-content/70 mb-1">Месяц <span class="opacity-60">(необяз.)</span></label>
        <input type="number" name="birth_month" id="bio-birth-month-{{ $suffix }}" value="{{ old('birth_month', optional($biography)->birth_month) }}" min="1" max="100" step="1"
            class="input input-bordered input-sm w-full rounded-none bg-base-200 border-base-300 @error('birth_month') input-error @enderror"
            placeholder="—" autocomplete="off">
        @error('birth_month')<p class="text-error text-sm mt-1">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="bio-birth-day-{{ $suffix }}" class="block text-sm text-base-content/70 mb-1">День <span class="opacity-60">(необяз.)</span></label>
        <input type="number" name="birth_day" id="bio-birth-day-{{ $suffix }}" value="{{ old('birth_day', optional($biography)->birth_day) }}" min="1" max="100" step="1"
            class="input input-bordered input-sm w-full rounded-none bg-base-200 border-base-300 @error('birth_day') input-error @enderror"
            placeholder="—" autocomplete="off">
        @error('birth_day')<p class="text-error text-sm mt-1">{{ $message }}</p>@enderror
    </div>
</div>
<div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-4">
    <div>
        <label for="bio-death-year-{{ $suffix }}" class="block text-sm text-base-content/70 mb-1">Год смерти</label>
        <input type="number" name="death_year" id="bio-death-year-{{ $suffix }}" value="{{ old('death_year', optional($biography)->death_year) }}" min="0" step="1"
            class="input input-bordered input-sm w-full rounded-none bg-base-200 border-base-300 @error('death_year') input-error @enderror"
            placeholder="—" autocomplete="off">
        @error('death_year')<p class="text-error text-sm mt-1">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="bio-death-month-{{ $suffix }}" class="block text-sm text-base-content/70 mb-1">Месяц <span class="opacity-60">(необяз.)</span></label>
        <input type="number" name="death_month" id="bio-death-month-{{ $suffix }}" value="{{ old('death_month', optional($biography)->death_month) }}" min="1" max="100" step="1"
            class="input input-bordered input-sm w-full rounded-none bg-base-200 border-base-300 @error('death_month') input-error @enderror"
            placeholder="—" autocomplete="off">
        @error('death_month')<p class="text-error text-sm mt-1">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="bio-death-day-{{ $suffix }}" class="block text-sm text-base-content/70 mb-1">День <span class="opacity-60">(необяз.)</span></label>
        <input type="number" name="death_day" id="bio-death-day-{{ $suffix }}" value="{{ old('death_day', optional($biography)->death_day) }}" min="1" max="100" step="1"
            class="input input-bordered input-sm w-full rounded-none bg-base-200 border-base-300 @error('death_day') input-error @enderror"
            placeholder="—" autocomplete="off">
        @error('death_day')<p class="text-error text-sm mt-1">{{ $message }}</p>@enderror
    </div>
</div>

<label for="bio-image-{{ $suffix }}" class="block text-sm text-base-content/70 mb-1 mt-4">Изображение</label>
<input type="file" name="image" id="bio-image-{{ $suffix }}" accept="image/*"
    class="file-input file-input-bordered w-full rounded-none bg-base-200 border-base-300 @error('image') input-error @enderror">
@error('image')<p class="text-error text-sm mt-1">{{ $message }}</p>@enderror

<label for="bio-short-{{ $suffix }}" class="block text-sm text-base-content/70 mb-1 mt-4">Краткое описание</label>
<textarea name="short_description" id="bio-short-{{ $suffix }}" rows="4"
    class="textarea textarea-bordered w-full rounded-none bg-base-200 border-base-300 resize-y min-h-[5rem] @error('short_description') textarea-error @enderror">{{ old('short_description', optional($biography)->short_description) }}</textarea>
@error('short_description')<p class="text-error text-sm mt-1">{{ $message }}</p>@enderror

<label for="bio-full-{{ $suffix }}" class="block text-sm text-base-content/70 mb-1 mt-4">Полное описание</label>
<textarea name="full_description" id="bio-full-{{ $suffix }}" rows="8"
    class="textarea textarea-bordered w-full rounded-none bg-base-200 border-base-300 resize-y min-h-[10rem] @error('full_description') textarea-error @enderror">{{ old('full_description', optional($biography)->full_description) }}</textarea>
@error('full_description')<p class="text-error text-sm mt-1">{{ $message }}</p>@enderror

<label for="bio-rel-{{ $suffix }}" class="block text-sm text-base-content/70 mb-1 mt-6">Родственные связи</label>
<select name="relative_ids[]" id="bio-rel-{{ $suffix }}" multiple size="5"
    aria-describedby="bio-rel-hint-{{ $suffix }}"
    class="select select-bordered w-full rounded-none bg-base-200 border-base-300 min-h-[8rem] @error('relative_ids') select-error @enderror">
    @foreach ($others as $b)
        <option value="{{ $b->id }}" @selected(in_array($b->id, (array) $oldRel, true))>{{ $b->name }}</option>
    @endforeach
</select>
<p id="bio-rel-hint-{{ $suffix }}" class="text-xs text-base-content/50 mt-1">Удерживайте Ctrl/Cmd для выбора нескольких биографий.</p>
@error('relative_ids')<p class="text-error text-sm mt-1">{{ $message }}</p>@enderror
@error('relative_ids.*')<p class="text-error text-sm mt-1">{{ $message }}</p>@enderror

<label for="bio-fr-{{ $suffix }}" class="block text-sm text-base-content/70 mb-1 mt-4">Друзья</label>
<select name="friend_ids[]" id="bio-fr-{{ $suffix }}" multiple size="5"
    class="select select-bordered w-full rounded-none bg-base-200 border-base-300 min-h-[8rem] @error('friend_ids') select-error @enderror">
    @foreach ($others as $b)
        <option value="{{ $b->id }}" @selected(in_array($b->id, (array) $oldFr, true))>{{ $b->name }}</option>
    @endforeach
</select>
@error('friend_ids')<p class="text-error text-sm mt-1">{{ $message }}</p>@enderror

<label for="bio-en-{{ $suffix }}" class="block text-sm text-base-content/70 mb-1 mt-4">Враги</label>
<select name="enemy_ids[]" id="bio-en-{{ $suffix }}" multiple size="5"
    class="select select-bordered w-full rounded-none bg-base-200 border-base-300 min-h-[8rem] @error('enemy_ids') select-error @enderror">
    @foreach ($others as $b)
        <option value="{{ $b->id }}" @selected(in_array($b->id, (array) $oldEn, true))>{{ $b->name }}</option>
    @endforeach
</select>
@error('enemy_ids')<p class="text-error text-sm mt-1">{{ $message }}</p>@enderror
