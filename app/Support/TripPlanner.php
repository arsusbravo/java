<?php

namespace App\Support;

use App\Models\Accommodation;
use App\Models\Destination;
use App\Models\DestinationType;
use Illuminate\Database\Eloquent\Collection;

/**
 * Builds a day-by-day itinerary from the destinations' coordinates and trip
 * types, with a hotel for every night. Deterministic: the same choices always
 * give the same trip, so shared links show what the sender saw.
 */
class TripPlanner
{
    public const MIN_DAYS = 1;

    public const MAX_DAYS = 14;

    public const DEFAULT_DAYS = 5;

    /** Hotel star range per budget, as [min, max]. */
    public const BUDGETS = [
        'budget' => ['label' => 'Budget', 'hint' => 'Up to 3★ and homestays', 'stars' => [null, 3]],
        'comfort' => ['label' => 'Comfort', 'hint' => '3–4★ hotels', 'stars' => [3, 4]],
        'luxury' => ['label' => 'Luxury', 'hint' => '5★ hotels and resorts', 'stars' => [5, 5]],
    ];

    public const DEFAULT_BUDGET = 'comfort';

    protected const STOPS_PER_DAY = 2;

    /** Places this close to the last stop make a good day trip. */
    protected const DAY_TRIP_KM = 80;

    /** Furthest a travel day goes to reach the next area. */
    protected const TRAVEL_DAY_KM = 400;

    /** Every this many kilometres away costs a point, which keeps the route flowing instead of zig-zagging. */
    protected const KM_PER_POINT = 15;

    /** For equal stars, proper hotels beat rented houses and apartments. */
    protected const PREFERRED_HOTEL_TYPES = ['resort', 'hotel', 'villa', 'guesthouse'];

    /** Keep last night's hotel when the day ends this close to it. */
    protected const SAME_HOTEL_KM = 25;

    /** Rough drive time: roads wind about 30% longer than a straight line, at ~40 km/h. */
    protected const ROAD_FACTOR = 1.3;

    protected const AVERAGE_KMH = 40;

    /** @var Collection<int, Destination> */
    protected Collection $places;

    /** @var array<int> */
    protected array $interestIds = [];

    /** @var array<int, true> */
    protected array $visited = [];

    /**
     * @param  array<string>  $interests  DestinationType slugs
     * @param  array<string>  $skip  Destination slugs the traveller removed
     * @return array{days: array, totalKm: float, placeCount: int, nights: int}
     */
    public function plan(Destination $start, int $days, array $interests = [], string $budget = self::DEFAULT_BUDGET, array $skip = []): array
    {
        $days = max(self::MIN_DAYS, min(self::MAX_DAYS, $days));
        $stars = (self::BUDGETS[$budget] ?? self::BUDGETS[self::DEFAULT_BUDGET])['stars'];

        $this->interestIds = DestinationType::whereIn('slug', $interests)->pluck('id')->all();
        $this->places = Destination::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where(fn ($query) => $query->whereNotIn('slug', $skip)->orWhere('id', $start->id))
            ->with(['region', 'types', 'images'])
            ->get()
            ->keyBy('id');
        $this->visited = [];

        $start = $this->places->get($start->id) ?? $start->loadMissing(['region', 'types', 'images']);
        $current = $start;
        $previousHotel = null;
        $plan = [];
        $totalKm = 0.0;

        for ($number = 1; $number <= $days; $number++) {
            $stops = [];
            $travelDay = false;

            if ($number === 1) {
                $stops[] = $this->stop($start, null);
                $this->visited[$start->id] = true;
            }

            while (count($stops) < self::STOPS_PER_DAY) {
                $next = $this->pick($current, self::DAY_TRIP_KM);

                // Nothing left nearby: begin the day by travelling to the next area
                if (! $next && $stops === []) {
                    $next = $this->pick($current, self::TRAVEL_DAY_KM);
                    $travelDay = (bool) $next;
                }

                if (! $next) {
                    break;
                }

                $stop = $this->stop($next, $current);
                $totalKm += $stop['km'];
                $stops[] = $stop;
                $this->visited[$next->id] = true;
                $current = $next;
            }

            // The last day is for going home: no hotel needed
            $stay = $number < $days ? $this->hotelFor($current, $stars, $previousHotel) : null;
            $previousHotel = $stay['hotel'] ?? $previousHotel;

            $plan[] = [
                'number' => $number,
                'stops' => $stops,
                'travelDay' => $travelDay,
                'freeDayAround' => $stops === [] ? $current : null,
                'stay' => $stay,
            ];
        }

        return [
            'days' => $plan,
            'totalKm' => round($totalKm),
            'placeCount' => count($this->visited),
            'nights' => $days - 1,
        ];
    }

    /**
     * Rough road time for a straight-line distance, in minutes (rounded to 5).
     */
    public static function driveMinutes(float $km): int
    {
        return (int) (round($km * self::ROAD_FACTOR / self::AVERAGE_KMH * 60 / 5) * 5);
    }

    public static function durationLabel(int $minutes): string
    {
        if ($minutes < 60) {
            return max($minutes, 5) . ' min';
        }

        $hours = intdiv($minutes, 60);
        $rest = $minutes % 60;

        return $rest ? "{$hours} h {$rest} min" : "{$hours} h";
    }

    /**
     * Best unvisited place within reach: matching places first, then any.
     */
    protected function pick(Destination $from, float $maxKm): ?Destination
    {
        $candidates = $this->places
            ->reject(fn (Destination $place) => isset($this->visited[$place->id]))
            ->map(fn (Destination $place) => [
                'place' => $place,
                'km' => $this->distance($from, $place),
                'matches' => $this->matches($place),
            ])
            ->filter(fn ($candidate) => $candidate['km'] <= $maxKm);

        if ($this->interestIds) {
            $matching = $candidates->filter(fn ($candidate) => $candidate['matches'] > 0);
            $candidates = $matching->isNotEmpty() ? $matching : $candidates;
        }

        return $candidates
            ->sortBy([
                fn ($a, $b) => $this->value($b) <=> $this->value($a),
                fn ($a, $b) => $a['km'] <=> $b['km'],
                fn ($a, $b) => strcmp($a['place']->name, $b['place']->name),
            ])
            ->first()['place'] ?? null;
    }

    protected function value(array $candidate): float
    {
        // One matching interest is what counts; extra matches only nudge
        $score = ($candidate['matches'] > 0 ? 3 + ($candidate['matches'] - 1) * 0.5 : 0)
            + ($candidate['place']->is_featured ? 1 : 0);

        return $score - $candidate['km'] / self::KM_PER_POINT;
    }

    protected function matches(Destination $place): int
    {
        return count(array_intersect($place->types->modelKeys(), $this->interestIds));
    }

    protected function stop(Destination $place, ?Destination $from): array
    {
        $km = $from ? $this->distance($from, $place) : 0.0;

        return [
            'destination' => $place,
            'km' => round($km, 1),
            'minutes' => $from ? self::driveMinutes($km) : 0,
            'matches' => $this->matches($place),
        ];
    }

    /**
     * Tonight's hotel near the day's last stop, within the budget. Keeps last
     * night's hotel when the day ends close to it, so nobody repacks daily.
     */
    protected function hotelFor(Destination $place, array $stars, ?Accommodation $previous): ?array
    {
        if ($previous && Geo::distanceKm((float) $place->latitude, (float) $place->longitude, (float) $previous->latitude, (float) $previous->longitude) <= self::SAME_HOTEL_KM) {
            return ['hotel' => $previous, 'alternatives' => new Collection, 'sameAsLastNight' => true];
        }

        // Look at a few more than needed, then prefer proper hotels among equal stars
        $hotels = Accommodation::nearest(
            (float) $place->latitude,
            (float) $place->longitude,
            12,
            with: ['images', 'affiliateNetwork'],
            stars: $stars,
        )->sortBy([
            fn ($a, $b) => ($b->star_rating ?? 0) <=> ($a->star_rating ?? 0),
            fn ($a, $b) => $this->typeRank($a) <=> $this->typeRank($b),
            fn ($a, $b) => $a->distance_km <=> $b->distance_km,
        ])->take(3)->values();

        if ($hotels->isEmpty()) {
            return null;
        }

        return ['hotel' => $hotels->first(), 'alternatives' => $hotels->slice(1)->values(), 'sameAsLastNight' => false];
    }

    protected function typeRank(Accommodation $hotel): int
    {
        $rank = array_search($hotel->type, self::PREFERRED_HOTEL_TYPES, true);

        return $rank === false ? count(self::PREFERRED_HOTEL_TYPES) : $rank;
    }

    protected function distance(Destination $a, Destination $b): float
    {
        return Geo::distanceKm((float) $a->latitude, (float) $a->longitude, (float) $b->latitude, (float) $b->longitude);
    }
}
