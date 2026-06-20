<?php

use App\Support\Geocoder;

it('reverse geocodes a coordinate to the nearest city with a timezone', function () {
    $result = Geocoder::resolve(51.5074, -0.1278);

    expect($result['city'])->toBe('London')
        ->and($result['country'])->toBe('United Kingdom')
        ->and($result['timezone'])->toBe('Europe/London')
        ->and($result['label'])->toBe('London, England, United Kingdom');
});

it('snaps a jittered coordinate to the correct anchor city', function () {
    // ~0.4° from the New York anchor — still unambiguously New York.
    $result = Geocoder::resolve(40.9128, -74.4060);

    expect($result['city'])->toBe('New York')
        ->and($result['timezone'])->toBe('America/New_York');
});

it('falls back gracefully when coordinates are missing', function () {
    $result = Geocoder::resolve(null, null);

    expect($result['city'])->toBe('Unknown')
        ->and($result['timezone'])->toBe('UTC');
});

it('builds a bounding box around a known city', function () {
    $box = Geocoder::boundingBoxForCity('Tokyo');

    expect($box)->not->toBeNull();
    [$minLat, $maxLat, $minLng, $maxLng] = $box;
    expect(35.6762)->toBeGreaterThan($minLat)->toBeLessThan($maxLat)
        ->and(139.6503)->toBeGreaterThan($minLng)->toBeLessThan($maxLng);
});
