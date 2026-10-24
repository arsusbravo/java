<?php

namespace App\Models;

use App\Models\Concerns\HasAffiliateLink;
use App\Models\Concerns\HasFeaturedImage;
use App\Support\Geo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Accommodation extends Model
{
    use HasAffiliateLink, HasFactory, HasFeaturedImage;

    protected $placeholderImage = 'destination';

    public const TYPES = [
        'hotel' => 'Hotel',
        'resort' => 'Resort',
        'villa' => 'Villa',
        'apartment' => 'Apartment',
        'house' => 'Entire House',
        'guesthouse' => 'Guesthouse / Homestay',
        'hostel' => 'Hostel',
        'other' => 'Other',
    ];

    protected $fillable = [
        'name',
        'slug',
        'type',
        'description',
        'short_description',
        'address',
        'latitude',
        'longitude',
        'price_from',
        'currency',
        'star_rating',
        'amenities',
        'affiliate_link',
        'affiliate_network_id',
        'external_id',
        'commission_rate',
        'is_featured',
        'status',
        'views_count',
        'clicks_count',
        'meta_title',
        'meta_description',
        'is_active',
        'crossed_out_price',
        'discount_percent',
        'review_score',
        'review_count',
        'includes_breakfast',
        'free_wifi',
        'prices_checked_at',
        'check_in_time',
        'check_out_time',
        'room_count',
        'year_opened',
        'year_renovated',
        'chain_name',
    ];

    protected $casts = [
        'amenities' => 'array',
        'is_featured' => 'boolean',
        'price_from' => 'decimal:2',
        'crossed_out_price' => 'decimal:2',
        'discount_percent' => 'integer',
        'review_score' => 'decimal:1',
        'review_count' => 'integer',
        'includes_breakfast' => 'boolean',
        'free_wifi' => 'boolean',
        'prices_checked_at' => 'datetime',
        'commission_rate' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function destinations()
    {
        return $this->belongsToMany(Destination::class, 'destination_accommodation')
            ->withPivot('is_primary');
    }

    public function articles()
    {
        return $this->belongsToMany(Article::class, 'article_accommodation');
    }

    public function images()
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function reviews()
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    public function clicks()
    {
        return $this->morphMany(AffiliateClick::class, 'clickable');
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /** Search radii for nearest(), widened until enough hotels are found. */
    public const NEARBY_RADII_KM = [5, 15, 40, 100];

    // Helper Methods

    /**
     * Star rating between $min and $max (either may be null for no limit).
     * Unrated homestays and guesthouses count as budget stays: they match
     * when there is no minimum and the maximum is 3 stars or lower.
     */
    public function scopeStarRange(Builder $query, ?int $min, ?int $max): Builder
    {
        return $query
            ->when($min !== null, fn ($query) => $query->where('star_rating', '>=', $min))
            ->when($max !== null, fn ($query) => $query->where(fn ($query) => $query
                ->where('star_rating', '<=', $max)
                ->when($max <= 3 && $min === null, fn ($query) => $query->orWhereNull('star_rating'))));
    }

    /**
     * Published, active hotels near a point, each with a `distance_km` attribute.
     * Searches the smallest radius that has enough hotels (up to the largest),
     * then ranks them by star rating, highest first, and distance for equal ratings.
     *
     * @param  array<int>  $except  IDs to leave out, e.g. hotels already shown
     * @param  array{0: ?int, 1: ?int}|null  $stars  Star range [min, max]; a null max of 3
     *                                               or lower also allows unrated places
     * @param  bool  $closestFirst  Rank by distance only, to get the closest hotels
     */
    public static function nearest(float $lat, float $lng, int $limit, array $except = [], array $with = [], ?array $stars = null, bool $closestFirst = false): Collection
    {
        [$minStars, $maxStars] = $stars ?? [null, null];

        foreach (self::NEARBY_RADII_KM as $radius) {
            [[$minLat, $maxLat], [$minLng, $maxLng]] = Geo::boundingBox($lat, $lng, $radius);

            $hotels = static::query()
                ->published()
                ->where('is_active', true)
                ->whereBetween('latitude', [$minLat, $maxLat])
                ->whereBetween('longitude', [$minLng, $maxLng])
                ->whereNotIn('id', $except)
                ->starRange($minStars, $maxStars)
                ->get()
                ->each(fn ($hotel) => $hotel->distance_km = Geo::distanceKm($lat, $lng, (float) $hotel->latitude, (float) $hotel->longitude))
                ->filter(fn ($hotel) => $hotel->distance_km <= $radius)
                ->sortBy($closestFirst ? [
                    fn ($a, $b) => $a->distance_km <=> $b->distance_km,
                ] : [
                    fn ($a, $b) => ($b->star_rating ?? 0) <=> ($a->star_rating ?? 0),
                    fn ($a, $b) => $a->distance_km <=> $b->distance_km,
                ]);

            // Enough within this radius, or nowhere left to look
            if ($hotels->count() >= $limit || $radius === max(self::NEARBY_RADII_KM)) {
                return $hotels->take($limit)->values()->load($with);
            }
        }

        return new Collection;
    }

    /**
     * The hotel's own page on the site.
     */
    public function url(): string
    {
        return route('hotels.show', $this->slug);
    }

    public function incrementViews()
    {
        $this->increment('views_count');
    }

    public function incrementClicks()
    {
        $this->increment('clicks_count');
    }
}
