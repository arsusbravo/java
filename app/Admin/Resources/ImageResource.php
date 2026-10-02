<?php

namespace App\Admin\Resources;

use App\Admin\Admin;
use App\Admin\Column;
use App\Admin\Field;
use App\Admin\Resource;
use App\Models;
use Illuminate\Database\Eloquent\Model;

class ImageResource extends Resource
{
    public static string $model = Models\Image::class;

    public static string $key = 'images';

    public static string $label = 'Images';

    public static string $singular = 'Image';

    public static string $icon = 'Image';

    public static string $group = 'Media & Stats';

    public static array $search = ['alt_text', 'title', 'path'];

    public static array $with = ['imageable'];

    public const OWNERS = Admin::LISTINGS + ['article' => Models\Article::class];

    public function fields(): array
    {
        return [
            Field::morphTo('imageable', self::OWNERS, 'Belongs To')->required()->wide(),
            Field::image('path', 'Image')->required(),
            Field::text('alt_text', 'Alt Text')->help('Describe the image for screen readers and SEO.'),
            Field::text('title'),
            Field::number('order')->default(0),
            Field::boolean('is_featured', 'Featured'),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make('path', 'Image', 'image'),
            Column::make('owner', 'Belongs To')->value(fn ($image) => $image->imageable
                ? class_basename($image->imageable_type) . ': ' . ($image->imageable->name ?? $image->imageable->title)
                : '(deleted)'),
            Column::make('alt_text', 'Alt Text'),
            Column::make('is_featured', 'Featured', 'boolean')->sortable(),
            Column::make('order', 'Order', 'number')->sortable(),
            Column::make('created_at', 'Uploaded', 'date')->sortable(),
        ];
    }

    public function filters(): array
    {
        return ['imageable_type' => collect(self::OWNERS)->mapWithKeys(fn ($class, $alias) => [$class => str($alias)->headline()->toString()])->all()];
    }

    public function title(Model $model): string
    {
        return $model->title ?: $model->alt_text ?: 'Image #' . $model->id;
    }
}
