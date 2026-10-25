<?php

use App\Models\Accommodation;
use App\Models\Article;
use App\Models\Destination;
use App\Models\Image;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    $this->place = Destination::with('region')->where('slug', 'yogyakarta')->firstOrFail();
});

test('places and articles use their written photo description, else their name', function () {
    expect($this->place->featured_image_alt)->toBe('Yogyakarta');

    $this->place->update(['image_alt' => 'Sultan\'s Palace courtyard in Yogyakarta']);
    expect($this->place->fresh()->featured_image_alt)->toBe('Sultan\'s Palace courtyard in Yogyakarta');

    $article = Article::published()->firstOrFail();
    expect($article->featured_image_alt)->toBe($article->title);
});

test('the description is used wherever the photo is shown', function () {
    $this->place->update(['image_alt' => 'Kraton pavilion with gamelan players', 'is_featured' => true]);

    $this->get("/destinations/{$this->place->region->slug}/yogyakarta")
        ->assertSee('alt="Kraton pavilion with gamelan players" fetchpriority="high"', false);
    $this->get("/destinations/{$this->place->region->slug}")->assertSee('alt="Kraton pavilion with gamelan players"', false);
    $this->get('/destinations')->assertSee('alt="Kraton pavilion with gamelan players"', false);
});

test('the page hero alt text has no HTML in it', function () {
    $this->get('/plan')->assertSee('alt="Plan Your Perfect Java Adventure"', false);
});

test('hotel photos are described by type and place, unless written in the admin', function () {
    $hotel = Accommodation::create(['name' => 'Hotel Tugu', 'slug' => 'hotel-tugu', 'type' => 'resort', 'star_rating' => 5, 'status' => 'published', 'is_active' => true,
        'latitude' => $this->place->latitude, 'longitude' => $this->place->longitude]);
    foreach (['Hotel Tugu', 'Hotel Tugu', 'Lobby with Javanese antiques'] as $i => $alt) {
        Image::create(['imageable_type' => Accommodation::class, 'imageable_id' => $hotel->id, 'path' => "https://pix.example/{$i}.jpg", 'alt_text' => $alt, 'order' => $i]);
    }

    $this->get('/hotels/hotel-tugu')
        ->assertSee('alt="Hotel Tugu, 5-star resort near Yogyakarta"', false)
        ->assertSee('alt="Hotel Tugu near Yogyakarta – photo 2"', false)
        ->assertSee('alt="Lobby with Javanese antiques"', false);
});

test('the photo description can be edited in the admin', function () {
    $this->actingAs(User::firstOrFail());

    $this->get("/admin/destinations/{$this->place->id}/edit")->assertOk();

    expect(collect(App\Admin\Admin::find('destinations')->fields())->pluck('name'))->toContain('image_alt')
        ->and(collect(App\Admin\Admin::find('articles')->fields())->pluck('name'))->toContain('image_alt');
});

test('the written descriptions are only applied to the photo they describe', function () {
    $migration = require database_path('migrations/2026_10_25_113436_add_image_alt_to_destinations_and_articles.php');
    $descriptions = (new ReflectionClassConstant($migration, 'DESTINATIONS'))->getValue();
    [$photo, $alt] = $descriptions['borobudur-temple'];

    $borobudur = Destination::where('slug', 'borobudur-temple')->first() ?? Destination::create(['region_id' => $this->place->region_id, 'name' => 'Borobudur Temple', 'slug' => 'borobudur-temple']);
    $other = Destination::where('slug', 'prambanan-temple')->first() ?? Destination::create(['region_id' => $this->place->region_id, 'name' => 'Prambanan Temple', 'slug' => 'prambanan-temple']);
    DB::table('destinations')->where('id', $borobudur->id)->update(['featured_image' => $photo, 'image_alt' => null]);
    DB::table('destinations')->where('id', $other->id)->update(['featured_image' => 'uploads/destinations/a-new-photo.jpg', 'image_alt' => null]);

    // Run the fill step again
    Schema::table('destinations', fn ($table) => $table->dropColumn('image_alt'));
    Schema::table('articles', fn ($table) => $table->dropColumn('image_alt'));
    $migration->up();

    expect($borobudur->fresh()->image_alt)->toBe($alt)
        ->and($other->fresh()->image_alt)->toBeNull();       // photo was replaced: not described
});
