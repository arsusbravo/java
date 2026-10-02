<?php

namespace App\Admin\Resources;

use App\Admin\Column;
use App\Admin\Field;
use App\Models;

class AccommodationResource extends ListingResource
{
    public static string $model = Models\Accommodation::class;

    public static string $key = 'accommodations';

    public static string $label = 'Accommodations';

    public static string $singular = 'Accommodation';

    public static string $icon = 'Hotel';

    public function fields(): array
    {
        return [
            Field::text('name')->required(),
            Field::slug(),
            Field::select('type', Models\Accommodation::TYPES)->required()->default('hotel'),
            Field::select('star_rating', [1 => '1 star', 2 => '2 stars', 3 => '3 stars', 4 => '4 stars', 5 => '5 stars'], 'Star Rating'),
            ...$this->publishingFields(),
            Field::belongsToMany('destinations', Models\Destination::class),
            Field::textarea('short_description', 'Short Description'),
            Field::html('description'),
            Field::text('address')->wide(),
            Field::decimal('latitude')->rules(['between:-90,90']),
            Field::decimal('longitude')->rules(['between:-180,180']),
            Field::decimal('price_from', 'Price From')->rules(['min:0']),
            Field::text('currency')->default('IDR')->rules(['size:3']),
            Field::list('amenities')->help('One amenity per line.'),
            ...$this->affiliateFields(),
            Field::text('external_id', 'Network Hotel ID')->help('The hotel\'s ID on the network, e.g. Agoda\'s hid. Imports match hotels by this.'),
            Field::decimal('commission_rate', 'Commission Rate (%)')->rules(['between:0,100']),
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
            Column::make('type', 'Type', 'badge')->sortable(),
            Column::make('star_rating', 'Stars', 'number')->sortable(),
            ...$this->statColumns(),
        ];
    }

    public static array $with = ['images', 'affiliateNetwork'];

    public function filters(): array
    {
        return [...parent::filters(), 'type' => Models\Accommodation::TYPES];
    }
}
