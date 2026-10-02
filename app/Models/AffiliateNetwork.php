<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AffiliateNetwork extends Model
{
    use HasFactory;

    public const CATEGORIES = [
        'hotels' => 'Hotels',
        'tours' => 'Tours & Activities',
        'restaurants' => 'Restaurants',
        'transport' => 'Transport',
        'general' => 'General',
    ];

    protected $fillable = [
        'name',
        'slug',
        'affiliate_id',
        'category',
        'website',
        'default_commission_rate',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'default_commission_rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function accommodations()
    {
        return $this->hasMany(Accommodation::class);
    }

    public function tours()
    {
        return $this->hasMany(Tour::class);
    }

    public function restaurants()
    {
        return $this->hasMany(Restaurant::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Helper Methods
    public function listingsCount(): int
    {
        return $this->accommodations()->count() + $this->tours()->count() + $this->restaurants()->count();
    }
}
