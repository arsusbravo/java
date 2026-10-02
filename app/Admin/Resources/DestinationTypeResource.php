<?php

namespace App\Admin\Resources;

use App\Admin\Column;
use App\Admin\Field;
use App\Admin\Resource;
use App\Models;

class DestinationTypeResource extends Resource
{
    public static string $model = Models\DestinationType::class;

    public static string $key = 'destination-types';

    public static string $label = 'Destination Types';

    public static string $singular = 'Destination Type';

    public static string $icon = 'Shapes';

    public static string $group = 'Places';

    public static array $search = ['name', 'description'];

    public static string $sort = 'sort_order';

    public static string $direction = 'asc';

    public function fields(): array
    {
        return [
            Field::text('name')->required()->placeholder('e.g. Heritage & History'),
            Field::slug(),
            Field::textarea('description')->help('What kind of trip this is; can be shown when destinations are grouped by type.'),
            Field::number('sort_order', 'Sort Order')->rules(['min:0'])->help('Lower numbers are listed first. Leave empty to add at the end.'),
            Field::belongsToMany('destinations', Models\Destination::class),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make('sort_order', '#', 'number')->sortable(),
            Column::make('name')->sortable(),
            Column::make('description')->value(fn ($type) => str($type->description)->limit(80)->toString()),
            Column::make('destinations', 'Destinations', 'number')->value(fn ($type) => $type->destinations()->count()),
        ];
    }
}
