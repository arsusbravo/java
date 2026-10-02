<?php

use App\Models\Accommodation;
use App\Models\Article;
use App\Models\Destination;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

test('seeding keeps featured flags and ordering on destinations', function () {
    expect(Destination::where('is_featured', true)->count())->toBe(3);
    expect(Destination::where('slug', 'surabaya')->value('order'))->toBe(2);
});

test('public pages render', function (string $url) {
    $this->get($url)->assertOk();
})->with([
    '/',
    '/about',
    '/destinations',
    '/destinations?search=',
    '/destinations?search=temple',
    '/destinations/east-java',
    '/destinations/east-java/mount-bromo',
    '/articles',
    '/guides',
    '/plan',
]);

test('published article page renders', function () {
    $article = Article::published()->firstOrFail();

    $this->get($article->url())->assertOk()->assertSee($article->title);
});

test('region filter lists non-featured destinations in that region', function () {
    $this->get('/destinations?region=west-java')
        ->assertOk()
        ->assertSee('Jakarta')
        ->assertSee('Bandung');
});

test('destination page increments its view count', function () {
    $this->get('/destinations/east-java/mount-bromo')->assertOk();

    expect(Destination::where('slug', 'mount-bromo')->value('views_count'))->toBe(1);
});

test('draft and scheduled articles are hidden', function (array $attributes) {
    $article = Article::create([
        'title' => 'Hidden Article',
        'slug' => 'hidden-article',
        'content' => 'Not yet.',
        'article_type' => 'guide',
        ...$attributes,
    ]);

    $this->get($article->url())->assertNotFound();
    $this->get('/')->assertDontSee('Hidden Article');
    $this->get('/articles')->assertDontSee('Hidden Article');
})->with([
    'draft' => [['status' => 'draft', 'published_at' => now()->subDay()]],
    'scheduled' => [['status' => 'published', 'published_at' => now()->addWeek()]],
]);

test('featured image url falls back through column, images, then placeholder', function () {
    Storage::fake('public');

    $destination = Destination::where('slug', 'mount-bromo')->firstOrFail();
    expect($destination->featured_image_url)->toBe(asset('images/sunrise.png'));

    // Column points at a missing file: fall through to the uploaded image
    $destination->update(['featured_image' => 'images/missing.jpg']);
    Storage::disk('public')->put('uploads/bromo.jpg', 'fake');
    $destination->images()->create(['path' => 'uploads/bromo.jpg', 'is_featured' => true]);
    expect($destination->fresh()->featured_image_url)->toBe(asset('storage/uploads/bromo.jpg'));

    // Uploaded file missing too: placeholder
    Storage::disk('public')->delete('uploads/bromo.jpg');
    expect($destination->fresh()->featured_image_url)->toBe(asset('images/placeholders/destination.svg'));

    expect((new App\Models\Tour)->featured_image_url)->toBe(asset('images/placeholders/destination.svg'));
    expect((new Article)->featured_image_url)->toBe(asset('images/placeholders/article.svg'));
});

test('image_url returns existing images and placeholders for missing ones', function () {
    expect(image_url('images/sunrise.png'))->toBe(asset('images/sunrise.png'))
        ->and(image_url('/images/sunrise.png'))->toBe(asset('images/sunrise.png'))
        ->and(image_url('https://cdn.example.com/a.jpg'))->toBe('https://cdn.example.com/a.jpg')
        ->and(image_url('images/nope.jpg', 'article'))->toBe(asset('images/placeholders/article.svg'))
        ->and(image_url(null, 'hero'))->toBe(asset('images/placeholders/hero.svg'));

    foreach (['destination', 'article', 'region', 'hero'] as $placeholder) {
        expect(public_path("images/placeholders/{$placeholder}.svg"))->toBeFile();
    }
});

test('pages render placeholders instead of missing images', function () {
    Destination::where('slug', 'surabaya')->update(['featured_image' => 'images/deleted.jpg']);

    $this->get('/destinations/east-java')
        ->assertSee(asset('images/placeholders/region.svg'))
        ->assertSee(asset('images/placeholders/destination.svg'))
        ->assertDontSee('images/deleted.jpg');
});

test('accommodation destinations relation uses the destination_accommodation pivot', function () {
    $destination = Destination::firstOrFail();
    $accommodation = Accommodation::create(['name' => 'Test Hotel', 'slug' => 'test-hotel', 'type' => 'hotel']);

    $accommodation->destinations()->attach($destination, ['is_primary' => true]);

    expect($accommodation->destinations()->pluck('destinations.id')->all())->toBe([$destination->id]);
    expect($destination->accommodations()->pluck('accommodations.id')->all())->toBe([$accommodation->id]);
});

test('admin destination update saves all form fields', function () {
    $destination = Destination::where('slug', 'jakarta')->firstOrFail();

    $this->actingAs(User::firstOrFail())
        ->put(route('admin.places.destinations.update', $destination), [
            'region_id' => $destination->region_id,
            'name' => 'Jakarta',
            'slug' => 'jakarta',
            'featured_image' => 'images/sunrise.png',
            'latitude' => -6.2,
            'longitude' => 106.8,
            'best_time_to_visit' => 'May to September',
            'average_cost' => '$50/day',
            'is_featured' => true,
        ])
        ->assertRedirect();

    $destination->refresh();
    expect($destination->is_featured)->toBeTrue()
        ->and($destination->featured_image)->toBe('images/sunrise.png')
        ->and($destination->best_time_to_visit)->toBe('May to September')
        ->and($destination->average_cost)->toBe('$50/day');
});

test('articles can be filtered by destination', function () {
    $destination = Destination::where('slug', 'mount-bromo')->firstOrFail();
    [$included, $excluded] = Article::published()->take(2)->get();
    $included->destinations()->attach($destination);

    $this->get('/articles?destination=mount-bromo')
        ->assertOk()
        ->assertSee($included->title)
        ->assertDontSee($excluded->title);
});
