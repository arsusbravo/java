<?php

use App\Models\Accommodation;
use App\Models\Destination;
use App\Support\Geo;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);

    // Monas, Central Jakarta
    $this->destination = Destination::with('region')->where('slug', 'jakarta')->firstOrFail();
    $this->destination->update(['latitude' => -6.1754, 'longitude' => 106.8272]);
});

/**
 * A hotel $km kilometres north of Monas.
 */
function hotelNorthOf(float $km, array $attributes = []): Accommodation
{
    return Accommodation::create([
        'name' => "Hotel at {$km} km",
        'slug' => 'hotel-' . Str::random(8),
        'type' => 'hotel',
        'status' => 'published',
        'is_active' => true,
        'latitude' => -6.1754 + $km / Geo::KM_PER_DEGREE,
        'longitude' => 106.8272,
        ...$attributes,
    ]);
}

function whereToStay(): array
{
    return test()->get('/destinations/' . test()->destination->region->slug . '/jakarta')
        ->assertOk()
        ->viewData('accommodations')
        ->map(fn ($hotel) => [$hotel->name, isset($hotel->distance_km) ? round($hotel->distance_km, 1) : null])
        ->all();
}

test('distances are calculated along the earth surface', function () {
    // Monas to Bandung city centre is about 119 km in a straight line
    expect(Geo::distanceKm(-6.1754, 106.8272, -6.9175, 107.6191))->toBeGreaterThan(115)->toBeLessThan(123)
        ->and(Geo::distanceKm(-6.1754, 106.8272, -6.1754, 106.8272))->toBe(0.0);
});

test('a destination without linked hotels shows nearby ones, closest first when unrated', function () {
    hotelNorthOf(3);
    hotelNorthOf(0.5);
    hotelNorthOf(30);  // found by widening the search
    hotelNorthOf(1, ['status' => 'draft']);
    hotelNorthOf(1.5, ['is_active' => false]);
    hotelNorthOf(150); // beyond the 100 km limit

    expect(whereToStay())->toBe([
        ['Hotel at 0.5 km', 0.5],
        ['Hotel at 3 km', 3.0],
        ['Hotel at 30 km', 30.0],
    ]);

    $this->get('/destinations/' . $this->destination->region->slug . '/jakarta')
        ->assertSee('Including top-rated stays near Jakarta')
        ->assertSee('500 m away')
        ->assertSee('3.0 km away')
        ->assertDontSee('Hotel at 150 km');
});

test('linked hotels come first and the nearest fill the remaining places', function () {
    $linkedFar = hotelNorthOf(60, ['star_rating' => 5]);
    $linkedNear = hotelNorthOf(2, ['star_rating' => 3]);
    $this->destination->accommodations()->attach([$linkedFar->id, $linkedNear->id]);
    foreach ([0.2, 0.4, 0.6, 0.8, 1.0] as $km) {
        hotelNorthOf($km);
    }

    expect(whereToStay())->toBe([
        ['Hotel at 60 km', null],   // linked, by star rating
        ['Hotel at 2 km', null],
        ['Hotel at 0.2 km', 0.2],   // nearest, excluding the linked ones
        ['Hotel at 0.4 km', 0.4],
        ['Hotel at 0.6 km', 0.6],
        ['Hotel at 0.8 km', 0.8],
    ]);
});

test('a destination with six linked hotels shows no extra ones', function () {
    foreach (range(1, 6) as $i) {
        $this->destination->accommodations()->attach(hotelNorthOf(50 + $i));
    }
    hotelNorthOf(0.1);

    expect(collect(whereToStay())->pluck(1)->filter()->all())->toBe([]);
    $this->get('/destinations/' . $this->destination->region->slug . '/jakarta')->assertDontSee('Including top-rated stays');
});

test('a destination without coordinates only shows its linked hotels', function () {
    $this->destination->update(['latitude' => null, 'longitude' => null]);
    $this->destination->accommodations()->attach(hotelNorthOf(5));
    hotelNorthOf(0.1);

    expect(whereToStay())->toBe([['Hotel at 5 km', null]]);
});

test('nearby hotels with higher star ratings come first, then the closest', function () {
    hotelNorthOf(0.3, ['star_rating' => 2]);
    hotelNorthOf(4, ['star_rating' => 5]);
    hotelNorthOf(0.1);                       // unrated goes last
    hotelNorthOf(1, ['star_rating' => 4]);
    hotelNorthOf(2, ['star_rating' => 4]);
    hotelNorthOf(3.5, ['star_rating' => 5]);
    hotelNorthOf(60, ['star_rating' => 5]);  // outside the radius that already has enough hotels

    expect(whereToStay())->toBe([
        ['Hotel at 3.5 km', 3.5],
        ['Hotel at 4 km', 4.0],
        ['Hotel at 1 km', 1.0],
        ['Hotel at 2 km', 2.0],
        ['Hotel at 0.3 km', 0.3],
        ['Hotel at 0.1 km', 0.1],
    ]);
});
