<?php

namespace Database\Seeders;

use App\Models\CityAnchor;
use App\Support\SeedGeocoder;
use Illuminate\Database\Seeder;

/**
 * Seeds city_anchors: fixed coordinates from database/data/city_anchor_coordinates.php,
 * address fields from the geocoder APIs (config/geocoder.php).
 */
class CityAnchorSeeder extends Seeder
{
    public function run(): void
    {
        $coordinates = require database_path('data/city_anchor_coordinates.php');
        $total = count($coordinates);

        $this->command?->info("Seeding {$total} city anchors (Nominatim + timezone per point)…");

        $inserted = 0;

        SeedGeocoder::resolveMany($coordinates, function (array $result, int $current, int $total) use (&$inserted) {
            if ($result['address'] === null) {
                $this->command?->warn(sprintf(
                    '  [%d/%d] %.4f, %.4f — geocode failed (%s)',
                    $current,
                    $total,
                    $result['latitude'],
                    $result['longitude'],
                    $result['error'] ?? 'unknown error',
                ));

                return;
            }

            $address = $result['address'];

            CityAnchor::query()->updateOrCreate(
                ['latitude' => $result['latitude'], 'longitude' => $result['longitude']],
                [
                    'city' => $address['city'],
                    'region' => $address['region'],
                    'country' => $address['country'],
                    'country_code' => $address['country_code'],
                    'timezone' => $address['timezone'],
                    'label' => $address['label'],
                ],
            );

            $inserted++;
            $this->command?->line(sprintf(
                '  [%d/%d] %s',
                $current,
                $total,
                $address['label'],
            ));
        });

        CityAnchor::clearCache();

        $this->command?->info("Done. {$inserted}/{$total} city anchors saved.");
    }
}
