<?php

namespace App\Support\Agoda;

use App\Models\Accommodation;
use Carbon\CarbonImmutable;

/**
 * Live hotel prices for a planned trip (App\Support\TripPlanner) that has a
 * start date: one Agoda search per night, for that night's hotel and its
 * alternatives. A full hotel is swapped for an alternative with rooms.
 */
class TripPricing
{
    public function __construct(protected AgodaClient $agoda) {}

    /**
     * Adds to each night's stay: `stay` (Stay for the booking link), `offer`
     * (or null), `alternativeOffers` and `fullHotel` (when swapped). Also adds
     * `hotelTotal`, `pricedNights` and `currency` to the plan.
     *
     * @param  array{days: array<int, array<string, mixed>>}  $plan
     * @return array<string, mixed>
     */
    public function price(array $plan, CarbonImmutable $startDate, int $adults): array
    {
        $total = 0.0;
        $priced = 0;
        $currency = config('services.agoda.currency');
        $previousHotel = null;

        foreach ($plan['days'] as $index => &$day) {
            if (! $day['stay']) {
                continue;
            }

            $night = $startDate->addDays($index);
            $hotels = collect([$day['stay']['hotel'], ...$day['stay']['alternatives']]);
            $offers = $this->agoda->offers($hotels->pluck('external_id')->all(), $night, $night->addDay(), $adults);
            $offerFor = fn (Accommodation $hotel): ?Offer => $offers[(int) $hotel->external_id] ?? null;

            // Tonight's pick is full: take the first alternative that has rooms
            $fullHotel = null;
            if (! $offerFor($day['stay']['hotel']) && $available = $day['stay']['alternatives']->first(fn ($hotel) => $offerFor($hotel))) {
                $fullHotel = $day['stay']['hotel'];
                $day['stay']['alternatives'] = $hotels->reject(fn ($hotel) => $hotel->is($available))->values();
                $day['stay']['hotel'] = $available;
                $day['stay']['sameAsLastNight'] = false;
            }

            $hotel = $day['stay']['hotel'];
            $offer = $offerFor($hotel);
            $day['stay']['offer'] = $offer;
            $day['stay']['fullHotel'] = $fullHotel;
            $day['stay']['alternativeOffers'] = $day['stay']['alternatives']->mapWithKeys(fn ($alternative) => [$alternative->id => $offerFor($alternative)]);
            $day['stay']['stay'] = new Stay($night, $night->addDay(), $adults);

            // Same hotel as last night: one booking for all the nights in a row
            if ($previousHotel && $hotel->is($previousHotel['hotel'])) {
                $run = $previousHotel['stay'];
                $longer = new Stay($run->checkIn, $night->addDay(), $adults);
                foreach ($previousHotel['days'] as $earlier) {
                    $plan['days'][$earlier]['stay']['stay'] = $longer;
                }
                $day['stay']['stay'] = $longer;
                $previousHotel = ['hotel' => $hotel, 'stay' => $longer, 'days' => [...$previousHotel['days'], $index]];
            } else {
                $previousHotel = ['hotel' => $hotel, 'stay' => $day['stay']['stay'], 'days' => [$index]];
            }

            if ($offer) {
                $total += $offer->dailyRate;
                $priced++;
                $currency = $offer->currency;
            }
        }
        unset($day);

        return [...$plan, 'hotelTotal' => round($total, 2), 'pricedNights' => $priced, 'currency' => $currency];
    }
}
