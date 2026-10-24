<?php

use App\Models\Accommodation;
use App\Models\Destination;
use App\Models\DestinationType;
use App\Support\Geo;
use App\Support\TripPlanner;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    $this->planner = new TripPlanner;
    $this->yogya = Destination::where('slug', 'yogyakarta')->firstOrFail();
});

/**
 * A place $km north of Yogyakarta, with the given trip types.
 */
function placeNearYogya(string $name, float $km, array $types = [], array $attributes = []): Destination
{
    $place = Destination::create([
        'region_id' => test()->yogya->region_id,
        'name' => $name,
        'slug' => Str::slug($name),
        'latitude' => -7.7956 + $km / Geo::KM_PER_DEGREE,
        'longitude' => 110.3695,
        ...$attributes,
    ]);
    $place->types()->sync(DestinationType::whereIn('slug', $types)->pluck('id'));

    return $place;
}

function hotelNear(Destination $place, float $km, ?int $stars, array $attributes = []): Accommodation
{
    return Accommodation::create([
        'name' => "Hotel {$stars}★ at {$km} km from {$place->name}",
        'slug' => 'hotel-'.Str::random(8),
        'type' => 'hotel',
        'status' => 'published',
        'is_active' => true,
        'star_rating' => $stars,
        'latitude' => (float) $place->latitude + $km / Geo::KM_PER_DEGREE,
        'longitude' => (float) $place->longitude,
        'affiliate_link' => 'https://www.agoda.com/partners/partnersearch.aspx?hid=1',
        ...$attributes,
    ]);
}

function stopNames(array $plan): array
{
    return collect($plan['days'])->map(fn ($day) => collect($day['stops'])->pluck('destination.name')->all())->all();
}

test('a trip has the chosen number of days, no repeated places, and a hotel for every night', function () {
    foreach (['Market' => 3, 'Museum' => 6, 'Garden' => 9, 'Temple' => 12, 'Lake' => 15, 'Cave' => 18] as $name => $km) {
        placeNearYogya($name, $km);
    }
    hotelNear($this->yogya, 1, 4);
    $plan = $this->planner->plan($this->yogya, 4);

    $places = collect(stopNames($plan))->flatten();
    expect($plan['days'])->toHaveCount(4)
        ->and($plan['nights'])->toBe(3)
        ->and($places->duplicates())->toBeEmpty()
        ->and($places->first())->toBe('Yogyakarta')
        ->and(collect($plan['days'])->take(3)->every(fn ($day) => $day['stay'] !== null))->toBeTrue()
        ->and($plan['days'][3]['stay'])->toBeNull();        // last day: going home
});

test('the same choices always give the same trip', function () {
    expect(stopNames($this->planner->plan($this->yogya, 5, ['heritage-history'])))
        ->toBe(stopNames((new TripPlanner)->plan($this->yogya, 5, ['heritage-history'])));
});

test('places matching the chosen interests come first, nearby places before far ones', function () {
    placeNearYogya('Beach Far', 30, ['beach-islands']);
    placeNearYogya('Temple Near', 10, ['heritage-history']);
    placeNearYogya('Temple Further', 20, ['heritage-history']);
    placeNearYogya('Mall Closest', 2, ['shopping']);

    expect(stopNames($this->planner->plan($this->yogya, 2, ['heritage-history']))[0])
        ->toBe(['Yogyakarta', 'Temple Near']);
});

test('a place the traveller removed is left out', function () {
    placeNearYogya('Temple Near', 10, ['heritage-history']);
    placeNearYogya('Temple Further', 20, ['heritage-history']);

    $plan = $this->planner->plan($this->yogya, 2, ['heritage-history'], skip: ['temple-near']);

    expect(collect(stopNames($plan))->flatten())->not->toContain('Temple Near')
        ->and(stopNames($plan)[0])->toBe(['Yogyakarta', 'Temple Further']);
});

test('hotels follow the budget', function (string $budget, ?int $expectedStars) {
    hotelNear($this->yogya, 0.5, 2);
    hotelNear($this->yogya, 0.6, 4);
    hotelNear($this->yogya, 0.7, 5);

    $hotel = $this->planner->plan($this->yogya, 2, budget: $budget)['days'][0]['stay']['hotel'];

    expect($hotel->star_rating)->toBe($expectedStars);
})->with([
    'budget' => ['budget', 2],
    'comfort' => ['comfort', 4],
    'luxury' => ['luxury', 5],
]);

test('proper hotels beat rented houses with the same stars', function () {
    hotelNear($this->yogya, 0.2, 5, ['type' => 'house', 'name' => 'Party villa house']);
    hotelNear($this->yogya, 1.5, 5, ['type' => 'resort', 'name' => 'Grand Resort']);

    expect($this->planner->plan($this->yogya, 2, budget: 'luxury')['days'][0]['stay']['hotel']->name)->toBe('Grand Resort');
});

test('the same hotel is kept when the day ends close to it', function () {
    placeNearYogya('Market', 3);
    placeNearYogya('Museum', 6);
    placeNearYogya('Garden', 9);
    hotelNear($this->yogya, 4, 4);

    $days = $this->planner->plan($this->yogya, 3)['days'];

    expect($days[1]['stay']['sameAsLastNight'])->toBeTrue()
        ->and($days[1]['stay']['hotel']->is($days[0]['stay']['hotel']))->toBeTrue();
});

test('far areas are reached with a travel day, and spare days become free days', function () {
    $jakarta = Destination::where('slug', 'jakarta')->firstOrFail();

    $plan = $this->planner->plan($jakarta, 14);

    // Bandung is ~119 km away: too far for a day trip, so day 2 travels there
    expect(stopNames($plan)[0])->toBe(['Jakarta'])
        ->and($plan['days'][1]['travelDay'])->toBeTrue()
        ->and(stopNames($plan)[1][0])->toBe('Bandung')
        // Only 6 seeded places: the rest of two weeks are free days
        ->and(collect($plan['days'])->whereNotNull('freeDayAround'))->not->toBeEmpty();
});

test('drive times are rough road estimates', function () {
    expect(TripPlanner::driveMinutes(0))->toBe(0)
        ->and(TripPlanner::driveMinutes(40))->toBe(80)       // 40 km x 1.3 at 40 km/h
        ->and(TripPlanner::durationLabel(80))->toBe('1 h 20 min')
        ->and(TripPlanner::durationLabel(120))->toBe('2 h')
        ->and(TripPlanner::durationLabel(3))->toBe('5 min');
});

test('the planner page shows the form, and the trip once a start is chosen', function () {
    $hotel = hotelNear($this->yogya, 1, 4, ['affiliate_network_id' => App\Models\AffiliateNetwork::first()->id]);
    placeNearYogya('Temple Near', 10, ['heritage-history']);

    $this->get('/trip-planner')
        ->assertOk()
        ->assertSee('Plan your Java trip in a minute')
        ->assertSee('Heritage &amp; History', false)
        ->assertDontSee('Tonight');

    $this->get('/trip-planner?start=yogyakarta&days=3&interests[]=heritage-history&budget=comfort')
        ->assertOk()
        ->assertSee('Your 3-day trip from Yogyakarta')
        ->assertSeeInOrder(['Day 1', 'Yogyakarta', 'Temple Near', "Tonight's stay", $hotel->name, 'Day 2'])
        ->assertSee('/go/hotel/'.$hotel->id.'?from=trip_planner', false)
        ->assertSee('skip%5B0%5D=temple-near', false)          // "Not for me" link
        ->assertSee('Share this trip');
});

test('invalid choices are corrected instead of failing', function () {
    $this->get('/trip-planner?start=nowhere')
        ->assertOk()
        ->assertSee("We couldn't find that place", false);

    $this->get('/trip-planner?start=yogyakarta&days=99&budget=caviar&interests[]=space-travel')
        ->assertOk()
        ->assertSee('Your 14-day trip from Yogyakarta')
        ->assertViewHas('choices', fn ($choices) => $choices['days'] === 14 && $choices['budget'] === 'comfort' && $choices['interests'] === []);
});

test('the plan page and destination pages lead to the planner', function () {
    $this->get('/plan')->assertSee(route('trip-planner'), false)->assertSee('Build my itinerary');
    $this->get('/destinations/'.$this->yogya->region->slug.'/yogyakarta')->assertSee('trip-planner?start=yogyakarta', false);
    $this->get('/')->assertSee('Plan my trip');
});

test('a night without a partner hotel nearby says so', function () {
    $jakarta = Destination::where('slug', 'jakarta')->firstOrFail();

    $this->get('/trip-planner?start=jakarta&days=2')
        ->assertOk()
        ->assertSee("We don't have a partner hotel near", false);
});

/** Agoda answers per night: [check-in date => [agoda hotel id => rate]]. */
function fakeAgodaNights(array $nights): void
{
    config(['services.agoda.url' => 'https://agoda.test/lt_v1', 'services.agoda.api_key' => '1234567:secret', 'services.agoda.currency' => 'EUR']);
    Illuminate\Support\Facades\Http::fake(['agoda.test/*' => function ($request) use ($nights) {
        $rates = $nights[$request['criteria']['checkInDate']] ?? [];

        return Illuminate\Support\Facades\Http::response(['results' => collect($rates)
            ->only($request['criteria']['hotelId'])
            ->map(fn ($rate, $id) => ['hotelId' => $id, 'currency' => 'EUR', 'dailyRate' => $rate, 'reviewScore' => 8.8, 'reviewCount' => 50])
            ->values()->all()]);
    }]);
}

test('with a start date every night gets its live price, a total and dated booking links', function () {
    placeNearYogya('Market', 3);
    placeNearYogya('Museum', 6);
    placeNearYogya('Garden', 60);
    placeNearYogya('Lake', 65);
    $near = hotelNear($this->yogya, 1, 4, ['external_id' => '11']);
    $far = hotelNear(Destination::where('slug', 'lake')->first(), 1, 4, ['external_id' => '22']);
    $first = today()->addDays(20);
    fakeAgodaNights([
        $first->toDateString() => [11 => 40.0],
        $first->copy()->addDay()->toDateString() => [22 => 55.5],
    ]);

    $response = $this->get('/trip-planner?'.http_build_query(['start' => 'yogyakarta', 'days' => 3, 'budget' => 'comfort', 'date' => $first->toDateString(), 'adults' => 2]))->assertOk();
    $plan = $response->viewData('plan');

    expect($plan['days'][0]['stay']['hotel']->is($near))->toBeTrue()
        ->and($plan['days'][0]['stay']['offer']->dailyRate)->toBe(40.0)
        ->and($plan['days'][1]['stay']['hotel']->is($far))->toBeTrue()
        ->and($plan['hotelTotal'])->toBe(95.5)
        ->and($plan['pricedNights'])->toBe(2);

    $response->assertSee('Hotels: about')
        ->assertSee('€96')
        ->assertSee('that night, excl. taxes')
        ->assertSee("Tonight's stay · ".$first->format('D j M'), false)
        ->assertSee('/go/hotel/'.$near->id.'?from=trip_planner&amp;checkin='.$first->toDateString().'&amp;checkout='.$first->copy()->addDay()->toDateString(), false);
});

test('nights in the same hotel are booked together', function () {
    placeNearYogya('Market', 3);
    placeNearYogya('Museum', 6);
    placeNearYogya('Garden', 9);
    $hotel = hotelNear($this->yogya, 4, 4, ['external_id' => '11']);
    $first = today()->addDays(20);
    fakeAgodaNights([$first->toDateString() => [11 => 40], $first->copy()->addDay()->toDateString() => [11 => 45]]);

    $plan = $this->get('/trip-planner?start=yogyakarta&days=3&date='.$first->toDateString())->viewData('plan');

    expect($plan['days'][1]['stay']['sameAsLastNight'])->toBeTrue()
        ->and($plan['days'][0]['stay']['stay']->nights())->toBe(2)
        ->and($plan['days'][1]['stay']['stay']->checkIn->toDateString())->toBe($first->toDateString())
        ->and($plan['hotelTotal'])->toBe(85.0);
    $this->get('/trip-planner?start=yogyakarta&days=3&date='.$first->toDateString())->assertSee('Book 2 nights on');
});

test('a full hotel is swapped for an alternative with rooms', function () {
    hotelNear($this->yogya, 0.5, 4, ['external_id' => '11']);
    hotelNear($this->yogya, 0.8, 4, ['external_id' => '22']);
    $planned = (new TripPlanner)->plan($this->yogya, 2)['days'][0]['stay'];
    [$full, $open] = [$planned['hotel'], $planned['alternatives']->first()];
    $first = today()->addDays(20);
    fakeAgodaNights([$first->toDateString() => [(int) $open->external_id => 61]]);

    $response = $this->get('/trip-planner?start=yogyakarta&days=2&date='.$first->toDateString());
    $stay = $response->viewData('plan')['days'][0]['stay'];

    expect($stay['hotel']->is($open))->toBeTrue()
        ->and($stay['fullHotel']->is($full))->toBeTrue()
        ->and($stay['offer']->dailyRate)->toBe(61.0)
        ->and($stay['alternatives']->pluck('id'))->toContain($full->id);
    $response->assertSee($full->name.' is full that night');
});

test('without a date or with a past date the planner asks nothing from Agoda', function () {
    hotelNear($this->yogya, 1, 4, ['external_id' => '11']);
    Illuminate\Support\Facades\Http::fake();

    $this->get('/trip-planner?start=yogyakarta&days=2')->assertOk()->assertDontSee('Hotels: about');
    $this->get('/trip-planner?start=yogyakarta&days=2&date=2020-01-01')
        ->assertOk()
        ->assertSee('That date has passed')
        ->assertDontSee('that night, excl. taxes');
    $this->get('/trip-planner?start=yogyakarta&days=2&date=nonsense')->assertOk();

    Illuminate\Support\Facades\Http::assertNothingSent();
});

test('the date is kept in the planner links', function () {
    placeNearYogya('Temple Near', 10, ['heritage-history']);
    fakeAgodaNights([]);
    $date = today()->addDays(20)->toDateString();

    $this->get("/trip-planner?start=yogyakarta&days=2&date={$date}&adults=3")
        ->assertSee('value="'.$date.'"', false)
        ->assertSee('date='.$date.'&amp;adults=3', false);    // "Not for me" keeps the date
});
