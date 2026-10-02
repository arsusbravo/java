<?php

namespace App\Admin\Resources;

use App\Admin\Column;
use App\Admin\Field;
use App\Models;

class TourResource extends ListingResource
{
    public static string $model = Models\Tour::class;

    public static string $key = 'tours';

    public static string $label = 'Tours';

    public static string $singular = 'Tour';

    public static string $icon = 'Compass';

    public static array $search = ['name', 'description', 'meeting_point'];

    public static array $with = ['images', 'affiliateNetwork'];

    public const TYPES = ['day_tour' => 'Day Tour', 'multi_day' => 'Multi-day', 'private' => 'Private', 'group' => 'Group', 'adventure' => 'Adventure', 'cultural' => 'Cultural'];

    public function fields(): array
    {
        return [
            Field::text('name')->required(),
            Field::slug(),
            Field::select('type', self::TYPES)->required()->default('day_tour'),
            Field::select('difficulty_level', ['easy' => 'Easy', 'moderate' => 'Moderate', 'hard' => 'Hard'], 'Difficulty'),
            ...$this->publishingFields(),
            Field::belongsToMany('destinations', Models\Destination::class),
            Field::textarea('short_description', 'Short Description'),
            Field::html('description'),
            Field::number('duration')->rules(['min:1']),
            Field::select('duration_unit', ['hours' => 'Hours', 'days' => 'Days'], 'Duration Unit')->required()->default('hours'),
            Field::number('max_group_size', 'Max Group Size')->rules(['min:1']),
            Field::text('meeting_point', 'Meeting Point'),
            Field::decimal('price_from', 'Price From')->rules(['min:0']),
            Field::text('currency')->default('IDR')->rules(['size:3']),
            Field::list('included', 'Included')->help('One item per line.'),
            Field::list('not_included', 'Not Included')->help('One item per line.'),
            ...$this->affiliateFields(),
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
            Column::make('duration')->value(fn ($tour) => $tour->duration ? "{$tour->duration} {$tour->duration_unit}" : null),
            ...$this->statColumns(),
        ];
    }

    public function filters(): array
    {
        return [...parent::filters(), 'type' => self::TYPES];
    }
}
