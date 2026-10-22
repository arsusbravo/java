<?php

namespace App\Models;

use App\Models\Concerns\HasAffiliateLink;
use App\Support\Geo;
use Illuminate\Database\Eloquent\Collection;
use App\Models\Concerns\HasFeaturedImage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Restaurant extends Model
{
    use HasAffiliateLink, HasFactory, HasFeaturedImage;

    protected $placeholderImage = 'destination';

    protected $fillable = [
        'destination_id',
        'name',
        'slug',
        'description',
        'cuisine_type',
        'price_range',
        'address',
        'latitude',
        'longitude',
        'phone',
        'opening_hours',
        'affiliate_link',
        'affiliate_network_id',
        'average_rating',
        'is_featured',
        'status',
        'views_count',
        'clicks_count',
        'meta_title',
        'meta_description',
        'is_active',
    ];

    protected $casts = [
        'cuisine_type' => 'array',
        'opening_hours' => 'array',
        'is_featured' => 'boolean',
        'average_rating' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function destination()
    {
        return $this->belongsTo(Destination::class);
    }

    public function destinations()
    {
        return $this->belongsToMany(Destination::class);
    }

    public function articles()
    {
        return $this->belongsToMany(Article::class);
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

    /** Search radii for nearest(): people walk or take a short ride to eat. */
    public const NEARBY_RADII_KM = [2, 5, 10, 25];

    // Helper Methods

    /**
     * Published, active restaurants near a point, closest first (better rated
     * first at the same distance), each with a `distance_km` attribute.
     *
     * @param  array<int>  $except  IDs to leave out, e.g. restaurants already shown
     */
    public static function nearest(float $lat, float $lng, int $limit, array $except = [], array $with = []): Collection
    {
        foreach (self::NEARBY_RADII_KM as $radius) {
            [[$minLat, $maxLat], [$minLng, $maxLng]] = Geo::boundingBox($lat, $lng, $radius);

            $restaurants = static::query()
                ->published()
                ->where('is_active', true)
                ->whereBetween('latitude', [$minLat, $maxLat])
                ->whereBetween('longitude', [$minLng, $maxLng])
                ->whereNotIn('id', $except)
                ->get()
                ->each(fn ($restaurant) => $restaurant->distance_km = Geo::distanceKm($lat, $lng, (float) $restaurant->latitude, (float) $restaurant->longitude))
                ->filter(fn ($restaurant) => $restaurant->distance_km <= $radius)
                ->sortBy([
                    fn ($a, $b) => round($a->distance_km, 1) <=> round($b->distance_km, 1),
                    fn ($a, $b) => ($b->average_rating ?? 0) <=> ($a->average_rating ?? 0),
                ]);

            if ($restaurants->count() >= $limit || $radius === max(self::NEARBY_RADII_KM)) {
                return $restaurants->take($limit)->values()->load($with);
            }
        }

        return new Collection;
    }

    /**
     * The restaurant's own page on the site.
     */
    public function url(): string
    {
        return route('restaurants.show', $this->slug);
    }

    /**
     * Google Maps directions to the restaurant, when it has coordinates.
     */
    public function getDirectionsUrlAttribute(): ?string
    {
        if ($this->latitude === null || $this->longitude === null) {
            return null;
        }

        return 'https://www.google.com/maps/dir/?api=1&destination=' . $this->latitude . ',' . $this->longitude;
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