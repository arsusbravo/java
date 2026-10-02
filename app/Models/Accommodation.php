<?php

namespace App\Models;

use App\Models\Concerns\HasAffiliateLink;
use App\Models\Concerns\HasFeaturedImage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Support\Geo;
use Illuminate\Database\Eloquent\Collection;
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
    ];

    protected $casts = [
        'amenities' => 'array',
        'is_featured' => 'boolean',
        'price_from' => 'decimal:2',
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
     * Published, active hotels near a point, each with a `distance_km` attribute.
     * Searches the smallest radius that has enough hotels (up to the largest),
     * then ranks them by star rating, highest first, and distance for equal ratings.
     *
     * @param  array<int>  $except  IDs to leave out, e.g. hotels already shown
     */
    public static function nearest(float $lat, float $lng, int $limit, array $except = [], array $with = []): Collection
    {
        foreach (self::NEARBY_RADII_KM as $radius) {
            [[$minLat, $maxLat], [$minLng, $maxLng]] = Geo::boundingBox($lat, $lng, $radius);

            $hotels = static::query()
                ->published()
                ->where('is_active', true)
                ->whereBetween('latitude', [$minLat, $maxLat])
                ->whereBetween('longitude', [$minLng, $maxLng])
                ->whereNotIn('id', $except)
                ->get()
                ->each(fn ($hotel) => $hotel->distance_km = Geo::distanceKm($lat, $lng, (float) $hotel->latitude, (float) $hotel->longitude))
                ->filter(fn ($hotel) => $hotel->distance_km <= $radius)
                ->sortBy([
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


    public function incrementViews()
    {
        $this->increment('views_count');
    }

    public function incrementClicks()
    {
        $this->increment('clicks_count');
    }
}