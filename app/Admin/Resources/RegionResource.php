<?php

namespace App\Admin\Resources;

use App\Admin\Column;
use App\Admin\Field;
use App\Admin\Resource;
use App\Models;
use App\Support\Seo;
use Illuminate\Database\Eloquent\Model;

class RegionResource extends Resource
{
    public static string $model = Models\Region::class;

    public static string $key = 'regions';

    public static string $label = 'Regions';

    public static string $singular = 'Region';

    public static string $icon = 'Map';

    public static string $group = 'Places';

    // Change the order on the Places page
    public static string $sort = 'sort_order';

    public static string $direction = 'asc';

    public function fields(): array
    {
        return [
            Field::text('name')->required(),
            Field::slug(),
            Field::textarea('description'),
            Field::image('image', 'Banner Image'),
            Field::text('meta_title', 'Meta Title')->wide()
                ->help('Leave empty to generate from the name, e.g. "West Java Travel Guide | ' . Seo::SITE_NAME . '".'),
            Field::textarea('meta_description', 'Meta Description')
                ->rules(['max:' . Seo::DESCRIPTION_LENGTH])
                ->help('Up to ' . Seo::DESCRIPTION_LENGTH . ' characters. Leave empty to use the start of the description.'),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make('sort_order', '#', 'number')->sortable(),
            Column::make('image', 'Image', 'image'),
            Column::make('name')->sortable(),
            Column::make('meta_title', 'Meta Title'),
            Column::make('destinations_count', 'Destinations', 'number')->value(fn ($region) => $region->destinations()->count()),
        ];
    }

    public function publicUrl(Model $model): ?string
    {
        return route('destinations.region', $model->slug);
    }
}
