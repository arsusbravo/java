<?php

use App\Models\Destination;
use App\Models\Restaurant;
use App\Models\Tour;
use App\Support\Geo;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    $this->destination = Destination::with('region')->where('slug', 'yogyakarta')->firstOrFail();
    $this->url = "/destinations/{$this->destination->region->slug}/yogyakarta";
});

function restaurantNear(Destination $place, float $km, array $attributes = []): Restaurant
{
    return Restaurant::create([
        'name' => "Warung at {$km} km",
        'slug' => 'warung-' . Str::random(8),
        'status' => 'published',
        'is_active' => true,
        'latitude' => (float) $place->latitude + $km / Geo::KM_PER_DEGREE,
        'longitude' => (float) $place->longitude,
        ...$attributes,
    ]);
}

function whereToEat(): array
{
    return test()->get(test()->url)->assertOk()->viewData('restaurants')
        ->map(fn ($restaurant) => [$restaurant->name, isset($restaurant->distance_km) ? round($restaurant->distance_km, 1) : null])
        ->all();
}

test('restaurants linked either way are shown first', function () {
    $picked = restaurantNear($this->destination, 40, ['name' => 'Picked in the picker', 'average_rating' => 4.5]);
    $picked->destinations()->attach($this->destination);
    restaurantNear($this->destination, 45, ['name' => 'Set as its destination', 'destination_id' => $this->destination->id, 'average_rating' => 4.0]);

    expect(array_slice(whereToEat(), 0, 2))->toBe([
        ['Picked in the picker', null],
        ['Set as its destination', null],
    ]);
});

test('new restaurants nearby appear without linking, closest first', function () {
    restaurantNear($this->destination, 3);
    restaurantNear($this->destination, 0.4);
    restaurantNear($this->destination, 30);   // too far to go for dinner

    expect(whereToEat())->toBe([['Warung at 0.4 km', 0.4], ['Warung at 3 km', 3.0]]);

    $this->get($this->url)
        ->assertSee('Including places to eat near Yogyakarta')
        ->assertSee('400 m away');
});

test('drafts and inactive restaurants stay hidden', function () {
    restaurantNear($this->destination, 1, ['name' => 'Draft warung', 'status' => 'draft']);
    restaurantNear($this->destination, 1, ['name' => 'Closed warung', 'is_active' => false]);

    expect(whereToEat())->toBe([]);
    $this->get($this->url)->assertDontSee('Where to Eat');
});

test('the restaurant card shows what is known, with directions and tracked booking', function () {
    restaurantNear($this->destination, 1, [
        'name' => 'Gudeg Yu Djum',
        'price_range' => '$$',
        'cuisine_type' => ['Javanese'],
        'address' => 'Jl. Wijilan 167',
        'phone' => '+62 274 515968',
        'opening_hours' => ['Daily 06:00-22:00'],
    ]);
    $booked = restaurantNear($this->destination, 2, ['name' => 'Bookable bistro', 'average_rating' => 4.6, 'affiliate_link' => 'https://partner.example/r/1']);

    $this->get($this->url)
        ->assertSee('Gudeg Yu Djum')
        ->assertSee('Javanese')
        ->assertSee('>$$</span>', false)                  // once, not "$$$"
        ->assertDontSee('$$$')
        ->assertSee('Jl. Wijilan 167')
        ->assertSee('tel:+62274515968', false)
        ->assertSee('Daily 06:00-22:00')
        ->assertSee('https://www.google.com/maps/dir/?api=1&amp;destination=', false)
        ->assertSee('Rated 4.6 out of 5')
        ->assertDontSee('0.0')                            // unrated places show no stars
        ->assertSee('/go/restaurant/' . $booked->id . '?from=destination', false);
});

test('tour cards show real duration and price', function () {
    $tour = Tour::create([
        'name' => 'Sunrise jeep tour', 'slug' => 'sunrise-jeep-tour', 'type' => 'day_tour',
        'status' => 'published', 'is_active' => true,
        'duration' => 6, 'duration_unit' => 'hours', 'price_from' => 450000, 'currency' => 'IDR',
    ]);
    $tour->destinations()->attach($this->destination);

    $this->get($this->url)
        ->assertSee('Sunrise jeep tour')
        ->assertSee('6 hours')
        ->assertSee('From IDR 450,000')
        ->assertDontSee('$0/person');
});
