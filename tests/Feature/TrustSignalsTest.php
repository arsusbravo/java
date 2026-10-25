<?php

use App\Models\Accommodation;
use App\Models\Article;
use App\Models\Destination;
use App\Models\Restaurant;
use App\Models\Review;
use Database\Seeders\DatabaseSeeder;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

test('the site ships without made-up reviews', function () {
    expect(Review::count())->toBe(0);

    $this->get('/')
        ->assertOk()
        ->assertDontSee('What Travelers Say')
        ->assertDontSee('Verified Reviews');
});

test('the sample review cleanup removes only the seeded @example.com reviews', function () {
    $restaurant = Restaurant::create(['name' => 'Warung', 'slug' => 'warung']);
    $review = fn (string $email) => Review::create(['reviewable_type' => Restaurant::class, 'reviewable_id' => $restaurant->id, 'user_name' => 'X', 'user_email' => $email, 'rating' => 5, 'status' => 'approved']);
    $review('sarah.m@example.com');
    $real = $review('traveller@gmail.com');

    (require database_path('migrations/2026_10_24_000000_remove_sample_reviews.php'))->up();

    expect(Review::pluck('id')->all())->toBe([$real->id]);
});

test('home page stats show real numbers and leave out zeros', function () {
    $this->get('/')
        ->assertSeeInOrder([number_format(Destination::count()), 'Java Destinations'])
        ->assertDontSee('Curated Tours')
        ->assertDontSee('>0+<', false)
        ->assertDontSee('Partner Hotels');   // none seeded

    Accommodation::create(['name' => 'Hotel', 'slug' => 'hotel', 'type' => 'hotel', 'status' => 'published']);
    $this->get('/')->assertSeeInOrder(['1', 'Partner Hotels']);
});

test('the plan page shows only real counts', function () {
    $this->get('/plan')
        ->assertOk()
        ->assertDontSee('50+')
        ->assertDontSee('20+')
        ->assertDontSee('100+')
        ->assertSeeInOrder([(string) Destination::count(), 'Destinations']);
});

test('the footer has real links, the current year and no placeholder phone', function () {
    config(['site.social.Instagram' => 'https://instagram.com/javasunrise']);

    $this->get('/')
        ->assertDontSee('+62 123 456 789')
        ->assertDontSee('Terms of Service')
        ->assertDontSee('href="#"', false)
        ->assertSee('&copy; '.now()->year, false)
        ->assertSee(route('privacy'), false)
        ->assertSee(route('affiliate-disclosure'), false)
        ->assertSee(route('sitemap'), false)
        ->assertSee('href="https://instagram.com/javasunrise"', false)
        ->assertDontSee('aria-label="Facebook"', false);

    config(['site.phone' => '+62 812 3456 7890']);
    $this->get('/')->assertSee('tel:+6281234567890', false);
});

test('the privacy policy and affiliate disclosure describe what the site really does', function () {
    $this->get('/privacy-policy')
        ->assertOk()
        ->assertSee('Privacy Policy')
        ->assertSee(config('session.cookie'))
        ->assertSee('shortened IP address')
        ->assertSee('Google Fonts')
        ->assertSee('Agoda')
        ->assertSee(route('contact'), false)
        ->assertDontSee('mailto:', false);

    $this->get('/affiliate-disclosure')
        ->assertOk()
        ->assertSee('Agoda')
        ->assertSee('commission')
        ->assertSee("don't pay more", false);
});

test('booking sections carry a short disclosure', function () {
    $place = Destination::with('region')->where('slug', 'yogyakarta')->firstOrFail();
    Accommodation::create([
        'name' => 'Hotel Tugu', 'slug' => 'hotel-tugu', 'type' => 'hotel', 'status' => 'published', 'is_active' => true,
        'latitude' => $place->latitude, 'longitude' => $place->longitude,
        'affiliate_link' => 'https://www.agoda.com/partners/partnersearch.aspx?hid=1',
    ])->destinations()->attach($place);

    $this->get("/destinations/{$place->region->slug}/yogyakarta")
        ->assertSee('We may earn a commission when you book through these links');
});

test('the sitemap lists every public page and nothing private', function () {
    $draft = Article::create(['title' => 'Secret draft', 'slug' => 'secret-draft', 'content' => 'x', 'status' => 'draft', 'article_type' => 'guide']);
    $published = Article::published()->firstOrFail();
    Restaurant::create(['name' => 'Open', 'slug' => 'open-warung', 'status' => 'published', 'is_active' => true]);
    Restaurant::create(['name' => 'Closed', 'slug' => 'closed-warung', 'status' => 'published', 'is_active' => false]);
    $place = Destination::with('region')->firstOrFail();

    $response = $this->get('/sitemap.xml')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

    $xml = simplexml_load_string($response->getContent());
    $urls = collect($xml->xpath("//*[local-name()='url']/*[local-name()='loc']"))->map(fn ($loc) => (string) $loc);

    expect($urls)->toContain(route('home'), route('trip-planner'), route('privacy'), route('affiliate-disclosure'))
        ->toContain(route('destinations.region', $place->region->slug))
        ->toContain(route('destinations.show', [$place->region->slug, $place->slug]))
        ->toContain($published->url(), route('restaurants.show', 'open-warung'))
        ->not->toContain($draft->url(), route('restaurants.show', 'closed-warung'))
        ->and($urls->filter(fn ($url) => str_contains($url, '/admin') || str_contains($url, '/go/')))->toBeEmpty()
        ->and($urls->duplicates())->toBeEmpty();

    expect(file_get_contents(public_path('robots.txt')))->toContain('Sitemap: https://javasunrise.com/sitemap.xml');
});

test('there is no newsletter form that collects nothing', function () {
    $this->get('/')->assertOk()->assertDontSee('Subscribe');
    $this->get('/guides')->assertOk()->assertDontSee('#newsletter', false)->assertSee(route('trip-planner'), false);

    $article = Article::published()->firstOrFail();
    $this->get($article->url())->assertOk()->assertDontSee('#newsletter', false)->assertSee(route('trip-planner'), false);
});
