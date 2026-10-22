<?php

use App\Models\Accommodation;
use App\Models\AffiliateClick;
use App\Models\Destination;
use App\Models\Restaurant;
use App\Models\Review;
use App\Models\User;
use App\Support\Geo;
use Database\Seeders\DatabaseSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    $this->jakarta = Destination::with('region')->where('slug', 'jakarta')->firstOrFail();

    $this->restaurant = Restaurant::create([
        'name' => 'Café Batavia',
        'slug' => 'cafe-batavia',
        'status' => 'published',
        'is_active' => true,
        'cuisine_type' => ['Indonesian', 'European'],
        'price_range' => '$$$$',
        'average_rating' => 4.5,
        'description' => '<h2>Key Info</h2><p><strong>History:</strong> restored in 1993.</p><script>alert(1)</script>',
        'address' => 'Jl. Pintu Besar Utara No.14, Jakarta',
        'phone' => '+62 21 6915531',
        'opening_hours' => ['Monday – Thursday: 9:00 AM – 12:00 AM', 'Friday: 9:00 AM – 1:00 AM'],
        'latitude' => -6.13437296,
        'longitude' => 106.81272995,
        'affiliate_link' => 'https://partner.example/cafe-batavia',
        'meta_description' => 'Colonial dining on Fatahillah Square.',
    ]);
    $this->restaurant->destinations()->attach($this->jakarta);
});

test('a restaurant has its own page with everything a visitor needs', function () {
    $response = $this->get('/restaurants/cafe-batavia')
        ->assertOk()
        ->assertSee('<title>Café Batavia – Indonesian, European restaurant near Jakarta | Java Sunrise</title>', false)
        ->assertSee('<meta name="description" content="Colonial dining on Fatahillah Square.">', false)
        ->assertSee('<h2>Key Info</h2><p><strong>History:</strong> restored in 1993.</p>', false)
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertSee('Jl. Pintu Besar Utara No.14, Jakarta')
        ->assertSee('Friday: 9:00 AM – 1:00 AM')
        ->assertSee('tel:+62216915531', false)
        ->assertSee('maps.google.com/maps?q=-6.13437296,106.81272995', false)
        ->assertSee('Get directions')
        ->assertSee('/go/restaurant/' . $this->restaurant->id . '?from=restaurant', false)
        ->assertSeeInOrder(['Home', $this->jakarta->region->name, 'Jakarta', 'Café Batavia']);

    // Structured data for search engines
    preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $response->getContent(), $json);
    $data = json_decode($json[1], true);
    expect($data['@type'])->toBe('Restaurant')
        ->and($data['servesCuisine'])->toBe(['Indonesian', 'European'])
        ->and($data['priceRange'])->toBe('$$$$')
        ->and($data['geo']['latitude'])->toBe(-6.13437296)
        ->and($data)->not->toHaveKey('aggregateRating');   // no approved reviews yet
});

test('approved reviews are shown and feed the rating', function () {
    Review::create(['reviewable_type' => Restaurant::class, 'reviewable_id' => $this->restaurant->id, 'user_name' => 'Dewi', 'user_email' => 'd@example.com', 'rating' => 5, 'comment' => 'Wonderful oxtail soup.', 'status' => 'approved']);
    Review::create(['reviewable_type' => Restaurant::class, 'reviewable_id' => $this->restaurant->id, 'user_name' => 'Spam', 'user_email' => 's@example.com', 'rating' => 1, 'comment' => 'Buy pills', 'status' => 'pending']);

    $response = $this->get('/restaurants/cafe-batavia')
        ->assertSee('Wonderful oxtail soup.')
        ->assertDontSee('Buy pills');

    preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $response->getContent(), $json);
    expect(json_decode($json[1], true)['aggregateRating']['reviewCount'])->toBe(1);
});

test('only published, active restaurants have a page, and visits are counted', function () {
    $this->get('/restaurants/cafe-batavia')->assertOk();
    expect($this->restaurant->fresh()->views_count)->toBe(1);

    $this->restaurant->update(['status' => 'draft']);
    $this->get('/restaurants/cafe-batavia')->assertNotFound();

    $this->restaurant->update(['status' => 'published', 'is_active' => false]);
    $this->get('/restaurants/cafe-batavia')->assertNotFound();

    $this->get('/restaurants/does-not-exist')->assertNotFound();
});

test('places, hotels and other restaurants nearby are suggested', function () {
    $hotel = Accommodation::create([
        'name' => 'Hotel near the square', 'slug' => 'hotel-near-the-square', 'type' => 'hotel', 'star_rating' => 4,
        'status' => 'published', 'is_active' => true, 'latitude' => -6.1350, 'longitude' => 106.8130,
        'affiliate_link' => 'https://www.agoda.com/partners/partnersearch.aspx?hid=9',
    ]);
    $other = Restaurant::create([
        'name' => 'Kopi Es Tak Kie', 'slug' => 'kopi-es-tak-kie', 'status' => 'published', 'is_active' => true,
        'latitude' => -6.1440, 'longitude' => 106.8130, 'cuisine_type' => ['Coffee'],
    ]);

    $this->get('/restaurants/cafe-batavia')
        ->assertSee('Near Café Batavia')
        ->assertSee('Jakarta')
        ->assertSee('Hotel near the square')
        ->assertSee('/go/hotel/' . $hotel->id . '?from=restaurant', false)
        ->assertSee('More places to eat nearby')
        ->assertSee($other->url(), false);
});

test('the breadcrumb falls back to the nearest place when none is linked', function () {
    $this->restaurant->destinations()->detach();

    // Bundaran-style seeded Jakarta (-6.2088, 106.8456) is the closest seeded place to Kota Tua
    $this->get('/restaurants/cafe-batavia')->assertSeeInOrder(['Home', 'Jakarta', 'Café Batavia']);
});

test('restaurant cards and the admin link to the page', function () {
    $this->get("/destinations/{$this->jakarta->region->slug}/jakarta")
        ->assertSee(route('restaurants.show', 'cafe-batavia'), false)
        ->assertSee('Details');

    $this->actingAs(User::firstOrFail())
        ->get('/admin/restaurants')
        ->assertInertia(fn (Assert $page) => $page->where('records.data.0.publicUrl', route('restaurants.show', 'cafe-batavia')));
});

test('booking clicks from the restaurant page are recorded as such', function () {
    $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (Macintosh) Chrome/130.0 Safari/537.36'])
        ->get('/go/restaurant/' . $this->restaurant->id . '?from=restaurant')
        ->assertRedirect('https://partner.example/cafe-batavia');

    expect(AffiliateClick::sole()->source)->toBe('restaurant');
});
