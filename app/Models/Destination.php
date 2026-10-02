<?php

namespace App\Models;

use App\Models\Concerns\HasFeaturedImage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Support\Geo;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Destination extends Model
{
    use HasFactory, HasFeaturedImage;

    protected $fillable = [
        'region_id',
        'name',
        'slug',
        'description',
        'featured_image',
        'latitude',
        'longitude',
        'best_time_to_visit',
        'average_cost',
        'meta_title',
        'meta_description',
        'is_featured',
        'order',
        'views_count',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'order' => 'integer',
        'views_count' => 'integer',
    ];

    protected $placeholderImage = 'destination';

    // Relationships
    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    /** Kinds of trip this destination suits, e.g. Shopping, Heritage & History. */
    public function types()
    {
        return $this->belongsToMany(DestinationType::class)->orderBy('sort_order');
    }

        public function images()
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    public function accommodations()
    {
        return $this->belongsToMany(Accommodation::class, 'destination_accommodation');
    }

    public function tours()
    {
        return $this->belongsToMany(Tour::class, 'destination_tour');
    }

    public function restaurants()
    {
        return $this->belongsToMany(Restaurant::class, 'destination_restaurant');
    }

    public function articles()
    {
        return $this->belongsToMany(Article::class, 'article_destination');
    }

    public function reviews()
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    /**
     * The other destinations closest to this one, closest first, each with a
     * `distance_km` attribute. Empty when this destination has no coordinates.
     */
    public function nearby(int $limit = 6): Collection
    {
        if ($this->latitude === null || $this->longitude === null) {
            return new Collection;
        }

        [$lat, $lng] = [(float) $this->latitude, (float) $this->longitude];

        return static::query()
            ->whereKeyNot($this->getKey())
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->with(['region', 'images'])
            ->get()
            ->each(fn ($place) => $place->distance_km = Geo::distanceKm($lat, $lng, (float) $place->latitude, (float) $place->longitude))
            ->sortBy('distance_km')
            ->take($limit)
            ->values();
    }

    // Auto-generate slug
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($destination) {
            if (empty($destination->slug)) {
                $destination->slug = Str::slug($destination->name);
            }
        });
    }
}