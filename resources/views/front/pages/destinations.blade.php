@extends('front.layout.app')

@section('title', 'Explore Java Destinations - West, Central & East Java')

@section('hero')
<!-- Hero Section -->
<section class="relative h-[70vh] min-h-[600px] flex items-center justify-center overflow-hidden">
    <!-- Background Image -->
    <div class="absolute inset-0">
        <img src="{{ asset('images/sunrise.png') }}" alt="Java Destinations" class="w-full h-full object-cover">
        <div class="absolute inset-0 bg-linear-to-b from-black/60 via-black/40 to-black/70"></div>
    </div>
    
    <!-- Content -->
    <div class="relative z-10 max-w-7xl mx-auto px-6 lg:px-8 text-center text-white">
        <span class="inline-block px-4 py-2 bg-java-accent/80 rounded-full text-sm font-semibold mb-6 backdrop-blur-sm">
            Explore Java Island
        </span>
        <h1 class="font-playfair text-5xl md:text-7xl font-bold mb-6">
            Discover Amazing Destinations
        </h1>
        <p class="text-xl md:text-2xl text-white/90 max-w-3xl mx-auto mb-12">
            From volcanic peaks to ancient temples, explore {{ $totalDestinations }}+ incredible destinations across West, Central, and East Java
        </p>
        
        <!-- Search Bar -->
        <div class="max-w-4xl mx-auto">
            <form action="{{ route('destinations.index') }}" method="GET" class="bg-white rounded-full shadow-2xl p-2 flex flex-col md:flex-row gap-2">
                <!-- Search Input -->
                <div class="flex-1 relative">
                    <svg class="absolute left-6 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <input 
                        type="text" 
                        name="search" 
                        value="{{ $searchQuery ?? '' }}"
                        placeholder="Search destinations..." 
                        class="w-full pl-14 pr-6 py-4 rounded-full focus:outline-none text-gray-800 font-medium"
                    >
                </div>

                <!-- Region Filter -->
                <div class="relative">
                    <svg class="absolute left-6 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                    </svg>
                    <select 
                        name="region" 
                        class="pl-14 pr-10 py-4 rounded-full focus:outline-none text-gray-800 font-medium appearance-none bg-white cursor-pointer min-w-[200px]"
                    >
                        <option value="">All Regions</option>
                        @foreach($regions as $region)
                            <option value="{{ $region->slug }}" {{ $selectedRegion == $region->slug ? 'selected' : '' }}>
                                {{ $region->name }}
                            </option>
                        @endforeach
                    </select>
                    <svg class="absolute right-4 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </div>

                <!-- Search Button -->
                <button 
                    type="submit" 
                    class="px-8 py-4 bg-java-accent hover:bg-java-primary text-white rounded-full font-semibold transition-all duration-300 flex items-center justify-center gap-2"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <span class="hidden md:inline">Search</span>
                </button>
            </form>

            <!-- Active Filters Display -->
            @if($searchQuery || $selectedRegion)
            <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
                <span class="text-white/80 text-sm">Active filters:</span>
                
                @if($searchQuery)
                <div class="inline-flex items-center gap-2 px-4 py-2 bg-white/20 backdrop-blur-sm rounded-full text-white text-sm">
                    <span>"{{ $searchQuery }}"</span>
                    <a href="{{ route('destinations.index', ['region' => $selectedRegion]) }}" class="hover:text-java-accent transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </a>
                </div>
                @endif

                @if($selectedRegion)
                @php $selectedRegionName = $regions->where('slug', $selectedRegion)->first()->name ?? ''; @endphp
                <div class="inline-flex items-center gap-2 px-4 py-2 bg-white/20 backdrop-blur-sm rounded-full text-white text-sm">
                    <span>{{ $selectedRegionName }}</span>
                    <a href="{{ route('destinations.index', ['search' => $searchQuery]) }}" class="hover:text-java-accent transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </a>
                </div>
                @endif

                <a href="{{ route('destinations.index') }}" class="text-white/80 hover:text-white text-sm underline">
                    Clear all
                </a>
            </div>
            @endif
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
<!-- Search Results / Featured Destinations -->
@if($searchQuery || $selectedRegion)
<!-- Search Results Section -->
<section class="py-24 bg-gray-50">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
        <div class="text-center mb-16 scroll-animate opacity-0">
            <h2 class="font-playfair text-4xl md:text-6xl font-bold text-java-primary mb-6">
                Search Results
            </h2>
            <p class="text-gray-600 text-lg">
                @if($featuredDestinations->count() > 0)
                    Found {{ $featuredDestinations->count() }} destination(s) matching your search
                @else
                    No destinations found matching your search
                @endif
            </p>
        </div>

        @if($featuredDestinations->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach($featuredDestinations as $destination)
            <div class="scroll-animate opacity-0">
                <a href="{{ route('destinations.show', [$destination->region->slug, $destination->slug]) }}" class="destination-card group relative overflow-hidden rounded-3xl h-[400px] cursor-pointer block">
                    <img src="{{ image_url($destination->featured_image, 'destination') }}" 
                         alt="{{ $destination->name }}" 
                         class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                    <div class="absolute inset-0 bg-gradient-to-t from-black via-black/40 to-transparent opacity-80 group-hover:opacity-90 transition-opacity duration-300"></div>
                    <div class="absolute inset-0 p-6 flex flex-col justify-end">
                        <span class="inline-block w-fit px-3 py-1 bg-java-accent rounded-full text-white text-xs font-semibold mb-3">
                            {{ $destination->region->name }}
                        </span>
                        <h3 class="font-playfair text-3xl font-bold text-white mb-2">{{ $destination->name }}</h3>
                        <p class="text-white/90 text-sm">
                            {{ text_excerpt($destination->description, 100) }}
                        </p>
                    </div>
                </a>
            </div>
            @endforeach
        </div>
        @else
        <div class="text-center py-16">
            <svg class="w-24 h-24 mx-auto text-gray-300 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <h3 class="text-2xl font-bold text-gray-400 mb-4">No destinations found</h3>
            <p class="text-gray-500 mb-8">Try adjusting your search or filters</p>
            <a href="{{ route('destinations.index') }}" class="inline-block px-8 py-4 bg-java-primary text-white hover:bg-java-accent rounded-full font-semibold transition-all duration-300">
                View All Destinations
            </a>
        </div>
        @endif
    </div>
</section>
@else
<!-- Featured Destinations (when no search) -->
@if($featuredDestinations->count() > 0)
@include('front.pages.sections.features', ['homeFeaturedDestinations' => $featuredDestinations])
@endif
@endif

<!-- Destinations by Region -->
<section class="py-24 bg-white">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
        <div class="text-center mb-16 scroll-animate opacity-0">
            <span class="inline-block px-4 py-2 bg-java-accent/10 text-java-accent rounded-full text-sm font-semibold mb-4">
                Explore by Region
            </span>
            <h2 class="font-playfair text-4xl md:text-6xl font-bold text-java-primary mb-6">
                All Destinations
            </h2>
            <p class="text-gray-600 text-lg max-w-2xl mx-auto">
                Browse destinations organized by Java's three main regions
            </p>
        </div>

        @foreach($regions as $region)
        @if($region->destinations->count() > 0)
        <!-- Region Block -->
        <div class="mb-20 scroll-animate opacity-0">
            <div class="mb-10">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="font-playfair text-4xl md:text-5xl font-bold text-java-primary mb-3">
                            {{ $region->name }}
                        </h3>
                        <p class="text-gray-600 text-lg max-w-3xl">
                            {{ Str::limit($region->description, 200) }}
                        </p>
                    </div>
                    <a href="{{ route('destinations.region', $region->slug) }}" class="hidden md:inline-flex items-center gap-2 text-java-primary font-semibold hover:text-java-accent transition-colors">
                        <span>View All</span>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                        </svg>
                    </a>
                </div>
                
                <!-- Destinations Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    @foreach($region->destinations->take(4) as $destination)
                    <a href="{{ route('destinations.show', [$region->slug, $destination->slug]) }}" class="group bg-white rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-300">
                        <div class="relative h-48 overflow-hidden">
                            <img src="{{ image_url($destination->featured_image, 'destination') }}" 
                                 alt="{{ $destination->name }}" 
                                 class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                            @if($destination->is_featured)
                            <span class="absolute top-3 right-3 px-3 py-1 bg-java-accent text-white rounded-full text-xs font-semibold">
                                Featured
                            </span>
                            @endif
                        </div>
                        <div class="p-5">
                            <h4 class="font-playfair text-xl font-bold text-java-primary mb-2 group-hover:text-java-accent transition-colors">
                                {{ $destination->name }}
                            </h4>
                            <p class="text-gray-600 text-sm line-clamp-2">
                                {{ text_excerpt($destination->description, 80) }}
                            </p>
                        </div>
                    </a>
                    @endforeach
                </div>

                @if($region->destinations->count() > 4)
                <div class="text-center mt-8">
                    <a href="{{ route('destinations.region', $region->slug) }}" class="inline-flex items-center gap-2 px-6 py-3 border-2 border-java-primary text-java-primary hover:bg-java-primary hover:text-white rounded-full font-semibold transition-all duration-300">
                        <span>View All {{ $region->destinations->count() }} Destinations</span>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                        </svg>
                    </a>
                </div>
                @endif
            </div>

            @if(!$loop->last)
            <hr class="border-gray-200 my-16">
            @endif
        </div>
        @endif
        @endforeach
    </div>
</section>

<!-- Call to Action -->
<section class="py-24 bg-gradient-to-br from-java-primary to-java-dark text-white">
    <div class="max-w-4xl mx-auto px-6 lg:px-8 text-center">
        <h2 class="font-playfair text-4xl md:text-5xl font-bold mb-6">
            Ready to Explore Java?
        </h2>
        <p class="text-xl text-white/90 mb-8">
            Start planning your perfect Java adventure with our comprehensive guides and local recommendations
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('articles.index') }}" class="inline-block px-8 py-4 bg-white text-java-primary hover:bg-java-accent hover:text-white rounded-full font-semibold transition-all duration-300">
                Read Travel Guides
            </a>
            <a href="{{ route('home') }}#articles" class="inline-block px-8 py-4 border-2 border-white text-white hover:bg-white hover:text-java-primary rounded-full font-semibold transition-all duration-300">
                Browse Articles
            </a>
        </div>
    </div>
</section>
@endsection