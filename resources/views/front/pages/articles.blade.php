@extends('front.layout.app')

@section('hero')
<!-- Hero Section with Parallax Effect -->
<section class="hero-parallax relative h-screen flex items-center justify-center overflow-hidden">
    <div class="parallax-bg absolute inset-0">
        <img src="{{ image_url('images/' . $heroImage, 'hero') }}" alt="{{ $heroTitle }}" class="w-full h-full object-cover scale-110">
    </div>
    <div class="absolute inset-0 bg-linear-to-b from-black/65 via-black/55 to-black/75"></div>
    
    <!-- Animated overlay patterns -->
    <div class="absolute inset-0 opacity-20">
        <div class="absolute top-20 left-10 w-72 h-72 bg-java-accent rounded-full mix-blend-multiply filter blur-3xl animate-blob"></div>
        <div class="absolute top-40 right-10 w-72 h-72 bg-orange-400 rounded-full mix-blend-multiply filter blur-3xl animate-blob animation-delay-2000"></div>
        <div class="absolute bottom-20 left-1/3 w-72 h-72 bg-yellow-400 rounded-full mix-blend-multiply filter blur-3xl animate-blob animation-delay-4000"></div>
    </div>
    
    <div class="hero-content relative z-10 text-center text-white max-w-5xl px-6 lg:px-8">
        <div class="scroll-animate opacity-0">
            <span class="inline-block px-4 py-2 bg-java-accent/90 backdrop-blur-sm rounded-full text-sm font-semibold mb-6 animate-bounce-slow">
                {{ $heroBadge }}
            </span>
            <h1 class="font-playfair text-5xl md:text-7xl lg:text-8xl font-black mb-8 leading-tight">
                {!! $heroTitle !!}
            </h1>
            <p class="text-xl md:text-2xl mb-12 font-light max-w-3xl mx-auto leading-relaxed">
                {{ $heroDescription }}
            </p>
            
            <!-- Quick Stats (only for plan page) -->
            @if($pageType === 'plan')
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 max-w-3xl mx-auto mb-12">
                <div class="text-center backdrop-blur-sm bg-white/10 rounded-2xl p-4 border border-white/20">
                    <div class="text-3xl md:text-4xl font-bold text-java-accent mb-1">{{ $articles->total() }}</div>
                    <div class="text-sm text-blue-200">Planning Guides</div>
                </div>
                <div class="text-center backdrop-blur-sm bg-white/10 rounded-2xl p-4 border border-white/20">
                    <div class="text-3xl md:text-4xl font-bold text-java-accent mb-1">50+</div>
                    <div class="text-sm text-blue-200">Destinations</div>
                </div>
                <div class="text-center backdrop-blur-sm bg-white/10 rounded-2xl p-4 border border-white/20">
                    <div class="text-3xl md:text-4xl font-bold text-java-accent mb-1">20+</div>
                    <div class="text-sm text-blue-200">Itineraries</div>
                </div>
                <div class="text-center backdrop-blur-sm bg-white/10 rounded-2xl p-4 border border-white/20">
                    <div class="text-3xl md:text-4xl font-bold text-java-accent mb-1">100+</div>
                    <div class="text-sm text-blue-200">Travel Tips</div>
                </div>
            </div>
            @endif

            <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                <a href="#articles-list" class="cta-button group inline-flex items-center gap-3 px-10 py-5 bg-java-accent hover:bg-java-accent/90 text-white rounded-full font-semibold transition-all duration-300 hover:-translate-y-1 shadow-2xl hover:shadow-java-accent/50">
                    <span>{{ $ctaPrimary }}</span>
                    <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                    </svg>
                </a>
                <a href="#{{ $pageType === 'plan' ? 'tips' : 'featured' }}" class="inline-flex items-center gap-2 px-10 py-5 bg-white/10 backdrop-blur-md hover:bg-white/20 text-white rounded-full font-semibold transition-all duration-300 border-2 border-white/30">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        @if($pageType === 'plan')
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                        @else
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                        @endif
                    </svg>
                    <span>{{ $ctaSecondary }}</span>
                </a>
            </div>
        </div>
    </div>
    
    <!-- Scroll Indicator -->
    <div class="absolute bottom-10 left-1/2 transform -translate-x-1/2 animate-bounce">
        <div class="w-6 h-10 border-2 border-white/50 rounded-full flex justify-center">
            <div class="w-1.5 h-3 bg-white/80 rounded-full mt-2 animate-scroll"></div>
        </div>
    </div>
</section>
@endsection

@section('content')

<!-- Filter Tabs (only for plan and guides) -->
@if($pageType !== 'articles')
<section class="py-12 bg-gray-50 border-b border-gray-200" id="articles-list">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="scroll-animate opacity-0 translate-y-8">
            <div class="flex flex-wrap items-center justify-between gap-6">
                <!-- Category Tabs -->
                <div class="flex flex-wrap gap-3">
                    @if($pageType === 'plan')
                        <a href="{{ route('articles.plan') }}" 
                           class="px-6 py-3 {{ !request('filter') ? 'bg-java-primary text-white shadow-lg shadow-java-primary/30' : 'bg-white text-gray-700 border border-gray-200' }} rounded-full font-semibold transition-all duration-300 hover:-translate-y-1">
                            All Planning Guides
                        </a>
                        <a href="{{ route('articles.plan') }}?filter=itinerary" 
                           class="px-6 py-3 {{ request('filter') === 'itinerary' ? 'bg-java-primary text-white shadow-lg shadow-java-primary/30' : 'bg-white text-gray-700 hover:bg-java-accent hover:text-white border border-gray-200' }} rounded-full font-semibold transition-all duration-300 hover:-translate-y-1">
                            📍 Itineraries
                        </a>
                        <a href="{{ route('articles.plan') }}?filter=tips" 
                           class="px-6 py-3 {{ request('filter') === 'tips' ? 'bg-java-primary text-white shadow-lg shadow-java-primary/30' : 'bg-white text-gray-700 hover:bg-java-accent hover:text-white border border-gray-200' }} rounded-full font-semibold transition-all duration-300 hover:-translate-y-1">
                            💡 Travel Tips
                        </a>
                    @elseif($pageType === 'guides')
                        <a href="{{ route('articles.guides') }}" 
                           class="px-6 py-3 {{ !request('filter') ? 'bg-java-primary text-white shadow-lg shadow-java-primary/30' : 'bg-white text-gray-700 border border-gray-200' }} rounded-full font-semibold transition-all duration-300 hover:-translate-y-1">
                            All Guides
                        </a>
                        <a href="{{ route('articles.guides') }}?filter=guide" 
                           class="px-6 py-3 {{ request('filter') === 'guide' ? 'bg-java-primary text-white shadow-lg shadow-java-primary/30' : 'bg-white text-gray-700 hover:bg-java-accent hover:text-white border border-gray-200' }} rounded-full font-semibold transition-all duration-300 hover:-translate-y-1">
                            📚 Travel Guides
                        </a>
                        <a href="{{ route('articles.guides') }}?filter=news" 
                           class="px-6 py-3 {{ request('filter') === 'news' ? 'bg-java-primary text-white shadow-lg shadow-java-primary/30' : 'bg-white text-gray-700 hover:bg-java-accent hover:text-white border border-gray-200' }} rounded-full font-semibold transition-all duration-300 hover:-translate-y-1">
                            📰 Travel News
                        </a>
                    @endif
                </div>

                <!-- Search -->
                <div class="relative">
                    <input type="text" 
                           placeholder="Search {{ $pageType === 'plan' ? 'guides' : 'articles' }}..." 
                           class="pl-12 pr-6 py-3 rounded-full border border-gray-300 focus:border-java-accent focus:ring-2 focus:ring-java-accent/20 outline-none w-full md:w-80 transition-all duration-300">
                    <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>
</section>
@else
<div id="articles-list" class="pt-12"></div>
@endif

<!-- Main Content -->
<section class="py-16 lg:py-24">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-3 gap-12">
            <!-- Articles Grid -->
            <div class="lg:col-span-2">
                @if($articles->count() > 0)
                    <div class="grid md:grid-cols-2 gap-8">
                        @foreach($articles as $index => $article)
                            <article class="group bg-white rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-500 scroll-animate {{ $index % 2 == 0 ? 'opacity-0 translate-y-8' : 'opacity-0 -translate-y-8' }}">
                                <!-- Image -->
                                <div class="relative overflow-hidden aspect-[4/3]">
                                    @if($article->featured_image)
                                        <img src="{{ image_url($article->featured_image, 'article') }}" 
                                             alt="{{ $article->title }}" 
                                             class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                                    @else
                                        <div class="w-full h-full bg-linear-to-br from-java-primary to-java-accent"></div>
                                    @endif
                                    
                                    <!-- Overlay Gradient -->
                                    <div class="absolute inset-0 bg-linear-to-t from-black/60 via-black/20 to-transparent"></div>
                                    
                                    <!-- Article Type Badge -->
                                    <div class="absolute top-4 left-4">
                                        <span class="bg-java-accent text-white px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-wide shadow-lg">
                                            @if($article->article_type === 'itinerary')
                                                📍 Itinerary
                                            @elseif($article->article_type === 'tips')
                                                💡 Tips
                                            @elseif($article->article_type === 'guide')
                                                📚 Guide
                                            @elseif($article->article_type === 'news')
                                                📰 News
                                            @else
                                                ⭐ Article
                                            @endif
                                        </span>
                                    </div>

                                    <!-- Reading Time -->
                                    <div class="absolute bottom-4 left-4 flex items-center gap-2 text-white text-sm">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                        </svg>
                                        <span>{{ rand(3, 8) }} min read</span>
                                    </div>
                                </div>

                                <!-- Content -->
                                <div class="p-6">
                                    <!-- Destinations -->
                                    @if($article->destinations && $article->destinations->count() > 0)
                                        <div class="flex flex-wrap gap-2 mb-3">
                                            @foreach($article->destinations->take(2) as $destination)
                                                <span class="text-xs text-java-primary font-semibold">
                                                    📍 {{ $destination->name }}
                                                </span>
                                            @endforeach
                                            @if($article->destinations->count() > 2)
                                                <span class="text-xs text-gray-500">
                                                    +{{ $article->destinations->count() - 2 }} more
                                                </span>
                                            @endif
                                        </div>
                                    @endif

                                    <!-- Title -->
                                    <h3 class="font-playfair text-xl lg:text-2xl font-bold text-gray-900 mb-3 group-hover:text-java-primary transition-colors duration-300 line-clamp-2">
                                        {{ $article->title }}
                                    </h3>

                                    <!-- Excerpt -->
                                    <p class="text-gray-600 leading-relaxed mb-4 line-clamp-3">
                                        {{ $article->excerpt }}
                                    </p>

                                    <!-- Meta Info -->
                                    <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                                        <div class="flex items-center gap-3">
                                            @if($article->author)
                                                <div class="flex items-center gap-2">
                                                    <div class="w-8 h-8 rounded-full bg-java-primary/10 flex items-center justify-center">
                                                        <span class="text-java-primary text-sm font-bold">
                                                            {{ strtoupper(substr($article->author->name, 0, 1)) }}
                                                        </span>
                                                    </div>
                                                    <span class="text-sm text-gray-600">{{ $article->author->name }}</span>
                                                </div>
                                            @endif
                                        </div>
                                        
                                        <a href="{{ route('articles.show', [$article->id, $article->slug]) }}" 
                                           class="inline-flex items-center gap-2 text-java-primary font-semibold hover:gap-3 transition-all duration-300 group">
                                            Read More
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                            </svg>
                                        </a>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <!-- Pagination -->
                    @if($articles->hasPages())
                        <div class="mt-12">
                            {{ $articles->links() }}
                        </div>
                    @endif
                @else
                    <!-- Empty State -->
                    <div class="text-center py-16 bg-gray-50 rounded-2xl">
                        <div class="text-6xl mb-4">
                            @if($pageType === 'plan')
                                🗺️
                            @elseif($pageType === 'guides')
                                📚
                            @else
                                📝
                            @endif
                        </div>
                        <h3 class="font-playfair text-2xl font-bold text-gray-900 mb-2">
                            No {{ ucfirst($pageType) }} Yet
                        </h3>
                        <p class="text-gray-600">Check back soon for new content!</p>
                    </div>
                @endif
            </div>

            <!-- Sidebar -->
            <aside class="lg:col-span-1 space-y-8">
                <!-- Featured Articles -->
                @if($featuredArticles->count() > 0)
                    <div class="bg-white rounded-2xl p-6 shadow-lg sticky top-24" id="featured">
                        <h3 class="font-playfair text-2xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                            <span class="text-java-accent">⭐</span>
                            Featured {{ ucfirst($pageType) }}
                        </h3>
                        
                        <div class="space-y-6">
                            @foreach($featuredArticles as $featured)
                                <a href="{{ route('articles.show', [$featured->id, $featured->slug]) }}" 
                                   class="group block">
                                    <div class="flex gap-4">
                                        @if($featured->featured_image)
                                            <div class="flex-shrink-0 w-24 h-24 rounded-lg overflow-hidden">
                                                <img src="{{ image_url($featured->featured_image, 'article') }}" 
                                                     alt="{{ $featured->title }}" 
                                                     class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                                            </div>
                                        @endif
                                        <div class="flex-1 min-w-0">
                                            <h4 class="font-semibold text-gray-900 group-hover:text-java-primary transition-colors duration-300 line-clamp-2 mb-2">
                                                {{ $featured->title }}
                                            </h4>
                                            <p class="text-sm text-gray-500">
                                                {{ $featured->published_at->diffForHumans() }}
                                            </p>
                                        </div>
                                    </div>
                                </a>
                                
                                @if(!$loop->last)
                                    <div class="border-t border-gray-100"></div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Quick Tips (only for plan page) -->
                @if($pageType === 'plan')
                <div class="bg-linear-to-br from-java-accent to-orange-600 rounded-2xl p-6 text-white shadow-lg scroll-animate opacity-0 translate-y-8" id="tips">
                    <h3 class="font-playfair text-2xl font-bold mb-4">
                        💡 Planning Tips
                    </h3>
                    <ul class="space-y-3">
                        <li class="flex items-start gap-3">
                            <span class="text-2xl flex-shrink-0">✈️</span>
                            <span class="text-sm leading-relaxed">Book flights 2-3 months in advance for best prices</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-2xl flex-shrink-0">🏨</span>
                            <span class="text-sm leading-relaxed">Mix hotels and homestays for authentic experiences</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-2xl flex-shrink-0">🌦️</span>
                            <span class="text-sm leading-relaxed">Dry season (April-October) is best for travel</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-2xl flex-shrink-0">💰</span>
                            <span class="text-sm leading-relaxed">Budget $30-50/day for mid-range travel</span>
                        </li>
                    </ul>
                </div>
                @endif

                <!-- Destinations Filter -->
                @if($destinations->count() > 0)
                    <div class="bg-white rounded-2xl p-6 shadow-lg">
                        <h3 class="font-playfair text-2xl font-bold text-gray-900 mb-4">
                            Filter by Destination
                        </h3>
                        <div class="flex flex-wrap gap-2">
                            @foreach($destinations->take(8) as $destination)
                                <a href="{{ route($pageType === 'articles' ? 'articles.index' : 'articles.' . $pageType, ['destination' => $destination->slug]) }}" 
                                   class="px-4 py-2 bg-gray-100 hover:bg-java-primary hover:text-white text-gray-700 rounded-full text-sm font-medium transition-all duration-300">
                                    {{ $destination->name }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </aside>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-20 bg-linear-to-br from-java-primary via-blue-900 to-java-primary relative overflow-hidden">
    <div class="absolute inset-0 opacity-10">
        <div class="absolute top-0 left-1/4 w-96 h-96 bg-java-accent rounded-full blur-3xl animate-blob"></div>
        <div class="absolute bottom-0 right-1/4 w-96 h-96 bg-blue-500 rounded-full blur-3xl animate-blob animation-delay-2000"></div>
    </div>
    
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="max-w-3xl mx-auto text-center scroll-animate opacity-0 translate-y-8">
            <h2 class="font-playfair text-3xl md:text-5xl font-black text-white mb-6">
                {{ $ctaTitle }}
            </h2>
            <p class="text-xl text-blue-100 mb-10 font-light leading-relaxed">
                {{ $ctaDescription }}
            </p>
            <a href="{{ $ctaLink }}" 
               class="cta-button group inline-flex items-center gap-3 px-10 py-5 bg-java-accent hover:bg-orange-600 text-white rounded-full font-bold text-lg shadow-2xl shadow-java-accent/30 transition-all duration-300 hover:-translate-y-1">
                <span>{{ $ctaButton }}</span>
                <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                </svg>
            </a>
        </div>
    </div>
</section>

<script>
// Scroll animations
document.addEventListener('DOMContentLoaded', function() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('opacity-100', 'translate-y-0');
                entry.target.classList.remove('opacity-0', 'translate-y-8', '-translate-y-8');
            }
        });
    }, observerOptions);

    document.querySelectorAll('.scroll-animate').forEach((el) => observer.observe(el));
});
</script>
@endsection