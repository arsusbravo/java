<?php

namespace App\Admin\Resources;

use App\Admin\Column;
use App\Admin\Field;
use App\Models;

class RestaurantResource extends ListingResource
{
    public static string $model = Models\Restaurant::class;

    public static string $key = 'restaurants';

    public static string $label = 'Restaurants';

    public static string $singular = 'Restaurant';

    public static string $icon = 'Utensils';

    public static array $with = ['images', 'affiliateNetwork'];

    public const PRICES = ['$' => '$ (budget)', '$$' => '$$', '$$$' => '$$$', '$$$$' => '$$$$ (fine dining)'];

    public function fields(): array
    {
        return [
            Field::text('name')->required(),
            Field::slug(),
            Field::select('price_range', self::PRICES, 'Price Range'),
            Field::decimal('average_rating', 'Average Rating')->rules(['between:0,5']),
            ...$this->publishingFields(),
            Field::belongsToMany('destinations', Models\Destination::class)->help('Shown on these destination pages.'),
            Field::list('cuisine_type', 'Cuisine')->help('One cuisine per line, e.g. Javanese.'),
            Field::html('description'),
            Field::text('address')->wide(),
            Field::text('phone'),
            Field::list('opening_hours', 'Opening Hours')->help('One line per day or range, e.g. Mon-Fri: 09:00-22:00.'),
            Field::decimal('latitude')->rules(['between:-90,90']),
            Field::decimal('longitude')->rules(['between:-180,180']),
            ...$this->affiliateFields(),
            Field::gallery(),
            Field::belongsToMany('tags', Models\Tag::class),
            ...$this->seoFields(),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make('images', 'Image', 'image')->value(fn ($model) => $model->images->firstWhere('is_featured', true)?->path ?? $model->images->first()?->path),
            Column::make('name')->sortable(),
            Column::make('cuisine_type', 'Cuisine')->value(fn ($restaurant) => implode(', ', $restaurant->cuisine_type ?? [])),
            Column::make('price_range', 'Price', 'badge')->sortable(),
            ...$this->statColumns(),
        ];
    }

    public function filters(): array
    {
        return [...parent::filters(), 'price_range' => self::PRICES];
    }
}
