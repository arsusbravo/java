<?php

namespace App\Support;

use Illuminate\Support\Str;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * HTML written in the admin's rich text editor: cleaning it before it's
 * stored, showing it on the site, and reducing it to plain-text excerpts.
 */
class RichText
{
    /** CSS the editor writes inline (alignment, text colour, highlight); anything else is dropped. */
    protected const STYLE_PROPERTIES = ['text-align', 'color', 'background-color'];

    /** Only video players from these hosts may be embedded. */
    protected const EMBED_HOSTS = ['www.youtube.com', 'youtube.com', 'www.youtube-nocookie.com', 'youtube-nocookie.com'];

    protected static ?HtmlSanitizer $sanitizer = null;

    /**
     * Keep the editor's formatting, drop anything that could run code.
     */
    public static function sanitize(?string $html): ?string
    {
        if (blank($html)) {
            return null;
        }

        $clean = static::sanitizer()->sanitize(static::toHtml($html));
        $clean = static::filterStyles(static::filterEmbeds($clean));

        return blank(strip_tags($clean, '<img><iframe><hr>')) ? null : $clean;
    }

    /**
     * Stored value as HTML: plain text from before the editor (or from feeds)
     * becomes escaped paragraphs; HTML is returned as is.
     */
    public static function toHtml(?string $value): string
    {
        $value = (string) $value;

        if ($value === '' || static::isHtml($value)) {
            return $value;
        }

        return collect(preg_split('/\R{2,}/u', trim($value)))
            ->map(fn ($paragraph) => '<p>' . nl2br(e(trim($paragraph)), false) . '</p>')
            ->implode('');
    }

    /**
     * Safe HTML for display on the site.
     */
    public static function render(?string $value): string
    {
        return (string) static::sanitize($value);
    }

    /**
     * Plain-text excerpt for cards and listings.
     */
    public static function excerpt(?string $value, int $limit = 150): string
    {
        // Keep words from separate blocks apart once tags are removed
        $text = preg_replace('/<\/(p|h[1-6]|li|blockquote|td|th|div)>|<br\s*\/?>/i', ' ', (string) $value);
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5);

        return Str::limit(trim(preg_replace('/\s+/u', ' ', $text)), $limit);
    }

    /** Tags that mark a stored value as HTML rather than plain text. */
    protected const HTML_TAGS = 'p|br|div|span|h[1-6]|ul|ol|li|strong|b|em|i|u|s|a|img|blockquote|pre|code|hr|table|thead|tbody|tr|td|th|mark|sub|sup|iframe|figure';

    public static function isHtml(string $value): bool
    {
        return (bool) preg_match('/<\/?(' . self::HTML_TAGS . ')(\s[^>]*)?\/?>/i', $value);
    }

    protected static function sanitizer(): HtmlSanitizer
    {
        return static::$sanitizer ??= new HtmlSanitizer(
            (new HtmlSanitizerConfig)
                ->allowSafeElements()
                ->allowLinkSchemes(['https', 'http', 'mailto', 'tel'])
                ->allowMediaSchemes(['https', 'http'])
                ->allowRelativeLinks()
                ->allowRelativeMedias()
                ->allowAttribute('style', ['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'span', 'mark', 'td', 'th', 'li', 'blockquote'])
                ->allowAttribute('data-color', ['mark'])
                ->allowAttribute('colspan', ['td', 'th'])
                ->allowAttribute('rowspan', ['td', 'th'])
                ->allowAttribute('colwidth', ['td', 'th'])
                ->allowAttribute('target', ['a'])
                ->allowAttribute('rel', ['a'])
                ->allowElement('div', ['data-youtube-video'])
                ->allowElement('iframe', ['src', 'width', 'height', 'allowfullscreen', 'allow', 'title', 'frameborder'])
                ->forceAttribute('iframe', 'loading', 'lazy')
                ->withMaxInputLength(1_000_000)
        );
    }

    /**
     * Remove embedded frames that aren't YouTube players.
     */
    protected static function filterEmbeds(string $html): string
    {
        return preg_replace_callback('/<iframe\b[^>]*>.*?<\/iframe>|<iframe\b[^>]*\/?>/is', function ($match) {
            preg_match('/\bsrc="([^"]*)"/i', $match[0], $src);
            $url = html_entity_decode($src[1] ?? '', ENT_QUOTES | ENT_HTML5);

            return parse_url($url, PHP_URL_SCHEME) === 'https' && in_array(parse_url($url, PHP_URL_HOST), self::EMBED_HOSTS, true)
                ? $match[0]
                : '';
        }, $html);
    }

    /**
     * Keep only the inline CSS properties the editor produces.
     */
    protected static function filterStyles(string $html): string
    {
        return preg_replace_callback('/\sstyle="([^"]*)"/i', function ($match) {
            $declarations = collect(explode(';', html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5)))
                ->map(fn ($declaration) => array_map('trim', explode(':', $declaration, 2)))
                ->filter(fn ($parts) => count($parts) === 2
                    && in_array(strtolower($parts[0]), self::STYLE_PROPERTIES, true)
                    && preg_match('/^[#\w\s(),.%-]+$/', $parts[1]))
                ->map(fn ($parts) => strtolower($parts[0]) . ': ' . $parts[1]);

            return $declarations->isEmpty() ? '' : ' style="' . e($declarations->implode('; ')) . '"';
        }, $html);
    }
}
