<?php

use App\Admin\Admin;
use App\Models\Accommodation;
use App\Models\AffiliateClick;
use App\Models\Article;
use App\Models\Destination;
use App\Models\Image;
use App\Models\Restaurant;
use App\Models\Review;
use App\Models\Tag;
use App\Models\Tour;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    Storage::fake('public');

    $this->admin = User::firstOrFail();
    $this->actingAs($this->admin);

    // One of each listing so every list and edit page has a row to render
    $destination = Destination::firstOrFail();
    $hotel = Accommodation::create(['name' => 'Hotel Majapahit', 'slug' => 'hotel-majapahit', 'type' => 'hotel', 'amenities' => ['Pool']]);
    $tour = Tour::create(['name' => 'Bromo Sunrise Jeep', 'slug' => 'bromo-sunrise-jeep', 'type' => 'day_tour', 'included' => ['Jeep']]);
    $restaurant = Restaurant::create(['name' => 'Warung Sate', 'slug' => 'warung-sate', 'cuisine_type' => ['Javanese']]);
    $hotel->destinations()->attach($destination);
    Tag::create(['name' => 'Sunrise', 'slug' => 'sunrise']);
    Image::create(['imageable_type' => Destination::class, 'imageable_id' => $destination->id, 'path' => 'images/sunrise.png']);
    AffiliateClick::create(['clickable_type' => Tour::class, 'clickable_id' => $tour->id, 'ip_address' => '127.0.0.1', 'clicked_at' => now()]);
});

test('guests cannot reach the admin', function () {
    auth()->logout();

    $this->get('/admin/articles')->assertRedirect('/login');
    $this->post('/admin/tags', ['name' => 'X'])->assertRedirect('/login');
});

test('every resource list renders with search and filters', function (string $key) {
    $resource = Admin::find($key);
    $filters = collect($resource->filters())->map(fn ($options) => array_key_first($options))->all();

    $this->get("/admin/{$key}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Resources/Index')
            ->where('resource.key', $key)
            ->has('records.data'));

    $this->get("/admin/{$key}?" . http_build_query(['search' => 'a', 'sort' => 'id', 'direction' => 'asc', ...$filters]))
        ->assertOk();
})->with(fn () => Admin::keys());

test('every editable resource has working create and edit forms', function (string $key) {
    $resource = Admin::find($key);
    if (! $resource::$editable) {
        $this->get("/admin/{$key}/create")->assertNotFound();

        return;
    }

    $this->get("/admin/{$key}/create")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Admin/Resources/Form')->where('record', null));

    $record = $resource::$model::firstOrFail();
    $this->get("/admin/{$key}/{$record->id}/edit")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('record.id', $record->id)->has('values'));
})->with(fn () => Admin::keys());

test('unknown resources and read-only writes are rejected', function () {
    $this->get('/admin/secrets')->assertNotFound();
    $this->post('/admin/affiliate-clicks', [])->assertNotFound();
    $this->get('/admin/affiliate-clicks/' . AffiliateClick::first()->id . '/edit')->assertNotFound();
});

test('creating an article saves relations, uploads and a generated slug', function () {
    $destination = Destination::where('slug', 'mount-bromo')->firstOrFail();
    $tag = Tag::firstOrFail();

    $this->post('/admin/articles', [
        'title' => 'Ijen Blue Fire Guide',
        'slug' => '',
        'article_type' => 'guide',
        'status' => 'published',
        'is_featured' => '1',
        'content' => '<p>Hike at night.</p>',
        'destinations' => [$destination->id],
        'tags' => [$tag->id],
        'featured_image_upload' => UploadedFile::fake()->image('ijen.jpg'),
        'images_new' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')],
    ])->assertRedirect()->assertSessionHas('success');

    $article = Article::where('title', 'Ijen Blue Fire Guide')->firstOrFail();
    expect($article->slug)->toBe('ijen-blue-fire-guide')
        ->and($article->is_featured)->toBeTrue()
        ->and($article->author_id)->toBe($this->admin->id)
        ->and($article->published_at)->not->toBeNull()
        ->and($article->destinations->pluck('id')->all())->toBe([$destination->id])
        ->and($article->tags->pluck('id')->all())->toBe([$tag->id])
        ->and($article->images)->toHaveCount(2);

    Storage::disk('public')->assertExists($article->featured_image);
    expect($article->featured_image)->toStartWith('uploads/articles/');

    // Publicly visible straight away, since publishing without a date means now
    auth()->logout();
    $this->get($article->url())->assertOk()->assertSee('Ijen Blue Fire Guide');
});

test('validation errors are returned for invalid input', function () {
    $this->from('/admin/articles/create')
        ->post('/admin/articles', ['title' => '', 'slug' => 'ultimate-guide-mount-bromo-sunrise', 'article_type' => 'poem'])
        ->assertRedirect('/admin/articles/create')
        ->assertSessionHasErrors(['title', 'slug', 'article_type', 'status']);
});

test('updating replaces an uploaded image and deletes the old file', function () {
    $region = App\Models\Region::firstOrFail();
    $fields = ['name' => $region->name, 'slug' => $region->slug];

    $this->put("/admin/regions/{$region->id}", [...$fields, 'image_upload' => UploadedFile::fake()->image('one.jpg')]);
    $first = $region->fresh()->image;
    Storage::disk('public')->assertExists($first);

    $this->put("/admin/regions/{$region->id}", [...$fields, 'image_upload' => UploadedFile::fake()->image('two.jpg')]);
    $second = $region->fresh()->image;
    Storage::disk('public')->assertMissing($first);
    Storage::disk('public')->assertExists($second);

    $this->put("/admin/regions/{$region->id}", [...$fields, 'image_remove' => '1']);
    expect($region->fresh()->image)->toBeNull();
    Storage::disk('public')->assertMissing($second);
});

test('seeded images under public are never deleted', function () {
    $destination = Destination::where('slug', 'mount-bromo')->firstOrFail();

    $this->put("/admin/destinations/{$destination->id}", [
        'name' => $destination->name,
        'region_id' => $destination->region_id,
        'featured_image_remove' => '1',
    ])->assertSessionHasNoErrors();

    expect($destination->fresh()->featured_image)->toBeNull();
    expect(public_path('images/sunrise.png'))->toBeFile();
});

test('gallery uploads, featured choice and removals are applied', function () {
    $hotel = Accommodation::firstOrFail();
    $fields = ['name' => $hotel->name, 'slug' => $hotel->slug, 'type' => 'hotel', 'status' => 'draft', 'duration_unit' => 'hours'];

    $this->put("/admin/accommodations/{$hotel->id}", [
        ...$fields,
        'images_new' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')],
    ])->assertSessionHasNoErrors();

    [$a, $b] = $hotel->images()->orderBy('order')->get();
    expect([$a->order, $b->order])->toBe([1, 2]);

    $this->put("/admin/accommodations/{$hotel->id}", [
        ...$fields,
        'images_remove' => [$a->id],
        'images_featured' => $b->id,
    ])->assertSessionHasNoErrors();

    Storage::disk('public')->assertMissing($a->path);
    expect($hotel->images()->pluck('id')->all())->toBe([$b->id])
        ->and($b->fresh()->is_featured)->toBeTrue();
});

test('list fields are stored as arrays and empty relations are cleared', function () {
    $tour = Tour::firstOrFail();
    $tour->tags()->attach(Tag::first());

    $this->put("/admin/tours/{$tour->id}", [
        'name' => $tour->name,
        'slug' => $tour->slug,
        'type' => 'day_tour',
        'status' => 'published',
        'duration_unit' => 'hours',
        'included' => "Jeep\r\n\r\n  Breakfast  \nGuide",
        'not_included' => '',
    ])->assertSessionHasNoErrors();

    $tour->refresh();
    expect($tour->included)->toBe(['Jeep', 'Breakfast', 'Guide'])
        ->and($tour->not_included)->toBe([])
        ->and($tour->tags)->toHaveCount(0);
});

test('reviews can be created for any listing and moderated with actions', function () {
    $restaurant = Restaurant::firstOrFail();

    $this->post('/admin/reviews', [
        'reviewable' => "restaurant:{$restaurant->id}",
        'user_name' => 'Dewi',
        'user_email' => 'dewi@example.com',
        'rating' => 4,
        'status' => 'pending',
        'comment' => 'Great sate.',
    ])->assertSessionHasNoErrors();

    $review = Review::where('user_name', 'Dewi')->firstOrFail();
    expect($review->reviewable->is($restaurant))->toBeTrue();

    $this->post("/admin/reviews/{$review->id}/actions/approve")->assertRedirect();
    expect($review->fresh()->status)->toBe('approved');

    $this->post("/admin/reviews/{$review->id}/actions/delete-everything")->assertNotFound();
    $this->post('/admin/reviews', ['reviewable' => 'user:1'])->assertSessionHasErrors('reviewable');
});

test('deleting a listing removes its gallery files and tag links', function () {
    $hotel = Accommodation::firstOrFail();
    $hotel->tags()->attach(Tag::first());
    $this->put("/admin/accommodations/{$hotel->id}", [
        'name' => $hotel->name, 'type' => 'hotel', 'status' => 'draft',
        'images_new' => [UploadedFile::fake()->image('a.jpg')],
    ]);
    $path = $hotel->images()->value('path');

    $this->delete("/admin/accommodations/{$hotel->id}")->assertRedirect('/admin/accommodations');

    expect(Accommodation::find($hotel->id))->toBeNull()
        ->and(Image::where('imageable_type', Accommodation::class)->count())->toBe(0);
    Storage::disk('public')->assertMissing($path);
    $this->assertDatabaseMissing('taggables', ['taggable_type' => Accommodation::class, 'taggable_id' => $hotel->id]);
});

test('users are verified on creation, keep passwords when left blank, and cannot delete themselves', function () {
    $this->post('/admin/users', ['name' => 'Editor', 'email' => 'editor@example.com', 'password' => 'secret-pass'])
        ->assertSessionHasNoErrors();

    $editor = User::where('email', 'editor@example.com')->firstOrFail();
    expect($editor->email_verified_at)->not->toBeNull();
    $hash = $editor->password;

    $this->put("/admin/users/{$editor->id}", ['name' => 'Editor 2', 'email' => 'editor@example.com', 'password' => ''])
        ->assertSessionHasNoErrors();
    expect($editor->fresh()->password)->toBe($hash);

    $this->delete("/admin/users/{$this->admin->id}")->assertSessionHas('error');
    expect(User::find($this->admin->id))->not->toBeNull();
});

test('dashboard shows stats and navigation is shared', function () {
    Review::create(['reviewable_type' => Tour::class, 'reviewable_id' => Tour::first()->id, 'user_name' => 'Budi', 'user_email' => 'b@example.com', 'rating' => 3, 'status' => 'pending']);

    $this->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('stats.pendingReviews', 1)
            ->has('counts', count(Admin::RESOURCES))
            ->has('adminNav', 5));
});

test('large relations are searched on the server instead of sent with the form', function () {
    $rows = collect(range(1, App\Admin\Field::MAX_INLINE_CHOICES + 5))->map(fn ($i) => [
        'name' => "Bulk Hotel {$i}", 'slug' => "bulk-hotel-{$i}", 'type' => 'hotel',
        'created_at' => now(), 'updated_at' => now(),
    ]);
    DB::table('accommodations')->insert($rows->all());

    $article = Article::firstOrFail();
    $picked = Accommodation::where('slug', 'bulk-hotel-7')->firstOrFail();
    $article->accommodations()->attach($picked);

    // The form only carries the current selection, flagged for searching
    $this->get("/admin/articles/{$article->id}/edit")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('fields', fn ($fields) => collect($fields)->firstWhere('name', 'accommodations')['remote'] === true
                && collect($fields)->firstWhere('name', 'accommodations')['choices'] === [['value' => $picked->id, 'label' => 'Bulk Hotel 7']])
            ->where('fields', fn ($fields) => collect($fields)->firstWhere('name', 'destinations')['remote'] === false));

    $this->getJson('/admin/articles/fields/accommodations/options?search=Hotel 20')
        ->assertOk()
        ->assertJsonFragment(['label' => 'Bulk Hotel 20'])
        ->assertJsonFragment(['label' => 'Bulk Hotel 200'])
        ->assertJsonMissing(['label' => 'Bulk Hotel 7']);

    expect($this->getJson('/admin/articles/fields/accommodations/options')->json())->toHaveCount(50);

    // Polymorphic owners are searched across every listing type
    $this->getJson('/admin/reviews/fields/reviewable/options?search=Bulk Hotel 3')
        ->assertOk()
        ->assertJsonFragment(['label' => 'Bulk Hotel 3', 'group' => 'Accommodation']);

    $this->getJson('/admin/articles/fields/title/options')->assertNotFound();
    $this->getJson('/admin/affiliate-clicks/fields/clickable/options')->assertNotFound();
});

test('slugs and emails are limited to 191 characters, the longest that old MariaDB can index', function () {
    $this->post('/admin/tags', ['name' => 'Long', 'slug' => str_repeat('a', 192)])->assertSessionHasErrors('slug');
    $this->post('/admin/tags', ['name' => 'Long', 'slug' => str_repeat('a', 191)])->assertSessionHasNoErrors();

    $this->post('/admin/users', ['name' => 'X', 'email' => str_repeat('a', 180) . '@example.com', 'password' => 'secret-pass'])
        ->assertSessionHasErrors('email');
});

test('featured can be switched on and off from the destinations list', function () {
    $destination = Destination::where('slug', 'jakarta')->firstOrFail();
    expect($destination->is_featured)->toBeFalse();

    $this->get('/admin/destinations')->assertInertia(fn (Assert $page) => $page
        ->where('columns', fn ($columns) => collect($columns)->firstWhere('name', 'is_featured')['toggleable'] === true));

    $this->post("/admin/destinations/{$destination->id}/toggle/is_featured")
        ->assertRedirect()
        ->assertSessionHas('success', 'Jakarta: Featured on.');
    expect($destination->fresh()->is_featured)->toBeTrue();

    $this->post("/admin/destinations/{$destination->id}/toggle/is_featured")->assertSessionHas('success', 'Jakarta: Featured off.');
    expect($destination->fresh()->is_featured)->toBeFalse();
});

test('only columns marked toggleable can be switched', function () {
    $destination = Destination::firstOrFail();

    $this->post("/admin/destinations/{$destination->id}/toggle/name")->assertNotFound();          // not a yes/no column
    $this->post("/admin/destinations/{$destination->id}/toggle/views_count")->assertNotFound();
    $this->post('/admin/articles/' . Article::first()->id . '/toggle/is_featured')->assertNotFound(); // not enabled there
    $this->post('/admin/destinations/999999/toggle/is_featured')->assertNotFound();

    auth()->logout();
    $this->post("/admin/destinations/{$destination->id}/toggle/is_featured")->assertRedirect('/login');
});
