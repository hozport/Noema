{{-- $biography: ?Biography, $world, $allBiographies: Collection, $formSuffix: string --}}
@once
<style>
    .bio-multi-relations-select { max-height: 6lh; overflow-y: auto; }
    .bio-kinship-degree-select { max-height: min(40vh, 14rem); }
</style>
@endonce
@php
    $suffix = $formSuffix;
    $raceFactions = $raceFactions ?? collect();
    $peopleFactions = $peopleFactions ?? collect();
    $countryFactions = $countryFactions ?? collect();
    $membershipFactions = $membershipFactions ?? collect();
    $oldRaceFaction = old('race_faction_id');
    if ($oldRaceFaction === null) {
        $oldRaceFaction = isset($biography) && $biography?->race_faction_id ? (string) $biography->race_faction_id : '';
    } else {
        $oldRaceFaction = (string) $oldRaceFaction;
    }
    $oldPeopleFaction = old('people_faction_id');
    if ($oldPeopleFaction === null) {
        $oldPeopleFaction = isset($biography) && $biography?->people_faction_id ? (string) $biography->people_faction_id : '';
    } else {
        $oldPeopleFaction = (string) $oldPeopleFaction;
    }
    $oldCountryFaction = old('country_faction_id');
    if ($oldCountryFaction === null) {
        $oldCountryFaction = isset($biography) && $biography?->country_faction_id ? (string) $biography->country_faction_id : '';
    } else {
        $oldCountryFaction = (string) $oldCountryFaction;
    }
    $oldMembership = old('faction_membership_ids', isset($biography) && $biography ? $biography->membershipFactions->pluck('id')->all() : []);
    $others = isset($biography) && $biography
        ? $allBiographies->where('id', '!=', $biography->id)
        : $allBiographies;
    $oldRel = old('relative_ids', isset($biography) && $biography ? $biography->relatives->pluck('id')->all() : []);
    $oldFr = old('friend_ids', isset($biography) && $biography ? $biography->friends->pluck('id')->all() : []);
    $oldEn = old('enemy_ids', isset($biography) && $biography ? $biography->enemies->pluck('id')->all() : []);
    $initialKinship = [];
    $initialKinshipCustom = [];
    if (isset($biography) && $biography) {
        $biography->loadMissing(['relatives', 'membershipFactions']);
        foreach ($biography->relatives as $r) {
            $initialKinship[$r->id] = $r->pivot->kinship ?? null;
            $initialKinshipCustom[$r->id] = $r->pivot->kinship_custom ?? null;
        }
    }
    $relKinshipOld = old('relative_kinship', $initialKinship);
    $relKinshipCustomOld = old('relative_kinship_custom', $initialKinshipCustom);
    $bioGender = old('gender', isset($biography) && $biography ? $biography->gender : null);
@endphp

<label for="bio-name-{{ $suffix }}" class="block text-sm text-base-content/70 mb-1">Имя</label>
<input type="text" name="name" id="bio-name-{{ $suffix }}" value="{{ old('name', optional($biography)->name) }}" required maxlength="255"
    class="input input-bordered w-full rounded-none bg-base-200 border-base-300 @error('name') input-error @enderror">
@error('name')<p class="text-error text-sm mt-1">{{ $message }}</p>@enderror

<label for="bio-race-{{ $suffix }}" class="block text-sm text-base-content/70 mb-1 mt-4">Раса</label>
<select name="race_faction_id" id="bio-race-{{ $suffix }}" data-bio-faction-other-select="{{ $suffix }}" data-bio-faction-other-wrap-prefix="bio-race-other-wrap"
    class="select select-bordered w-full rounded-none bg-base-200 border-base-300 @error('race_faction_id') select-error @enderror">
    <option value="">—</option>
    @foreach ($raceFactions as $rf)
        <option value="{{ $rf->id }}" @selected($oldRaceFaction === (string) $rf->id)>{{ $rf->name }}</option>
    @endforeach
    <option value="other" @selected($oldRaceFaction === 'other')>Другое…</option>
</select>
@error('race_faction_id')<p class="text-error text-sm mt-1">{{ $message }}</p>@enderror
<div id="bio-race-other-wrap-{{ $suffix }}" class="mt-2 @if ($oldRaceFaction !== 'other') hidden @endif">
    <label for="bio-race-other-{{ $suffix }}" class="block text-sm text-base-content/70 mb-1">Своя раса</label>
    <input type="text" name="race_other_name" id="bio-race-other-{{ $suffix }}" value="{{ old('race_other_name') }}" maxlength="255"
        class="input input-bordered w-full rounded-none bg-base-200 border-base-300 @error('race_other_name') input-error @enderror"
        placeholder="Будет создана фракция с типом «Раса»">
    @error('race_other_name')<p class="text-error text-sm mt-1">{{ $message }}</p>@enderror
</div>

<label for="bio-people-{{ $suffix }}" class="block text-sm text-base-content/70 mb-1 mt-4">Народ</label>
<select name="people_faction_id" id="bio-people-{{ $suffix }}" data-bio-faction-other-select="{{ $suffix }}" data-bio-faction-other-wrap-prefix="bio-people-other-wrap"
    class="select select-bordered w-full rounded-none bg-base-200 border-base-300 @error('people_faction_id') select-error @enderror">
    <option value="">—</option>
    @foreach ($peopleFactions as $pf)
        <option value="{{ $pf->id }}" @selected($oldPeopleFaction === (string) $pf->id)>{{ $pf->name }}</option>
    @endforeach
    <option value="other" @selected($oldPeopleFaction === 'other')>Другое…</option>
</select>
@error('people_faction_id')<p class="text-error text-sm mt-1">{{ $message }}</p>@enderror
<div id="bio-people-other-wrap-{{ $suffix }}" class="mt-2 @if ($oldPeopleFaction !== 'other') hidden @endif">
    <label for="bio-people-other-{{ $suffix }}" class="block text-sm text-base-content/70 mb-1">Свой народ</label>
    <input type="text" name="people_other_name" id="bio-people-other-{{ $suffix }}" value="{{ old('people_other_name') }}" maxlength="255"
        class="input input-bordered w-full rounded-none bg-base-200 border-base-300 @error('people_other_name') input-error @enderror"
        placeholder="Будет создана фракция с типом «Народ»">
    @error('people_other_name')<p class="text-error text-sm mt-1">{{ $message }}</p>@enderror
</div>

<label for="bio-country-{{ $suffix }}" class="block text-sm text-base-content/70 mb-1 mt-4">Страна</label>
<select name="country_faction_id" id="bio-country-{{ $suffix }}" data-bio-faction-other-select="{{ $suffix }}" data-bio-faction-other-wrap-prefix="bio-country-other-wrap"
    class="select select-bordered w-full rounded-none bg-base-200 border-base-300 @error('country_faction_id') select-error @enderror">
    <option value="">—</option>
    @foreach ($countryFactions as $cf)
        <option value="{{ $cf->id }}" @selected($oldCountryFaction === (string) $cf->id)>{{ $cf->name }}</option>
    @endforeach
    <option value="other" @selected($oldCountryFaction === 'other')>Другое…</option>
</select>
@error('country_faction_id')<p class="text-error text-sm mt-1">{{ $message }}</p>@enderror
<div id="bio-country-other-wrap-{{ $suffix }}" class="mt-2 @if ($oldCountryFaction !== 'other') hidden @endif">
    <label for="bio-country-other-{{ $suffix }}" class="block text-sm text-base-content/70 mb-1">Своя страна</label>
    <input type="text" name="country_other_name" id="bio-country-other-{{ $suffix }}" value="{{ old('country_other_name') }}" maxlength="255"
        class="input input-bordered w-full rounded-none bg-base-200 border-base-300 @error('country_other_name') input-error @enderror"
        placeholder="Будет создана фракция с типом «Страна»">
    @error('country_other_name')<p class="text-error text-sm mt-1">{{ $message }}</p>@enderror
</div>

<label for="bio-gender-{{ $suffix }}" class="block text-sm text-base-content/70 mb-1 mt-4">Пол</label>
<select name="gender" id="bio-gender-{{ $suffix }}"
    class="select select-bordered w-full rounded-none bg-base-200 border-base-300 @error('gender') select-error @enderror">
    <option value="" @selected($bioGender === null || $bioGender === '')>—</option>
    <option value="m" @selected($bioGender === 'm')>мужчина</option>
    <option value="f" @selected($bioGender === 'f')>женщина</option>
</select>
@error('gender')<p class="text-error text-sm mt-1">{{ $message }}</p>@enderror

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
<select name="relative_ids[]" id="bio-rel-{{ $suffix }}" multiple size="6"
    data-bio-kinship-select="{{ $suffix }}"
    aria-describedby="bio-rel-hint-{{ $suffix }}"
    class="bio-rel-multiselect bio-multi-relations-select select select-bordered w-full rounded-none bg-base-200 border-base-300 @error('relative_ids') select-error @enderror">
    @foreach ($others as $b)
        <option value="{{ $b->id }}" @selected(in_array($b->id, (array) $oldRel, true))>{{ $b->name }}</option>
    @endforeach
</select>
<p id="bio-rel-hint-{{ $suffix }}" class="text-xs text-base-content/50 mt-1">Для каждого выберите степень родства ниже.</p>
<script type="application/json" id="bio-kinship-labels-{{ $suffix }}">@json(\App\Support\BiographyKinship::labels())</script>
<div
    id="bio-rel-kinship-{{ $suffix }}"
    class="bio-rel-kinship-rows space-y-3 mt-3 border border-base-300/40 rounded-none p-3 bg-base-200/30"
    data-initial-kinship="{{ json_encode($relKinshipOld) }}"
    data-initial-custom="{{ json_encode($relKinshipCustomOld) }}"
></div>
@error('relative_ids')<p class="text-error text-sm mt-1">{{ $message }}</p>@enderror
@error('relative_ids.*')<p class="text-error text-sm mt-1">{{ $message }}</p>@enderror
@error('relative_kinship')<p class="text-error text-sm mt-1">{{ $message }}</p>@enderror
@foreach ($errors->getMessages() as $key => $msgs)
    @if (str_starts_with($key, 'relative_kinship.') || str_starts_with($key, 'relative_kinship_custom.'))
        @foreach ($msgs as $m)
            <p class="text-error text-sm mt-1">{{ $m }}</p>
        @endforeach
    @endif
@endforeach

<label for="bio-fr-{{ $suffix }}" class="block text-sm text-base-content/70 mb-1 mt-4">Друзья</label>
<select name="friend_ids[]" id="bio-fr-{{ $suffix }}" multiple size="6"
    class="bio-multi-relations-select select select-bordered w-full rounded-none bg-base-200 border-base-300 @error('friend_ids') select-error @enderror">
    @foreach ($others as $b)
        <option value="{{ $b->id }}" @selected(in_array($b->id, (array) $oldFr, true))>{{ $b->name }}</option>
    @endforeach
</select>
@error('friend_ids')<p class="text-error text-sm mt-1">{{ $message }}</p>@enderror

<label for="bio-en-{{ $suffix }}" class="block text-sm text-base-content/70 mb-1 mt-4">Враги</label>
<select name="enemy_ids[]" id="bio-en-{{ $suffix }}" multiple size="6"
    class="bio-multi-relations-select select select-bordered w-full rounded-none bg-base-200 border-base-300 @error('enemy_ids') select-error @enderror">
    @foreach ($others as $b)
        <option value="{{ $b->id }}" @selected(in_array($b->id, (array) $oldEn, true))>{{ $b->name }}</option>
    @endforeach
</select>
@error('enemy_ids')<p class="text-error text-sm mt-1">{{ $message }}</p>@enderror

<label for="bio-membership-{{ $suffix }}" class="block text-sm text-base-content/70 mb-1 mt-4">Принадлежность к фракции</label>
<select name="faction_membership_ids[]" id="bio-membership-{{ $suffix }}" multiple size="6"
    aria-describedby="bio-membership-hint-{{ $suffix }}"
    class="bio-multi-relations-select select select-bordered w-full rounded-none bg-base-200 border-base-300 @error('faction_membership_ids') select-error @enderror">
    @foreach ($membershipFactions as $mf)
        <option value="{{ $mf->id }}" @selected(in_array($mf->id, (array) $oldMembership, true))>{{ $mf->name }} — {{ $mf->typeLabel() }}</option>
    @endforeach
</select>
<p id="bio-membership-hint-{{ $suffix }}" class="text-xs text-base-content/50 mt-1">Организации, союзы, гильдии и т.д. (без расы, народа и страны — они задаются полями выше).</p>
@error('faction_membership_ids')<p class="text-error text-sm mt-1">{{ $message }}</p>@enderror
@error('faction_membership_ids.*')<p class="text-error text-sm mt-1">{{ $message }}</p>@enderror
