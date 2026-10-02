@extends('front.layout.app')

@section('title', $region->meta_title ?: $region->name . ' - Explore Destinations | Java Sunrise')
@section('meta_description', $region->meta_description ?: \App\Support\Seo::description($region->description))

@section('hero')
<!-- Hero Section -->
<section class="relative h-[60vh] min-h-[500px] flex items-center justify-center overflow-hidden">
    <!-- Background Image -->
    <div class="absolute inset-0">
        <img src="{{ image_url($region->image, 'region') }}" alt="{{ $region->name }}" class="w-full h-full object-cover">
        <div class="absolute inset-0 bg-linear-to-b from-black/70 via-black/50 to-black/80"></div>
    </div>
    
    <!-- Content -->
    <div class="relative z-10 max-w-7xl mx-auto px-6 lg:px-8 text-center text-white">
        <!-- Breadcrumb -->
        <div class="mb-6">
            <nav class="flex justify-center text-sm">
                <ol class="flex items-center space-x-2 text-white/80">
                    <li><a href="{{ route('home') }}" class="hover:text-white transition-colors">Home</a></li>
                    <li><span class="mx-2">/</span></li>
                    <li><a href="{{ route('destinations.index') }}" class="hover:text-white transition-colors">Destinations</a></li>
                    <li><span class="mx-2">/</span></li>
                    <li class="text-java-accent">{{ $region->name }}</li>
                </ol>
            </nav>
        </div>

        <h1 class="font-playfair text-5xl md:text-7xl font-bold mb-6">
            {{ $region->name }}
        </h1>
        <p class="text-xl md:text-2xl text-white/90 max-w-3xl mx-auto mb-8">
            {{ $region->description }}
        </p>

        <!-- Stats -->
        <div class="flex flex-wrap justify-center gap-8 mt-10">
            <div class="text-center">
                <div class="text-4xl font-bold text-java-accent mb-2">{{ $region->destinations->count() }}</div>
                <div class="text-white/80">Destinations</div>
            </div>
            <div class="text-center">
                <div class="text-4xl font-bold text-java-accent mb-2">{{ $featuredDestinations->count() }}</div>
                <div class="text-white/80">Featured</div>
            </div>
        </div>
    </div>

    <!-- Scroll Indicator -->
    <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 animate-bounce">
        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
        </svg>
    </div>
</section>
@endsection

@section('content')
<!-- Featured Destinations -->
@if($featuredDestinations->count() > 0)
<section class="py-24 bg-gray-50">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
        <div class="text-center mb-16 scroll-animate opacity-0">
            <span class="inline-block px-4 py-2 bg-java-accent/10 text-java-accent rounded-full text-sm font-semibold mb-4">
                Must-Visit
            </span>
            <h2 class="font-playfair text-4xl md:text-6xl font-bold text-java-primary mb-6">
                Featured in {{ $region->name }}
            </h2>
            <p class="text-gray-600 text-lg max-w-2xl mx-auto">
                Don't miss these top destinations in this region
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-{{ $featuredDestinations->count() == 1 ? '1' : ($featuredDestinations->count() == 2 ? '2' : '3') }} gap-8">
            @foreach($featuredDestinations as $destination)
            <div class="scroll-animate opacity-0">
                <a href="{{ route('destinations.show', [$region->slug, $destination->slug]) }}" class="destination-card group relative overflow-hidden rounded-3xl h-[450px] cursor-pointer block">
                    <img src="{{ image_url($destination->featured_image, 'destination') }}" 
                         alt="{{ $destination->name }}" 
                         class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                    <div class="absolute inset-0 bg-linear-to-t from-black via-black/40 to-transparent opacity-80 group-hover:opacity-90 transition-opacity duration-300"></div>
                    <div class="absolute inset-0 p-8 flex flex-col justify-end">
                        <span class="inline-block w-fit px-4 py-2 bg-java-accent rounded-full text-white text-sm font-semibold mb-4">
                            Featured
                        </span>
                        <h3 class="font-playfair text-3xl lg:text-4xl font-bold text-white mb-3">{{ $destination->name }}</h3>
                        <p class="text-white/90 text-base mb-4">
                            {{ text_excerpt($destination->description, 150) }}
                        </p>
                        <div class="flex items-center gap-2 text-white/70">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                            </svg>
                            <span class="font-semibold">Explore</span>
                        </div>
                    </div>
                </a>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

<!-- All Destinations in Region -->
<section class="py-24 bg-white">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
        <div class="text-center mb-16 scroll-animate opacity-0">
            <h2 class="font-playfair text-4xl md:text-6xl font-bold text-java-primary mb-6">
                All Destinations
            </h2>
            <p class="text-gray-600 text-lg max-w-2xl mx-auto">
                Explore {{ $region->destinations->count() }} amazing places in {{ $region->name }}
            </p>
        </div>

        @if($region->destinations->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
            @foreach($region->destinations as $destination)
            <div class="scroll-animate opacity-0">
                <a href="{{ route('destinations.show', [$region->slug, $destination->slug]) }}" class="group bg-white rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-300 block">
                    <div class="relative h-56 overflow-hidden">
                        <img src="{{ image_url($destination->featured_image, 'destination') }}" 
                             alt="{{ $destination->name }}" 
                             class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                        @if($destination->is_featured)
                        <span class="absolute top-3 right-3 px-3 py-1 bg-java-accent text-white rounded-full text-xs font-semibold">
                            Featured
                        </span>
                        @endif
                        @if($destination->views_count > 0)
                        <div class="absolute bottom-3 left-3 flex items-center gap-1 px-2 py-1 bg-black/60 backdrop-blur-sm rounded-full text-white text-xs">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            <span>{{ number_format($destination->views_count) }}</span>
                        </div>
                        @endif
                    </div>
                    <div class="p-6">
                        <h3 class="font-playfair text-xl font-bold text-java-primary mb-2 group-hover:text-java-accent transition-colors line-clamp-1">
                            {{ $destination->name }}
                        </h3>
                        <p class="text-gray-600 text-sm line-clamp-2 mb-4">
                            {{ text_excerpt($destination->description, 100) }}
                        </p>
                        <div class="flex items-center gap-2 text-java-primary font-semibold text-sm group-hover:gap-3 transition-all">
                            <span>Discover More</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                            </svg>
                        </div>
                    </div>
                </a>
            </div>
            @endforeach
        </div>
        @else
        <div class="text-center py-16 bg-gray-50 rounded-3xl">
            <svg class="w-24 h-24 mx-auto text-gray-300 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
            </svg>
            <h3 class="text-2xl font-bold text-gray-400 mb-4">No destinations available yet</h3>
            <p class="text-gray-500 mb-8">Check back soon for new destinations in {{ $region->name }}</p>
            <a href="{{ route('destinations.index') }}" class="inline-block px-8 py-4 bg-java-primary text-white hover:bg-java-accent rounded-full font-semibold transition-all duration-300">
                Browse All Regions
            </a>
        </div>
        @endif
    </div>
</section>

<!-- Other Regions -->
@if($otherRegions->count() > 0)
<section class="py-24 bg-gray-50">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
        <div class="text-center mb-16 scroll-animate opacity-0">
            <span class="inline-block px-4 py-2 bg-java-accent/10 text-java-accent rounded-full text-sm font-semibold mb-4">
                Explore More
            </span>
            <h2 class="font-playfair text-4xl md:text-6xl font-bold text-java-primary mb-6">
                Other Regions of Java
            </h2>
            <p class="text-gray-600 text-lg max-w-2xl mx-auto">
                Discover more amazing destinations across Java Island
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            @foreach($otherRegions as $otherRegion)
            <div class="scroll-animate opacity-0">
                <a href="{{ route('destinations.region', $otherRegion->slug) }}" class="group bg-white rounded-3xl overflow-hidden shadow-xl hover:shadow-2xl transition-all duration-500 block">
                    <div class="relative h-64 overflow-hidden">
                        <img src="{{ image_url($otherRegion->image, 'region') }}" 
                             alt="{{ $otherRegion->name }}" 
                             class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                        <div class="absolute inset-0 bg-linear-to-t from-black/80 via-black/30 to-transparent"></div>
                        <div class="absolute bottom-6 left-6 right-6">
                            <h3 class="font-playfair text-3xl font-bold text-white mb-2 group-hover:text-java-accent transition-colors">
                                {{ $otherRegion->name }}
                            </h3>
                            <div class="flex items-center gap-4 text-white/80 text-sm">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    </svg>
                                    <span>{{ $otherRegion->destinations_count }} destinations</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="p-6">
                        <p class="text-gray-600 mb-4 line-clamp-2">
                            {{ Str::limit($otherRegion->description, 150) }}
                        </p>
                        <div class="flex items-center gap-2 text-java-primary font-semibold group-hover:gap-3 transition-all">
                            <span>Explore {{ $otherRegion->name }}</span>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                            </svg>
                        </div>
                    </div>
                </a>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

<!-- Call to Action -->
<section class="py-24 bg-linear-to-br from-java-primary to-java-dark text-white">
    <div class="max-w-4xl mx-auto px-6 lg:px-8 text-center">
        <h2 class="font-playfair text-4xl md:text-5xl font-bold mb-6">
            Ready to Explore {{ $region->name }}?
        </h2>
        <p class="text-xl text-white/90 mb-8">
            Find the best hotels, tours, and travel guides for your {{ $region->name }} adventure
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('articles.index') }}" class="inline-block px-8 py-4 bg-white text-java-primary hover:bg-java-accent hover:text-white rounded-full font-semibold transition-all duration-300">
                Read Travel Guides
            </a>
            <a href="{{ route('destinations.index') }}" class="inline-block px-8 py-4 border-2 border-white text-white hover:bg-white hover:text-java-primary rounded-full font-semibold transition-all duration-300">
                View All Destinations
            </a>
        </div>
    </div>
</section>
@endsection