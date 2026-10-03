<?php

namespace App\Admin\Resources;

use App\Admin\Column;
use App\Admin\Field;
use App\Admin\Resource;
use App\Models;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class DestinationResource extends Resource
{
    public static string $model = Models\Destination::class;

    public static string $key = 'destinations';

    public static string $label = 'Destinations';

    public static string $singular = 'Destination';

    public static string $icon = 'MapPin';

    public static string $group = 'Places';

    public static array $search = ['name', 'description'];

    public static array $with = ['region', 'types'];

    public static string $sort = 'order';

    public static string $direction = 'asc';

    public function fields(): array
    {
        return [
            Field::text('name')->required(),
            Field::slug(),
            Field::belongsTo('region_id', Models\Region::class)->required(),
            Field::number('order', 'Sort Order')->default(0)->help('Lower numbers are listed first.'),
            Field::boolean('is_featured', 'Featured'),
            Field::belongsToMany('types', Models\DestinationType::class, 'Types')
                ->help('What kind of trip it suits. Pick as many as apply.'),
            Field::html('description'),
            Field::text('best_time_to_visit', 'Best Time to Visit')->placeholder('e.g. April - October'),
            Field::text('average_cost', 'Average Cost')->placeholder('e.g. $50-100/day'),
            Field::decimal('latitude')->rules(['between:-90,90']),
            Field::decimal('longitude')->rules(['between:-180,180']),
            Field::image('featured_image'),
            Field::gallery(),
            Field::text('meta_title', 'Meta Title')->wide(),
            Field::textarea('meta_description', 'Meta Description'),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make('featured_image', 'Image', 'image'),
            Column::make('name')->sortable(),
            Column::make('region.name', 'Region'),
            Column::make('types', 'Types')->value(fn ($destination) => $destination->types->pluck('name')->implode(', ')),
            Column::make('is_featured', 'Featured', 'boolean')->sortable()->toggleable(),
            Column::make('order', 'Order', 'number')->sortable(),
            Column::make('views_count', 'Views', 'number')->sortable(),
        ];
    }

    public function filters(): array
    {
        return [
            'region_id' => Models\Region::ordered()->pluck('name', 'id')->all(),
            'is_featured' => [1 => 'Featured', 0 => 'Not featured'],
            'types' => Models\DestinationType::ordered()->pluck('name', 'id')->all(),
        ];
    }

    public function applyFilter(Builder $query, string $name, mixed $value): void
    {
        if ($name === 'types') {
            $query->whereHas('types', fn ($types) => $types->whereKey($value));

            return;
        }

        parent::applyFilter($query, $name, $value);
    }

    public function publicUrl(Model $model): ?string
    {
        return route('destinations.show', [$model->region->slug, $model->slug]);
    }
}
