<?php

namespace App\Admin\Resources;

use App\Admin\Column;
use App\Admin\Field;
use App\Admin\Resource;
use App\Models;

class TagResource extends Resource
{
    public static string $model = Models\Tag::class;

    public static string $key = 'tags';

    public static string $label = 'Tags';

    public static string $singular = 'Tag';

    public static string $icon = 'Tag';

    public static string $sort = 'name';

    public static string $direction = 'asc';

    public function fields(): array
    {
        return [
            Field::text('name')->required(),
            Field::slug(),
            Field::text('type')->placeholder('e.g. activity, budget, season'),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make('name')->sortable(),
            Column::make('slug'),
            Column::make('type', 'Type', 'badge')->sortable(),
            Column::make('articles_count', 'Articles', 'number')->value(fn ($tag) => $tag->articles()->count()),
        ];
    }
}
