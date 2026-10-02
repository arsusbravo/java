<?php

namespace App\Support;

class Geo
{
    /** Mean Earth radius. */
    public const EARTH_RADIUS_KM = 6371.0;

    /** Kilometres per degree of latitude. */
    public const KM_PER_DEGREE = 111.045;

    /**
     * Great-circle distance between two points (haversine formula).
     */
    public static function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return self::EARTH_RADIUS_KM * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /**
     * Latitude/longitude bounds of a square around a point, for a cheap indexed
     * pre-filter before calculating exact distances.
     *
     * @return array{0: array{float, float}, 1: array{float, float}} [[minLat, maxLat], [minLng, maxLng]]
     */
    public static function boundingBox(float $lat, float $lng, float $km): array
    {
        $dLat = $km / self::KM_PER_DEGREE;
        $dLng = $km / (self::KM_PER_DEGREE * max(cos(deg2rad($lat)), 0.01));

        return [[$lat - $dLat, $lat + $dLat], [$lng - $dLng, $lng + $dLng]];
    }
}
