@extends('front.layout.app')

@section('title', $destination->name . ' - ' . $destination->region->name)
@section('meta_description', $destination->meta_description ?: \App\Support\Seo::description($destination->description))

@section('content')
<!-- Hero Section -->
<div class="relative h-96 bg-cover bg-center" style="background-image: url('{{ $destination->featured_image_url }}');">
    <div class="absolute inset-0 bg-black/50"></div>
    <div class="relative container mx-auto px-4 h-full flex items-center">
        <div class="text-white">
            <!-- Breadcrumb -->
            <nav class="text-sm mb-4">
                <ol class="flex items-center space-x-2">
                    <li><a href="{{ route('home') }}" class="hover:text-blue-300">Home</a></li>
                    <li>/</li>
                    <li><a href="{{ route('destinations.index') }}" class="hover:text-blue-300">Destinations</a></li>
                    <li>/</li>
                    <li><a href="{{ route('destinations.region', $destination->region->slug) }}" class="hover:text-blue-300">{{ $destination->region->name }}</a></li>
                    <li>/</li>
                    <li class="text-blue-300">{{ $destination->name }}</li>
                </ol>
            </nav>
            
            <h1 class="text-5xl font-bold mb-4">{{ $destination->name }}</h1>
            <p class="text-xl">{{ $destination->region->name }}</p>
            
            <!-- Quick Stats -->
            <div class="flex items-center space-x-6 mt-6">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                        <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                    </svg>
                    <span>{{ number_format($destination->views_count) }} views</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="container mx-auto px-4 py-12">
    <!-- Destination Overview, with other places in the region alongside -->
    <div class="mb-16 grid gap-10 lg:grid-cols-3 lg:gap-12">
        <div class="prose max-w-none lg:col-span-2">
            {!! rich_text($destination->description) !!}
        </div>

        @if($otherDestinations->count() > 0)
        <aside aria-labelledby="other-places-heading">
            {{-- Scrolls on its own when the region has more places than fit on screen --}}
            <div class="lg:sticky lg:top-28 lg:max-h-[calc(100vh-8rem)] lg:overflow-y-auto lg:pr-1">
                <h2 id="other-places-heading" class="text-xl font-bold mb-4">Other Places in {{ $destination->region->name }}</h2>
                <div class="space-y-3">
                    @foreach($otherDestinations as $other)
                    <a href="{{ route('destinations.show', [$other->region->slug, $other->slug]) }}" class="group flex overflow-hidden rounded-lg bg-white shadow-md transition-shadow hover:shadow-xl">
                        <div class="w-24 min-h-24 shrink-0 bg-cover bg-center" style="background-image: url('{{ $other->featured_image_url }}');" role="img" aria-label="{{ $other->name }}"></div>
                        <div class="min-w-0 px-4 py-3">
                            <h3 class="font-bold leading-snug group-hover:text-blue-600">{{ $other->name }}</h3>
                            <p class="mt-1 text-sm text-gray-600 line-clamp-2">{{ text_excerpt($other->description, 100) }}</p>
                        </div>
                    </a>
                    @endforeach
                </div>
                <a href="{{ route('destinations.region', $destination->region->slug) }}" class="mt-4 inline-block text-sm text-blue-600 hover:text-blue-800">All places in {{ $destination->region->name }} →</a>
            </div>
        </aside>
        @endif
    </div>

    <!-- Accommodations Section -->
    @if($accommodations->count() > 0)
    <section class="mb-16">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h2 class="text-3xl font-bold">Where to Stay</h2>
                @if($accommodations->contains(fn ($accommodation) => isset($accommodation->distance_km)))
                    <p class="mt-1 text-gray-600">Including top-rated stays near {{ $destination->name }}</p>
                @endif
            </div>
            <a href="{{ route('destinations.show', [$destination->region->slug, $destination->slug]) }}?section=accommodations" class="text-blue-600 hover:text-blue-800">View All →</a>
        </div>
        
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($accommodations as $accommodation)
            <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow">
                <div class="relative h-48 bg-cover bg-center" style="background-image: url('{{ $accommodation->featured_image_url }}');">
                    @isset($accommodation->distance_km)
                        <div class="absolute top-4 left-4 bg-white/90 text-gray-800 px-3 py-1 rounded-full text-sm font-medium shadow-sm">
                            {{ distance_label($accommodation->distance_km) }} away
                        </div>
                    @endisset
                    <div class="absolute top-4 right-4 bg-blue-600 text-white px-3 py-1 rounded-full text-sm">
                        {{ \App\Models\Accommodation::TYPES[$accommodation->type] ?? ucfirst($accommodation->type) }}
                    </div>
                </div>
                <div class="p-4">
                    <h3 class="font-bold text-lg mb-2">{{ $accommodation->name }}</h3>
                    @if($accommodation->star_rating)
                        <div class="flex items-center mb-2 text-yellow-400" aria-label="{{ $accommodation->star_rating }}-star property">
                            @for($i = 0; $i < 5; $i++)
                                <svg class="w-4 h-4 fill-current {{ $i < $accommodation->star_rating ? '' : 'text-gray-300' }}" viewBox="0 0 20 20" aria-hidden="true">
                                    <path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/>
                                </svg>
                            @endfor
                        </div>
                    @endif
                    <p class="text-gray-600 text-sm mb-4 line-clamp-2">{{ $accommodation->short_description ?? strip_tags($accommodation->description) }}</p>
                    <div class="flex justify-between items-center gap-3">
                        @if($accommodation->price_from)
                            <span class="text-blue-600 font-bold">From {{ $accommodation->currency }} {{ number_format($accommodation->price_from, 0) }}/night</span>
                        @else
                            <span class="text-sm text-gray-500">Check prices on {{ $accommodation->affiliateNetwork?->name ?? 'partner site' }}</span>
                        @endif
                        @if($accommodation->affiliate_url)
                            <a href="{{ $accommodation->affiliate_url }}" target="_blank" rel="nofollow sponsored noopener" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm whitespace-nowrap">
                                Book Now
                            </a>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </section>
    @endif

    {{-- Nearby Places: the closest other destinations --}}
    @if($nearbyDestinations->count() > 0)
    <section class="mb-16" aria-labelledby="nearby-places-heading">
        <div class="mb-8">
            <h2 id="nearby-places-heading" class="text-3xl font-bold">Nearby Places</h2>
            <p class="mt-1 text-gray-600">Closest to {{ $destination->name }}</p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($nearbyDestinations as $nearby)
            <a href="{{ route('destinations.show', [$nearby->region->slug, $nearby->slug]) }}" class="group bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow">
                <div class="relative h-48 bg-cover bg-center" style="background-image: url('{{ $nearby->featured_image_url }}');" role="img" aria-label="{{ $nearby->name }}">
                    <div class="absolute top-4 left-4 bg-white/90 text-gray-800 px-3 py-1 rounded-full text-sm font-medium shadow-sm">
                        {{ distance_label($nearby->distance_km) }} away
                    </div>
                </div>
                <div class="p-4">
                    <h3 class="font-bold text-lg group-hover:text-blue-600">{{ $nearby->name }}</h3>
                    @if($nearby->region_id !== $destination->region_id)
                        <p class="text-sm text-gray-500">{{ $nearby->region->name }}</p>
                    @endif
                    <p class="mt-2 text-gray-600 text-sm line-clamp-2">{{ text_excerpt($nearby->description, 160) }}</p>
                </div>
            </a>
            @endforeach
        </div>
    </section>
    @endif

    <!-- Tours Section -->
    @if($tours->count() > 0)
    <section class="mb-16">
        <div class="flex justify-between items-center mb-8">
            <h2 class="text-3xl font-bold">Things to Do</h2>
            <a href="{{ route('destinations.show', [$destination->region->slug, $destination->slug]) }}?section=tours" class="text-blue-600 hover:text-blue-800">View All →</a>
        </div>
        
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($tours as $tour)
            <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow">
                <div class="relative h-48 bg-cover bg-center" style="background-image: url('{{ $tour->featured_image_url }}');">
                    <div class="absolute top-4 right-4 bg-green-600 text-white px-3 py-1 rounded-full text-sm">
                        {{ $tour->duration }}
                    </div>
                </div>
                <div class="p-4">
                    <h3 class="font-bold text-lg mb-2">{{ $tour->name }}</h3>
                    <div class="flex items-center mb-2">
                        <div class="flex text-yellow-400">
                            @for($i = 0; $i < 5; $i++)
                                @if($i < floor($tour->average_rating))
                                    <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20">
                                        <path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/>
                                    </svg>
                                @else
                                    <svg class="w-4 h-4 fill-current text-gray-300" viewBox="0 0 20 20">
                                        <path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/>
                                    </svg>
                                @endif
                            @endfor
                        </div>
                        <span class="ml-2 text-sm text-gray-600">{{ number_format($tour->average_rating, 1) }}</span>
                    </div>
                    <p class="text-gray-600 text-sm mb-4 line-clamp-2">{{ text_excerpt($tour->short_description ?: $tour->description, 160) }}</p>
                    <div class="flex justify-between items-center">
                        <span class="text-green-600 font-bold">From ${{ number_format($tour->price_per_person, 0) }}/person</span>
                        <a href="{{ $tour->affiliate_url }}" target="_blank" rel="nofollow" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 text-sm">
                            Book Tour
                        </a>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </section>
    @endif

    <!-- Restaurants Section -->
    @if($restaurants->count() > 0)
    <section class="mb-16">
        <div class="flex justify-between items-center mb-8">
            <h2 class="text-3xl font-bold">Where to Eat</h2>
            <a href="{{ route('destinations.show', [$destination->region->slug, $destination->slug]) }}?section=restaurants" class="text-blue-600 hover:text-blue-800">View All →</a>
        </div>
        
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($restaurants as $restaurant)
            <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow">
                <div class="relative h-48 bg-cover bg-center" style="background-image: url('{{ $restaurant->featured_image_url }}');">
                    <div class="absolute top-4 right-4 bg-red-600 text-white px-3 py-1 rounded-full text-sm">
                        {{ implode(', ', $restaurant->cuisine_type ?? []) }}
                    </div>
                </div>
                <div class="p-4">
                    <h3 class="font-bold text-lg mb-2">{{ $restaurant->name }}</h3>
                    <div class="flex items-center mb-2">
                        <div class="flex text-yellow-400">
                            @for($i = 0; $i < 5; $i++)
                                @if($i < floor($restaurant->average_rating))
                                    <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20">
                                        <path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/>
                                    </svg>
                                @else
                                    <svg class="w-4 h-4 fill-current text-gray-300" viewBox="0 0 20 20">
                                        <path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/>
                                    </svg>
                                @endif
                            @endfor
                        </div>
                        <span class="ml-2 text-sm text-gray-600">{{ number_format($restaurant->average_rating, 1) }}</span>
                    </div>
                    <p class="text-gray-600 text-sm mb-4 line-clamp-2">{{ text_excerpt($restaurant->description, 160) }}</p>
                    <div class="flex justify-between items-center">
                        <span class="text-red-600 font-bold">${{ $restaurant->price_range }}</span>
                        <a href="{{ $restaurant->affiliate_url }}" target="_blank" rel="nofollow" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 text-sm">
                            View Menu
                        </a>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </section>
    @endif

    <!-- Related Articles -->
    @if($articles->count() > 0)
    <section class="mb-16">
        <div class="flex justify-between items-center mb-8">
            <h2 class="text-3xl font-bold">Travel Guides & Tips</h2>
        </div>
        
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach($articles as $article)
            <a href="{{ route('articles.show', [$article->id, $article->slug]) }}" class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow">
                <div class="relative h-48 bg-cover bg-center" style="background-image: url('{{ $article->featured_image_url }}');">
                    <span class="absolute top-4 left-4 bg-java-accent text-white px-3 py-1 rounded-full text-sm font-medium">
                        {{ \App\Admin\Resources\ArticleResource::TYPES[$article->article_type] ?? ucfirst($article->article_type) }}
                    </span>
                </div>
                <div class="p-4">
                    <h3 class="font-bold text-lg mb-2 line-clamp-2">{{ $article->title }}</h3>
                    <p class="text-gray-600 text-sm mb-2 line-clamp-2">{{ $article->excerpt ?: text_excerpt($article->content, 160) }}</p>
                    <span class="text-blue-600 text-sm">Read more →</span>
                </div>
            </a>
            @endforeach
        </div>
    </section>
    @endif

</div>
@endsection