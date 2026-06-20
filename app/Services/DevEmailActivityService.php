<?php

namespace App\Services;

use App\Models\Attendee;
use App\Models\Event;
use App\Models\CityAnchor;

class DevEmailActivityService
{
    /**
     * @return array<string, mixed>
     */
    public function pageData(): array
    {
        return [
            'totals' => [
                'attendees' => Attendee::count(),
                'confirmed' => Attendee::whereNotNull('confirmation_sent_at')->count(),
                'reminded_3d' => Attendee::whereNotNull('reminded_3d_at')->count(),
                'reminded_24h' => Attendee::whereNotNull('reminded_24h_at')->count(),
            ],
            'events' => $this->eventsWithEmailStats(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function eventsWithEmailStats(): array
    {
        return Event::query()
            ->has('attendees')
            ->withCount([
                'attendees',
                'attendees as confirmed_count' => fn ($q) => $q->whereNotNull('confirmation_sent_at'),
                'attendees as reminded_3d_count' => fn ($q) => $q->whereNotNull('reminded_3d_at'),
                'attendees as reminded_24h_count' => fn ($q) => $q->whereNotNull('reminded_24h_at'),
            ])
            ->orderByDesc('attendees_count')
            ->limit(50)
            ->get()
            ->map(function (Event $event) {
                $address = CityAnchor::resolveAddress($event->latitude, $event->longitude);

                return [
                    'id' => $event->id,
                    'name' => $event->name(),
                    'location' => $address['label'],
                    'starts_at_utc' => $event->startsAt()->toIso8601String(),
                    'starts_at_label' => $event->startsAt()->setTimezone($address['timezone'])->format('D, M j, Y · g:i A'),
                    'attendees_count' => (int) $event->attendees_count,
                    'confirmed_count' => (int) $event->getAttribute('confirmed_count'),
                    'reminded_3d_count' => (int) $event->getAttribute('reminded_3d_count'),
                    'reminded_24h_count' => (int) $event->getAttribute('reminded_24h_count'),
                ];
            })
            ->all();
    }
}
