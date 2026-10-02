<?php

use App\Models\Region;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    $this->actingAs(User::firstOrFail());
});

function regionNames(): array
{
    return Region::ordered()->pluck('name')->all();
}

test('new regions are added at the end of the order', function () {
    $this->post('/admin/places/regions', ['name' => 'Banten', 'slug' => 'banten'])->assertSessionHasNoErrors();

    expect(regionNames())->toBe(['West Java', 'Central Java', 'East Java', 'Banten']);
});

test('the places page saves a new order', function () {
    $banten = Region::create(['name' => 'Banten', 'slug' => 'banten']);
    $ids = Region::whereIn('slug', ['banten', 'west-java', 'central-java', 'east-java'])
        ->get()->sortBy(fn ($region) => array_search($region->slug, ['banten', 'west-java', 'central-java', 'east-java']))
        ->pluck('id')->values()->all();

    $this->put('/admin/places/regions/order', ['ids' => $ids])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(regionNames())->toBe(['Banten', 'West Java', 'Central Java', 'East Java']);

    $this->get('/admin/places')->assertInertia(fn (Assert $page) => $page
        ->where('regions.0.name', 'Banten')
        ->where('regions.3.name', 'East Java'));
});

test('reordering needs every region exactly once', function (Closure $ids) {
    $before = regionNames();

    $this->put('/admin/places/regions/order', ['ids' => $ids()])->assertSessionHasErrors();

    expect(regionNames())->toBe($before);
})->with([
    'missing one' => [fn () => Region::ordered()->take(2)->pluck('id')->all()],
    'duplicate' => [fn () => [Region::first()->id, Region::first()->id, Region::skip(1)->first()->id]],
    'unknown id' => [fn () => [...Region::ordered()->take(2)->pluck('id')->all(), 999]],
]);

test('reordering does not touch region meta or other fields', function () {
    $west = Region::where('slug', 'west-java')->firstOrFail();
    $meta = $west->meta_title;

    Region::reorder(Region::ordered()->pluck('id')->reverse()->all());

    expect($west->fresh()->meta_title)->toBe($meta)
        ->and(regionNames())->toBe(['East Java', 'Central Java', 'West Java']);
});

test('public pages list regions in the chosen order', function () {
    Region::reorder(Region::whereIn('slug', ['east-java', 'west-java', 'central-java'])
        ->orderByRaw("case slug when 'east-java' then 1 when 'west-java' then 2 else 3 end")->pluck('id')->all());

    $this->get('/destinations')
        ->assertOk()
        ->assertSeeInOrder(['East Java', 'West Java', 'Central Java']);

    $this->get('/destinations/central-java')
        ->assertOk()
        ->assertSeeInOrder(['East Java', 'West Java']);
});
