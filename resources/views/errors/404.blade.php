@extends('front.layout.app')

@section('title', 'Page not found | Java Sunrise')
@section('meta_description', "This page doesn't exist (any more). Find destinations, hotels and travel guides for Java instead.")
@section('robots', 'noindex, follow')

@section('content')
<section class="bg-linear-to-br from-java-primary to-java-dark text-white pt-36 pb-20">
    <div class="max-w-3xl mx-auto px-6 text-center">
        <p class="text-java-accent font-semibold mb-3">404</p>
        <h1 class="font-playfair text-4xl md:text-5xl font-bold">We couldn't find that page</h1>
        <p class="mt-4 text-lg text-white/80">It may have moved, or the link has a typo. These will get you back on the road:</p>
    </div>
</section>

<div class="max-w-3xl mx-auto px-6 py-14">
    <div class="grid gap-4 sm:grid-cols-3">
        <a href="{{ route('destinations.index') }}" class="rounded-2xl border border-gray-200 p-6 text-center hover:border-java-accent hover:shadow-md">
            <span class="block text-3xl mb-2" aria-hidden="true">🗺️</span>
            <span class="font-semibold text-gray-900">Destinations</span>
        </a>
        <a href="{{ route('trip-planner') }}" class="rounded-2xl border border-gray-200 p-6 text-center hover:border-java-accent hover:shadow-md">
            <span class="block text-3xl mb-2" aria-hidden="true">🧭</span>
            <span class="font-semibold text-gray-900">Trip Planner</span>
        </a>
        <a href="{{ route('articles.index') }}" class="rounded-2xl border border-gray-200 p-6 text-center hover:border-java-accent hover:shadow-md">
            <span class="block text-3xl mb-2" aria-hidden="true">📖</span>
            <span class="font-semibold text-gray-900">Travel guides</span>
        </a>
    </div>
    <p class="mt-10 text-center"><a href="{{ route('home') }}" class="text-java-accent hover:underline">← Back to the home page</a></p>
</div>
@endsection
