<section class="py-24 bg-gray-50" id="articles">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-16 scroll-animate opacity-0">
            <div>
                <span class="inline-block px-4 py-2 bg-java-accent/10 text-java-accent rounded-full text-sm font-semibold mb-4">
                    Travel Insights
                </span>
                <h2 class="font-playfair text-4xl md:text-6xl font-bold text-java-primary mb-4">
                    Latest Articles
                </h2>
                <p class="text-gray-600 text-lg max-w-2xl">
                    Expert guides, hidden gems, and insider tips for your Java adventure
                </p>
            </div>
            <a href="{{ route('articles.index') }}" class="mt-6 md:mt-0 inline-flex items-center gap-2 text-java-primary font-semibold hover:text-java-accent transition-colors">
                <span>View All Articles</span>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                </svg>
            </a>
        </div>

        @if($homeFeaturedArticles->count() > 0)
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Featured Article (First Article) -->
            @php $featuredArticle = $homeFeaturedArticles->first(); @endphp
            <div class="lg:col-span-2 scroll-animate opacity-0">
                <article class="article-card group bg-white rounded-3xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-500">
                    <a href="{{ $featuredArticle->url() }}" class="block">
                        <div class="relative overflow-hidden h-96">
                            <img src="{{ image_url($featuredArticle->featured_image, 'article') }}" 
                                 alt="{{ $featuredArticle->title }}" 
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700">
                            <span class="absolute top-6 left-6 px-4 py-2 bg-java-accent text-white rounded-full text-sm font-semibold uppercase">
                                {{ $featuredArticle->article_type }}
                            </span>
                        </div>
                        <div class="p-8">
                            <div class="flex items-center gap-4 text-sm text-gray-500 mb-4">
                                <span class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    {{ $featuredArticle->published_at->format('F d, Y') }}
                                </span>
                                <span class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    {{ ceil(str_word_count(strip_tags($featuredArticle->content)) / 200) }} min read
                                </span>
                            </div>
                            <h3 class="font-playfair text-3xl font-bold text-java-primary mb-4 group-hover:text-java-accent transition-colors duration-300">
                                {{ $featuredArticle->title }}
                            </h3>
                            <p class="text-gray-600 leading-relaxed mb-6">
                                {{ $featuredArticle->excerpt }}
                            </p>
                            <span class="inline-flex items-center gap-2 text-java-primary font-semibold group-hover:gap-3 transition-all duration-300">
                                <span>Read Full Article</span>
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                                </svg>
                            </span>
                        </div>
                    </a>
                </article>
            </div>

            <!-- Side Articles (Remaining Articles) -->
            <div class="space-y-6">
                @foreach($homeFeaturedArticles->skip(1) as $article)
                <article class="article-card-small group bg-white rounded-2xl overflow-hidden shadow-lg hover:shadow-xl transition-all duration-500 scroll-animate opacity-0">
                    <a href="{{ $article->url() }}" class="flex gap-4 p-6">
                        <div class="w-24 h-24 shrink-0 rounded-xl overflow-hidden">
                            <img src="{{ image_url($article->featured_image, 'article') }}" 
                                 alt="{{ $article->title }}" 
                                 class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                        </div>
                        <div class="flex-1">
                            <span class="inline-block text-xs font-semibold text-java-accent mb-2 uppercase">
                                {{ str_replace('_', ' & ', $article->article_type) }}
                            </span>
                            <h4 class="font-playfair text-lg font-bold text-java-primary mb-2 group-hover:text-java-accent transition-colors">
                                {{ $article->title }}
                            </h4>
                            <p class="text-sm text-gray-500">
                                {{ ceil(str_word_count(strip_tags($article->content)) / 200) }} min read
                            </p>
                        </div>
                    </a>
                </article>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</section>