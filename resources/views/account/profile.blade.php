@extends('layouts.noema-app')

@section('title', 'Профиль — Noema')

@section('content')
    <div class="mb-6">
        <a href="{{ route('worlds.index') }}" class="link link-hover text-base-content/70 text-sm">← Назад к мирам</a>
    </div>

    <h1 class="font-display text-3xl md:text-4xl text-base-content font-semibold tracking-tight mb-2">Профиль</h1>
    <p class="text-base text-base-content/75 max-w-2xl leading-relaxed mb-8">
        Отображаемое имя, краткая биография и аватар используются в интерфейсе Noema.
    </p>

    @if (session('success'))
        <div role="alert" class="alert alert-success mb-6 rounded-none max-w-2xl" data-auto-dismiss>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if ($user)
        <form method="POST" action="{{ route('account.profile.update') }}" enctype="multipart/form-data" class="max-w-2xl space-y-8">
            @csrf
            @method('PUT')

            <div class="border border-base-300 bg-base-200/40 p-6 md:p-8 rounded-none space-y-6">
                <div>
                    <label class="label"><span class="label-text font-medium">Отображаемое имя</span></label>
                    <input type="text" name="display_name" value="{{ old('display_name', $user->display_name) }}"
                        class="input input-bordered w-full rounded-none bg-base-100 border-base-300"
                        placeholder="{{ $user->name }}" autocomplete="nickname">
                    @error('display_name')
                        <p class="text-error text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-base-content/50 mt-2">Если не указано, показывается учётное имя: {{ $user->name }}</p>
                </div>

                <div>
                    <label class="label"><span class="label-text font-medium">Краткая биография</span></label>
                    <textarea name="bio" rows="5"
                        class="textarea textarea-bordered w-full rounded-none bg-base-100 border-base-300 min-h-[8rem]"
                        placeholder="Несколько предложений о себе">{{ old('bio', $user->bio) }}</textarea>
                    @error('bio')
                        <p class="text-error text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <span class="label-text font-medium block mb-3">Аватар</span>
                    <div class="flex flex-col sm:flex-row gap-6 items-start">
                        <div class="avatar shrink-0">
                            <div class="w-24 h-24 rounded-none border border-base-300 bg-base-300/30 overflow-hidden">
                                @if ($user->avatarUrl())
                                    <img src="{{ $user->avatarUrl() }}" alt="" class="w-full h-full object-cover" id="profileAvatarPreview">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-base-content/40 text-sm text-center px-2" id="profileAvatarPlaceholder">Нет фото</div>
                                    <img src="" alt="" class="w-full h-full object-cover hidden" id="profileAvatarPreview">
                                @endif
                            </div>
                        </div>
                        <div class="flex-1 min-w-0 w-full">
                            <input type="file" name="avatar" accept="image/*"
                                class="file-input file-input-bordered w-full max-w-md rounded-none bg-base-100 border-base-300"
                                id="profileAvatarInput">
                            @error('avatar')
                                <p class="text-error text-sm mt-1">{{ $message }}</p>
                            @enderror
                            <p class="text-xs text-base-content/50 mt-2">PNG, JPG или WebP, до 2 МБ.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap gap-3">
                <button type="submit" class="btn btn-primary rounded-none">Сохранить</button>
            </div>
        </form>
    @endif
@endsection

@push('scripts')
    <script>
        (function () {
            const input = document.getElementById('profileAvatarInput');
            const preview = document.getElementById('profileAvatarPreview');
            const placeholder = document.getElementById('profileAvatarPlaceholder');
            if (!input || !preview) return;
            input.addEventListener('change', function () {
                const file = this.files && this.files[0];
                if (!file || !file.type.startsWith('image/')) return;
                const url = URL.createObjectURL(file);
                preview.src = url;
                preview.classList.remove('hidden');
                if (placeholder) placeholder.classList.add('hidden');
            });
        })();
    </script>
@endpush
