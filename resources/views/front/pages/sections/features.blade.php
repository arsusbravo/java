<section class="py-24 bg-white" id="destinations">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
        <div class="text-center mb-16 scroll-animate opacity-0">
            <span class="inline-block px-4 py-2 bg-java-accent/10 text-java-accent rounded-full text-sm font-semibold mb-4">
                Must-Visit Places
            </span>
            <h2 class="font-playfair text-4xl md:text-6xl font-bold text-java-primary mb-6">
                Iconic Destinations
            </h2>
            <p class="text-gray-600 text-lg max-w-2xl mx-auto">
                From ancient temples to volcanic peaks, discover the most breathtaking locations across Java Island
            </p>
        </div>

        @if($homeFeaturedDestinations->count() > 0)
        <!-- Destination Grid - Masonry Style -->
        <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
            <!-- Large Featured Card (First Destination) -->
            @php $firstDestination = $homeFeaturedDestinations->first(); @endphp
            <div class="md:col-span-8 md:row-span-2 scroll-animate opacity-0">
                <a href="{{ route('destinations.show', [$firstDestination->region->slug, $firstDestination->slug]) }}" class="destination-card group relative overflow-hidden rounded-3xl h-full min-h-[600px] cursor-pointer block">
                    <img src="{{ image_url($firstDestination->featured_image, 'destination') }}" 
                         alt="{{ $firstDestination->name }}" 
                         class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                    <div class="absolute inset-0 bg-linear-to-t from-black via-black/40 to-transparent opacity-80 group-hover:opacity-90 transition-opacity duration-300"></div>
                    <div class="absolute inset-0 p-8 flex flex-col justify-end">
                        <span class="inline-block w-fit px-4 py-2 bg-java-accent rounded-full text-white text-sm font-semibold mb-4">
                            Featured
                        </span>
                        <h3 class="font-playfair text-4xl lg:text-5xl font-bold text-white mb-4">{{ $firstDestination->name }}</h3>
                        <p class="text-white/90 text-lg mb-6 max-w-2xl">
                            {{ $firstDestination->short_description ?? text_excerpt($firstDestination->description, 120) }}
                        </p>
                        <div class="flex items-center gap-6 text-white/80">
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                </svg>
                                <span>{{ $firstDestination->region->name }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                </svg>
                                <span>4.9/5</span>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Smaller Cards (Remaining Destinations) -->
            @foreach($homeFeaturedDestinations->skip(1) as $destination)
            <div class="md:col-span-4 scroll-animate opacity-0">
                <a href="{{ route('destinations.show', [$destination->region->slug, $destination->slug]) }}" class="destination-card group relative overflow-hidden rounded-3xl h-full min-h-[290px] cursor-pointer block">
                    <img src="{{ image_url($destination->featured_image, 'destination') }}" 
                         alt="{{ $destination->name }}" 
                         class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                    <div class="absolute inset-0 bg-linear-to-t from-black/90 to-transparent"></div>
                    <div class="absolute inset-0 p-6 flex flex-col justify-end">
                        <h3 class="font-playfair text-2xl font-bold text-white mb-2">{{ $destination->name }}</h3>
                        <p class="text-white/80 text-sm mb-3">{{ $destination->short_description ?? text_excerpt($destination->description, 50) }}</p>
                        <div class="flex items-center gap-2 text-white/70 text-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            </svg>
                            <span>{{ $destination->region->name }}</span>
                        </div>
                    </div>
                </a>
            </div>
            @endforeach
        </div>
        @endif

        @if (request()->path() !== 'destinations')
            <div class="text-center mt-12">
            <a href="{{ route('destinations.index') }}" class="inline-block px-8 py-4 border-2 border-java-primary text-java-primary hover:bg-java-primary hover:text-white rounded-full font-semibold transition-all duration-300">
                View All Destinations →
            </a>
        </div>
        @endif
    </div>
</section>