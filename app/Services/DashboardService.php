<?php

namespace App\Services;

use App\Models\Attendee;
use App\Models\Event;

class DashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function pageData(): array
    {
        return [
            'stats' => [
                'total_events' => Event::count(),
                'total_attendees' => Attendee::count(),
                'events_with_attendees' => Event::has('attendees')->count(),
            ],
            'recentRegistrations' => $this->recentRegistrations(),
            'topEvents' => $this->topEvents(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function recentRegistrations(): array
    {
        return Attendee::query()
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
            ])
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function topEvents(): array
    {
        return Event::query()
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
            ])
            ->all();
    }
}
