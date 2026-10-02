<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DestinationType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (DestinationType $type) {
            $type->slug = $type->slug ?: Str::slug($type->name);

            // Types without a position go to the end of the list
            if ($type->sort_order === null || (! $type->exists && ! $type->sort_order)) {
                $others = static::query()->when($type->exists, fn ($query) => $query->whereKeyNot($type->getKey()));
                $type->sort_order = (int) $others->max('sort_order') + 1;
            }
        });
    }

    // Relationships
    public function destinations()
    {
        return $this->belongsToMany(Destination::class);
    }

    // Scopes
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
