<?php

namespace App\Support;

/**
 * Offline reverse geocoder.
 *
 * Resolves a latitude/longitude to the nearest known city anchor (see {@see Cities}).
 * Because the dataset jitters each event by at most ±0.5° around an anchor, and the
 * anchors are separated by far more than that, the nearest anchor is always the
 * correct city — giving us a human-readable address and a timezone with zero network
 * calls. Lookups are O(number of anchors) and the result for a given event never
 * changes, so it is safe to compute on read.
 */
final class Geocoder
{
    /**
     * Half-width (in degrees) of the bounding box used when filtering events by city.
     * Slightly larger than the seeder's ±0.5° jitter so every event around an anchor
     * is captured.
     */
    public const CITY_BOX = 0.55;

    /**
     * Resolve coordinates to a structured address + timezone.
     *
     * @return array{city: string, region: string, country: string, country_code: string, timezone: string, label: string}
     */
    public static function resolve(?float $lat, ?float $lng): array
    {
        if ($lat === null || $lng === null) {
            return [
                'city' => 'Unknown',
                'region' => '',
                'country' => '',
                'country_code' => '',
                'timezone' => 'UTC',
                'label' => 'Location unavailable',
            ];
        }

        $anchor = self::nearestAnchor($lat, $lng);

        [, , $city, $region, $country, $code, $tz] = $anchor;

        return [
            'city' => $city,
            'region' => $region,
            'country' => $country,
            'country_code' => $code,
            'timezone' => $tz,
            'label' => "{$city}, {$region}, {$country}",
        ];
    }

    /**
     * @return array{0: float, 1: float, 2: string, 3: string, 4: string, 5: string, 6: string}
     */
    private static function nearestAnchor(float $lat, float $lng): array
    {
        $best = Cities::ANCHORS[0];
        $bestDistance = PHP_FLOAT_MAX;

        foreach (Cities::ANCHORS as $anchor) {
            // Squared euclidean distance in degree space — monotonic with true
            // distance at this scale, and cheap (no sqrt / trig needed for a
            // nearest-neighbour comparison among well-separated anchors).
            $dLat = $lat - $anchor[0];
            $dLng = $lng - $anchor[1];
            $distance = ($dLat * $dLat) + ($dLng * $dLng);

            if ($distance < $bestDistance) {
                $bestDistance = $distance;
                $best = $anchor;
            }
        }

        return $best;
    }

    /**
     * City options for the location filter, grouped by country and sorted.
     * Each option carries the anchor coordinates so the client/controller can
     * translate a selected city into a lat/lng bounding box.
     *
     * @return list<array{city: string, country: string, country_code: string, lat: float, lng: float}>
     */
    public static function filterOptions(): array
    {
        $options = array_map(static fn (array $a) => [
            'city' => $a[2],
            'country' => $a[4],
            'country_code' => $a[5],
            'lat' => $a[0],
            'lng' => $a[1],
        ], Cities::ANCHORS);

        usort($options, static function (array $a, array $b) {
            return [$a['country'], $a['city']] <=> [$b['country'], $b['city']];
        });

        return $options;
    }

    /**
     * Bounding box [minLat, maxLat, minLng, maxLng] around a city anchor, used to
     * filter events by location directly against the indexed lat/lng columns.
     *
     * @return array{0: float, 1: float, 2: float, 3: float}|null
     */
    public static function boundingBoxForCity(string $city): ?array
    {
        foreach (Cities::ANCHORS as $anchor) {
            if (strcasecmp($anchor[2], $city) === 0) {
                return [
                    $anchor[0] - self::CITY_BOX,
                    $anchor[0] + self::CITY_BOX,
                    $anchor[1] - self::CITY_BOX,
                    $anchor[1] + self::CITY_BOX,
                ];
            }
        }

        return null;
    }
}
