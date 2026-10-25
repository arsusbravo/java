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
            route('contact'),
            route('privacy'),
            route('affiliate-disclosure'),
        ])->map(fn ($url) => ['loc' => $url, 'lastmod' => null]);

        $regions = Region::ordered()->get()
            ->map(fn ($region) => ['loc' => route('destinations.region', $region->slug), 'lastmod' => $region->updated_at]);

        $destinations = Destination::with(['region:id,slug', 'images'])->orderBy('id')->get()
            ->filter(fn ($destination) => $destination->region)
            ->flatMap(fn ($destination) => array_filter([
                ['loc' => route('destinations.show', [$destination->region->slug, $destination->slug]), 'lastmod' => $destination->updated_at, 'image' => $this->image($destination->featured_image_url)],
                // Hotels pages are only useful where nearby hotels can be found
                $destination->latitude !== null ? ['loc' => route('destinations.hotels', [$destination->region->slug, $destination->slug]), 'lastmod' => null] : null,
            ]));

        $articles = Article::published()->with('images')->orderBy('id')->get()
            ->map(fn ($article) => ['loc' => $article->url(), 'lastmod' => $article->updated_at, 'image' => $this->image($article->featured_image_url)]);

        $restaurants = Restaurant::published()->where('is_active', true)->with('images')->orderBy('id')->get()
            ->map(fn ($restaurant) => ['loc' => $restaurant->url(), 'lastmod' => $restaurant->updated_at, 'image' => $this->image($restaurant->featured_image_url)]);

        $urls = $pages->concat($regions)->concat($destinations)->concat($articles)->concat($restaurants);

        return response()
            ->view('sitemap', compact('urls'))
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    /**
     * A real photo for Google Images, or null for placeholders.
     */
    protected function image(?string $url): ?string
    {
        return $url && ! str_ends_with(parse_url($url, PHP_URL_PATH) ?? '', '.svg') ? $url : null;
    }
}
