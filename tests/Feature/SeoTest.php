<?php

use App\Models\Accommodation;
use App\Models\Article;
use App\Models\Destination;
use App\Support\Geo;
use Database\Seeders\DatabaseSeeder;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    $this->place = Destination::with('region')->where('slug', 'yogyakarta')->firstOrFail();
});

/** @return array<int, array<string, mixed>> */
function structuredData(string $html): array
{
    preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $matches);

    return array_map(fn ($json) => json_decode($json, true, flags: JSON_THROW_ON_ERROR), $matches[1]);
}

function tag(string $html, string $pattern): ?string
{
    return preg_match($pattern, $html, $match) ? html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5) : null;
}

test('every public page has its own title, description and canonical link', function () {
    $article = Article::published()->firstOrFail();
    $pages = ['/', '/destinations', "/destinations/{$this->place->region->slug}", "/destinations/{$this->place->region->slug}/yogyakarta",
        '/articles', $article->url(), '/guides', '/plan', '/trip-planner', '/about', '/contact', '/privacy-policy', '/affiliate-disclosure'];

    $titles = [];
    $descriptions = [];
    foreach ($pages as $page) {
        $html = $this->get($page)->assertOk()->getContent();
        $titles[$page] = tag($html, '#<title>(.*?)</title>#');
        $descriptions[$page] = tag($html, '#<meta name="description" content="([^"]*)"#');

        // Long titles typed in the admin are kept as they are; all others carry the site name
        expect(mb_strlen($titles[$page]) > 50 || str_contains($titles[$page], 'Java Sunrise'))->toBeTrue()
            ->and($titles[$page])->not->toContain('&amp;')
            ->and(mb_strlen($descriptions[$page]))->toBeGreaterThan(50)
            ->and(tag($html, '#<link rel="canonical" href="([^"]*)"#'))->toBe(url(parse_url($page, PHP_URL_PATH) ?: '/') === url('/') ? url('/') : url($page))
            ->and(tag($html, '#<meta name="robots" content="([^"]*)"#'))->toStartWith('index, follow');
    }

    expect(array_unique($titles))->toHaveCount(count($pages))
        ->and(array_unique($descriptions))->toHaveCount(count($pages));
});

test('pages get sharing tags with a real photo', function () {
    $html = $this->get("/destinations/{$this->place->region->slug}/yogyakarta")->getContent();

    expect(tag($html, '#<meta property="og:title" content="([^"]*)"#'))->toBe(tag($html, '#<title>(.*?)</title>#'))
        ->and(tag($html, '#<meta property="og:url" content="([^"]*)"#'))->toBe(url("/destinations/{$this->place->region->slug}/yogyakarta"))
        ->and(tag($html, '#<meta property="og:image" content="([^"]*)"#'))->toStartWith('http')->not->toEndWith('.svg')
        ->and(tag($html, '#<meta name="twitter:card" content="([^"]*)"#'))->toBe('summary_large_image');

    $article = Article::published()->firstOrFail();
    expect(tag($this->get($article->url())->getContent(), '#<meta property="og:type" content="([^"]*)"#'))->toBe('article');
});

test('filtered, sorted and dated views point to the plain page and stay out of the index', function (string $query) {
    $base = "/destinations/{$this->place->region->slug}/yogyakarta/hotels";
    $html = $this->get($base.$query)->getContent();

    expect(tag($html, '#<link rel="canonical" href="([^"]*)"#'))->toBe(url($base))
        ->and(tag($html, '#<meta name="robots" content="([^"]*)"#'))->toBe('noindex, follow');
})->with([
    'sort' => ['?sort=price'],
    'budget' => ['?budget=high'],
    'dates' => ['?checkin=2030-01-01&checkout=2030-01-03'],
]);

test('page 2 of a list has its own title and canonical', function () {
    foreach (range(1, 14) as $i) {
        Accommodation::create(['name' => "Stay {$i}", 'slug' => "stay-{$i}", 'type' => 'hotel', 'status' => 'published', 'is_active' => true,
            'latitude' => (float) $this->place->latitude + $i / Geo::KM_PER_DEGREE, 'longitude' => $this->place->longitude]);
    }
    $base = "/destinations/{$this->place->region->slug}/yogyakarta/hotels";
    $html = $this->get($base.'?page=2')->getContent();

    expect(tag($html, '#<title>(.*?)</title>#'))->toBe("Hotels near Yogyakarta, {$this->place->region->name} – Page 2 | Java Sunrise")
        ->and(tag($html, '#<link rel="canonical" href="([^"]*)"#'))->toBe(url($base).'?page=2')
        ->and(tag($html, '#<meta name="robots" content="([^"]*)"#'))->toStartWith('index, follow');
});

test('a trip built in the planner is not indexed', function () {
    $html = $this->get('/trip-planner?start=yogyakarta&days=3')->getContent();

    expect(tag($html, '#<meta name="robots" content="([^"]*)"#'))->toBe('noindex, follow')
        ->and(tag($html, '#<link rel="canonical" href="([^"]*)"#'))->toBe(url('/trip-planner'));
});

test('structured data describes the site, places, articles and breadcrumbs', function () {
    $home = structuredData($this->get('/')->getContent());
    expect(array_column($home, '@type'))->toBe(['WebSite', 'Organization'])
        ->and($home[1]['logo'])->toEndWith('/images/logo-lg.png');

    $place = structuredData($this->get("/destinations/{$this->place->region->slug}/yogyakarta")->getContent());
    expect($place[0]['@type'])->toBe('TouristAttraction')
        ->and($place[0]['name'])->toBe('Yogyakarta')
        ->and($place[0]['geo']['latitude'])->toBe((float) $this->place->latitude)
        ->and($place[1]['@type'])->toBe('BreadcrumbList')
        ->and(array_column($place[1]['itemListElement'], 'name'))->toBe(['Home', 'Destinations', $this->place->region->name, 'Yogyakarta'])
        ->and(array_column($place[1]['itemListElement'], 'position'))->toBe([1, 2, 3, 4]);

    $article = Article::published()->with('author')->firstOrFail();
    $data = structuredData($this->get($article->url())->getContent());
    expect($data[0]['@type'])->toBeIn(['BlogPosting', 'NewsArticle'])
        ->and($data[0]['headline'])->toBe($article->title)
        ->and($data[0]['datePublished'])->toBe($article->published_at->toAtomString())
        ->and($data[0]['publisher']['name'])->toBe('Java Sunrise')
        ->and($data[1]['@type'])->toBe('BreadcrumbList');

    $hotel = Accommodation::create(['name' => 'Hotel Tugu', 'slug' => 'hotel-tugu', 'type' => 'hotel', 'status' => 'published', 'is_active' => true,
        'latitude' => $this->place->latitude, 'longitude' => $this->place->longitude]);
    $types = array_column(structuredData($this->get($hotel->url())->getContent()), '@type');
    expect($types)->toContain('Hotel', 'BreadcrumbList');
});

test('unknown pages get a helpful 404 that is not indexed', function () {
    $this->get('/no-such-page')
        ->assertNotFound()
        ->assertSee("We couldn't find that page", false)
        ->assertSee('<meta name="robots" content="noindex, follow">', false)
        ->assertSee(route('trip-planner'), false);
});

test('the sitemap lists photos and robots.txt keeps private pages out', function () {
    $response = $this->get('/sitemap.xml')->assertOk();
    $xml = simplexml_load_string($response->getContent());
    $xml->registerXPathNamespace('s', 'http://www.sitemaps.org/schemas/sitemap/0.9');
    $xml->registerXPathNamespace('image', 'http://www.google.com/schemas/sitemap-image/1.1');

    expect($xml->xpath('//image:loc'))->not->toBeEmpty()
        ->and(collect($xml->xpath('//image:loc'))->map(fn ($loc) => (string) $loc)->filter(fn ($url) => str_ends_with($url, '.svg')))->toBeEmpty();

    $robots = file_get_contents(public_path('robots.txt'));
    expect($robots)->toContain('Disallow: /admin/', 'Disallow: /go/', 'Disallow: /login', 'Sitemap: https://javasunrise.com/sitemap.xml');
});

test('Search Console verification is added when configured', function () {
    $this->get('/')->assertDontSee('google-site-verification', false);

    config(['services.google.site_verification' => 'abc123']);
    $this->get('/')->assertSee('<meta name="google-site-verification" content="abc123">', false);
});

test('links use https when the site runs on https', function () {
    config(['app.url' => 'https://javasunrise.com']);
    (new App\Providers\AppServiceProvider(app()))->boot();

    expect(route('home'))->toStartWith('https://');
});
