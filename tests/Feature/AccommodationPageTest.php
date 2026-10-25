<?php

use App\Models\Accommodation;
use App\Models\AffiliateClick;
use App\Models\Destination;
use App\Models\Image;
use App\Models\Restaurant;
use App\Models\User;
use App\Support\Geo;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    $this->place = Destination::with('region')->where('slug', 'yogyakarta')->firstOrFail();
    $this->hotel = Accommodation::create([
        'name' => 'Hotel Tugu Jogja', 'slug' => 'hotel-tugu-jogja', 'type' => 'hotel', 'star_rating' => 4,
        'status' => 'published', 'is_active' => true, 'external_id' => '555', 'affiliate_network_id' => App\Models\AffiliateNetwork::where('name', 'Agoda')->value('id'),
        'description' => 'A colonial-era hotel. It has a pool. Rooms are large. Breakfast is served daily. The station is close.',
        'address' => 'Jl. Pangeran Mangkubumi 1, Yogyakarta',
        'latitude' => (float) $this->place->latitude + 0.6 / Geo::KM_PER_DEGREE, 'longitude' => (float) $this->place->longitude,
        'affiliate_link' => 'https://www.agoda.com/partners/partnersearch.aspx?pcs=1&cid={affiliate_id}&hid=555',
        'price_from' => 48, 'currency' => 'EUR', 'prices_checked_at' => now(),
        'review_score' => 8.7, 'review_count' => 512, 'free_wifi' => true,
        'check_in_time' => '14:00', 'check_out_time' => '12:00', 'room_count' => 120, 'year_opened' => 1998, 'chain_name' => 'Tugu Hotels',
    ]);
    foreach (range(1, 7) as $i) {
        Image::create(['imageable_type' => Accommodation::class, 'imageable_id' => $this->hotel->id, 'path' => "https://pix.example/{$i}.jpg", 'order' => $i]);
    }
});

test('a hotel has its own page with photos, facts, rating and location', function () {
    $response = $this->get('/hotels/hotel-tugu-jogja')
        ->assertOk()
        ->assertSee('<title>Hotel Tugu Jogja – 4-star hotel near Yogyakarta | Java Sunrise</title>', false)
        ->assertSee('<meta name="description" content="A colonial-era hotel.', false)
        ->assertSeeInOrder(['Home', $this->place->region->name, 'Yogyakarta', 'Hotels', 'Hotel Tugu Jogja'])
        ->assertSee('Jl. Pangeran Mangkubumi 1, Yogyakarta')
        ->assertSee('600 m from Yogyakarta')
        ->assertSeeInOrder(['8.7', 'Excellent', '512 reviews on Agoda'])
        ->assertSeeInOrder(['Check-in', 'From 14:00', 'Check-out', 'Until 12:00', 'Rooms', '120', 'Opened', '1998', 'Part of', 'Tugu Hotels'])
        ->assertSee('https://pix.example/1.jpg', false)
        ->assertSee('https://pix.example/7.jpg', false)
        ->assertSee('maps.google.com/maps?q=', false)
        ->assertSee('From')
        ->assertSee('€48')
        ->assertSee('View deals on Agoda')
        ->assertSee('/go/hotel/'.$this->hotel->id.'?from=hotel', false)
        ->assertSee('We may earn a commission');

    // Long feed text is split into paragraphs
    $response->assertSee('<p>A colonial-era hotel. It has a pool. Rooms are large.</p>', false);

    $data = ldOfType($response->getContent(), 'Hotel');
    expect($data['@type'])->toBe('Hotel')
        ->and($data['starRating']['ratingValue'])->toBe(4)
        ->and($data['checkinTime'])->toBe('14:00')
        ->and($data['numberOfRooms'])->toBe(120)
        ->and($data)->not->toHaveKey('aggregateRating');

    expect($this->hotel->fresh()->views_count)->toBe(1);
});

test('with dates the page shows the live price and books those nights', function () {
    config(['services.agoda.url' => 'https://agoda.test/lt_v1', 'services.agoda.api_key' => '1:secret']);
    Http::fake(['agoda.test/*' => Http::response(['results' => [['hotelId' => 555, 'currency' => 'EUR', 'dailyRate' => 61.5, 'crossedOutRate' => 80, 'discountPercentage' => 0]]])]);
    $query = ['checkin' => today()->addDays(10)->toDateString(), 'checkout' => today()->addDays(13)->toDateString(), 'adults' => 2];

    $this->get('/hotels/hotel-tugu-jogja?'.http_build_query($query))
        ->assertSee('€62')
        ->assertSee('€80')
        ->assertSee('−23%')
        ->assertSee('About €185 for 3 nights')
        ->assertSee('Book 3 nights on Agoda')
        ->assertSee('/go/hotel/'.$this->hotel->id.'?from=hotel&amp;checkin='.$query['checkin'].'&amp;checkout='.$query['checkout'], false);

    Http::assertSent(fn (Request $request) => $request['criteria']['hotelId'] === [555]);
});

test('no rooms and bad dates are explained', function () {
    config(['services.agoda.url' => 'https://agoda.test/lt_v1', 'services.agoda.api_key' => '1:secret']);
    Http::fake(['agoda.test/*' => Http::response(['error' => ['id' => 911, 'message' => 'No search result']])]);

    $this->get('/hotels/hotel-tugu-jogja?checkin='.today()->addDays(5)->toDateString().'&checkout='.today()->addDays(6)->toDateString())
        ->assertSee('No rooms found for your dates.')
        ->assertSee('See availability on Agoda');

    $this->get('/hotels/hotel-tugu-jogja?checkin=2020-01-01&checkout=2020-01-02')
        ->assertSee('Please pick a check-in date from today');
});

test('restaurants and other hotels nearby are suggested', function () {
    $other = Accommodation::create([
        'name' => 'Guesthouse next door', 'slug' => 'guesthouse-next-door', 'type' => 'guesthouse', 'status' => 'published', 'is_active' => true,
        'latitude' => $this->hotel->latitude, 'longitude' => $this->hotel->longitude,
    ]);
    Restaurant::create(['name' => 'Gudeg Yu Djum', 'slug' => 'gudeg-yu-djum', 'status' => 'published', 'is_active' => true, 'latitude' => $this->hotel->latitude, 'longitude' => $this->hotel->longitude]);

    $this->get('/hotels/hotel-tugu-jogja')
        ->assertSee('Places to see near Hotel Tugu Jogja')
        ->assertSee('Places to eat nearby')
        ->assertSee('Gudeg Yu Djum')
        ->assertSee('Other places to stay nearby')
        ->assertSee($other->url(), false)
        ->assertSee(route('destinations.hotels', [$this->place->region->slug, 'yogyakarta']), false);
});

test('only published, active hotels have a page', function () {
    $this->hotel->update(['status' => 'draft']);
    $this->get('/hotels/hotel-tugu-jogja')->assertNotFound();

    $this->hotel->update(['status' => 'published', 'is_active' => false]);
    $this->get('/hotels/hotel-tugu-jogja')->assertNotFound();

    $this->get('/hotels/nope')->assertNotFound();
});

test('hotel cards, the planner and the admin link to the page', function () {
    $this->get("/destinations/{$this->place->region->slug}/yogyakarta/hotels")
        ->assertSee($this->hotel->url(), false)
        ->assertSee('Details');

    $this->actingAs(User::firstOrFail())
        ->get('/admin/accommodations?search=Tugu')
        ->assertInertia(fn (Assert $page) => $page->where('records.data.0.publicUrl', $this->hotel->url()));
});

test('booking from the hotel page is recorded as such', function () {
    $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (Macintosh) Chrome/130.0 Safari/537.36'])
        ->get('/go/hotel/'.$this->hotel->id.'?from=hotel')
        ->assertRedirect();

    expect(AffiliateClick::sole()->source)->toBe('hotel');
});

test('photos open in a lightbox, with every photo in it', function () {
    $content = $this->get('/hotels/hotel-tugu-jogja')
        ->assertSee('Show all 7 photos')
        ->assertSee('<dialog id="lightbox"', false)
        ->assertSee('aria-label="Next photo"', false)
        ->getContent();

    foreach (range(1, 7) as $i) {
        expect($content)->toContain('href="https://pix.example/'.$i.'.jpg" data-lightbox="hotel"');
    }
    expect(substr_count($content, '<dialog id="lightbox"'))->toBe(1);
});

test('a hotel with one photo has no "show all" button', function () {
    Image::where('imageable_id', $this->hotel->id)->where('order', '>', 1)->delete();

    $this->get('/hotels/hotel-tugu-jogja')
        ->assertSee('data-lightbox="hotel"', false)
        ->assertDontSee('Show all');
});
