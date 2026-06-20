<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Reverse geocoding (used by CityAnchorSeeder only)
    |--------------------------------------------------------------------------
    |
    | Nominatim requires a valid User-Agent — see
    | https://operations.osmfoundation.org/policies/nominatim/
    |
    */

    'nominatim_url' => env('GEOCODER_NOMINATIM_URL', 'https://nominatim.openstreetmap.org/reverse'),

    'timezone_url' => env('GEOCODER_TIMEZONE_URL', 'https://timeapi.io/api/TimeZone/coordinate'),

    'user_agent' => env('GEOCODER_USER_AGENT', env('APP_NAME', 'Laravel').' ('.env('APP_URL', 'http://localhost').')'),

    /** Seconds to wait between anchor lookups (Nominatim policy: max 1 req/s). */
    'nominatim_delay_seconds' => (float) env('GEOCODER_NOMINATIM_DELAY', 1.1),

];
