<?php

use App\Models\Article;
use App\Models\Destination;
use Database\Seeders\DatabaseSeeder;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    $this->destination = Destination::with('region')->where('slug', 'yogyakarta')->firstOrFail();
});

function destinationArticle(array $attributes): Article
{
    $article = Article::create([
        'title' => 'Article',
        'slug' => 'article-' . Str::random(6),
        'article_type' => 'tips',
        'status' => 'published',
        'published_at' => now()->subHour(),
        'content' => '<p>Take the free shuttle bus between the gates.</p>',
        ...$attributes,
    ]);
    test()->destination->articles()->attach($article);

    return $article;
}

test('guides and tips linked to a destination are shown on its page with their type', function () {
    destinationArticle(['title' => 'Malioboro on a budget', 'article_type' => 'tips']);
    destinationArticle(['title' => 'Two days in Yogyakarta', 'article_type' => 'itinerary', 'excerpt' => 'A relaxed plan.']);

    $this->get("/destinations/{$this->destination->region->slug}/yogyakarta")
        ->assertOk()
        ->assertSee('Travel Guides & Tips', false)
        ->assertSee('Malioboro on a budget')
        ->assertSee('Tips')
        ->assertSee('Take the free shuttle bus between the gates.')  // no excerpt: start of the content
        ->assertSee('Two days in Yogyakarta')
        ->assertSee('Itinerary');
});

test('drafts and scheduled articles are not shown yet', function () {
    destinationArticle(['title' => 'Draft tips', 'status' => 'draft']);
    destinationArticle(['title' => 'Next week tips', 'published_at' => now()->addWeek()]);

    $this->get("/destinations/{$this->destination->region->slug}/yogyakarta")
        ->assertOk()
        ->assertDontSee('Draft tips')
        ->assertDontSee('Next week tips');
});

test('publish dates typed in the admin are in the site timezone', function () {
    config(['app.timezone' => 'Europe/Amsterdam']);
    date_default_timezone_set('Europe/Amsterdam');

    // A minute ago on the admin's clock must already be live
    $article = destinationArticle(['title' => 'Just published', 'published_at' => now()->subMinute()->format('Y-m-d H:i')]);

    expect(Article::published()->whereKey($article->id)->exists())->toBeTrue();
    $this->get("/destinations/{$this->destination->region->slug}/yogyakarta")->assertSee('Just published');
});
