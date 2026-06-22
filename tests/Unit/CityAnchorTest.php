<?php

use App\Models\CityAnchor;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedCityAnchors();
});

it('resolves jittered coordinates to the nearest anchor address', function () {
    $result = CityAnchor::resolveAddress(51.5123, -0.1301);

    expect($result['city'])->toBe('London')
        ->and($result['timezone'])->toBe('Europe/London');
});

it('returns unavailable for missing coordinates', function () {
    $result = CityAnchor::resolveAddress(null, null);

    expect($result['city'])->toBe('Unknown')
        ->and($result['timezone'])->toBe('UTC');
});

it('builds a bounding box around a known city anchor', function () {
    $box = CityAnchor::boundingBoxForCity('London');

    expect($box)->not->toBeNull();
    [$minLat, $maxLat, $minLng, $maxLng] = $box;
    expect(51.5074)->toBeGreaterThan($minLat)->toBeLessThan($maxLat)
        ->and(-0.1278)->toBeGreaterThan($minLng)->toBeLessThan($maxLng);
});

it('lists filter options from the table', function () {
    expect(CityAnchor::filterOptions())
        ->not->toBeEmpty()
        ->and(CityAnchor::filterOptions()[0])
        ->toHaveKeys(['city', 'country', 'country_code', 'lat', 'lng']);
});

it('provides coordinate pairs for event seeding', function () {
    expect(CityAnchor::coordinatePairs())->toHaveCount(75);
});
