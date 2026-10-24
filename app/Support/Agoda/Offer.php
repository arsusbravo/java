<?php

namespace App\Support\Agoda;

/**
 * One hotel's price and rating for a stay, as returned by the Agoda Affiliate Lite API.
 */
final readonly class Offer
{
    public function __construct(
        public int $hotelId,
        public float $dailyRate,
        public ?float $crossedOutRate,
        public int $discountPercent,
        public string $currency,
        public ?float $reviewScore,
        public ?int $reviewCount,
        public bool $breakfast,
        public bool $wifi,
    ) {}

    /**
     * @param  array<string, mixed>  $result  One item of the API's `results`
     */
    public static function fromApi(array $result): self
    {
        $daily = (float) $result['dailyRate'];
        $crossed = (float) ($result['crossedOutRate'] ?? 0) > $daily ? (float) $result['crossedOutRate'] : null;

        // The API often leaves discountPercentage at 0 while still giving a higher crossed-out rate
        $discount = (int) round((float) ($result['discountPercentage'] ?? 0));
        if ($discount === 0 && $crossed) {
            $discount = (int) round((1 - $daily / $crossed) * 100);
        }

        return new self(
            hotelId: (int) $result['hotelId'],
            dailyRate: $daily,
            crossedOutRate: $crossed,
            discountPercent: max(0, min(99, $discount)),
            currency: (string) ($result['currency'] ?? config('services.agoda.currency')),
            reviewScore: ($result['reviewScore'] ?? 0) > 0 ? round((float) $result['reviewScore'], 1) : null,
            reviewCount: ($result['reviewCount'] ?? 0) > 0 ? (int) $result['reviewCount'] : null,
            breakfast: (bool) ($result['includeBreakfast'] ?? false),
            wifi: (bool) ($result['freeWifi'] ?? false),
        );
    }
}
