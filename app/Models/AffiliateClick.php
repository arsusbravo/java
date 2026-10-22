<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AffiliateClick extends Model
{
    use HasFactory;

    public $timestamps = false;

    /** Where on the site a booking click can come from (`?from=` on /go links). */
    public const SOURCES = [
        'destination' => 'Destination page',
        'trip_planner' => 'Trip Planner',
        'article' => 'Article',
        'restaurant' => 'Restaurant page',
        'other' => 'Other',
    ];

    protected $fillable = [
        'clickable_id',
        'clickable_type',
        'source',
        'ip_address',
        'user_agent',
        'referrer',
        'clicked_at',
    ];

    protected $casts = [
        'clicked_at' => 'datetime',
    ];

    // Relationships
    public function clickable()
    {
        return $this->morphTo();
    }
}