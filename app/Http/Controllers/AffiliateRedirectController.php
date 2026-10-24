<?php

namespace App\Http\Controllers;

use App\Models\Accommodation;
use App\Models\AffiliateClick;
use App\Models\Restaurant;
use App\Models\Tour;
use App\Support\Agoda\Stay;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Booking links go through /go/{type}/{id}: the click is recorded, then the
 * visitor is sent on to the partner (Agoda...) with the current affiliate ID.
 */
class AffiliateRedirectController extends Controller
{
    public const TYPES = [
        'hotel' => Accommodation::class,
        'tour' => Tour::class,
        'restaurant' => Restaurant::class,
    ];

    /** Repeat clicks on the same listing within this time count once. */
    protected const REPEAT_WINDOW_MINUTES = 10;

    /** User agents of crawlers and link previews, which aren't real visitors. */
    protected const BOT_PATTERN = '/bot|crawl|spider|slurp|preview|facebookexternalhit|whatsapp|telegram|curl|wget|python|headless/i';

    public function __invoke(Request $request, string $type, int $id): RedirectResponse
    {
        $listing = self::TYPES[$type]::query()
            ->published()
            ->where('is_active', true)
            ->with('affiliateNetwork')
            ->findOrFail($id);

        $url = $listing->affiliate_url;
        abort_unless(filled($url), 404);

        if (! $this->isBot($request) && ! $this->isRepeat($request, $type, $id)) {
            AffiliateClick::create([
                'clickable_type' => $listing::class,
                'clickable_id' => $listing->getKey(),
                'source' => array_key_exists($request->query('from'), AffiliateClick::SOURCES) ? $request->query('from') : 'other',
                'ip_address' => $this->anonymiseIp($request->ip()),
                'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
                'referrer' => $this->referrerPath($request),
                'clicked_at' => now(),
            ]);

            // Plain query, so updated_at stays as it is: a click isn't an edit
            DB::table($listing->getTable())->where('id', $listing->getKey())->increment('clicks_count');
        }

        return redirect()->away($this->withStay($url, Stay::fromQuery($request->query())));
    }

    /**
     * Agoda opens with the visitor's dates and guests already filled in.
     * Other partners, and invalid dates, get the plain link.
     */
    protected function withStay(string $url, ?Stay $stay): string
    {
        $host = (string) parse_url($url, PHP_URL_HOST);
        if (! $stay || ! ($host === 'agoda.com' || str_ends_with($host, '.agoda.com'))) {
            return $url;
        }

        return $url.(str_contains($url, '?') ? '&' : '?').http_build_query([
            'checkin' => $stay->checkIn->toDateString(),
            'checkout' => $stay->checkOut->toDateString(),
            'NumberofAdults' => $stay->adults,
            'NumberofChildren' => $stay->children,
            'Rooms' => 1,
            'currency' => config('services.agoda.currency'),
        ]);
    }

    protected function isBot(Request $request): bool
    {
        return blank($request->userAgent()) || (bool) preg_match(self::BOT_PATTERN, $request->userAgent());
    }

    protected function isRepeat(Request $request, string $type, int $id): bool
    {
        $key = "affiliate_click.{$type}.{$id}";
        $last = $request->session()->get($key);
        $request->session()->put($key, now()->timestamp);

        return $last && now()->timestamp - $last < self::REPEAT_WINDOW_MINUTES * 60;
    }

    /**
     * Keep the network but not the exact visitor (GDPR): the last IPv4 octet
     * becomes 0, IPv6 keeps its first 48 bits.
     */
    protected function anonymiseIp(?string $ip): ?string
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return preg_replace('/\.\d+$/', '.0', $ip);
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $packed = inet_pton($ip);

            return inet_ntop(substr($packed, 0, 6).str_repeat("\0", 10));
        }

        return null;
    }

    /**
     * The page the click came from, without query strings that may hold personal data.
     */
    protected function referrerPath(Request $request): ?string
    {
        $referrer = $request->headers->get('referer');
        if (! $referrer) {
            return null;
        }

        $path = parse_url($referrer, PHP_URL_PATH) ?: '/';
        $host = parse_url($referrer, PHP_URL_HOST);
        $own = $host === $request->getHost();

        return Str::limit($own ? $path : "{$host}{$path}", 190, '');
    }
}
