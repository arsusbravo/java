<?php

namespace App\Admin;

use App\Admin\Resources;
use App\Models;

class Admin
{
    /** @var array<class-string<Resource>> */
    public const RESOURCES = [
        Resources\ArticleResource::class,
        Resources\TagResource::class,
        Resources\ReviewResource::class,
        Resources\RegionResource::class,
        Resources\DestinationResource::class,
        Resources\DestinationTypeResource::class,
        Resources\AccommodationResource::class,
        Resources\TourResource::class,
        Resources\RestaurantResource::class,
        Resources\AffiliateNetworkResource::class,
        Resources\ImageResource::class,
        Resources\AffiliateClickResource::class,
        Resources\UserResource::class,
    ];

    /** Publication status shared by articles and listings. */
    public const STATUSES = ['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived'];

    /** Models that can own reviews, clicks and images, keyed by alias. */
    public const LISTINGS = [
        'destination' => Models\Destination::class,
        'accommodation' => Models\Accommodation::class,
        'tour' => Models\Tour::class,
        'restaurant' => Models\Restaurant::class,
    ];

    public static function find(string $key): ?Resource
    {
        foreach (self::RESOURCES as $class) {
            if ($class::$key === $key) {
                return new $class;
            }
        }

        return null;
    }

    public static function keys(): array
    {
        return array_map(fn ($class) => $class::$key, self::RESOURCES);
    }

    /**
     * Navigation entries grouped for the sidebar.
     */
    public static function navigation(): array
    {
        return collect(self::RESOURCES)
            ->map(fn ($class) => (new $class)->meta() + ['href' => '/admin/' . $class::$key])
            ->groupBy('group')
            ->map(fn ($items, $group) => ['label' => $group, 'items' => $items->values()->all()])
            ->values()
            ->all();
    }
}
