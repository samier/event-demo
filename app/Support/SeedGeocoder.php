<?php

namespace App\Support;

use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/**
 * Reverse-geocodes coordinates during seeding only (see config/geocoder.php).
 */
final class SeedGeocoder
{
    /**
     * Geocode coordinates one at a time. Each point runs Nominatim + timezone in
     * parallel (2 requests), then waits before the next (Nominatim rate limit).
     *
     * @param  list<array{latitude: float|int|string, longitude: float|int|string}>  $coordinates
     * @param  callable|null  $onResult  Called after each point with ($result, $current, $total)
     * @return list<array{latitude: float, longitude: float, address: ?array{city: string, region: string, country: string, country_code: string, timezone: string, label: string}, error: ?string}>
     */
    public static function resolveMany(array $coordinates, ?callable $onResult = null): array
    {
        $results = [];
        $total = count($coordinates);

        foreach ($coordinates as $index => $coordinate) {
            $lat = (float) $coordinate['latitude'];
            $lng = (float) $coordinate['longitude'];
            $current = $index + 1;

            try {
                $address = self::resolvePair($lat, $lng);
                $result = [
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'address' => $address,
                    'error' => null,
                ];
            } catch (Throwable $e) {
                $result = [
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'address' => null,
                    'error' => $e->getMessage(),
                ];
            }

            $results[] = $result;

            if ($onResult !== null) {
                $onResult($result, $current, $total);
            }

            if ($current < $total) {
                self::delay();
            }
        }

        return $results;
    }

    /**
     * @return array{city: string, region: string, country: string, country_code: string, timezone: string, label: string}
     */
    private static function resolvePair(float $lat, float $lng): array
    {
        $responses = Http::pool(function (Pool $pool) use ($lat, $lng) {
            $pool->as('n')
                ->withHeaders(['User-Agent' => config('geocoder.user_agent')])
                ->connectTimeout(5)
                ->timeout(10)
                ->get(config('geocoder.nominatim_url'), [
                    'lat' => $lat,
                    'lon' => $lng,
                    'format' => 'json',
                    'addressdetails' => 1,
                    'zoom' => 10,
                ]);

            $pool->as('t')
                ->connectTimeout(5)
                ->timeout(10)
                ->get(config('geocoder.timezone_url'), [
                    'latitude' => $lat,
                    'longitude' => $lng,
                ]);
        });

        return self::mergePooledResponses($responses['n'] ?? null, $responses['t'] ?? null);
    }

    /**
     * @return array{city: string, region: string, country: string, country_code: string, timezone: string, label: string}
     */
    private static function mergePooledResponses(mixed $nominatim, mixed $timezone): array
    {
        $nominatim = self::poolResponse($nominatim, 'Nominatim');
        $timezone = self::poolResponse($timezone, 'Timezone');

        if (! $nominatim->successful()) {
            throw new RuntimeException('Nominatim request failed.');
        }

        $payload = $nominatim->json();

        if (! is_array($payload) || ! isset($payload['address']) || ! is_array($payload['address'])) {
            throw new RuntimeException('Invalid Nominatim response.');
        }

        $address = self::parseNominatimAddress($payload['address'], $payload['display_name'] ?? null);
        $address['timezone'] = self::parseTimezoneResponse($timezone);

        return $address;
    }

    private static function poolResponse(mixed $result, string $label): Response
    {
        if ($result instanceof Throwable) {
            throw new RuntimeException("{$label}: {$result->getMessage()}");
        }

        if (! $result instanceof Response) {
            throw new RuntimeException("{$label}: no response returned.");
        }

        return $result;
    }

    private static function parseTimezoneResponse(Response $response): string
    {
        if (! $response->successful()) {
            throw new RuntimeException('Timezone request failed.');
        }

        $payload = $response->json();
        $timezone = is_array($payload) ? ($payload['timeZone'] ?? null) : null;

        if (! is_string($timezone) || $timezone === '') {
            throw new RuntimeException('Invalid timezone response.');
        }

        return $timezone;
    }

    /**
     * @param  array<string, mixed>  $address
     * @return array{city: string, region: string, country: string, country_code: string, label: string}
     */
    private static function parseNominatimAddress(array $address, ?string $displayName): array
    {
        $city = self::firstAddressPart($address, [
            'city', 'town', 'village', 'municipality', 'borough', 'suburb', 'county',
        ]);

        $region = self::firstAddressPart($address, [
            'state', 'region', 'state_district', 'province',
        ]);

        $country = (string) ($address['country'] ?? '');
        $countryCode = strtoupper((string) ($address['country_code'] ?? ''));

        if ($city === '') {
            $city = self::firstAddressPart($address, ['state', 'country']) ?: 'Unknown';
        }

        $labelParts = array_values(array_unique(array_filter([
            $city,
            $region !== '' && $region !== $city ? $region : null,
            $country !== '' ? $country : null,
        ])));

        $label = $labelParts !== []
            ? implode(', ', $labelParts)
            : ($displayName ?: 'Unknown location');

        return [
            'city' => $city,
            'region' => $region,
            'country' => $country,
            'country_code' => $countryCode,
            'label' => $label,
        ];
    }

    /**
     * @param  array<string, mixed>  $address
     * @param  list<string>  $keys
     */
    private static function firstAddressPart(array $address, array $keys): string
    {
        foreach ($keys as $key) {
            if (! empty($address[$key]) && is_string($address[$key])) {
                return $address[$key];
            }
        }

        return '';
    }

    public static function delay(): void
    {
        $seconds = (float) config('geocoder.nominatim_delay_seconds', 1.1);

        if ($seconds > 0) {
            usleep((int) ($seconds * 1_000_000));
        }
    }
}
