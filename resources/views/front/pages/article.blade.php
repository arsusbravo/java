@extends('front.layout.app')

@section('hero')
<!-- Article Hero Section -->
<section class="hero-parallax relative h-[70vh] lg:h-screen flex items-end overflow-hidden">
    <div class="parallax-bg absolute inset-0">
        @if($article->featured_image)
            <img src="{{ image_url($article->featured_image, 'hero') }}" 
                 alt="{{ $article->title }}" 
                 class="w-full h-full object-cover scale-110">
        @else
            <div class="w-full h-full bg-linear-to-br from-java-primary to-java-accent"></div>
        @endif
    </div>
    <div class="absolute inset-0 bg-linear-to-b from-black/20 via-black/40 to-black/80"></div>
    
    <!-- Animated overlay patterns -->
    <div class="absolute inset-0 opacity-10">
        <div class="absolute top-20 left-10 w-72 h-72 bg-java-accent rounded-full mix-blend-multiply filter blur-3xl animate-blob"></div>
        <div class="absolute bottom-20 right-10 w-72 h-72 bg-orange-400 rounded-full mix-blend-multiply filter blur-3xl animate-blob animation-delay-2000"></div>
    </div>
    
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 relative z-10 pb-16 lg:pb-24">
        <div class="max-w-4xl scroll-animate opacity-0 translate-y-8">
            <!-- Article Type Badge -->
            <div class="mb-6">
                <span class="inline-block px-4 py-2 bg-java-accent/90 backdrop-blur-sm rounded-full text-sm font-bold uppercase tracking-wide text-white">
                    @if($article->article_type === 'itinerary')
                        📍 Itinerary
                    @elseif($article->article_type === 'tips')
                        💡 Travel Tips
                    @elseif($article->article_type === 'guide')
                        📚 Travel Guide
                    @elseif($article->article_type === 'news')
                        📰 Travel News
                    @elseif($article->article_type === 'review')
                        ⭐ Review
                    @else
                        📝 Article
                    @endif
                </span>
            </div>

            <!-- Title -->
            <h1 class="font-playfair text-4xl md:text-5xl lg:text-6xl font-black text-white mb-6 leading-tight">
                {{ $article->title }}
            </h1>

            <!-- Meta Info -->
            <div class="flex flex-wrap items-center gap-6 text-white/90 mb-6">
                <!-- Author -->
                @if($article->author)
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center border-2 border-white/30">
                        <span class="text-white text-lg font-bold">
                            {{ strtoupper(substr($article->author->name, 0, 1)) }}
                        </span>
                    </div>
                    <div>
                        <div class="text-sm text-white/70">Written by</div>
                        <div class="font-semibold">{{ $article->author->name }}</div>
                    </div>
                </div>
                @endif

                <div class="h-8 w-px bg-white/30"></div>

                <!-- Date -->
                <div>
                    <div class="text-sm text-white/70">Published</div>
                    <div class="font-semibold">{{ $article->published_at->format('M d, Y') }}</div>
                </div>

                <div class="h-8 w-px bg-white/30"></div>

                <!-- Reading Time -->
                <div>
                    <div class="text-sm text-white/70">Reading time</div>
                    <div class="font-semibold">{{ rand(5, 12) }} min read</div>
                </div>

                <div class="h-8 w-px bg-white/30"></div>

                <!-- Views -->
                <div>
                    <div class="text-sm text-white/70">Views</div>
                    <div class="font-semibold">{{ number_format($article->views_count) }}</div>
                </div>
            </div>

            <!-- Destinations -->
            @if($article->destinations && $article->destinations->count() > 0)
            <div class="flex flex-wrap gap-2">
                @foreach($article->destinations as $destination)
                <a href="{{ route('destinations.show', [$destination->region->slug, $destination->slug]) }}" 
                   class="px-4 py-2 bg-white/10 backdrop-blur-sm hover:bg-white/20 text-white rounded-full text-sm font-medium transition-all duration-300 border border-white/20">
                    📍 {{ $destination->name }}
                </a>
                @endforeach
            </div>
            @endif
        </div>
    </div>
</section>
@endsection

@section('content')

<!-- Article Content -->
<section class="py-16 lg:py-24 bg-white">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-3 gap-12">
            <!-- Main Content -->
            <article class="lg:col-span-2">
                <div class="prose prose-lg max-w-none">
                    <!-- Excerpt -->
                    @if($article->excerpt)
                    <div class="not-prose mb-10 p-6 bg-gray-50 rounded-2xl border-l-4 border-java-accent">
                        <p class="text-xl text-gray-700 leading-relaxed italic">
                            {{ $article->excerpt }}
                        </p>
                    </div>
                    @endif

                    <!-- Article Content -->
                    <div class="article-content prose prose-lg max-w-none text-gray-700 prose-headings:font-playfair prose-a:text-java-accent prose-img:rounded-xl">
                        {!! rich_text($article->content) !!}
                    </div>

                    <!-- Tags -->
                    @if($article->tags && $article->tags->count() > 0)
                    <div class="not-prose mt-12 pt-8 border-t border-gray-200">
                        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">Tags</h3>
                        <div class="flex flex-wrap gap-2">
                            @foreach($article->tags as $tag)
                            <span class="px-4 py-2 bg-gray-100 hover:bg-java-accent hover:text-white text-gray-700 rounded-full text-sm font-medium transition-all duration-300 cursor-pointer">
                                #{{ $tag->name }}
                            </span>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <!-- Related Accommodations -->
                    @if($article->accommodations && $article->accommodations->count() > 0)
                    <div class="not-prose mt-12 p-8 bg-linear-to-br from-blue-50 to-blue-100 rounded-2xl">
                        <h3 class="font-playfair text-2xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                            🏨 <span>Recommended Accommodations</span>
                        </h3>
                        <div class="grid md:grid-cols-2 gap-4">
                            @foreach($article->accommodations as $accommodation)
                            <div class="bg-white rounded-xl p-4 hover:shadow-lg transition-all duration-300">
                                <h4 class="font-semibold text-gray-900 mb-1">{{ $accommodation->name }}</h4>
                                <p class="text-sm text-gray-600">{{ $accommodation->location }}</p>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <!-- Related Tours -->
                    @if($article->tours && $article->tours->count() > 0)
                    <div class="not-prose mt-8 p-8 bg-linear-to-br from-orange-50 to-orange-100 rounded-2xl">
                        <h3 class="font-playfair text-2xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                            🎫 <span>Recommended Tours</span>
                        </h3>
                        <div class="grid md:grid-cols-2 gap-4">
                            @foreach($article->tours as $tour)
                            <div class="bg-white rounded-xl p-4 hover:shadow-lg transition-all duration-300">
                                <h4 class="font-semibold text-gray-900 mb-1">{{ $tour->name }}</h4>
                                <p class="text-sm text-gray-600">{{ text_excerpt($tour->short_description ?: $tour->description, 120) }}</p>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <!-- Related Restaurants -->
                    @if($article->restaurants && $article->restaurants->count() > 0)
                    <div class="not-prose mt-8 p-8 bg-linear-to-br from-green-50 to-green-100 rounded-2xl">
                        <h3 class="font-playfair text-2xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                            🍽️ <span>Recommended Restaurants</span>
                        </h3>
                        <div class="grid md:grid-cols-2 gap-4">
                            @foreach($article->restaurants as $restaurant)
                            <div class="bg-white rounded-xl p-4 hover:shadow-lg transition-all duration-300">
                                <h4 class="font-semibold text-gray-900 mb-1">{{ $restaurant->name }}</h4>
                                <p class="text-sm text-gray-600">{{ implode(', ', $restaurant->cuisine_type ?? []) }}</p>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <!-- Share Section -->
                    <div class="not-prose mt-12 pt-8 border-t border-gray-200">
                        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">Share this article</h3>
                        <div class="flex gap-3">
                            <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(route('articles.show', [$article->id, $article->slug])) }}" 
                               target="_blank"
                               class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-full font-semibold transition-all duration-300 flex items-center gap-2">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                </svg>
                                Facebook
                            </a>
                            <a href="https://twitter.com/intent/tweet?url={{ urlencode(route('articles.show', [$article->id, $article->slug])) }}&text={{ urlencode($article->title) }}" 
                               target="_blank"
                               class="px-6 py-3 bg-sky-500 hover:bg-sky-600 text-white rounded-full font-semibold transition-all duration-300 flex items-center gap-2">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                                </svg>
                                Twitter
                            </a>
                            <a href="https://wa.me/?text={{ urlencode($article->title . ' - ' . route('articles.show', [$article->id, $article->slug])) }}" 
                               target="_blank"
                               class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white rounded-full font-semibold transition-all duration-300 flex items-center gap-2">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                                </svg>
                                WhatsApp
                            </a>
                        </div>
                    </div>
                </div>
            </article>

            <!-- Sidebar -->
            <aside class="lg:col-span-1">
                <div class="sticky top-24 space-y-8">
                    <!-- Latest Articles -->
                    @if($latestArticles->count() > 0)
                    <div class="bg-white rounded-2xl p-6 shadow-lg">
                        <h3 class="font-playfair text-2xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                            <span class="text-java-accent">🔥</span>
                            Latest Articles
                        </h3>
                        
                        <div class="space-y-6">
                            @foreach($latestArticles as $latest)
                            <a href="{{ route('articles.show', [$latest->id, $latest->slug]) }}" 
                               class="group block">
                                <div class="flex gap-4">
                                    @if($latest->featured_image)
                                    <div class="flex-shrink-0 w-20 h-20 rounded-lg overflow-hidden">
                                        <img src="{{ image_url($latest->featured_image, 'article') }}" 
                                             alt="{{ $latest->title }}" 
                                             class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                                    </div>
                                    @endif
                                    <div class="flex-1 min-w-0">
                                        <h4 class="font-semibold text-gray-900 group-hover:text-java-primary transition-colors duration-300 line-clamp-2 text-sm mb-1">
                                            {{ $latest->title }}
                                        </h4>
                                        <p class="text-xs text-gray-500">
                                            {{ $latest->published_at->diffForHumans() }}
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

                    <!-- Newsletter CTA -->
                    <div class="bg-linear-to-br from-java-accent to-orange-600 rounded-2xl p-6 text-white shadow-lg">
                        <h3 class="font-playfair text-2xl font-bold mb-3">
                            📬 Stay Updated
                        </h3>
                        <p class="text-sm mb-4 text-white/90">
                            Get the latest travel tips and destination guides delivered to your inbox
                        </p>
                        <a href="{{ route('home') }}#newsletter" 
                           class="block w-full text-center px-6 py-3 bg-white text-java-accent rounded-full font-bold hover:bg-gray-100 transition-all duration-300">
                            Subscribe Now
                        </a>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</section>

<!-- Related Articles -->
@if($relatedArticles->count() > 0)
<section class="py-16 lg:py-24 bg-gray-50">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="font-playfair text-3xl md:text-4xl lg:text-5xl font-black text-gray-900 mb-4">
                You Might Also Like
            </h2>
            <p class="text-xl text-gray-600">
                More stories about Java Island
            </p>
        </div>

        <div class="grid md:grid-cols-3 gap-8">
            @foreach($relatedArticles as $related)
            <article class="group bg-white rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-500">
                <!-- Image -->
                <div class="relative overflow-hidden aspect-[4/3]">
                    @if($related->featured_image)
                        <img src="{{ image_url($related->featured_image, 'article') }}" 
                             alt="{{ $related->title }}" 
                             class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                    @else
                        <div class="w-full h-full bg-linear-to-br from-java-primary to-java-accent"></div>
                    @endif
                    
                    <div class="absolute inset-0 bg-linear-to-t from-black/60 via-black/20 to-transparent"></div>
                    
                    <!-- Article Type Badge -->
                    <div class="absolute top-4 left-4">
                        <span class="bg-java-accent text-white px-3 py-1 rounded-full text-xs font-bold uppercase">
                            @if($related->article_type === 'itinerary')
                                📍 Itinerary
                            @elseif($related->article_type === 'tips')
                                💡 Tips
                            @elseif($related->article_type === 'guide')
                                📚 Guide
                            @else
                                📝 Article
                            @endif
                        </span>
                    </div>
                </div>

                <!-- Content -->
                <div class="p-6">
                    <h3 class="font-playfair text-xl font-bold text-gray-900 mb-3 group-hover:text-java-primary transition-colors duration-300 line-clamp-2">
                        {{ $related->title }}
                    </h3>

                    <p class="text-gray-600 leading-relaxed mb-4 line-clamp-2 text-sm">
                        {{ $related->excerpt }}
                    </p>

                    <a href="{{ route('articles.show', [$related->id, $related->slug]) }}" 
                       class="inline-flex items-center gap-2 text-java-primary font-semibold hover:gap-3 transition-all duration-300">
                        Read More
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            </article>
            @endforeach
        </div>
    </div>
</section>
@endif

<style>
/* Article Content Styling */
.article-content h2 {
    @apply font-playfair text-3xl font-bold text-gray-900 mt-12 mb-6;
}

.article-content h3 {
    @apply font-playfair text-2xl font-bold text-gray-900 mt-10 mb-4;
}

.article-content h4 {
    @apply font-playfair text-xl font-bold text-gray-900 mt-8 mb-3;
}

.article-content p {
    @apply mb-6 leading-relaxed;
}

.article-content ul, .article-content ol {
    @apply mb-6 ml-6;
}

.article-content ul {
    @apply list-disc;
}

.article-content ol {
    @apply list-decimal;
}

.article-content li {
    @apply mb-2;
}

.article-content a {
    @apply text-java-accent hover:text-java-primary underline font-semibold;
}

.article-content img {
    @apply rounded-2xl my-8 w-full;
}

.article-content blockquote {
    @apply border-l-4 border-java-accent bg-gray-50 p-6 my-8 italic rounded-r-xl;
}

.article-content code {
    @apply bg-gray-100 px-2 py-1 rounded text-sm font-mono;
}

.article-content pre {
    @apply bg-gray-900 text-gray-100 p-6 rounded-xl my-8 overflow-x-auto;
}

.article-content pre code {
    @apply bg-transparent p-0;
}
</style>

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
                entry.target.classList.remove('opacity-0', 'translate-y-8');
            }
        });
    }, observerOptions);

    document.querySelectorAll('.scroll-animate').forEach((el) => observer.observe(el));
});
</script>
@endsection