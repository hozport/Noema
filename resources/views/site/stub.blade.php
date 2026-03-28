@extends('site.layout')

@section('title', $title.' — Noema')

@section('content')
    <div class="max-w-[1344px] mx-auto px-4 sm:px-6 py-16 sm:py-20">
        <h1 class="font-display text-3xl sm:text-4xl font-semibold text-base-content">{{ $title }}</h1>
        <p class="mt-6 text-base text-base-content/70 max-w-2xl leading-relaxed">
            {{ __('site.pages.stub_message') }}
        </p>
    </div>
@endsection
