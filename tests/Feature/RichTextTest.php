<?php

use App\Models\Article;
use App\Models\Destination;
use App\Models\User;
use App\Support\RichText;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    $this->actingAs(User::firstOrFail());
});

test('editor formatting is kept', function (string $html) {
    expect(RichText::sanitize($html))->toBe($html);
})->with([
    'headings and alignment' => '<h2 style="text-align: center">Title</h2><h3>Sub</h3>',
    'marks' => '<p><strong>b</strong> <em>i</em> <u>u</u> <s>s</s> <code>c</code> <sub>2</sub> <sup>3</sup></p>',
    'colour and highlight' => '<p><span style="color: #e67e22">o</span> <mark data-color="#fef08a" style="background-color: #fef08a">h</mark></p>',
    'lists and quote' => '<ul><li><p>a</p></li></ul><ol><li><p>b</p></li></ol><blockquote><p>q</p></blockquote>',
    'link' => '<p><a href="https://www.agoda.com" rel="noopener noreferrer nofollow">Agoda</a></p>',
    'uploaded image' => '<img src="/storage/uploads/editor/a.jpg" alt="Temple" />',
    'table' => '<table><tbody><tr><th colspan="1" rowspan="1"><p>H</p></th></tr><tr><td colspan="2" rowspan="1"><p>C</p></td></tr></tbody></table>',
]);

test('dangerous markup is removed', function (string $html, string $expected) {
    expect(RichText::sanitize($html))->toBe($expected);
})->with([
    'script' => ['<p>Hi<script>alert(1)</script></p>', '<p>Hi</p>'],
    'event handler' => ['<p onclick="alert(1)">Hi</p>', '<p>Hi</p>'],
    'javascript link' => ['<p><a href="javascript:alert(1)">x</a></p>', '<p><a>x</a></p>'],
    'foreign iframe' => ['<p>a</p><iframe src="https://evil.example/x"></iframe>', '<p>a</p>'],
    'plain http youtube' => ['<p>a</p><iframe src="http://www.youtube.com/embed/x"></iframe>', '<p>a</p>'],
    'unsafe css' => ['<p style="color: red; position: fixed; background-image: url(x)">a</p>', '<p style="color: red">a</p>'],
]);

test('youtube embeds are allowed and lazy-loaded', function () {
    expect(RichText::sanitize('<div data-youtube-video=""><iframe src="https://www.youtube-nocookie.com/embed/abc" width="640" height="360"></iframe></div>'))
        ->toContain('src="https://www.youtube-nocookie.com/embed/abc"')
        ->toContain('loading="lazy"');
});

test('plain text becomes paragraphs and excerpts drop the markup', function () {
    expect(RichText::toHtml("First line\nsecond line\n\nNext < 5 & more"))
        ->toBe("<p>First line<br>\nsecond line</p><p>Next &lt; 5 &amp; more</p>")
        ->and(RichText::sanitize('<p></p>'))->toBeNull()
        ->and(RichText::excerpt('<h2>Title</h2><p>Hello <b>world</b> &amp; friends</p><ul><li>One</li><li>Two</li></ul>', 200))
        ->toBe('Title Hello world & friends One Two');
});

test('destination descriptions are edited as rich text and cleaned on save', function () {
    $destination = Destination::where('slug', 'yogyakarta')->firstOrFail();

    // Existing plain text opens in the editor as paragraphs
    $this->get("/admin/destinations/{$destination->id}/edit")
        ->assertInertia(fn (Assert $page) => $page
            ->where('fields', fn ($fields) => collect($fields)->firstWhere('name', 'description')['type'] === 'html')
            ->where('values.description', RichText::toHtml($destination->description)));

    $this->put("/admin/destinations/{$destination->id}", [
        'name' => $destination->name,
        'region_id' => $destination->region_id,
        'description' => '<h2>Highlights</h2><p><strong>Borobudur</strong> at dawn<script>alert(1)</script></p>',
    ])->assertSessionHasNoErrors();

    expect($destination->fresh()->description)->toBe('<h2>Highlights</h2><p><strong>Borobudur</strong> at dawn</p>');
});

test('the destination page renders rich text and cards show clean excerpts', function () {
    $destination = Destination::with('region')->where('slug', 'yogyakarta')->firstOrFail();
    $destination->update(['description' => '<h2>Highlights</h2><p><strong>Borobudur</strong> at dawn</p>']);

    $this->get("/destinations/{$destination->region->slug}/yogyakarta")
        ->assertOk()
        ->assertSee('<h2>Highlights</h2><p><strong>Borobudur</strong> at dawn</p>', false);

    $this->get("/destinations/{$destination->region->slug}")
        ->assertOk()
        ->assertSee('Highlights Borobudur at dawn')
        ->assertDontSee('&lt;h2&gt;', false);

    $this->get('/destinations')->assertOk()->assertDontSee('&lt;strong&gt;', false);
});

test('article content is sanitized when shown, even if stored unsafely', function () {
    $article = Article::published()->firstOrFail();
    $article->update(['content' => '<h2>Safe</h2><img src="x" onerror="alert(1)"><script>alert(1)</script>']);

    $this->get($article->url())
        ->assertOk()
        ->assertSee('<h2>Safe</h2>', false)
        ->assertDontSee('onerror', false)
        ->assertDontSee('<script>alert(1)</script>', false);
});

test('editor images are uploaded to the public disk', function () {
    Storage::fake('public');

    $response = $this->postJson('/admin/editor-images', ['image' => UploadedFile::fake()->image('temple.jpg')])
        ->assertOk();

    $url = $response->json('url');
    expect($url)->toStartWith('/storage/uploads/editor/');
    Storage::disk('public')->assertExists(str_replace('/storage/', '', $url));

    $this->postJson('/admin/editor-images', ['image' => UploadedFile::fake()->create('evil.php', 1)])
        ->assertUnprocessable();

    auth()->logout();
    $this->postJson('/admin/editor-images', ['image' => UploadedFile::fake()->image('x.jpg')])->assertUnauthorized();
});
