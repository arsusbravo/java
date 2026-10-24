<?php

use App\Models\Accommodation;
use App\Models\AffiliateClick;
use App\Models\Destination;
use App\Support\Geo;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    config(['services.agoda.url' => 'https://agoda.test/lt_v1', 'services.agoda.api_key' => '1234567:secret', 'services.agoda.currency' => 'EUR']);
    $this->place = Destination::with('region')->where('slug', 'yogyakarta')->firstOrFail();
    $this->url = "/destinations/{$this->place->region->slug}/yogyakarta/hotels";
});

function pricedHotel(Destination $place, float $km, int $agodaId, array $attributes = []): Accommodation
{
    return Accommodation::create([
        'name' => "Agoda hotel {$agodaId}", 'slug' => 'agoda-'.Str::random(8), 'type' => 'hotel',
        'status' => 'published', 'is_active' => true, 'star_rating' => 3, 'external_id' => (string) $agodaId,
        'latitude' => (float) $place->latitude + $km / Geo::KM_PER_DEGREE, 'longitude' => (float) $place->longitude,
        'affiliate_link' => 'https://www.agoda.com/partners/partnersearch.aspx?pcs=1&cid={affiliate_id}&hid='.$agodaId,
        ...$attributes,
    ]);
}

/** Agoda answers with these offers (hotel id => [rate, overrides]) for any request. */
function fakeAgoda(array $offers): void
{
    Http::fake(['agoda.test/*' => Http::response(['results' => collect($offers)->map(fn ($offer, $id) => [
        'hotelId' => $id, 'currency' => 'EUR', 'dailyRate' => $offer[0], 'crossedOutRate' => 0, 'discountPercentage' => 0,
        'reviewScore' => 8.6, 'reviewCount' => 1240, 'includeBreakfast' => true, 'freeWifi' => true, ...($offer[1] ?? []),
    ])->values()->all()])]);
}

function stayQuery(int $nights = 2, array $extra = []): string
{
    return '?'.http_build_query(['checkin' => today()->addDays(10)->toDateString(), 'checkout' => today()->addDays(10 + $nights)->toDateString(), 'adults' => 2, ...$extra]);
}

test('the nightly command stores prices and ratings, and clears stale prices', function () {
    $priced = pricedHotel($this->place, 1, 101);
    $full = pricedHotel($this->place, 2, 202, ['price_from' => 40, 'review_score' => 7.5, 'review_count' => 10]);
    $manual = Accommodation::create(['name' => 'No Agoda id', 'slug' => 'no-agoda', 'type' => 'hotel', 'status' => 'published', 'is_active' => true, 'price_from' => 30]);
    fakeAgoda([101 => [5.5, ['crossedOutRate' => 10.0]]]);

    $this->artisan('agoda:prices')->expectsOutputToContain('1 of 2 hotels have rooms')->assertSuccessful();

    $priced->refresh();
    expect((float) $priced->price_from)->toBe(5.5)
        ->and($priced->currency)->toBe('EUR')
        ->and((float) $priced->crossed_out_price)->toBe(10.0)
        ->and($priced->discount_percent)->toBe(45)
        ->and((float) $priced->review_score)->toBe(8.6)
        ->and($priced->review_count)->toBe(1240)
        ->and($priced->includes_breakfast)->toBeTrue()
        ->and($priced->prices_checked_at)->not->toBeNull();

    $full->refresh();
    expect($full->price_from)->toBeNull()
        ->and((float) $full->review_score)->toBe(7.5)          // rating kept
        ->and((float) $manual->fresh()->price_from)->toBe(30.0);   // not an Agoda hotel: untouched

    Http::assertSent(fn (Request $request) => $request['criteria']['checkInDate'] === today()->addDays(14)->toDateString()
        && $request['criteria']['checkOutDate'] === today()->addDays(15)->toDateString());
});

test('the command can be limited and needs a key', function () {
    pricedHotel($this->place, 1, 101);
    pricedHotel($this->place, 2, 202);
    fakeAgoda([]);

    $this->artisan('agoda:prices --limit=1')->expectsOutputToContain('Checking 1 hotels')->assertSuccessful();
    Http::assertSent(fn (Request $request) => count($request['criteria']['hotelId']) === 1);

    config(['services.agoda.api_key' => null]);
    $this->artisan('agoda:prices')->assertFailed();
});

test('hotel cards show the stored price, discount, rating and extras', function () {
    pricedHotel($this->place, 1, 101, [
        'price_from' => 23, 'currency' => 'EUR', 'crossed_out_price' => 40, 'discount_percent' => 42,
        'review_score' => 8.6, 'review_count' => 1240, 'includes_breakfast' => true, 'free_wifi' => true, 'prices_checked_at' => now(),
    ]);

    $this->get($this->url)
        ->assertSee('From €23')
        ->assertSee('€40')
        ->assertSee('−42%')
        ->assertSee('excl. taxes · checked '.now()->format('j M'))
        ->assertSeeInOrder(['8.6', 'Excellent', '1,240 reviews'])
        ->assertSee('Breakfast included')
        ->assertSee('Free Wi-Fi');
});

test('hotel cards without Agoda data look as before', function () {
    pricedHotel($this->place, 1, 101);

    $this->get($this->url)
        ->assertSee('Check prices on')
        ->assertDontSee('excl. taxes')
        ->assertDontSee('Breakfast included');
});

test('with dates, live prices are shown and full hotels go last', function () {
    $full = pricedHotel($this->place, 0.5, 101);
    pricedHotel($this->place, 1, 202);
    pricedHotel($this->place, 2, 303);
    fakeAgoda([202 => [80], 303 => [35]]);

    $response = $this->get($this->url.stayQuery())->assertOk();

    expect($response->viewData('hotels')->pluck('external_id')->all())->toBe(['202', '303', '101']);
    $response->assertSee('2 with rooms for your dates')
        ->assertSee('€80')
        ->assertSee('for your dates')
        ->assertSee('No rooms for your dates')
        ->assertSee('2 nights · 2 adults')
        ->assertSee('/go/hotel/'.$full->id.'?from=destination&amp;checkin='.today()->addDays(10)->toDateString(), false);

    Http::assertSent(fn (Request $request) => $request['criteria']['checkOutDate'] === today()->addDays(12)->toDateString());
});

test('hotels can be sorted by price and rating', function () {
    pricedHotel($this->place, 0.5, 101, ['review_score' => 7.1]);
    pricedHotel($this->place, 1, 202, ['review_score' => 9.2]);
    pricedHotel($this->place, 2, 303, ['review_score' => 8.0]);
    fakeAgoda([101 => [60], 202 => [90], 303 => [20]]);

    expect($this->get($this->url.stayQuery(extra: ['sort' => 'price']))->viewData('hotels')->pluck('external_id')->all())->toBe(['303', '101', '202'])
        ->and($this->get($this->url.'?sort=rating')->viewData('hotels')->pluck('external_id')->all())->toBe(['202', '303', '101']);
});

test('dates stay in the filter, sort and page links', function () {
    foreach (range(1, 14) as $i) {
        pricedHotel($this->place, $i, 100 + $i);
    }
    fakeAgoda([]);
    $dates = 'checkin='.today()->addDays(10)->toDateString().'&amp;checkout='.today()->addDays(12)->toDateString().'&amp;adults=2';

    $this->get($this->url.stayQuery())
        ->assertSee($this->url.'?'.$dates.'&amp;page=2', false)
        ->assertSee($this->url.'?'.$dates.'&amp;budget=high', false)
        ->assertSee($this->url.'?'.$dates.'&amp;sort=price', false)
        ->assertSee('Clear dates');
});

test('impossible dates are explained and ignored', function () {
    pricedHotel($this->place, 1, 101);
    Http::fake();

    $this->get($this->url.'?checkin=2020-01-01&checkout=2020-01-03')
        ->assertOk()
        ->assertSee('Please pick a check-in date from today')
        ->assertDontSee('for your dates');

    Http::assertNothingSent();
});

test('booking links take the dates to Agoda, and only to Agoda', function () {
    $hotel = pricedHotel($this->place, 1, 101);
    $other = pricedHotel($this->place, 1, 102, ['affiliate_link' => 'https://partner.example/hotel/102']);
    $checkIn = today()->addDays(10)->toDateString();
    $checkOut = today()->addDays(12)->toDateString();
    $browser = ['User-Agent' => 'Mozilla/5.0 (Macintosh) Chrome/130.0 Safari/537.36'];

    $this->withHeaders($browser)->get("/go/hotel/{$hotel->id}?from=destination&checkin={$checkIn}&checkout={$checkOut}&adults=3&children=1")
        ->assertRedirect($hotel->affiliate_url."&checkin={$checkIn}&checkout={$checkOut}&NumberofAdults=3&NumberofChildren=1&Rooms=1&currency=EUR");

    $this->withHeaders($browser)->get("/go/hotel/{$hotel->id}?from=destination&checkin=2020-01-01&checkout=2020-01-03")
        ->assertRedirect($hotel->affiliate_url);

    $this->withHeaders($browser)->get("/go/hotel/{$other->id}?checkin={$checkIn}&checkout={$checkOut}")
        ->assertRedirect('https://partner.example/hotel/102');

    expect(AffiliateClick::count())->toBe(2);
});
