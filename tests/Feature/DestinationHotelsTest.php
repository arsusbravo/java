<?php

use App\Http\Controllers\DestinationController;
use App\Models\Accommodation;
use App\Models\Destination;
use App\Support\Geo;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    $this->place = Destination::with('region')->where('slug', 'yogyakarta')->firstOrFail();
    $this->url = "/destinations/{$this->place->region->slug}/yogyakarta/hotels";
});

function stayNear(Destination $place, float $km, array $attributes = []): Accommodation
{
    return Accommodation::create([
        'name' => "Stay at {$km} km",
        'slug' => 'stay-'.Str::random(8),
        'type' => 'hotel',
        'status' => 'published',
        'is_active' => true,
        'latitude' => (float) $place->latitude + $km / Geo::KM_PER_DEGREE,
        'longitude' => (float) $place->longitude,
        'affiliate_link' => 'https://www.agoda.com/partners/partnersearch.aspx?hid=1',
        ...$attributes,
    ]);
}

function hotelNames(string $url): array
{
    return test()->get($url)->assertOk()->viewData('hotels')->pluck('name')->all();
}

test('the destination page links to all hotels near it', function () {
    stayNear($this->place, 1);

    $this->get("/destinations/{$this->place->region->slug}/yogyakarta")
        ->assertSee(route('destinations.hotels', [$this->place->region->slug, 'yogyakarta']), false)
        ->assertSee('All hotels near Yogyakarta')
        ->assertDontSee('section=accommodations', false);
});

test('hotels are listed closest first, with a tracked booking link and a disclosure', function () {
    $far = stayNear($this->place, 8, ['star_rating' => 5]);
    stayNear($this->place, 0.5, ['star_rating' => 2]);
    stayNear($this->place, 3);

    expect(hotelNames($this->url))->toBe(['Stay at 0.5 km', 'Stay at 3 km', 'Stay at 8 km']);

    $this->get($this->url)
        ->assertSee('<title>Hotels near Yogyakarta, '.$this->place->region->name.' | Java Sunrise</title>', false)
        ->assertSee('Hotels near Yogyakarta')
        ->assertSee('The 3 closest places to stay, all within 8.0 km')
        ->assertSee('500 m away')
        ->assertSee('/go/hotel/'.$far->id.'?from=destination', false)
        ->assertSee('We may earn a commission');
});

test('hotels can be sorted by stars', function () {
    stayNear($this->place, 0.5, ['star_rating' => 2]);
    stayNear($this->place, 8, ['star_rating' => 5]);
    stayNear($this->place, 3);

    expect(hotelNames($this->url.'?sort=stars'))->toBe(['Stay at 8 km', 'Stay at 0.5 km', 'Stay at 3 km'])
        ->and(hotelNames($this->url.'?sort=nonsense'))->toBe(['Stay at 0.5 km', 'Stay at 3 km', 'Stay at 8 km']);
});

test('at most 50 hotels are shown, the closest ones, 12 per page', function () {
    foreach (range(1, 55) as $i) {
        stayNear($this->place, $i / 10);
    }

    $firstPage = $this->get($this->url)->assertOk();
    $hotels = $firstPage->viewData('hotels');
    expect($hotels->total())->toBe(DestinationController::HOTELS_PAGE_LIMIT)
        ->and($hotels->pluck('name')->all())->toHaveCount(DestinationController::HOTELS_PER_PAGE)
        ->and($hotels->first()->name)->toBe('Stay at 0.1 km');

    $firstPage->assertSee('Showing 1–12 of 50')
        ->assertSee('The 50 closest places to stay, all within 5.0 km')
        ->assertSee($this->url.'?page=2', false)
        ->assertSee($this->url.'?page=5', false)
        ->assertDontSee($this->url.'?page=6', false);

    expect(hotelNames($this->url.'?page=2')[0])->toBe('Stay at 1.3 km')
        ->and(hotelNames($this->url.'?page=5'))->toBe(['Stay at 4.9 km', 'Stay at 5 km'])
        ->and(collect(range(1, 5))->flatMap(fn ($page) => hotelNames($this->url.'?page='.$page)))->not->toContain('Stay at 5.1 km');

    $this->get($this->url.'?page=6')->assertNotFound();
});

test('sorting keeps working across pages', function () {
    foreach (range(1, 14) as $i) {
        stayNear($this->place, $i, ['star_rating' => $i <= 2 ? 5 : 3]);
    }

    $this->get($this->url.'?sort=stars')->assertSee($this->url.'?sort=stars&amp;page=2', false);
    expect(hotelNames($this->url.'?sort=stars&page=2'))->toBe(['Stay at 13 km', 'Stay at 14 km']);
});

test('hotels linked to the place are included, drafts and inactive ones are not', function () {
    $linked = stayNear($this->place, 90, ['name' => 'Linked far away']);
    $linked->destinations()->attach($this->place);
    stayNear($this->place, 1, ['name' => 'Draft hotel', 'status' => 'draft']);
    stayNear($this->place, 1, ['name' => 'Closed hotel', 'is_active' => false]);

    expect(hotelNames($this->url))->toBe(['Linked far away']);
});

test('a place without hotels says so, and unknown places are not found', function () {
    $this->get($this->url)->assertOk()->assertSee("We don't have partner hotels near Yogyakarta yet.", false);

    $this->get("/destinations/{$this->place->region->slug}/nowhere/hotels")->assertNotFound();
    $this->get('/destinations/wrong-region/yogyakarta/hotels')->assertNotFound();
});

test('hotels pages are in the sitemap', function () {
    $this->get('/sitemap.xml')->assertSee(route('destinations.hotels', [$this->place->region->slug, 'yogyakarta']), false);
});

test('hotels can be grouped by budget', function (string $budget, array $expected) {
    stayNear($this->place, 1, ['name' => 'Homestay', 'type' => 'guesthouse', 'star_rating' => null]);
    stayNear($this->place, 2, ['name' => 'Two star', 'star_rating' => 2]);
    stayNear($this->place, 3, ['name' => 'Three star', 'star_rating' => 3]);
    stayNear($this->place, 4, ['name' => 'Four star', 'star_rating' => 4]);
    stayNear($this->place, 5, ['name' => 'Five star', 'star_rating' => 5]);

    expect(hotelNames($this->url.'?budget='.$budget))->toBe($expected);
})->with([
    'low' => ['low', ['Homestay', 'Two star']],
    'medium' => ['medium', ['Three star']],
    'high' => ['high', ['Four star', 'Five star']],
    'unknown means all' => ['caviar', ['Homestay', 'Two star', 'Three star', 'Four star', 'Five star']],
]);

test('a budget looks further away to find the 50 closest in that group', function () {
    foreach (range(1, 20) as $i) {
        stayNear($this->place, $i / 10, ['star_rating' => 2]);
    }
    $luxury = stayNear($this->place, 30, ['name' => 'Far resort', 'star_rating' => 5]);

    expect(hotelNames($this->url.'?budget=high'))->toBe(['Far resort']);

    $this->get($this->url.'?budget=high')->assertSee('The 1 closest high-end places to stay');
});

test('linked hotels follow the budget too', function () {
    stayNear($this->place, 90, ['name' => 'Linked budget inn', 'star_rating' => 1])->destinations()->attach($this->place);

    expect(hotelNames($this->url.'?budget=low'))->toBe(['Linked budget inn'])
        ->and(hotelNames($this->url.'?budget=high'))->toBe([]);
});

test('budget, sort and page work together in the links', function () {
    foreach (range(1, 14) as $i) {
        stayNear($this->place, $i, ['star_rating' => 4]);
    }

    $this->get($this->url.'?budget=high&sort=stars')
        ->assertSee($this->url.'?budget=high&amp;sort=stars&amp;page=2', false)
        ->assertSee($this->url.'?sort=stars', false)            // "All budgets" keeps the sort
        ->assertSee($this->url.'?budget=high"', false);          // "Closest first" keeps the budget
});

test('an empty budget group says so and offers all budgets', function () {
    stayNear($this->place, 1, ['star_rating' => 2]);

    $this->get($this->url.'?budget=high')
        ->assertOk()
        ->assertSee('No high-end hotels near Yogyakarta yet.')
        ->assertSee('Show all budgets');
});
