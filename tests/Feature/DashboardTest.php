<?php

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows attendee stats and recent registrations on the dashboard', function () {
    $event = Event::factory()->for(User::factory())->create([
        'status' => 'published',
        'created_time' => now()->addDays(5)->timestamp,
        'payload' => ['name' => 'Synthwave Night', 'schedule' => ['ends_at' => now()->addDays(5)->timestamp]],
    ]);
    $event->attendees()->create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com']);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('stats.total_events', 1)
            ->where('stats.total_attendees', 1)
            ->where('stats.events_with_attendees', 1)
            ->has('recentRegistrations', 1)
            ->where('recentRegistrations.0.name', 'Ada L.')
            ->where('recentRegistrations.0.event_name', 'Synthwave Night')
            ->has('topEvents', 1)
            ->where('topEvents.0.attendees_count', 1)
        );
});

it('renders an empty dashboard without registrations', function () {
    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('stats.total_attendees', 0)
            ->has('recentRegistrations', 0)
            ->has('topEvents', 0)
        );
});
