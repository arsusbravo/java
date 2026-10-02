<?php

namespace App\Models;

use App\Support\Seo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Region extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'sort_order',
        'description',
        'image',
        'meta_title',
        'meta_description',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        // New regions go to the end of the list
        static::creating(function (Region $region) {
            if (! $region->sort_order) {
                $region->sort_order = (int) static::max('sort_order') + 1;
            }
        });

        // Fill empty meta fields from the name and description, and store them
        static::saving(function (Region $region) {
            $region->fillGeneratedMeta('meta_title', 'name', fn ($name) => Seo::title($name));
            $region->fillGeneratedMeta('meta_description', 'description', fn ($text) => Seo::description($text));
        });
    }

    /**
     * Generate $field from $source when it's empty, or when it still holds the
     * value generated from the previous $source (so it follows edits).
     * Hand-written values are left alone.
     */
    protected function fillGeneratedMeta(string $field, string $source, \Closure $generate): void
    {
        $current = $this->getAttribute($field);
        $wasGenerated = $this->exists
            && $this->isDirty($source)
            && filled($current)
            && $current === $generate($this->getOriginal($source));

        if (blank($current) || $wasGenerated) {
            $this->setAttribute($field, filled($this->getAttribute($source)) ? $generate($this->getAttribute($source)) : null);
        }
    }

    // Scopes
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    // Helper Methods

    /**
     * Save a new order, given region IDs from first to last.
     */
    public static function reorder(array $ids): void
    {
        DB::transaction(function () use ($ids) {
            foreach (array_values($ids) as $index => $id) {
                static::whereKey($id)->update(['sort_order' => $index + 1]);
            }
        });
    }

    // Relationships
    public function destinations()
    {
        return $this->hasMany(Destination::class);
    }
}