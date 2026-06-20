<?php

use App\Http\Controllers\DevController;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

it('reports per-event email activity and totals', function () {
    // The real route is local-only; register it here so we can exercise the page.
    Route::get('dev/emails', [DevController::class, 'emailActivity']);

    $event = Event::factory()->for(User::factory())->create([
        'status' => 'published',
        'created_time' => now()->addDays(2)->timestamp,
        'payload' => ['name' => 'Synthwave Night', 'schedule' => ['ends_at' => now()->addDays(2)->timestamp]],
    ]);

    $event->attendees()->create([
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'confirmation_sent_at' => now(),
        'reminded_3d_at' => now(),
    ]);
    $event->attendees()->create([
        'name' => 'Grace Hopper',
        'email' => 'grace@example.com',
        'confirmation_sent_at' => now(),
    ]);

    $this->get('/dev/emails')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dev/EmailActivity')
            ->where('totals.attendees', 2)
            ->where('totals.confirmed', 2)
            ->where('totals.reminded_3d', 1)
            ->where('totals.reminded_24h', 0)
            ->where('events.0.name', 'Synthwave Night')
            ->where('events.0.attendees_count', 2)
            ->where('events.0.confirmed_count', 2)
            ->where('events.0.reminded_3d_count', 1)
            ->where('events.0.reminded_24h_count', 0)
        );
});
