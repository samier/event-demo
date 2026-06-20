<?php

namespace App\Http\Controllers;

use App\Models\Attendee;
use App\Models\Event;
use App\Support\Geocoder;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Local-only developer/testing tools. Registered only in the local environment
 * (see routes/web.php). The email-activity page lets a reviewer see, at a glance,
 * how many confirmation and reminder emails have gone out — and trigger or preview
 * them — without waiting days or reading the log file.
 */
class DevController extends Controller
{
    public function emailActivity(): Response
    {
        $events = Event::query()
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
                $address = Geocoder::resolve($event->latitude, $event->longitude);

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
            });

        return Inertia::render('Dev/EmailActivity', [
            'totals' => [
                'attendees' => Attendee::count(),
                'confirmed' => Attendee::whereNotNull('confirmation_sent_at')->count(),
                'reminded_3d' => Attendee::whereNotNull('reminded_3d_at')->count(),
                'reminded_24h' => Attendee::whereNotNull('reminded_24h_at')->count(),
            ],
            'events' => $events,
        ]);
    }
}
