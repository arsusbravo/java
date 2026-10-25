@extends('front.layout.app')

@php
    use App\Models\Accommodation;
    use App\Support\Agoda\Stay;

    $typeLabel = Accommodation::TYPES[$hotel->type] ?? ucfirst($hotel->type);
    $kind = $hotel->star_rating ? "{$hotel->star_rating}-star ".Str::lower($typeLabel) : Str::lower($typeLabel);
    $pageTitle = $hotel->meta_title ?: $hotel->name.' – '.$kind.($area ? " near {$area->name}" : '').' | Java Sunrise';
    $metaDescription = $hotel->meta_description
        ?: \App\Support\Seo::description($hotel->description)
        ?: "{$hotel->name} is a {$kind}".($area ? " near {$area->name}, Java" : ' in Java').'. See photos, location and prices, and book on '.($hotel->affiliateNetwork?->name ?? 'our partner site').'.';
    $photos = $hotel->images;
    $network = $hotel->affiliateNetwork?->name ?? 'partner site';
    $facts = array_filter([
        'Check-in' => $hotel->check_in_time ? 'From '.$hotel->check_in_time : null,
        'Check-out' => $hotel->check_out_time ? 'Until '.$hotel->check_out_time : null,
        'Rooms' => $hotel->room_count ? number_format($hotel->room_count) : null,
        'Opened' => $hotel->year_opened,
        'Renovated' => $hotel->year_renovated,
        'Part of' => $hotel->chain_name,
    ]);
    $clearDatesUrl = route('hotels.show', $hotel->slug);

    // Feed descriptions are one long block of plain text: break it into paragraphs of a few sentences
    $description = $hotel->description;
    if ($description && $description === strip_tags($description) && ! str_contains($description, "\n")) {
        $description = collect(preg_split('/(?<=[.!?])\s+(?=[A-Z])/', trim($description)))
            ->chunk(3)
            ->map(fn ($sentences) => $sentences->implode(' '))
            ->implode("\n\n");
    }
    $isLong = Str::length(strip_tags((string) $description)) > 700;

    // Feed photos only carry the hotel name as alt text: describe them by type and place instead
    $near = $area ? " near {$area->name}" : '';
    $photoAlt = fn ($image, int $number): string => filled($image->alt_text) && $image->alt_text !== $hotel->name
        ? $image->alt_text
        : ($number === 1 ? "{$hotel->name}, {$kind}{$near}" : "{$hotel->name}{$near} – photo {$number}");
@endphp

@section('title', $pageTitle)
@section('meta_description', $metaDescription)
@section('og_image', $photos->isNotEmpty() ? image_url($photos->first()->path) : '')

@push('structured_data')
    {{ breadcrumb_ld(array_filter([
        'Home' => route('home'),
        ...($area ? [
            $area->region->name => route('destinations.region', $area->region->slug),
            $area->name => route('destinations.show', [$area->region->slug, $area->slug]),
            'Hotels near '.$area->name => route('destinations.hotels', [$area->region->slug, $area->slug]),
        ] : []),
        $hotel->name => $hotel->url(),
    ])) }}
@endpush

@section('content')
{{-- Structured data for search engines (no ratings: they are Agoda's, not ours) --}}
<script type="application/ld+json">
{!! json_encode(array_filter([
    '@context' => 'https://schema.org',
    '@type' => in_array($hotel->type, ['resort', 'hostel', 'guesthouse', 'apartment'], true)
        ? ['resort' => 'Resort', 'hostel' => 'Hostel', 'guesthouse' => 'BedAndBreakfast', 'apartment' => 'Apartment'][$hotel->type]
        : 'Hotel',
    'name' => $hotel->name,
    'url' => $hotel->url(),
    'image' => $photos->take(5)->map(fn ($image) => image_url($image->path))->values()->all() ?: null,
    'description' => \App\Support\Seo::description($hotel->description),
    'starRating' => $hotel->star_rating ? ['@type' => 'Rating', 'ratingValue' => $hotel->star_rating] : null,
    'address' => $hotel->address ? ['@type' => 'PostalAddress', 'streetAddress' => $hotel->address, 'addressCountry' => 'ID'] : null,
    'geo' => $hotel->latitude !== null ? ['@type' => 'GeoCoordinates', 'latitude' => (float) $hotel->latitude, 'longitude' => (float) $hotel->longitude] : null,
    'checkinTime' => $hotel->check_in_time,
    'checkoutTime' => $hotel->check_out_time,
    'numberOfRooms' => $hotel->room_count,
]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}
</script>

<div class="container mx-auto px-4 pt-28 pb-12">
    {{-- Heading --}}
    <nav class="text-sm text-gray-500 mb-4" aria-label="Breadcrumb">
        <ol class="flex flex-wrap items-center gap-2">
            <li><a href="{{ route('home') }}" class="hover:text-java-accent">Home</a></li>
            @if($area)
                <li aria-hidden="true">/</li>
                <li><a href="{{ route('destinations.region', $area->region->slug) }}" class="hover:text-java-accent">{{ $area->region->name }}</a></li>
                <li aria-hidden="true">/</li>
                <li><a href="{{ route('destinations.show', [$area->region->slug, $area->slug]) }}" class="hover:text-java-accent">{{ $area->name }}</a></li>
                <li aria-hidden="true">/</li>
                <li><a href="{{ route('destinations.hotels', [$area->region->slug, $area->slug]) }}" class="hover:text-java-accent">Hotels</a></li>
            @endif
            <li aria-hidden="true">/</li>
            <li class="text-gray-800" aria-current="page">{{ $hotel->name }}</li>
        </ol>
    </nav>

    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl md:text-4xl font-bold text-gray-900">{{ $hotel->name }}</h1>
            <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-gray-600">
                @if($hotel->star_rating)
                    <span class="text-yellow-500 text-base" aria-label="{{ $hotel->star_rating }}-star property">{{ str_repeat('★', $hotel->star_rating) }}<span class="text-gray-300">{{ str_repeat('★', 5 - $hotel->star_rating) }}</span></span>
                @endif
                <span class="rounded-full bg-blue-50 px-3 py-1 font-medium text-blue-700">{{ $typeLabel }}</span>
                @if($hotel->review_score)
                    <span class="flex items-center gap-1.5" title="Agoda guest rating">
                        <span class="rounded bg-blue-900 px-1.5 py-0.5 font-semibold text-white">{{ number_format((float) $hotel->review_score, 1) }}</span>
                        @if(rating_label($hotel->review_score))<span class="font-medium text-gray-800">{{ rating_label($hotel->review_score) }}</span>@endif
                        @if($hotel->review_count)<span>· {{ number_format($hotel->review_count) }} {{ Str::plural('review', $hotel->review_count) }} on {{ $network }}</span>@endif
                    </span>
                @endif
            </div>
            @if($hotel->address)
                <p class="mt-2 flex items-start gap-1.5 text-gray-600">
                    <svg class="mt-0.5 h-5 w-5 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <span>{{ $hotel->address }}@if($area && isset($area->distance_km)) · {{ distance_label($area->distance_km) }} from {{ $area->name }}@endif</span>
                </p>
            @endif
        </div>
        @if($hotel->affiliate_url)
            <a href="#book" class="rounded-full bg-java-accent px-6 py-3 font-semibold text-white hover:bg-orange-600 lg:hidden">Check prices</a>
        @endif
    </div>

    {{-- Photos: one large, four small; all of them in the lightbox --}}
    @if($photos->isNotEmpty())
        <div class="relative mb-10 grid gap-2 overflow-hidden rounded-2xl md:grid-cols-4 md:grid-rows-2 md:h-[28rem]">
            @foreach($photos->take(5) as $image)
                <a href="{{ image_url($image->path) }}" data-lightbox="hotel" data-caption="{{ $image->title }}" target="_blank" rel="noopener"
                   class="{{ $loop->first ? 'md:col-span-2 md:row-span-2 h-64 md:h-auto' : 'hidden md:block' }} group block cursor-zoom-in overflow-hidden bg-gray-100">
                    <img src="{{ image_url($image->path) }}" alt="{{ $photoAlt($image, $loop->iteration) }}" @unless($loop->first) loading="lazy" @endunless
                         class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105">
                </a>
            @endforeach
            @if($photos->count() > 1)
                <a href="{{ image_url($photos->first()->path) }}" data-lightbox="hotel"
                   class="absolute bottom-4 right-4 inline-flex items-center gap-2 rounded-lg bg-white/95 px-4 py-2 text-sm font-semibold text-gray-900 shadow-md hover:bg-white">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                    Show all {{ $photos->count() }} photos
                </a>
            @endif
            {{-- The other photos, for the lightbox --}}
            <div hidden>
                @foreach($photos->skip(5) as $image)
                    <a href="{{ image_url($image->path) }}" data-lightbox="hotel" data-caption="{{ $image->title }}"><img src="" alt="{{ $photoAlt($image, $loop->iteration + 5) }}"></a>
                @endforeach
            </div>
        </div>
    @else
        <div class="mb-10 h-64 rounded-2xl bg-cover bg-center" style="background-image: url('{{ $hotel->featured_image_url }}');" role="img" aria-label="{{ $hotel->name }}"></div>
    @endif

    <div class="grid gap-10 lg:grid-cols-3 lg:gap-12 mb-16">
        <div class="lg:col-span-2 space-y-12">
            {{-- About --}}
            <section aria-labelledby="about-heading">
                <h2 id="about-heading" class="text-2xl font-bold mb-4">About {{ $hotel->name }}</h2>
                @if($description)
                    @if($isLong)<input type="checkbox" id="read-more" class="peer sr-only">@endif
                    <div class="prose max-w-none text-gray-700 {{ $isLong ? 'max-h-60 overflow-hidden peer-checked:max-h-none [mask-image:linear-gradient(to_bottom,black_60%,transparent)] peer-checked:[mask-image:none]' : '' }}">
                        {!! rich_text($description) !!}
                    </div>
                    @if($isLong)
                        <label for="read-more" class="mt-2 inline-block cursor-pointer font-medium text-java-accent hover:underline peer-checked:hidden">Read more</label>
                    @endif
                @else
                    <p class="text-gray-600">
                        {{ $hotel->name }} is a {{ $kind }}@if($area) near {{ $area->name }}@endif. See the photos, location and live prices here, and book on {{ $network }}.
                    </p>
                @endif

                @if($hotel->includes_breakfast || $hotel->free_wifi)
                    <div class="mt-5 flex flex-wrap gap-2 text-sm">
                        @if($hotel->includes_breakfast)<span class="rounded-full bg-green-50 px-3 py-1 font-medium text-green-700">Breakfast included</span>@endif
                        @if($hotel->free_wifi)<span class="rounded-full bg-gray-100 px-3 py-1 text-gray-700">Free Wi-Fi</span>@endif
                    </div>
                @endif
            </section>

            {{-- Good to know --}}
            @if($facts)
                <section aria-labelledby="facts-heading">
                    <h2 id="facts-heading" class="text-2xl font-bold mb-4">Good to know</h2>
                    <dl class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                        @foreach($facts as $label => $value)
                            <div class="rounded-xl bg-gray-50 p-4">
                                <dt class="text-sm text-gray-500">{{ $label }}</dt>
                                <dd class="mt-1 font-semibold text-gray-900">{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </section>
            @endif

            {{-- Location --}}
            @if($hotel->latitude !== null)
                <section aria-labelledby="location-heading">
                    <h2 id="location-heading" class="text-2xl font-bold mb-4">Location</h2>
                    <iframe
                        title="Map of {{ $hotel->name }}"
                        src="https://maps.google.com/maps?q={{ $hotel->latitude }},{{ $hotel->longitude }}&z=15&output=embed"
                        class="h-80 w-full rounded-2xl border-0"
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                    ></iframe>
                    <a href="https://www.google.com/maps/dir/?api=1&destination={{ $hotel->latitude }},{{ $hotel->longitude }}" target="_blank" rel="noopener" class="mt-3 inline-block text-java-accent hover:underline">Get directions →</a>
                </section>
            @endif
        </div>

        {{-- Booking --}}
        <aside id="book" aria-label="Prices and booking" class="scroll-mt-28">
            <div class="lg:sticky lg:top-28 space-y-5 rounded-2xl bg-white p-6 shadow-lg">
                {{-- Price --}}
                <div>
                    @if($offer)
                        <p class="text-sm text-gray-600">{{ $stay->label() }}</p>
                        <p class="mt-1 flex flex-wrap items-baseline gap-2">
                            <span class="text-3xl font-bold text-gray-900">{{ money($offer->dailyRate, $offer->currency) }}</span>
                            <span class="text-gray-600">/ night</span>
                            @if($offer->crossedOutRate)<span class="text-gray-400 line-through">{{ money($offer->crossedOutRate, $offer->currency) }}</span>@endif
                            @if($offer->discountPercent)<span class="rounded-full bg-red-600 px-2 py-0.5 text-sm font-semibold text-white">−{{ $offer->discountPercent }}%</span>@endif
                        </p>
                        <p class="text-sm text-gray-500">
                            About {{ money($offer->dailyRate * $stay->nights(), $offer->currency) }} for {{ $stay->nights() }} {{ Str::plural('night', $stay->nights()) }}, excl. taxes and fees
                        </p>
                    @elseif($stay)
                        <p class="text-sm text-gray-600">{{ $stay->label() }}</p>
                        <p class="mt-1 font-semibold text-gray-900">No rooms found for your dates.</p>
                        <p class="text-sm text-gray-500">Try other dates, or check {{ $network }} for availability.</p>
                    @elseif($hotel->price_from)
                        <p class="flex flex-wrap items-baseline gap-2">
                            <span class="text-gray-600">From</span>
                            <span class="text-3xl font-bold text-gray-900">{{ money($hotel->price_from, $hotel->currency) }}</span>
                            <span class="text-gray-600">/ night</span>
                            @if($hotel->crossed_out_price)<span class="text-gray-400 line-through">{{ money($hotel->crossed_out_price, $hotel->currency) }}</span>@endif
                        </p>
                        @if($hotel->prices_checked_at)
                            <p class="text-sm text-gray-500">Excl. taxes · checked {{ $hotel->prices_checked_at->format('j M') }}. Add your dates for the real price.</p>
                        @endif
                    @else
                        <p class="font-semibold text-gray-900">Add your dates to see prices</p>
                    @endif
                </div>

                {{-- Dates --}}
                <form method="GET" action="{{ route('hotels.show', $hotel->slug) }}#book" class="space-y-3">
                    <div class="grid grid-cols-2 gap-3">
                        <label class="block text-sm">
                            <span class="font-semibold text-gray-900">Check-in</span>
                            <input type="date" name="checkin" value="{{ $stay?->checkIn->toDateString() ?? request('checkin') }}" min="{{ today()->toDateString() }}" required
                                   class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2">
                        </label>
                        <label class="block text-sm">
                            <span class="font-semibold text-gray-900">Check-out</span>
                            <input type="date" name="checkout" value="{{ $stay?->checkOut->toDateString() ?? request('checkout') }}" min="{{ today()->addDay()->toDateString() }}" required
                                   class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2">
                        </label>
                        <label class="block text-sm">
                            <span class="font-semibold text-gray-900">Adults</span>
                            <select name="adults" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2">
                                @foreach(range(1, Stay::MAX_ADULTS) as $count)
                                    <option value="{{ $count }}" @selected(($stay?->adults ?? 2) === $count)>{{ $count }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block text-sm">
                            <span class="font-semibold text-gray-900">Children</span>
                            <select name="children" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2">
                                @foreach(range(0, Stay::MAX_CHILDREN) as $count)
                                    <option value="{{ $count }}" @selected(($stay?->children ?? 0) === $count)>{{ $count }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                    @if($stayError)
                        <p class="text-sm text-red-600" role="alert">{{ $stayError }}</p>
                    @endif
                    <button type="submit" class="w-full rounded-full border-2 border-java-accent px-5 py-2.5 font-semibold text-java-accent hover:bg-java-accent hover:text-white">
                        {{ $stay ? 'Update price' : 'Check price for my dates' }}
                    </button>
                    @if($stay)
                        <a href="{{ $clearDatesUrl }}" class="block text-center text-sm text-gray-500 hover:text-gray-700">Clear dates</a>
                    @endif
                </form>

                @if($hotel->affiliate_url)
                    <a href="{{ booking_url($hotel, 'hotel', $stay) }}" target="_blank" rel="nofollow sponsored noopener"
                       class="block rounded-full bg-java-accent px-5 py-3 text-center font-semibold text-white hover:bg-orange-600">
                        @if($stay && $offer)
                            Book {{ $stay->nights() }} {{ Str::plural('night', $stay->nights()) }} on {{ $network }}
                        @else
                            {{ $stay ? 'See availability' : 'View deals' }} on {{ $network }}
                        @endif
                    </a>
                    @include('front.partials.affiliate-note', ['class' => 'text-center'])
                @endif
            </div>
        </aside>
    </div>

    {{-- Around the hotel --}}
    @if($nearbyPlaces->isNotEmpty())
        <section class="mb-16" aria-labelledby="near-places-heading">
            <h2 id="near-places-heading" class="text-3xl font-bold mb-8">Places to see near {{ $hotel->name }}</h2>
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

    @if($nearbyRestaurants->isNotEmpty())
        <section class="mb-16" aria-labelledby="eat-nearby-heading">
            <h2 id="eat-nearby-heading" class="text-3xl font-bold mb-8">Places to eat nearby</h2>
            <div class="grid md:grid-cols-3 gap-6">
                @foreach($nearbyRestaurants as $restaurant)
                    <a href="{{ $restaurant->url() }}" class="group overflow-hidden rounded-lg bg-white shadow-md transition-shadow hover:shadow-xl">
                        <div class="relative h-40 bg-cover bg-center" style="background-image: url('{{ $restaurant->featured_image_url }}');" role="img" aria-label="{{ $restaurant->name }}">
                            <span class="absolute top-3 left-3 rounded-full bg-white/90 px-3 py-1 text-sm font-medium text-gray-800 shadow-sm">{{ distance_label($restaurant->distance_km) }} away</span>
                        </div>
                        <div class="p-4">
                            <h3 class="font-bold group-hover:text-java-accent">{{ $restaurant->name }}</h3>
                            @if($restaurant->cuisine_type)<p class="text-sm text-gray-500">{{ implode(', ', $restaurant->cuisine_type) }}</p>@endif
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    @if($otherHotels->isNotEmpty())
        <section aria-labelledby="other-hotels-heading">
            <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
                <h2 id="other-hotels-heading" class="text-3xl font-bold">Other places to stay nearby</h2>
                @if($area)
                    <a href="{{ route('destinations.hotels', [$area->region->slug, $area->slug, ...($stay?->query() ?? [])]) }}" class="text-blue-600 hover:text-blue-800">All hotels near {{ $area->name }} →</a>
                @endif
            </div>
            <div class="grid md:grid-cols-3 gap-6">
                @foreach($otherHotels as $accommodation)
                    @include('front.partials.hotel-card', ['from' => 'hotel'])
                @endforeach
            </div>
            @include('front.partials.affiliate-note')
        </section>
    @endif
</div>
@include('front.partials.lightbox')
@endsection
