<?php

namespace App\Admin\Resources;

use App\Admin\Admin;
use App\Admin\Column;
use App\Admin\Field;
use App\Admin\Resource;
use App\Models;
use Illuminate\Database\Eloquent\Model;

class ArticleResource extends Resource
{
    public static string $model = Models\Article::class;

    public static string $key = 'articles';

    public static string $label = 'Articles';

    public static string $singular = 'Article';

    public static string $icon = 'Newspaper';

    public static array $search = ['title', 'excerpt'];

    public static array $with = ['author'];

    public static string $sort = 'published_at';

    public const TYPES = ['guide' => 'Guide', 'itinerary' => 'Itinerary', 'tips' => 'Tips', 'review' => 'Review', 'news' => 'News'];

    public function fields(): array
    {
        return [
            Field::text('title')->required()->wide(),
            Field::slug('slug', 'title'),
            Field::select('article_type', self::TYPES, 'Type')->required()->default('guide'),
            Field::select('status', Admin::STATUSES)->required()->default('draft'),
            Field::datetime('published_at', 'Publish Date')
                ->help('In ' . config('app.timezone') . ' time. Articles are visible once published and this date has passed.'),
            Field::belongsTo('author_id', Models\User::class, 'Author')->default(auth()->id()),
            Field::boolean('is_featured', 'Featured'),
            Field::textarea('excerpt'),
            Field::html('content'),
            Field::image('featured_image'),
            Field::gallery(),
            Field::belongsToMany('destinations', Models\Destination::class),
            Field::belongsToMany('accommodations', Models\Accommodation::class),
            Field::belongsToMany('tours', Models\Tour::class),
            Field::belongsToMany('restaurants', Models\Restaurant::class),
            Field::belongsToMany('tags', Models\Tag::class),
            Field::text('meta_title', 'Meta Title')->wide(),
            Field::textarea('meta_description', 'Meta Description'),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make('featured_image', 'Image', 'image'),
            Column::make('title')->sortable(),
            Column::make('article_type', 'Type', 'badge')->sortable(),
            Column::make('status', 'Status', 'badge')->sortable(),
            Column::make('is_featured', 'Featured', 'boolean')->sortable()->toggleable(),
            Column::make('author.name', 'Author'),
            Column::make('views_count', 'Views', 'number')->sortable(),
            Column::make('published_at', 'Published', 'date')->sortable(),
        ];
    }

    public function filters(): array
    {
        return ['status' => Admin::STATUSES, 'article_type' => self::TYPES];
    }

    public function actions(): array
    {
        return [
            'publish' => ['label' => 'Publish', 'attributes' => ['status' => 'published']],
            'unpublish' => ['label' => 'Unpublish', 'attributes' => ['status' => 'draft']],
        ];
    }

    public function publicUrl(Model $model): ?string
    {
        return $model->status === 'published' ? $model->url() : null;
    }

    public function saved(Model $model, bool $created): void
    {
        // Publishing without a date means "now"
        if ($model->status === 'published' && ! $model->published_at) {
            $model->update(['published_at' => now()]);
        }
    }
}
