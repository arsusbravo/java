<?php

use App\Models\Destination;
use App\Models\DestinationType;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DestinationTypeSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    $this->actingAs(User::firstOrFail());
});

function typeIds(string ...$slugs): array
{
    return DestinationType::whereIn('slug', $slugs)->orderBy('sort_order')->pluck('id')->all();
}

test('the starter types are seeded in order with first suggestions', function () {
    expect(DestinationType::ordered()->pluck('name')->all())->toBe(array_column(DestinationTypeSeeder::TYPES, 0));

    expect(Destination::where('slug', 'yogyakarta')->firstOrFail()->types->pluck('slug')->all())
        ->toBe(['heritage-history', 'culture-arts', 'culinary']);
});

test('the seeder never overwrites types chosen in the admin', function () {
    $bromo = Destination::where('slug', 'mount-bromo')->firstOrFail();
    $bromo->types()->sync(typeIds('beach-islands'));

    $this->seed(DestinationTypeSeeder::class);

    expect($bromo->fresh()->types->pluck('slug')->all())->toBe(['beach-islands'])
        ->and(DestinationType::count())->toBe(count(DestinationTypeSeeder::TYPES));
});

test('types are managed in the admin and new ones go to the end', function () {
    $this->post('/admin/destination-types', ['name' => 'Wellness & Spa'])->assertSessionHasNoErrors();

    $spa = DestinationType::where('slug', 'wellness-spa')->firstOrFail();
    expect($spa->sort_order)->toBe(count(DestinationTypeSeeder::TYPES) + 1);

    // Clearing the position keeps it at the end instead of failing
    $this->put("/admin/destination-types/{$spa->id}", ['name' => 'Wellness & Spa', 'slug' => 'wellness-spa', 'sort_order' => ''])
        ->assertSessionHasNoErrors();
    expect($spa->fresh()->sort_order)->toBe(count(DestinationTypeSeeder::TYPES) + 1);

    $this->get('/admin/destination-types')->assertOk()->assertSee('Wellness &amp; Spa', false);
});

test('a destination can have several types, set from the destinations admin', function () {
    $solo = Destination::where('slug', 'solo')->firstOrFail();

    $this->put("/admin/destinations/{$solo->id}", [
        'name' => 'Solo',
        'region_id' => $solo->region_id,
        'types' => typeIds('heritage-history', 'culinary', 'shopping'),
    ])->assertSessionHasNoErrors();

    expect($solo->fresh()->types->pluck('slug')->all())->toBe(['shopping', 'heritage-history', 'culinary']);

    $this->get("/admin/destinations/{$solo->id}/edit")->assertInertia(fn (Assert $page) => $page
        ->where('values.types', typeIds('shopping', 'heritage-history', 'culinary')));
});

test('the destinations list can be filtered by type', function () {
    [$volcano] = typeIds('mountains-volcanoes');

    $this->get("/admin/destinations?types={$volcano}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('records.data', 2)
            ->where('records.data.0.cells.types', fn ($types) => str_contains($types, 'Mountains & Volcanoes')));
});

test('the places page sets types and cleans the description', function () {
    $region = App\Models\Region::firstOrFail();

    $this->post('/admin/places/destinations', [
        'region_id' => $region->id,
        'name' => 'Kepulauan Seribu',
        'slug' => 'kepulauan-seribu',
        'description' => '<p>Island <strong>hopping</strong></p><script>alert(1)</script>',
        'is_featured' => false,
        'types' => typeIds('beach-islands', 'nature-adventure'),
    ])->assertSessionHasNoErrors();

    $islands = Destination::where('slug', 'kepulauan-seribu')->firstOrFail();
    expect($islands->description)->toBe('<p>Island <strong>hopping</strong></p>')
        ->and($islands->types->pluck('slug')->all())->toBe(['nature-adventure', 'beach-islands']);

    $this->get('/admin/places')->assertInertia(fn (Assert $page) => $page
        ->has('destinationTypes', count(DestinationTypeSeeder::TYPES))
        ->where('regions.0.destinations', fn ($destinations) => collect($destinations)->firstWhere('slug', 'kepulauan-seribu')['types'][0]['name'] === 'Nature & Adventure'));

    $this->post('/admin/places/destinations', [
        'region_id' => $region->id, 'name' => 'X', 'slug' => 'x', 'types' => [9999],
    ])->assertSessionHasErrors('types.0');
});

test('deleting a type only unlinks it from destinations', function () {
    $culinary = DestinationType::where('slug', 'culinary')->firstOrFail();

    $this->delete("/admin/destination-types/{$culinary->id}")->assertRedirect();

    expect(Destination::where('slug', 'yogyakarta')->firstOrFail()->types->pluck('slug')->all())
        ->toBe(['heritage-history', 'culture-arts']);
});
