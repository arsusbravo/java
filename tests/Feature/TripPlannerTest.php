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
        'slug' => 'hotel-' . Str::random(8),
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
        ->assertSee('/go/hotel/' . $hotel->id . '?from=trip_planner', false)
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
    $this->get('/destinations/' . $this->yogya->region->slug . '/yogyakarta')->assertSee('trip-planner?start=yogyakarta', false);
    $this->get('/')->assertSee('Plan my trip');
});

test('a night without a partner hotel nearby says so', function () {
    $jakarta = Destination::where('slug', 'jakarta')->firstOrFail();

    $this->get('/trip-planner?start=jakarta&days=2')
        ->assertOk()
        ->assertSee("We don't have a partner hotel near", false);
});
