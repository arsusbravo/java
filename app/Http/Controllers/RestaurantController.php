<?php

namespace App\Http\Controllers;

use App\Models\Accommodation;
use App\Models\Destination;
use App\Models\Restaurant;
use App\Support\Geo;
use Illuminate\View\View;

class RestaurantController extends Controller
{
    public function show(string $slug): View
    {
        $restaurant = Restaurant::query()
            ->where('slug', $slug)
            ->published()
            ->where('is_active', true)
            ->with([
                'images' => fn ($query) => $query->orderBy('order'),
                'destinations.region',
                'destination.region',
                'affiliateNetwork',
            ])
            ->firstOrFail();

        // A separate query: a limit inside with() needs window functions (MariaDB 10.2+),
        // and production runs MariaDB 5.5
        $restaurant->setRelation('reviews', $restaurant->reviews()->approved()->latest()->limit(6)->get());

        $restaurant->incrementViews();

        $hasLocation = $restaurant->latitude !== null && $restaurant->longitude !== null;
        [$lat, $lng] = [(float) $restaurant->latitude, (float) $restaurant->longitude];

        // The places it's linked to, else the closest one, for the breadcrumb and "nearby"
        $linked = $restaurant->destinations->push($restaurant->destination)->filter()->unique('id')->values();

        $nearbyPlaces = $hasLocation
            ? Destination::query()
                ->whereNotNull('latitude')
                ->with(['region', 'images'])
                ->get()
                ->each(fn ($place) => $place->distance_km = Geo::distanceKm($lat, $lng, (float) $place->latitude, (float) $place->longitude))
                ->sortBy('distance_km')
                ->take(4)
                ->values()
            : $linked;

        return view('front.pages.restaurant', [
            'restaurant' => $restaurant,
            'area' => $linked->first() ?? $nearbyPlaces->first(),
            'nearbyPlaces' => $nearbyPlaces,
            'nearbyHotels' => $hasLocation ? Accommodation::nearest($lat, $lng, 3, with: ['images', 'affiliateNetwork']) : collect(),
            'nearbyRestaurants' => $hasLocation ? Restaurant::nearest($lat, $lng, 3, [$restaurant->id], ['images']) : collect(),
        ]);
    }
}
