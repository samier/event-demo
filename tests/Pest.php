<?php

use Database\Seeders\CityAnchorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->in('Feature');

pest()->extend(TestCase::class)
    ->in('Unit');

beforeEach(function () {
    Config::set('geocoder.nominatim_delay_seconds', 0);
});

function seedCityAnchors(): void
{
    Http::fake([
        'nominatim.openstreetmap.org/*' => Http::response([
            'display_name' => 'London, England, United Kingdom',
            'address' => [
                'city' => 'London',
                'state' => 'England',
                'country' => 'United Kingdom',
                'country_code' => 'gb',
            ],
        ]),
        'timeapi.io/*' => Http::response(['timeZone' => 'Europe/London']),
    ]);

    (new CityAnchorSeeder)->run();
}
