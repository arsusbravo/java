@extends('front.layout.app')

@php
    $cuisine = implode(', ', $restaurant->cuisine_type ?? []);
    $pageTitle = $restaurant->meta_title
        ?: $restaurant->name . ($cuisine ? " – {$cuisine} restaurant" : ' – restaurant') . ($area ? " near {$area->name}" : '') . ' | Java Sunrise';
@endphp

@section('title', $pageTitle)
@section('meta_description', $restaurant->meta_description ?: \App\Support\Seo::description($restaurant->description))

@section('content')
{{-- Structured data, so search engines can show it as a restaurant --}}
<script type="application/ld+json">
{!! json_encode(array_filter([
    '@context' => 'https://schema.org',
    '@type' => 'Restaurant',
    'name' => $restaurant->name,
    'url' => $restaurant->url(),
    'image' => $restaurant->featured_image_url,
    'servesCuisine' => $restaurant->cuisine_type ?: null,
    'priceRange' => $restaurant->price_range,
    'telephone' => $restaurant->phone,
    'address' => $restaurant->address ? ['@type' => 'PostalAddress', 'streetAddress' => $restaurant->address, 'addressCountry' => 'ID'] : null,
    'geo' => $restaurant->latitude !== null ? ['@type' => 'GeoCoordinates', 'latitude' => (float) $restaurant->latitude, 'longitude' => (float) $restaurant->longitude] : null,
    'openingHours' => $restaurant->opening_hours ?: null,
    'aggregateRating' => $restaurant->average_rating && $restaurant->reviews->isNotEmpty()
        ? ['@type' => 'AggregateRating', 'ratingValue' => (float) $restaurant->average_rating, 'bestRating' => 5, 'reviewCount' => $restaurant->reviews->count()]
        : null,
]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}
</script>

<!-- Hero -->
<div class="relative h-96 bg-cover bg-center" style="background-image: url('{{ $restaurant->featured_image_url }}');">
    <div class="absolute inset-0 bg-black/55"></div>
    <div class="relative container mx-auto px-4 h-full flex items-end pb-12">
        <div class="text-white">
            <nav class="text-sm mb-4" aria-label="Breadcrumb">
                <ol class="flex flex-wrap items-center gap-2">
                    <li><a href="{{ route('home') }}" class="hover:text-java-accent">Home</a></li>
                    @if($area)
                        <li aria-hidden="true">/</li>
                        <li><a href="{{ route('destinations.region', $area->region->slug) }}" class="hover:text-java-accent">{{ $area->region->name }}</a></li>
                        <li aria-hidden="true">/</li>
                        <li><a href="{{ route('destinations.show', [$area->region->slug, $area->slug]) }}" class="hover:text-java-accent">{{ $area->name }}</a></li>
                    @endif
                    <li aria-hidden="true">/</li>
                    <li class="text-java-accent" aria-current="page">{{ $restaurant->name }}</li>
                </ol>
            </nav>
            <h1 class="font-playfair text-4xl md:text-6xl font-bold mb-3">{{ $restaurant->name }}</h1>
            <div class="flex flex-wrap items-center gap-3 text-lg">
                @if($cuisine)<span>{{ $cuisine }}</span>@endif
                @if($restaurant->price_range)<span class="rounded-full bg-white/15 px-3 py-0.5 text-base font-semibold" title="Price level">{{ $restaurant->price_range }}</span>@endif
                @if($restaurant->average_rating)
                    <span class="flex items-center gap-1 text-yellow-400" aria-label="Rated {{ number_format($restaurant->average_rating, 1) }} out of 5">
                        @for($i = 0; $i < 5; $i++)
                            <svg class="w-5 h-5 fill-current {{ $i < round($restaurant->average_rating) ? '' : 'text-white/30' }}" viewBox="0 0 20 20" aria-hidden="true"><path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/></svg>
                        @endfor
                        <span class="ml-1 text-white">{{ number_format($restaurant->average_rating, 1) }}</span>
                    </span>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="container mx-auto px-4 py-12">
    <div class="grid gap-10 lg:grid-cols-3 lg:gap-12 mb-16">
        {{-- About --}}
        <div class="lg:col-span-2">
            @if($restaurant->description)
                <div class="prose max-w-none">
                    {!! rich_text($restaurant->description) !!}
                </div>
            @endif

            @if($restaurant->images->count() > 1)
                <section class="mt-12" aria-labelledby="photos-heading">
                    <h2 id="photos-heading" class="text-2xl font-bold mb-4">Photos</h2>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                        @foreach($restaurant->images as $image)
                            <a href="{{ image_url($image->path) }}" data-lightbox="restaurant" data-caption="{{ $image->title }}" target="_blank" rel="noopener" class="group block aspect-[4/3] cursor-zoom-in overflow-hidden rounded-lg">
                                <img src="{{ image_url($image->path) }}" alt="{{ $image->alt_text ?: $restaurant->name }}" loading="lazy" class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105">
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif

            @if($restaurant->reviews->isNotEmpty())
                <section class="mt-12" aria-labelledby="reviews-heading">
                    <h2 id="reviews-heading" class="text-2xl font-bold mb-4">What guests say</h2>
                    <div class="space-y-4">
                        @foreach($restaurant->reviews as $review)
                            <blockquote class="rounded-xl bg-gray-50 p-5">
                                <p class="text-yellow-500" aria-label="{{ $review->rating }} out of 5">{{ str_repeat('★', $review->rating) }}<span class="text-gray-300">{{ str_repeat('★', 5 - $review->rating) }}</span></p>
                                @if($review->comment)<p class="mt-2 text-gray-700">{{ $review->comment }}</p>@endif
                                <footer class="mt-2 text-sm text-gray-500">— {{ $review->user_name }}</footer>
                            </blockquote>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>

        {{-- Practical info --}}
        <aside aria-label="Practical information">
            <div class="lg:sticky lg:top-28 space-y-5 rounded-2xl bg-white p-6 shadow-lg">
                @if($restaurant->latitude !== null)
                    <iframe
                        title="Map of {{ $restaurant->name }}"
                        src="https://maps.google.com/maps?q={{ $restaurant->latitude }},{{ $restaurant->longitude }}&z=16&output=embed"
                        class="h-48 w-full rounded-lg border-0"
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                    ></iframe>
                @endif

                <dl class="space-y-4 text-sm">
                    @if($restaurant->address)
                        <div>
                            <dt class="font-semibold text-gray-900">Address</dt>
                            <dd class="mt-1 text-gray-600">{{ $restaurant->address }}</dd>
                        </div>
                    @endif
                    @if($restaurant->opening_hours)
                        <div>
                            <dt class="font-semibold text-gray-900">Opening hours</dt>
                            <dd class="mt-1">
                                <ul class="space-y-0.5 text-gray-600">
                                    @foreach($restaurant->opening_hours as $line)
                                        <li>{{ $line }}</li>
                                    @endforeach
                                </ul>
                            </dd>
                        </div>
                    @endif
                    @if($restaurant->phone)
                        <div>
                            <dt class="font-semibold text-gray-900">Phone</dt>
                            <dd class="mt-1"><a href="tel:{{ preg_replace('/[^\d+]/', '', $restaurant->phone) }}" class="text-java-accent hover:underline">{{ $restaurant->phone }}</a></dd>
                        </div>
                    @endif
                </dl>

                <div class="flex flex-col gap-2">
                    @if($restaurant->directions_url)
                        <a href="{{ $restaurant->directions_url }}" target="_blank" rel="noopener" class="rounded-full border border-gray-300 px-5 py-3 text-center font-semibold hover:border-java-accent hover:text-java-accent">Get directions</a>
                    @endif
                    @if($restaurant->affiliate_url)
                        <a href="{{ route('go', ['restaurant', $restaurant->id, 'from' => 'restaurant']) }}" target="_blank" rel="nofollow sponsored noopener" class="rounded-full bg-java-accent px-5 py-3 text-center font-semibold text-white hover:bg-orange-600">Menu &amp; booking</a>
                        @include('front.partials.affiliate-note', ['class' => 'text-center'])
                    @endif
                </div>
            </div>
        </aside>
    </div>

    {{-- Around the restaurant --}}
    @if($nearbyPlaces->isNotEmpty())
        <section class="mb-16" aria-labelledby="near-places-heading">
            <h2 id="near-places-heading" class="text-3xl font-bold mb-8">Near {{ $restaurant->name }}</h2>
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($nearbyPlaces as $place)
                    <a href="{{ route('destinations.show', [$place->region->slug, $place->slug]) }}" class="group overflow-hidden rounded-lg bg-white shadow-md transition-shadow hover:shadow-xl">
                        <div class="relative h-40 bg-cover bg-center" style="background-image: url('{{ $place->featured_image_url }}');" role="img" aria-label="{{ $place->name }}">
                            @isset($place->distance_km)
                                <span class="absolute top-3 left-3 rounded-full bg-white/90 px-3 py-1 text-sm font-medium text-gray-800 shadow-sm">{{ distance_label($place->distance_km) }} away</span>
                            @endisset
                        </div>
                        <div class="p-4">
                            <h3 class="font-bold group-hover:text-java-accent">{{ $place->name }}</h3>
                            <p class="text-sm text-gray-500">{{ $place->region->name }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    @if($nearbyHotels->isNotEmpty())
        <section class="mb-16" aria-labelledby="stay-nearby-heading">
            <h2 id="stay-nearby-heading" class="text-3xl font-bold mb-8">Stay nearby</h2>
            <div class="grid md:grid-cols-3 gap-6">
                @foreach($nearbyHotels as $accommodation)
                    @include('front.partials.hotel-card', ['from' => 'restaurant'])
                @endforeach
            </div>
            @include('front.partials.affiliate-note')
        </section>
    @endif

    @if($nearbyRestaurants->isNotEmpty())
        <section aria-labelledby="more-food-heading">
            <h2 id="more-food-heading" class="text-3xl font-bold mb-8">More places to eat nearby</h2>
            <div class="grid md:grid-cols-3 gap-6">
                @foreach($nearbyRestaurants as $other)
                    <a href="{{ $other->url() }}" class="group overflow-hidden rounded-lg bg-white shadow-md transition-shadow hover:shadow-xl">
                        <div class="relative h-40 bg-cover bg-center" style="background-image: url('{{ $other->featured_image_url }}');" role="img" aria-label="{{ $other->name }}">
                            <span class="absolute top-3 left-3 rounded-full bg-white/90 px-3 py-1 text-sm font-medium text-gray-800 shadow-sm">{{ distance_label($other->distance_km) }} away</span>
                        </div>
                        <div class="p-4">
                            <h3 class="font-bold group-hover:text-java-accent">{{ $other->name }}</h3>
                            <p class="text-sm text-gray-500">{{ implode(', ', $other->cuisine_type ?? []) }}{{ $other->price_range ? ' · ' . $other->price_range : '' }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</div>
@include('front.partials.lightbox')
@endsection
