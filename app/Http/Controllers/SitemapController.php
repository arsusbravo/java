<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Destination;
use App\Models\Region;
use App\Models\Restaurant;

/**
 * /sitemap.xml: every public page, for Google Search Console.
 */
class SitemapController extends Controller
{
    public function __invoke()
    {
        $pages = collect([
            route('home'),
            route('destinations.index'),
            route('trip-planner'),
            route('articles.index'),
            route('articles.guides'),
            route('articles.plan'),
            route('home.about'),
            route('privacy'),
            route('affiliate-disclosure'),
        ])->map(fn ($url) => ['loc' => $url, 'lastmod' => null]);

        $regions = Region::ordered()->get()
            ->map(fn ($region) => ['loc' => route('destinations.region', $region->slug), 'lastmod' => $region->updated_at]);

        $destinations = Destination::with('region:id,slug')->orderBy('id')->get(['id', 'region_id', 'slug', 'updated_at'])
            ->filter(fn ($destination) => $destination->region)
            ->map(fn ($destination) => ['loc' => route('destinations.show', [$destination->region->slug, $destination->slug]), 'lastmod' => $destination->updated_at]);

        $articles = Article::published()->orderBy('id')->get(['id', 'slug', 'updated_at'])
            ->map(fn ($article) => ['loc' => $article->url(), 'lastmod' => $article->updated_at]);

        $restaurants = Restaurant::published()->where('is_active', true)->orderBy('id')->get(['id', 'slug', 'updated_at'])
            ->map(fn ($restaurant) => ['loc' => $restaurant->url(), 'lastmod' => $restaurant->updated_at]);

        $urls = $pages->concat($regions)->concat($destinations)->concat($articles)->concat($restaurants);

        return response()
            ->view('sitemap', compact('urls'))
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
