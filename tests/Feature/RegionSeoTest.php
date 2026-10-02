<?php

use App\Models\Destination;
use App\Models\Region;
use App\Models\User;
use App\Support\Seo;
use Database\Seeders\DatabaseSeeder;

const JAKARTA = 'DKI Jakarta, officially a special capital province rather than a single city, serves as the vibrant political, economic, and cultural heart of Indonesia. Spanning five administrative cities and the offshore Thousand Islands, this sprawling metropolis grew from a trading port.';

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    $this->actingAs(User::firstOrFail());
});

test('meta descriptions fit in 160 characters without cutting words', function () {
    // Ends on the last full sentence that fits
    expect(Seo::description(JAKARTA))
        ->toBe('DKI Jakarta, officially a special capital province rather than a single city, serves as the vibrant political, economic, and cultural heart of Indonesia.');

    // No sentence end that fits: whole words plus an ellipsis, 160 at most
    $long = trim(str_repeat('volcano sunrise ', 20));
    $cut = Seo::description($long);
    expect(mb_strlen($cut))->toBeLessThanOrEqual(160)
        ->and($cut)->toEndWith('…')
        ->and(str_replace('…', '', $cut))->toMatch('/^(volcano sunrise ?)+(volcano)?$/');

    // Short text is kept whole, with markup and extra spaces removed
    expect(Seo::description("<p>Temples &amp;\n  beaches.</p>"))->toBe('Temples & beaches.')
        ->and(Seo::description(''))->toBeNull()
        ->and(Seo::title('Madura'))->toBe('Madura Travel Guide | Java Sunrise');
});

test('empty meta fields are generated and stored when a region is created in the admin', function () {
    $this->post('/admin/regions', ['name' => 'DKI Jakarta', 'description' => JAKARTA])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('regions', [
        'slug' => 'dki-jakarta',
        'meta_title' => 'DKI Jakarta Travel Guide | Java Sunrise',
        'meta_description' => Seo::description(JAKARTA),
    ]);
});

test('the places page generates meta the same way', function () {
    $this->post('/admin/places/regions', ['name' => 'Madura', 'slug' => 'madura', 'description' => JAKARTA])
        ->assertSessionHasNoErrors();

    $region = Region::where('slug', 'madura')->firstOrFail();
    expect($region->meta_title)->toBe('Madura Travel Guide | Java Sunrise')
        ->and($region->meta_description)->toBe(Seo::description(JAKARTA));

    $this->put("/admin/places/regions/{$region->id}", [
        'name' => 'Madura', 'slug' => 'madura', 'meta_description' => str_repeat('x', 161),
    ])->assertSessionHasErrors('meta_description');
});

test('generated meta follows edits, while typed meta is kept', function () {
    $region = Region::create(['name' => 'Madura', 'slug' => 'madura', 'description' => 'Bull races and salt fields.']);
    expect($region->meta_description)->toBe('Bull races and salt fields.');

    // The admin form sends the stored (generated) values back with the edit
    $this->put("/admin/regions/{$region->id}", [
        'name' => 'Madura Island',
        'slug' => 'madura',
        'description' => 'Bull races, batik and salt fields.',
        'meta_title' => $region->meta_title,
        'meta_description' => $region->meta_description,
    ])->assertSessionHasNoErrors();

    $region->refresh();
    expect($region->meta_title)->toBe('Madura Island Travel Guide | Java Sunrise')
        ->and($region->meta_description)->toBe('Bull races, batik and salt fields.');

    // A hand-written value survives later description edits
    $region->update(['meta_description' => 'Handwritten for search.']);
    $region->update(['description' => 'Completely new text.']);
    expect($region->fresh()->meta_description)->toBe('Handwritten for search.');

    // Clearing the field regenerates it
    $region->update(['meta_description' => '']);
    expect($region->fresh()->meta_description)->toBe('Completely new text.');
});

test('seeded hand-written meta is left untouched', function () {
    expect(Region::where('slug', 'west-java')->value('meta_title'))
        ->toBe('Explore West Java - Destinations, Tours & Travel Guide');
});

test('region and destination pages print the title and meta description', function () {
    $region = Region::create(['name' => 'DKI Jakarta', 'slug' => 'dki-jakarta', 'description' => JAKARTA]);

    $this->get('/destinations/dki-jakarta')
        ->assertOk()
        ->assertSee('<title>DKI Jakarta Travel Guide | Java Sunrise</title>', false)
        ->assertSee('<meta name="description" content="' . e($region->meta_description) . '">', false);

    $destination = Destination::with('region')->where('slug', 'yogyakarta')->firstOrFail();
    $html = $this->get("/destinations/{$destination->region->slug}/yogyakarta")->assertOk()->getContent();
    preg_match('/<meta name="description" content="([^"]*)">/', $html, $meta);
    expect(mb_strlen(html_entity_decode($meta[1])))->toBeLessThanOrEqual(160);
});
