<?php

namespace App\Support;

use Illuminate\Support\Str;

class Seo
{
    /** Brand used in page titles on the public site. */
    public const SITE_NAME = 'Java Sunrise';

    /** Search engines truncate meta descriptions beyond this many characters. */
    public const DESCRIPTION_LENGTH = 160;

    /** Shortest sentence-ending cut worth keeping before falling back to a word cut. */
    protected const MIN_SENTENCE_CUT = 100;

    public static function title(string $name): string
    {
        return trim($name) . ' Travel Guide | ' . self::SITE_NAME;
    }

    /**
     * Plain text of at most $limit characters, ending on a full sentence where
     * one fits, otherwise on a whole word followed by an ellipsis.
     */
    public static function description(?string $text, int $limit = self::DESCRIPTION_LENGTH): ?string
    {
        $text = html_entity_decode(strip_tags((string) $text), ENT_QUOTES | ENT_HTML5);
        $text = trim(preg_replace('/\s+/u', ' ', $text));

        if ($text === '') {
            return null;
        }

        if (mb_strlen($text) <= $limit) {
            return $text;
        }

        // Last sentence end that fits, e.g. "…heart of Indonesia."
        $window = mb_substr($text, 0, $limit);
        if (preg_match('/^.*[.!?](?=\s|$)/us', $window, $sentence) && mb_strlen($sentence[0]) >= self::MIN_SENTENCE_CUT) {
            return $sentence[0];
        }

        // Otherwise the whole words that fit, leaving room for the ellipsis
        $cut = mb_substr($text, 0, $limit - 1);
        if (! preg_match('/\s/u', mb_substr($text, $limit - 1, 1))) {
            $cut = preg_replace('/\s+\S*$/u', '', $cut);
        }

        return rtrim($cut, " ,;:-–—") . '…';
    }
}
