<?php

use App\Models\Destination;
use App\Models\Region;
use App\Support\Geo;
use Database\Seeders\DatabaseSeeder;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);

    $this->jakarta = Destination::with('region')->where('slug', 'jakarta')->firstOrFail();
    $this->jakarta->update(['latitude' => -6.1754, 'longitude' => 106.8272]);
});

/**
 * A destination $km kilometres north of Monas.
 */
function placeNorthOf(float $km, ?Region $region = null, array $attributes = []): Destination
{
    return Destination::create([
        'region_id' => ($region ?? Region::firstOrFail())->id,
        'name' => "Place at {$km} km",
        'slug' => 'place-' . str_replace('.', '-', (string) $km),
        'latitude' => -6.1754 + $km / Geo::KM_PER_DEGREE,
        'longitude' => 106.8272,
        ...$attributes,
    ]);
}

function nearbyNames(): array
{
    return test()->get('/destinations/' . test()->jakarta->region->slug . '/jakarta')
        ->assertOk()
        ->viewData('nearbyDestinations')
        ->map(fn ($place) => [$place->name, round($place->distance_km, 1)])
        ->all();
}

test('the closest places are listed first, from any region and at any distance', function () {
    $otherRegion = Region::where('slug', 'east-java')->firstOrFail();
    placeNorthOf(12);
    placeNorthOf(3, $otherRegion);
    placeNorthOf(80);                                             // well beyond the old 20 km limit
    placeNorthOf(0.5, attributes: ['latitude' => null]);          // no coordinates

    // Seeded places (Bandung, Yogyakarta...) are further away and fill the rest
    $closest = array_slice(nearbyNames(), 0, 3);
    expect(array_column($closest, 0))->toBe(['Place at 3 km', 'Place at 12 km', 'Place at 80 km'])
        ->and(array_column($closest, 1))->toEqualWithDelta([3.0, 12.0, 80.0], 0.2);

    $this->get('/destinations/' . $this->jakarta->region->slug . '/jakarta')
        ->assertSee('Nearby Places')
        ->assertSee('Closest to Jakarta')
        ->assertSee('3.0 km away')
        ->assertSee('East Java');             // shown when the place is in another region

    // Places without coordinates can't be measured, so they're never "nearby"
    expect(array_column(nearbyNames(), 0))->not->toContain('Place at 0.5 km');
});

test('nearby places come after where to stay', function () {
    placeNorthOf(2);
    App\Models\Accommodation::create([
        'name' => 'Hotel Indonesia', 'slug' => 'hotel-indonesia', 'type' => 'hotel',
        'status' => 'published', 'is_active' => true, 'latitude' => -6.1760, 'longitude' => 106.8270,
    ]);

    $this->get('/destinations/' . $this->jakarta->region->slug . '/jakarta')
        ->assertSeeInOrder(['Where to Stay', 'Hotel Indonesia', 'Nearby Places', 'Place at 2 km']);
});

test('the section is hidden when the destination has no coordinates', function () {
    placeNorthOf(1);
    $this->jakarta->update(['latitude' => null]);

    expect(nearbyNames())->toBe([]);
    $this->get('/destinations/' . $this->jakarta->region->slug . '/jakarta')->assertDontSee('Nearby Places');
});

test('at most six nearby places are shown', function () {
    foreach (range(1, 8) as $km) {
        placeNorthOf($km);
    }

    expect(nearbyNames())->toHaveCount(6)
        ->and(collect(nearbyNames())->pluck(1)->all())->toBe([1.0, 2.0, 3.0, 4.0, 5.0, 6.0]);
});

test('other places show up to six random places from the same region', function () {
    $sameRegion = collect([2, 40, 60, 80, 100, 120, 140])->map(fn ($km) => placeNorthOf($km))->pluck('id')
        ->merge(Destination::where('region_id', $this->jakarta->region_id)->whereKeyNot($this->jakarta->id)->pluck('id'))
        ->unique();
    $elsewhere = placeNorthOf(90, Region::where('slug', 'east-java')->firstOrFail());

    $seen = collect();
    foreach (range(1, 8) as $visit) {
        $others = $this->get('/destinations/' . $this->jakarta->region->slug . '/jakarta')
            ->assertOk()
            ->assertSeeInOrder(['Other Places in ' . $this->jakarta->region->name, 'All places in'])
            ->viewData('otherDestinations');

        expect($others)->toHaveCount(6)
            ->and($others->modelKeys())->each->toBeIn($sameRegion->all())
            ->and($others->modelKeys())->not->toContain($elsewhere->id);
        $seen = $seen->merge($others->modelKeys());
    }

    // Random picks: over several visits more than six different places appear
    expect($seen->unique()->count())->toBeGreaterThan(6);
});
