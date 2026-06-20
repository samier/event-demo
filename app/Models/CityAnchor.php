<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * @property int $id
 * @property float $latitude
 * @property float $longitude
 * @property string $city
 * @property string $region
 * @property string $country
 * @property string $country_code
 * @property string $timezone
 * @property string $label
 */
class CityAnchor extends Model
{
    /** Half-width (degrees) of the city filter bounding box (seeder jitters ±0.5°). */
    private const BOUNDING_BOX_HALF_WIDTH = 0.55;

    protected $fillable = [
        'latitude',
        'longitude',
        'city',
        'region',
        'country',
        'country_code',
        'timezone',
        'label',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    /**
     * Resolve event coordinates to a stored anchor address (offline nearest match).
     *
     * @return array{city: string, region: string, country: string, country_code: string, timezone: string, label: string}
     */
    public static function resolveAddress(?float $lat, ?float $lng): array
    {
        if ($lat === null || $lng === null) {
            return self::unavailableAddress();
        }

        return self::nearest($lat, $lng)?->toAddressArray() ?? self::unavailableAddress();
    }

    /**
     * @return array{city: string, region: string, country: string, country_code: string, timezone: string, label: string}
     */
    public function toAddressArray(): array
    {
        return [
            'city' => $this->city,
            'region' => $this->region,
            'country' => $this->country,
            'country_code' => $this->country_code,
            'timezone' => $this->timezone,
            'label' => $this->label,
        ];
    }

    public static function nearest(float $lat, float $lng): ?self
    {
        $best = null;
        $bestDistance = PHP_FLOAT_MAX;

        foreach (self::cached() as $anchor) {
            $dLat = $lat - $anchor->latitude;
            $dLng = $lng - $anchor->longitude;
            $distance = ($dLat * $dLat) + ($dLng * $dLng);

            if ($distance < $bestDistance) {
                $bestDistance = $distance;
                $best = $anchor;
            }
        }

        return $best;
    }

    public static function findByCity(string $city): ?self
    {
        return self::cached()->first(fn (self $a) => strcasecmp($a->city, $city) === 0);
    }

    /**
     * @return list<array{city: string, country: string, country_code: string, lat: float, lng: float}>
     */
    public static function filterOptions(): array
    {
        return self::cached()
            ->sortBy(fn (self $a) => [$a->country, $a->city])
            ->values()
            ->map(fn (self $a) => [
                'city' => $a->city,
                'country' => $a->country,
                'country_code' => $a->country_code,
                'lat' => $a->latitude,
                'lng' => $a->longitude,
            ])
            ->all();
    }

    /**
     * @return array{0: float, 1: float, 2: float, 3: float}|null
     */
    public static function boundingBoxForCity(string $city): ?array
    {
        $anchor = self::findByCity($city);

        if ($anchor === null) {
            return null;
        }

        return [
            $anchor->latitude - self::BOUNDING_BOX_HALF_WIDTH,
            $anchor->latitude + self::BOUNDING_BOX_HALF_WIDTH,
            $anchor->longitude - self::BOUNDING_BOX_HALF_WIDTH,
            $anchor->longitude + self::BOUNDING_BOX_HALF_WIDTH,
        ];
    }

    /**
     * @return list<array{0: float, 1: float}>
     */
    public static function coordinatePairs(): array
    {
        return self::cached()
            ->map(fn (self $a) => [$a->latitude, $a->longitude])
            ->all();
    }

    /**
     * @return array{city: string, region: string, country: string, country_code: string, timezone: string, label: string}
     */
    private static function unavailableAddress(): array
    {
        return [
            'city' => 'Unknown',
            'region' => '',
            'country' => '',
            'country_code' => '',
            'timezone' => 'UTC',
            'label' => 'Location unavailable',
        ];
    }

    /** @return Collection<int, self> */
    private static function cached(): Collection
    {
        $rows = Cache::remember('city_anchors.all', 3600, function (): array {
            return self::query()
                ->orderBy('id')
                ->get()
                ->map(fn (self $anchor) => $anchor->getAttributes())
                ->all();
        });

        if (! is_array($rows)) {
            Cache::forget('city_anchors.all');

            return self::cached();
        }

        return self::hydrate($rows);
    }

    public static function clearCache(): void
    {
        Cache::forget('city_anchors.all');
    }
}
