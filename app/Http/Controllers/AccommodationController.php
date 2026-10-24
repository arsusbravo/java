<?php

namespace App\Http\Controllers;

use App\Models\Accommodation;
use App\Models\Destination;
use App\Models\Restaurant;
use App\Support\Agoda\AgodaClient;
use App\Support\Agoda\Stay;
use App\Support\Geo;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccommodationController extends Controller
{
    /**
     * A hotel's own page: photos, facts, map, and booking on the partner site,
     * with a live price when the visitor picks dates (?checkin&checkout&adults&children).
     */
    public function show(Request $request, string $slug, AgodaClient $agoda): View
    {
        $hotel = Accommodation::query()
            ->where('slug', $slug)
            ->published()
            ->where('is_active', true)
            ->with([
                'images' => fn ($query) => $query->orderByDesc('is_featured')->orderBy('order'),
                'destinations.region',
                'affiliateNetwork',
            ])
            ->firstOrFail();

        $hotel->incrementViews();

        $stay = Stay::fromQuery($request->query());
        $stayError = ! $stay && ($request->filled('checkin') || $request->filled('checkout'))
            ? 'Please pick a check-in date from today, and a check-out date 1 to '.Stay::MAX_NIGHTS.' nights later.'
            : null;
        $offer = $stay && $hotel->external_id
            ? ($agoda->offers([$hotel->external_id], $stay->checkIn, $stay->checkOut, $stay->adults, $stay->children)[(int) $hotel->external_id] ?? null)
            : null;

        $hasLocation = $hotel->latitude !== null && $hotel->longitude !== null;
        [$lat, $lng] = [(float) $hotel->latitude, (float) $hotel->longitude];

        // The 4 closest places, for "Near" and the breadcrumb when the hotel isn't linked to one
        $nearbyPlaces = $hasLocation
            ? Destination::query()
                ->whereNotNull('latitude')
                ->with(['region', 'images'])
                ->get()
                ->each(fn ($place) => $place->distance_km = Geo::distanceKm($lat, $lng, (float) $place->latitude, (float) $place->longitude))
                ->sortBy('distance_km')
                ->take(4)
                ->values()
            : $hotel->destinations;

        return view('front.pages.accommodation', [
            'hotel' => $hotel,
            'area' => $hotel->destinations->first() ?? $nearbyPlaces->first(),
            'stay' => $stay,
            'stayError' => $stayError,
            'offer' => $offer,
            'nearbyPlaces' => $nearbyPlaces,
            'nearbyRestaurants' => $hasLocation ? Restaurant::nearest($lat, $lng, 3, with: ['images']) : collect(),
            'otherHotels' => $hasLocation ? Accommodation::nearest($lat, $lng, 3, [$hotel->id], ['images', 'affiliateNetwork'], closestFirst: true) : collect(),
        ]);
    }
}
