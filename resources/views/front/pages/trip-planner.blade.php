@extends('front.layout.app')

@section('title', $plan ? "{$choices['days']}-day trip from {$start->name} | Java Sunrise" : 'Trip Planner: build your Java itinerary | Java Sunrise')
@section('meta_description', 'Plan your Java trip day by day: pick where you start, how long you stay and what you love, and get a route with drive times and a hotel for every night.')

@php
    use App\Support\TripPlanner;
    // Same choices, without the places the traveller removed
    $resetSkipUrl = route('trip-planner', array_filter([...$choices, 'skip' => null]));
@endphp

@section('content')
<section class="bg-linear-to-br from-java-primary to-java-dark text-white pt-32 pb-16 print:pt-4 print:pb-4 print:bg-none print:text-black">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <span class="inline-block px-4 py-1.5 bg-java-accent rounded-full text-sm font-semibold mb-4 print:hidden">Trip Planner</span>
        <h1 class="font-playfair text-4xl md:text-6xl font-bold mb-4">
            @if($plan)
                Your {{ $choices['days'] }}-day trip from {{ $start->name }}
            @else
                Plan your Java trip in a minute
            @endif
        </h1>
        <p class="text-lg md:text-xl text-white/80 max-w-3xl print:text-gray-600">
            Pick where you start, how long you have and what you love. We'll plan the route day by day, with drive times and a hotel for every night.
        </p>
    </div>
</section>

<div class="max-w-6xl mx-auto px-4 sm:px-6 py-12">
    {{-- The form --}}
    <form id="planner-form" method="GET" action="{{ route('trip-planner') }}" class="bg-white rounded-2xl shadow-lg p-6 md:p-8 mb-12 -mt-24 relative print:hidden">
        <div class="grid gap-6 md:grid-cols-2">
            <div>
                <label for="start" class="block font-semibold mb-2">Where does your trip start?</label>
                <select id="start" name="start" required class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-java-accent focus:ring-2 focus:ring-java-accent/30 outline-none">
                    <option value="">Choose a place…</option>
                    @foreach($regions as $region)
                        @if($region->destinations->isNotEmpty())
                            <optgroup label="{{ $region->name }}">
                                @foreach($region->destinations as $place)
                                    <option value="{{ $place->slug }}" @selected($choices['start'] === $place->slug)>{{ $place->name }}</option>
                                @endforeach
                            </optgroup>
                        @endif
                    @endforeach
                </select>
                @if($startNotFound)
                    <p class="mt-2 text-sm text-red-600" role="alert">We couldn't find that place. Please choose a start from the list.</p>
                @endif
            </div>
            <div>
                <label for="days" class="block font-semibold mb-2">How many days?</label>
                <select id="days" name="days" class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-java-accent focus:ring-2 focus:ring-java-accent/30 outline-none">
                    @for($d = TripPlanner::MIN_DAYS; $d <= TripPlanner::MAX_DAYS; $d++)
                        <option value="{{ $d }}" @selected($choices['days'] === $d)>{{ $d }} {{ Str::plural('day', $d) }}</option>
                    @endfor
                </select>
            </div>
        </div>

        <fieldset class="mt-6">
            <legend class="font-semibold mb-2">What do you love? <span class="font-normal text-gray-500">Pick any, or none for a bit of everything</span></legend>
            <div class="flex flex-wrap gap-2">
                @foreach($types as $type)
                    <label class="cursor-pointer">
                        <input type="checkbox" name="interests[]" value="{{ $type->slug }}" class="peer sr-only" @checked(in_array($type->slug, $choices['interests']))>
                        <span class="inline-block rounded-full border border-gray-300 px-4 py-2 text-sm transition-colors peer-checked:border-java-accent peer-checked:bg-java-accent peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-java-accent/40 hover:border-java-accent">
                            {{ $type->name }}
                        </span>
                    </label>
                @endforeach
            </div>
        </fieldset>

        <fieldset class="mt-6">
            <legend class="font-semibold mb-2">Hotel budget</legend>
            <div class="grid gap-3 sm:grid-cols-3">
                @foreach($budgets as $key => $budget)
                    <label class="cursor-pointer">
                        <input type="radio" name="budget" value="{{ $key }}" class="peer sr-only" @checked($choices['budget'] === $key)>
                        <span class="block rounded-xl border-2 border-gray-200 p-4 transition-colors peer-checked:border-java-accent peer-checked:bg-java-accent/5 peer-focus-visible:ring-2 peer-focus-visible:ring-java-accent/40 hover:border-java-accent/60">
                            <span class="block font-semibold">{{ $budget['label'] }}</span>
                            <span class="block text-sm text-gray-500">{{ $budget['hint'] }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
        </fieldset>

        <fieldset class="mt-6">
            <legend class="font-semibold mb-2">When do you travel? <span class="font-normal text-gray-500">Optional: adds real hotel prices for every night</span></legend>
            <div class="flex flex-wrap gap-4">
                <label class="block text-sm">
                    <span class="text-gray-700">First night</span>
                    <input type="date" name="date" value="{{ $choices['date'] }}" min="{{ today()->toDateString() }}"
                           class="mt-1 block rounded-lg border border-gray-300 px-4 py-3 focus:border-java-accent focus:ring-2 focus:ring-java-accent/30 outline-none">
                </label>
                <label class="block text-sm">
                    <span class="text-gray-700">Adults</span>
                    <select name="adults" class="mt-1 block rounded-lg border border-gray-300 px-4 py-3 focus:border-java-accent focus:ring-2 focus:ring-java-accent/30 outline-none">
                        @foreach(range(1, \App\Support\Agoda\Stay::MAX_ADULTS) as $count)
                            <option value="{{ $count }}" @selected($choices['adults'] === $count)>{{ $count }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
            @if($dateIgnored)
                <p class="mt-2 text-sm text-red-600" role="alert">That date has passed or isn't valid, so the trip is shown without prices. Pick a date from today.</p>
            @endif
        </fieldset>

        <button type="submit" class="mt-8 inline-flex items-center gap-2 rounded-full bg-java-accent px-8 py-4 font-semibold text-white shadow-lg transition hover:bg-orange-600">
            {{ $plan ? 'Plan again' : 'Plan my trip' }}
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
        </button>
    </form>

    @if($plan)
        {{-- Summary --}}
        <div class="flex flex-wrap items-center justify-between gap-4 mb-8">
            <div class="flex flex-wrap gap-3 text-sm">
                <span class="rounded-full bg-gray-100 px-4 py-2"><strong>{{ $choices['days'] }}</strong> {{ Str::plural('day', $choices['days']) }}</span>
                <span class="rounded-full bg-gray-100 px-4 py-2"><strong>{{ $plan['placeCount'] }}</strong> {{ Str::plural('place', $plan['placeCount']) }}</span>
                <span class="rounded-full bg-gray-100 px-4 py-2"><strong>{{ $plan['nights'] }}</strong> {{ Str::plural('night', $plan['nights']) }}</span>
                <span class="rounded-full bg-gray-100 px-4 py-2">≈ <strong>{{ number_format($plan['totalKm']) }}</strong> km on the road</span>
                @if(! empty($plan['pricedNights']))
                    <span class="rounded-full bg-orange-100 px-4 py-2 text-orange-900">
                        Hotels: about <strong>{{ money($plan['hotelTotal'], $plan['currency']) }}</strong>
                        for {{ $plan['pricedNights'] }} {{ Str::plural('night', $plan['pricedNights']) }}@if($plan['pricedNights'] < $plan['nights']) of {{ $plan['nights'] }}@endif, excl. taxes
                    </span>
                @endif
            </div>
            <div class="flex gap-2 print:hidden">
                <button type="button" id="share-trip" class="inline-flex items-center gap-2 rounded-full border border-gray-300 px-5 py-2.5 text-sm font-medium hover:border-java-accent hover:text-java-accent">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
                    <span>Share this trip</span>
                </button>
                <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 rounded-full border border-gray-300 px-5 py-2.5 text-sm font-medium hover:border-java-accent hover:text-java-accent">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Print
                </button>
            </div>
        </div>
        <p id="share-status" class="sr-only" aria-live="polite"></p>

        @if($choices['skip'])
            <p class="mb-8 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-900 print:hidden">
                Planned without {{ count($choices['skip']) }} {{ Str::plural('place', count($choices['skip'])) }} you removed.
                <a href="{{ $resetSkipUrl }}" class="font-semibold underline">Put them back</a>
            </p>
        @endif

        {{-- Day by day --}}
        @php $previous = null; @endphp
        <ol class="space-y-10">
            @foreach($plan['days'] as $day)
                <li class="relative md:pl-16 break-inside-avoid">
                    <div class="hidden md:flex absolute left-0 top-0 h-12 w-12 items-center justify-center rounded-full bg-java-primary font-playfair text-xl font-bold text-white print:hidden" aria-hidden="true">{{ $day['number'] }}</div>
                    <h2 class="font-playfair text-2xl md:text-3xl font-bold mb-4 flex flex-wrap items-center gap-3">
                        Day {{ $day['number'] }}
                        @if($day['travelDay'])
                            <span class="rounded-full bg-sky-100 px-3 py-1 font-poppins text-sm font-medium text-sky-800">Travel day</span>
                        @endif
                    </h2>

                    @if($day['stops'] === [])
                        <div class="rounded-2xl border-2 border-dashed border-gray-200 p-6 text-gray-600">
                            <p class="font-semibold text-gray-800">Free day around {{ $day['freeDayAround']->name }}</p>
                            <p class="text-sm mt-1">You've seen the highlights we know nearby. Explore at your own pace, or <a href="{{ route('destinations.show', [$day['freeDayAround']->region->slug, $day['freeDayAround']->slug]) }}" class="text-java-accent underline">read more about the area</a>.</p>
                        </div>
                    @endif

                    <div class="space-y-4">
                        @foreach($day['stops'] as $stop)
                            @php $place = $stop['destination']; @endphp
                            @if($previous && $stop['km'] > 0)
                                <p class="flex items-center gap-2 text-sm text-gray-500 pl-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                                    {{ distance_label($stop['km']) }} from {{ $previous->name }} · ≈ {{ TripPlanner::durationLabel($stop['minutes']) }} by road
                                </p>
                            @endif
                            <article class="flex flex-col sm:flex-row overflow-hidden rounded-2xl bg-white shadow-md">
                                <div class="h-48 sm:h-auto sm:w-56 shrink-0 bg-cover bg-center print:hidden" style="background-image: url('{{ $place->featured_image_url }}');" role="img" aria-label="{{ $place->name }}"></div>
                                <div class="flex-1 p-5">
                                    <div class="flex flex-wrap items-start justify-between gap-2">
                                        <div>
                                            <h3 class="text-xl font-bold"><a href="{{ route('destinations.show', [$place->region->slug, $place->slug]) }}" class="hover:text-java-accent">{{ $place->name }}</a></h3>
                                            <p class="text-sm text-gray-500">{{ $place->region->name }}</p>
                                        </div>
                                        @unless($place->is($start))
                                            <a href="{{ route('trip-planner', [...$choices, 'skip' => [...$choices['skip'], $place->slug]]) }}" class="text-sm text-gray-500 hover:text-red-600 print:hidden" title="Plan the trip without {{ $place->name }}">Not for me ✕</a>
                                        @endunless
                                    </div>
                                    @if($place->types->isNotEmpty())
                                        <div class="mt-2 flex flex-wrap gap-1.5">
                                            @foreach($place->types as $type)
                                                <span @class([
                                                    'rounded-full px-2.5 py-0.5 text-xs',
                                                    'bg-java-accent/15 text-orange-800 font-medium' => in_array($type->slug, $choices['interests']),
                                                    'bg-gray-100 text-gray-600' => ! in_array($type->slug, $choices['interests']),
                                                ])>{{ $type->name }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                    <p class="mt-3 text-gray-600 text-sm line-clamp-3">{{ text_excerpt($place->description, 220) }}</p>
                                </div>
                            </article>
                            @php $previous = $place; @endphp
                        @endforeach
                    </div>

                    {{-- Tonight's stay --}}
                    @if(! $day['stay'] && ! $loop->last)
                        <p class="mt-4 rounded-2xl bg-gray-50 p-5 text-sm text-gray-600">
                            We don't have a partner hotel near {{ $previous?->name ?? $day['freeDayAround']?->name }} yet.
                        </p>
                    @endif
                    @if($day['stay'])
                        @php
                            $hotel = $day['stay']['hotel'];
                            $nightStay = $day['stay']['stay'] ?? null;
                            $offer = $day['stay']['offer'] ?? null;
                        @endphp
                        <div class="mt-4 rounded-2xl bg-orange-50 p-5 break-inside-avoid">
                            <p class="text-xs font-semibold uppercase tracking-wide text-orange-800 mb-3">
                                Tonight's stay @if($nightStay)· {{ $choices['date'] ? \Carbon\CarbonImmutable::parse($choices['date'])->addDays($loop->index)->format('D j M') : '' }}@endif
                            </p>
                            @if(! empty($day['stay']['fullHotel']))
                                <p class="mb-3 text-sm text-gray-600">{{ $day['stay']['fullHotel']->name }} is full that night, so we picked the next best.</p>
                            @endif
                            <div class="flex flex-col sm:flex-row gap-4">
                                <div class="h-32 sm:w-44 shrink-0 rounded-xl bg-cover bg-center print:hidden" style="background-image: url('{{ $hotel->featured_image_url }}');" role="img" aria-label="{{ $hotel->name }}"></div>
                                <div class="flex-1">
                                    <h3 class="font-bold text-lg"><a href="{{ $hotel->url() }}{{ $nightStay ? '?'.http_build_query($nightStay->query()) : '' }}" class="hover:text-java-accent">{{ $hotel->name }}</a></h3>
                                    <p class="text-sm text-gray-600">
                                        @if($hotel->star_rating)<span class="text-yellow-500" aria-label="{{ $hotel->star_rating }} stars">{{ str_repeat('★', $hotel->star_rating) }}</span> · @endif
                                        {{ \App\Models\Accommodation::TYPES[$hotel->type] ?? ucfirst($hotel->type) }}
                                        @if($day['stay']['sameAsLastNight'])
                                            · same hotel as last night
                                        @elseif(isset($hotel->distance_km))
                                            · {{ distance_label($hotel->distance_km) }} from {{ $previous?->name }}
                                        @endif
                                    </p>
                                    @php $score = $offer?->reviewScore ?? $hotel->review_score; @endphp
                                    @if($score)
                                        <p class="mt-1 text-sm" title="Agoda guest rating">
                                            <span class="rounded bg-blue-900 px-1.5 py-0.5 font-semibold text-white">{{ number_format((float) $score, 1) }}</span>
                                            {{ rating_label($score) }}
                                            @if($offer?->reviewCount ?? $hotel->review_count)<span class="text-gray-500">· {{ number_format($offer?->reviewCount ?? $hotel->review_count) }} reviews</span>@endif
                                        </p>
                                    @endif
                                    @if($offer)
                                        <p class="mt-2 font-bold text-gray-900">
                                            {{ money($offer->dailyRate, $offer->currency) }} <span class="text-sm font-normal text-gray-600">that night, excl. taxes</span>
                                            @if($offer->crossedOutRate)<span class="ml-1 text-sm font-normal text-gray-400 line-through">{{ money($offer->crossedOutRate, $offer->currency) }}</span>@endif
                                        </p>
                                    @elseif($nightStay)
                                        <p class="mt-2 text-sm text-gray-600">No live price for that night. Check on {{ $hotel->affiliateNetwork?->name ?? 'the partner site' }}.</p>
                                    @endif
                                    @if($hotel->affiliate_url)
                                        <a href="{{ booking_url($hotel, 'trip_planner', $nightStay) }}" target="_blank" rel="nofollow sponsored noopener" class="mt-3 inline-block rounded-full bg-java-accent px-5 py-2 text-sm font-semibold text-white hover:bg-orange-600 print:hidden">
                                            @if($nightStay)
                                                Book {{ $nightStay->nights() > 1 ? $nightStay->nights().' nights' : 'this night' }} on {{ $hotel->affiliateNetwork?->name ?? 'partner site' }}
                                            @else
                                                Check prices on {{ $hotel->affiliateNetwork?->name ?? 'partner site' }}
                                            @endif
                                        </a>
                                    @endif
                                </div>
                            </div>
                            @if($day['stay']['alternatives']->isNotEmpty())
                                <div class="mt-4 border-t border-orange-200 pt-3 print:hidden">
                                    <p class="text-sm text-gray-600 mb-2">Or stay at</p>
                                    <ul class="space-y-1.5 text-sm">
                                        @foreach($day['stay']['alternatives'] as $alternative)
                                            <li class="flex flex-wrap items-center justify-between gap-2">
                                                @php $alternativeOffer = ($day['stay']['alternativeOffers'] ?? collect())->get($alternative->id); @endphp
                                                <span>{{ $alternative->name }} @if($alternative->star_rating)<span class="text-yellow-500">{{ str_repeat('★', $alternative->star_rating) }}</span>@endif <span class="text-gray-500">· {{ isset($alternative->distance_km) ? distance_label($alternative->distance_km) : '' }}</span>@if($alternativeOffer) <strong class="ml-1">{{ money($alternativeOffer->dailyRate, $alternativeOffer->currency) }}</strong>@endif</span>
                                                @if($alternative->affiliate_url)
                                                    <a href="{{ booking_url($alternative, 'trip_planner', $nightStay ? new \App\Support\Agoda\Stay($nightStay->checkIn, $nightStay->checkIn->addDay(), $nightStay->adults) : null) }}" target="_blank" rel="nofollow sponsored noopener" class="font-medium text-java-accent hover:underline">{{ $nightStay ? 'Book' : 'Check prices' }}</a>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                    @endif
                </li>
            @endforeach
        </ol>
        @include('front.partials.affiliate-note', ['class' => 'mt-8 text-center print:hidden'])

        @if($articles->isNotEmpty())
            <section class="mt-16 print:hidden" aria-labelledby="read-before-heading">
                <h2 id="read-before-heading" class="font-playfair text-3xl font-bold mb-6">Read before you go</h2>
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                    @foreach($articles as $article)
                        <a href="{{ $article->url() }}" class="overflow-hidden rounded-xl bg-white shadow-md transition-shadow hover:shadow-xl">
                            <div class="h-36 bg-cover bg-center" style="background-image: url('{{ $article->featured_image_url }}');"></div>
                            <div class="p-4">
                                <h3 class="font-bold line-clamp-2">{{ $article->title }}</h3>
                                <p class="mt-1 text-sm text-gray-600 line-clamp-2">{{ $article->excerpt ?: text_excerpt($article->content, 120) }}</p>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        <p class="mt-12 text-sm text-gray-500">Drive times are rough estimates from straight-line distances; Java's roads and traffic vary a lot, so allow extra time.</p>
    @endif
</div>

@if($plan)
    <script>
        // Copy the trip's link (all choices are in the URL)
        document.getElementById('share-trip').addEventListener('click', async (event) => {
            const button = event.currentTarget;
            const label = button.querySelector('span');
            const status = document.getElementById('share-status');
            try {
                if (navigator.share) {
                    await navigator.share({ title: document.title, url: location.href });
                    return;
                }
                await navigator.clipboard.writeText(location.href);
                label.textContent = 'Link copied!';
                status.textContent = 'Link to this trip copied';
                setTimeout(() => (label.textContent = 'Share this trip'), 2500);
            } catch (error) {
                if (error.name !== 'AbortError') {
                    window.prompt('Copy this link to share your trip:', location.href);
                }
            }
        });
    </script>
@endif
@endsection
