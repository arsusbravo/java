<?php

namespace App\Admin\Resources;

use App\Admin\Column;
use App\Admin\Resource;
use App\Models;

class AffiliateClickResource extends Resource
{
    public static string $model = Models\AffiliateClick::class;

    public static string $key = 'affiliate-clicks';

    public static string $label = 'Affiliate Clicks';

    public static string $singular = 'Affiliate Click';

    public static string $icon = 'MousePointerClick';

    public static string $group = 'Media & Stats';

    public static array $search = ['ip_address', 'referrer', 'user_agent'];

    public static array $with = ['clickable'];

    public static string $sort = 'clicked_at';

    public static bool $editable = false;

    public function fields(): array
    {
        return [];
    }

    public function columns(): array
    {
        return [
            Column::make('clicked_at', 'Clicked', 'date')->sortable(),
            Column::make('clickable', 'Listing')->value(fn ($click) => $click->clickable
                ? class_basename($click->clickable_type) . ': ' . $click->clickable->name
                : '(deleted)'),
            Column::make('referrer'),
            Column::make('ip_address', 'IP'),
        ];
    }

    public function filters(): array
    {
        return ['clickable_type' => [
            Models\Accommodation::class => 'Accommodation',
            Models\Tour::class => 'Tour',
            Models\Restaurant::class => 'Restaurant',
        ]];
    }
}
