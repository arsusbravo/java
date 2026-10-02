<?php

namespace App\Http\Controllers;

use App\Http\Services\ArticleService;
use App\Models\Article;
use App\Models\Destination;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    protected ArticleService $service;

    public function __construct(ArticleService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of all articles.
     */
    public function index(Request $request)
    {
        $articles = $this->service->getPublishedArticles($request->only(['destination', 'search']));

        // Get featured articles for sidebar
        $featuredArticles = Article::published()
            ->where('is_featured', true)
            ->orderBy('published_at', 'desc')
            ->take(3)
            ->get();

        // Get all destinations for filter
        $destinations = Destination::orderBy('name')->get();

        // Page-specific variables
        $pageData = [
            'pageType' => 'articles',
            'heroImage' => 'articles-hero.jpg',
            'heroBadge' => '📝 Travel Stories & Insights',
            'heroTitle' => 'Discover Amazing<br><span class="text-java-accent">Travel Stories</span>',
            'heroDescription' => 'Read inspiring travel experiences, cultural insights, and adventure stories from across Java Island',
            'ctaPrimary' => 'Browse Articles',
            'ctaSecondary' => 'Featured Stories',
            'ctaTitle' => 'Share Your Java Story',
            'ctaDescription' => 'Have an amazing Java experience to share? We\'d love to feature your story on our platform',
            'ctaButton' => 'Submit Your Story',
            'ctaLink' => route('home.about') . '#contact',
        ];

        return view('front.pages.articles', array_merge(compact('articles', 'featuredArticles', 'destinations'), $pageData));
    }

    public function plan(Request $request)
    {
        $articles = $this->service->getPublishedArticles([
            ...$request->only(['destination', 'search']),
            'type' => ['itinerary', 'tips'],
        ]);

        // Get featured articles for sidebar
        $featuredArticles = Article::published()
            ->where('is_featured', true)
            ->whereIn('article_type', ['itinerary', 'tips'])
            ->orderBy('published_at', 'desc')
            ->take(3)
            ->get();

        // Get all destinations for filter
        $destinations = Destination::orderBy('name')->get();

        // Page-specific variables
        $pageData = [
            'pageType' => 'plan',
            'heroImage' => 'plan-trip-hero.png',
            'heroBadge' => '🗺️ Travel Planning Made Easy',
            'heroTitle' => 'Plan Your Perfect<br><span class="text-java-accent">Java Adventure</span>',
            'heroDescription' => 'Expert itineraries, insider tips, and practical guides to help you create an unforgettable journey through Java Island',
            'ctaPrimary' => 'Browse Guides',
            'ctaSecondary' => 'Quick Tips',
            'ctaTitle' => 'Need a Custom Itinerary?',
            'ctaDescription' => 'Let us help you plan the perfect Java adventure tailored to your interests and schedule',
            'ctaButton' => 'Contact Us',
            'ctaLink' => route('home.about') . '#contact',
        ];

        return view('front.pages.articles', array_merge(compact('articles', 'featuredArticles', 'destinations'), $pageData));
    }

    public function guides(Request $request)
    {
        $articles = $this->service->getPublishedArticles([
            ...$request->only(['destination', 'search']),
            'type' => ['guide', 'news'],
        ]);

        // Get featured articles for sidebar
        $featuredArticles = Article::published()
            ->where('is_featured', true)
            ->whereIn('article_type', ['guide', 'news'])
            ->orderBy('published_at', 'desc')
            ->take(3)
            ->get();

        // Get all destinations for filter
        $destinations = Destination::orderBy('name')->get();

        // Page-specific variables
        $pageData = [
            'pageType' => 'guides',
            'heroImage' => 'guides-hero.jpg',
            'heroBadge' => '📚 Expert Travel Guides',
            'heroTitle' => 'Your Complete<br><span class="text-java-accent">Java Travel Guide</span>',
            'heroDescription' => 'Comprehensive guides and the latest travel news to help you explore Java Island like a local',
            'ctaPrimary' => 'Explore Guides',
            'ctaSecondary' => 'Featured Guides',
            'ctaTitle' => 'Want More Detailed Guides?',
            'ctaDescription' => 'Subscribe to our newsletter for in-depth travel guides, exclusive tips, and the latest Java travel news',
            'ctaButton' => 'Subscribe Now',
            'ctaLink' => route('home') . '#newsletter',
        ];

        return view('front.pages.articles', array_merge(compact('articles', 'featuredArticles', 'destinations'), $pageData));
    }

    /**
     * Display the specified article.
     */
    public function show($id, $slug)
    {
        $article = Article::where('id', $id)
            ->where('slug', $slug)
            ->published()
            ->with(['author', 'destinations', 'accommodations', 'tours', 'restaurants', 'tags'])
            ->firstOrFail();

        // Increment view count
        $article->increment('views_count');

        // Get related articles (same type or same destinations)
        $relatedArticles = Article::published()
            ->where('id', '!=', $article->id)
            ->where(function($query) use ($article) {
                $query->where('article_type', $article->article_type)
                    ->orWhereHas('destinations', function($q) use ($article) {
                        $q->whereIn('destinations.id', $article->destinations->pluck('id'));
                    });
            })
            ->orderBy('published_at', 'desc')
            ->take(3)
            ->get();

        // Get latest articles for sidebar
        $latestArticles = Article::published()
            ->where('id', '!=', $article->id)
            ->orderBy('published_at', 'desc')
            ->take(5)
            ->get();

        return view('front.pages.article', compact('article', 'relatedArticles', 'latestArticles'));
    }
}