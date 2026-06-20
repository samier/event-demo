<?php

namespace App\Support;

use Database\Seeders\EventSeeder;

/**
 * Curated list of the city anchors used by the {@see EventSeeder}.
 *
 * Seeded events are jittered by at most ±0.5° around one of these anchors, so the
 * nearest anchor to an event's coordinates is always the city it belongs to. This
 * lets us turn a bare lat/lng into a human-readable address and an IANA timezone
 * completely offline — no external geocoding API, no per-row network calls, and a
 * fully deterministic result for the 1.25M-row dataset.
 *
 * Each row: [lat, lng, city, region, country, country_code, iana_timezone].
 */
final class Cities
{
    /**
     * @var list<array{0: float, 1: float, 2: string, 3: string, 4: string, 5: string, 6: string}>
     */
    public const ANCHORS = [
        // United States
        [40.7128, -74.0060, 'New York', 'New York', 'United States', 'US', 'America/New_York'],
        [34.0522, -118.2437, 'Los Angeles', 'California', 'United States', 'US', 'America/Los_Angeles'],
        [41.8781, -87.6298, 'Chicago', 'Illinois', 'United States', 'US', 'America/Chicago'],
        [29.7604, -95.3698, 'Houston', 'Texas', 'United States', 'US', 'America/Chicago'],
        [33.4484, -112.0740, 'Phoenix', 'Arizona', 'United States', 'US', 'America/Phoenix'],
        [39.9526, -75.1652, 'Philadelphia', 'Pennsylvania', 'United States', 'US', 'America/New_York'],
        [29.4241, -98.4936, 'San Antonio', 'Texas', 'United States', 'US', 'America/Chicago'],
        [32.7157, -117.1611, 'San Diego', 'California', 'United States', 'US', 'America/Los_Angeles'],
        [32.7767, -96.7970, 'Dallas', 'Texas', 'United States', 'US', 'America/Chicago'],
        [37.3382, -121.8863, 'San Jose', 'California', 'United States', 'US', 'America/Los_Angeles'],
        [30.2672, -97.7431, 'Austin', 'Texas', 'United States', 'US', 'America/Chicago'],
        [37.7749, -122.4194, 'San Francisco', 'California', 'United States', 'US', 'America/Los_Angeles'],
        [47.6062, -122.3321, 'Seattle', 'Washington', 'United States', 'US', 'America/Los_Angeles'],
        [39.7392, -104.9903, 'Denver', 'Colorado', 'United States', 'US', 'America/Denver'],
        [42.3601, -71.0589, 'Boston', 'Massachusetts', 'United States', 'US', 'America/New_York'],
        [36.1699, -115.1398, 'Las Vegas', 'Nevada', 'United States', 'US', 'America/Los_Angeles'],
        [25.7617, -80.1918, 'Miami', 'Florida', 'United States', 'US', 'America/New_York'],
        [33.7490, -84.3880, 'Atlanta', 'Georgia', 'United States', 'US', 'America/New_York'],
        [38.9072, -77.0369, 'Washington', 'District of Columbia', 'United States', 'US', 'America/New_York'],
        [36.1627, -86.7816, 'Nashville', 'Tennessee', 'United States', 'US', 'America/Chicago'],
        [45.5152, -122.6784, 'Portland', 'Oregon', 'United States', 'US', 'America/Los_Angeles'],
        [29.9511, -90.0715, 'New Orleans', 'Louisiana', 'United States', 'US', 'America/Chicago'],
        // Canada
        [43.6532, -79.3832, 'Toronto', 'Ontario', 'Canada', 'CA', 'America/Toronto'],
        [45.5019, -73.5674, 'Montreal', 'Quebec', 'Canada', 'CA', 'America/Toronto'],
        [49.2827, -123.1207, 'Vancouver', 'British Columbia', 'Canada', 'CA', 'America/Vancouver'],
        [51.0447, -114.0719, 'Calgary', 'Alberta', 'Canada', 'CA', 'America/Edmonton'],
        [45.4215, -75.6972, 'Ottawa', 'Ontario', 'Canada', 'CA', 'America/Toronto'],
        [53.5461, -113.4938, 'Edmonton', 'Alberta', 'Canada', 'CA', 'America/Edmonton'],
        [46.8139, -71.2080, 'Quebec City', 'Quebec', 'Canada', 'CA', 'America/Toronto'],
        [49.8951, -97.1384, 'Winnipeg', 'Manitoba', 'Canada', 'CA', 'America/Winnipeg'],
        // Mexico
        [19.4326, -99.1332, 'Mexico City', 'CDMX', 'Mexico', 'MX', 'America/Mexico_City'],
        [20.6597, -103.3496, 'Guadalajara', 'Jalisco', 'Mexico', 'MX', 'America/Mexico_City'],
        [25.6866, -100.3161, 'Monterrey', 'Nuevo León', 'Mexico', 'MX', 'America/Monterrey'],
        [19.0414, -98.2063, 'Puebla', 'Puebla', 'Mexico', 'MX', 'America/Mexico_City'],
        [32.5149, -117.0382, 'Tijuana', 'Baja California', 'Mexico', 'MX', 'America/Tijuana'],
        [21.1619, -86.8515, 'Cancún', 'Quintana Roo', 'Mexico', 'MX', 'America/Cancun'],
        [20.9674, -89.5926, 'Mérida', 'Yucatán', 'Mexico', 'MX', 'America/Merida'],
        // Europe
        [51.5074, -0.1278, 'London', 'England', 'United Kingdom', 'GB', 'Europe/London'],
        [48.8566, 2.3522, 'Paris', 'Île-de-France', 'France', 'FR', 'Europe/Paris'],
        [52.5200, 13.4050, 'Berlin', 'Berlin', 'Germany', 'DE', 'Europe/Berlin'],
        [40.4168, -3.7038, 'Madrid', 'Community of Madrid', 'Spain', 'ES', 'Europe/Madrid'],
        [41.9028, 12.4964, 'Rome', 'Lazio', 'Italy', 'IT', 'Europe/Rome'],
        [52.3676, 4.9041, 'Amsterdam', 'North Holland', 'Netherlands', 'NL', 'Europe/Amsterdam'],
        [41.3851, 2.1734, 'Barcelona', 'Catalonia', 'Spain', 'ES', 'Europe/Madrid'],
        [48.1351, 11.5820, 'Munich', 'Bavaria', 'Germany', 'DE', 'Europe/Berlin'],
        [45.4642, 9.1900, 'Milan', 'Lombardy', 'Italy', 'IT', 'Europe/Rome'],
        [48.2082, 16.3738, 'Vienna', 'Vienna', 'Austria', 'AT', 'Europe/Vienna'],
        [50.0755, 14.4378, 'Prague', 'Prague', 'Czechia', 'CZ', 'Europe/Prague'],
        [38.7223, -9.1393, 'Lisbon', 'Lisbon', 'Portugal', 'PT', 'Europe/Lisbon'],
        [53.3498, -6.2603, 'Dublin', 'Leinster', 'Ireland', 'IE', 'Europe/Dublin'],
        [55.6761, 12.5683, 'Copenhagen', 'Capital Region', 'Denmark', 'DK', 'Europe/Copenhagen'],
        [59.3293, 18.0686, 'Stockholm', 'Stockholm', 'Sweden', 'SE', 'Europe/Stockholm'],
        [59.9139, 10.7522, 'Oslo', 'Oslo', 'Norway', 'NO', 'Europe/Oslo'],
        [60.1699, 24.9384, 'Helsinki', 'Uusimaa', 'Finland', 'FI', 'Europe/Helsinki'],
        [50.8503, 4.3517, 'Brussels', 'Brussels', 'Belgium', 'BE', 'Europe/Brussels'],
        [47.3769, 8.5417, 'Zurich', 'Zurich', 'Switzerland', 'CH', 'Europe/Zurich'],
        [52.2297, 21.0122, 'Warsaw', 'Masovia', 'Poland', 'PL', 'Europe/Warsaw'],
        [47.4979, 19.0402, 'Budapest', 'Budapest', 'Hungary', 'HU', 'Europe/Budapest'],
        [37.9838, 23.7275, 'Athens', 'Attica', 'Greece', 'GR', 'Europe/Athens'],
        [45.7640, 4.8357, 'Lyon', 'Auvergne-Rhône-Alpes', 'France', 'FR', 'Europe/Paris'],
        [53.5511, 9.9937, 'Hamburg', 'Hamburg', 'Germany', 'DE', 'Europe/Berlin'],
        [53.4808, -2.2426, 'Manchester', 'England', 'United Kingdom', 'GB', 'Europe/London'],
        [55.9533, -3.1883, 'Edinburgh', 'Scotland', 'United Kingdom', 'GB', 'Europe/London'],
        [50.1109, 8.6821, 'Frankfurt', 'Hesse', 'Germany', 'DE', 'Europe/Berlin'],
        [50.0647, 19.9450, 'Kraków', 'Lesser Poland', 'Poland', 'PL', 'Europe/Warsaw'],
        [41.1579, -8.6291, 'Porto', 'Porto', 'Portugal', 'PT', 'Europe/Lisbon'],
        [40.8518, 14.2681, 'Naples', 'Campania', 'Italy', 'IT', 'Europe/Rome'],
        // Global hubs
        [35.6762, 139.6503, 'Tokyo', 'Tokyo', 'Japan', 'JP', 'Asia/Tokyo'],
        [37.5665, 126.9780, 'Seoul', 'Seoul', 'South Korea', 'KR', 'Asia/Seoul'],
        [1.3521, 103.8198, 'Singapore', 'Singapore', 'Singapore', 'SG', 'Asia/Singapore'],
        [-33.8688, 151.2093, 'Sydney', 'New South Wales', 'Australia', 'AU', 'Australia/Sydney'],
        [-37.8136, 144.9631, 'Melbourne', 'Victoria', 'Australia', 'AU', 'Australia/Melbourne'],
        [25.2048, 55.2708, 'Dubai', 'Dubai', 'United Arab Emirates', 'AE', 'Asia/Dubai'],
        [-23.5505, -46.6333, 'São Paulo', 'São Paulo', 'Brazil', 'BR', 'America/Sao_Paulo'],
        [-34.6037, -58.3816, 'Buenos Aires', 'Buenos Aires', 'Argentina', 'AR', 'America/Argentina/Buenos_Aires'],
    ];
}
