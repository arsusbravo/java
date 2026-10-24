<?php

use Illuminate\Support\Facades\Storage;

if (! function_exists('image_url')) {
    /**
     * Get a URL for an image, falling back to a placeholder when it doesn't exist.
     *
     * Accepts absolute URLs, paths relative to public/ (e.g. "images/sunrise.png")
     * and paths on the public storage disk (e.g. uploaded Image records).
     *
     * @param  string  $placeholder  One of: destination, article, region, hero
     */
    function image_url(?string $path, string $placeholder = 'destination'): string
    {
        if (filled($path)) {
            if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
                return $path;
            }

            $path = ltrim($path, '/');

            if (is_file(public_path($path))) {
                return asset($path);
            }

            if (Storage::disk('public')->exists($path)) {
                return asset('storage/'.$path);
            }
        }

        return asset("images/placeholders/{$placeholder}.svg");
    }
}

if (! function_exists('rich_text')) {
    /**
     * Safe HTML for rich text written in the admin editor (plain text becomes paragraphs).
     */
    function rich_text(?string $value): string
    {
        return \App\Support\RichText::render($value);
    }
}

if (! function_exists('text_excerpt')) {
    /**
     * Plain-text excerpt of rich text, for cards and listings.
     */
    function text_excerpt(?string $value, int $limit = 150): string
    {
        return \App\Support\RichText::excerpt($value, $limit);
    }
}

if (! function_exists('distance_label')) {
    /**
     * Short distance for badges: "650 m" under a kilometre, otherwise "3.2 km".
     */
    function distance_label(float $km): string
    {
        return $km < 1 ? round($km * 1000, -1).' m' : number_format($km, 1).' km';
    }
}

if (! function_exists('money')) {
    /**
     * "€23" / "$23" / "IDR 450,000". Amounts under 10 keep their cents.
     */
    function money(float|string|null $amount, ?string $currency): string
    {
        $amount = (float) $amount;
        $symbols = ['EUR' => '€', 'USD' => '$', 'GBP' => '£', 'AUD' => 'A$', 'SGD' => 'S$'];
        $number = number_format($amount, $amount < 10 && floor($amount) != $amount ? 2 : 0);

        return isset($symbols[$currency]) ? $symbols[$currency].$number : trim("{$currency} {$number}");
    }
}

if (! function_exists('rating_label')) {
    /**
     * Words for an Agoda guest score out of 10, like Agoda's own.
     */
    function rating_label(float|string|null $score): ?string
    {
        $score = (float) $score;

        return match (true) {
            $score >= 9 => 'Exceptional',
            $score >= 8.5 => 'Excellent',
            $score >= 8 => 'Very good',
            $score >= 7 => 'Good',
            default => null,
        };
    }
}

if (! function_exists('booking_url')) {
    /**
     * Tracked booking link (/go) for a listing, with the searched dates when known.
     */
    function booking_url(\Illuminate\Database\Eloquent\Model $listing, string $from, ?\App\Support\Agoda\Stay $stay = null): string
    {
        $type = array_search($listing::class, \App\Http\Controllers\AffiliateRedirectController::TYPES, true);

        return route('go', [$type, $listing->getKey(), 'from' => $from, ...($stay?->query() ?? [])]);
    }
}
