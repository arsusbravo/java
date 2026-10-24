<?php

namespace App\Support\Agoda;

use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Agoda Affiliate Lite API (Hotel List Search): live prices and guest ratings
 * for our hotels (accommodations.external_id is the Agoda hotel ID).
 *
 * Never throws: when the API is down, not configured or has no rooms, the
 * result is simply empty and pages fall back to what they showed before.
 */
class AgodaClient
{
    /** Hotel IDs per request; the API handles 100 well. */
    public const CHUNK_SIZE = 100;

    /** How long a search result is reused. */
    public const CACHE_MINUTES = 30;

    /** Agoda answers some valid requests with "Site ID is invalid" (401); they work when repeated. */
    protected const ATTEMPTS = 3;

    public function isConfigured(): bool
    {
        return $this->credentials() !== null && filled(config('services.agoda.url'));
    }

    /**
     * Offers for the given Agoda hotel IDs, keyed by hotel ID. Hotels without
     * rooms for the stay are missing from the result.
     *
     * @param  array<int|string>  $hotelIds
     * @return array<int, Offer>
     */
    public function offers(array $hotelIds, CarbonImmutable $checkIn, CarbonImmutable $checkOut, int $adults = 2, int $children = 0, bool $cache = true): array
    {
        $hotelIds = array_values(array_unique(array_map('intval', array_filter($hotelIds))));
        if ($hotelIds === [] || ! $this->isConfigured()) {
            return [];
        }

        $offers = [];
        foreach (array_chunk($hotelIds, self::CHUNK_SIZE) as $chunk) {
            sort($chunk);
            $search = fn () => $this->search($chunk, $checkIn, $checkOut, $adults, $children);
            $key = 'agoda.offers.'.md5(implode(',', [...$chunk, $checkIn->toDateString(), $checkOut->toDateString(), $adults, $children, config('services.agoda.currency')]));

            // Failures (null) are not cached, so the next visitor tries again
            $found = $cache ? Cache::get($key) : null;
            if ($found === null) {
                $found = $search();
                if ($found !== null && $cache) {
                    Cache::put($key, $found, now()->addMinutes(self::CACHE_MINUTES));
                }
            }

            foreach ($found ?? [] as $offer) {
                $offers[$offer->hotelId] = $offer;
            }
        }

        return $offers;
    }

    /**
     * @param  array<int>  $hotelIds
     * @return array<int, Offer>|null Null when the request failed
     */
    protected function search(array $hotelIds, CarbonImmutable $checkIn, CarbonImmutable $checkOut, int $adults, int $children): ?array
    {
        [$siteId, $apiKey] = $this->credentials();

        try {
            $response = Http::withHeaders(['Authorization' => "{$siteId}:{$apiKey}", 'Accept-Encoding' => 'gzip,deflate'])
                ->acceptJson()
                ->timeout(config('services.agoda.timeout'))
                ->retry(self::ATTEMPTS, 300, fn ($exception) => $exception instanceof RequestException && $exception->response->status() === 401, throw: false)
                ->post(config('services.agoda.url'), [
                    // The API checks these against the Authorization header
                    'siteId' => (int) $siteId,
                    'apiKey' => $apiKey,
                    'criteria' => [
                        'additional' => [
                            'currency' => config('services.agoda.currency'),
                            'language' => config('services.agoda.language'),
                            'discountOnly' => false,
                            'occupancy' => ['numberOfAdult' => $adults, 'numberOfChildren' => $children],
                        ],
                        'checkInDate' => $checkIn->toDateString(),
                        'checkOutDate' => $checkOut->toDateString(),
                        'hotelId' => $hotelIds,
                    ],
                ]);
        } catch (ConnectionException $exception) {
            Log::warning('Agoda API unreachable: '.$exception->getMessage());

            return null;
        }

        return $this->offersFrom($response);
    }

    /**
     * @return array<int, Offer>|null
     */
    protected function offersFrom(Response $response): ?array
    {
        $error = $response->json('error');

        // 911: none of these hotels has rooms for the stay, a valid answer
        if (($error['id'] ?? null) === 911) {
            return [];
        }

        if ($response->failed() || $error || ! is_array($response->json('results'))) {
            Log::warning('Agoda API error', ['status' => $response->status(), 'error' => $error]);

            return null;
        }

        return collect($response->json('results'))
            ->filter(fn ($result) => is_array($result) && isset($result['hotelId'], $result['dailyRate']) && $result['dailyRate'] > 0)
            ->map(fn (array $result) => Offer::fromApi($result))
            ->values()
            ->all();
    }

    /**
     * @return array{0: string, 1: string}|null
     */
    protected function credentials(): ?array
    {
        $key = (string) config('services.agoda.api_key');
        $siteId = (string) config('services.agoda.site_id');

        // The partner portal shows the key as "siteid:key"
        if (str_contains($key, ':')) {
            [$fromKey, $key] = explode(':', $key, 2);
            $siteId = $siteId ?: $fromKey;
        }

        return $siteId !== '' && $key !== '' ? [$siteId, $key] : null;
    }
}
