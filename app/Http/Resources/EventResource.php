<?php

namespace App\Http\Resources;

use App\Models\CityAnchor;
use App\Models\Event;
use App\Support\EventImages;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Event
 */
class EventResource extends JsonResource
{
    /**
     * Shape an event for the front end: a flat, presentation-ready object that
     * hides the raw payload/timestamp details and adds the derived data the brief
     * asks for — a human-readable address, local placeholder images, and
     * timezone-aware times.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $address = CityAnchor::resolveAddress($this->latitude, $this->longitude);
        $start = $this->startsAt();
        $end = $this->endsAt();
        $tz = $address['timezone'];

        $payload = $this->payload ?? [];

        return [
            'id' => $this->id,
            'name' => $this->name(),
            'description' => $payload['description'] ?? null,
            'type' => $this->type,
            'status' => $this->status,

            'images' => EventImages::forEvent($this->id, $this->type),

            'venue' => [
                'name' => $payload['venue']['name'] ?? null,
                'capacity' => isset($payload['venue']['capacity']) ? (int) $payload['venue']['capacity'] : null,
            ],
            'organizer' => $payload['organizer']['name'] ?? ($this->user?->name),

            // Derived, human-readable location from raw lat/lng.
            'location' => [
                'city' => $address['city'],
                'region' => $address['region'],
                'country' => $address['country'],
                'country_code' => $address['country_code'],
                'label' => $address['label'],
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
            ],

            // Times are stored as UTC instants. We expose the canonical UTC ISO
            // string (so the client can render the viewer's local time) plus the
            // venue's timezone and a pre-formatted local string for display.
            'time' => [
                'timezone' => $tz,
                'starts_at_utc' => $start->toIso8601String(),
                'ends_at_utc' => $end?->toIso8601String(),
                'starts_at_local' => $start->setTimezone($tz)->toIso8601String(),
                'starts_at_label' => $start->setTimezone($tz)->format('D, M j, Y · g:i A'),
                'tz_abbreviation' => $start->setTimezone($tz)->format('T'),
            ],

            'pricing' => [
                'currency' => $payload['pricing']['currency'] ?? 'USD',
                'min_price' => isset($payload['pricing']['min_price']) ? (float) $payload['pricing']['min_price'] : null,
            ],

            'attendees_count' => $this->whenCounted('attendees'),
        ];
    }
}
