<?php

namespace App\Http\Controllers;

use App\Models\Attendee;
use App\Models\Event;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * A lightweight overview centred on attendee activity, so registrations are
     * visible in one place rather than only per-event.
     */
    public function index(): Response
    {
        $recent = Attendee::query()
            ->with(['event:id,payload,type,created_time'])
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (Attendee $a) => [
                'id' => $a->id,
                'name' => $a->displayName(),
                'event_id' => $a->event_id,
                'event_name' => $a->event?->name() ?? 'Event',
                'registered_at' => $a->created_at?->toIso8601String(),
            ]);

        $topEvents = Event::query()
            ->has('attendees')
            ->withCount('attendees')
            ->orderByDesc('attendees_count')
            ->limit(5)
            ->get()
            ->map(fn (Event $e) => [
                'id' => $e->id,
                'name' => $e->name(),
                'type' => $e->type,
                'attendees_count' => $e->attendees_count,
            ]);

        return Inertia::render('Dashboard', [
            'stats' => [
                'total_events' => Event::count(),
                'total_attendees' => Attendee::count(),
                'events_with_attendees' => Event::has('attendees')->count(),
            ],
            'recentRegistrations' => $recent,
            'topEvents' => $topEvents,
        ]);
    }
}
