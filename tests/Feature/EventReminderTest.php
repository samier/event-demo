<?php

use App\Mail\EventReminder;
use App\Models\Attendee;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(fn () => Mail::fake());

function eventStartingIn(int $seconds): Event
{
    return Event::factory()->for(User::factory())->create([
        'status' => 'published',
        'created_time' => now()->timestamp + $seconds,
        'latitude' => 40.7128,
        'longitude' => -74.0060,
    ]);
}

function registerFor(Event $event): Attendee
{
    return $event->attendees()->create(['name' => 'Test Goer', 'email' => 'goer@example.com']);
}

it('sends a 3-day reminder for events ~2 days out', function () {
    $event = eventStartingIn(2 * 86400);
    $attendee = registerFor($event);

    $this->artisan('events:send-reminders')->assertSuccessful();

    Mail::assertSent(EventReminder::class, fn ($mail) => $mail->window === '3-day' && $mail->hasTo('goer@example.com'));
    expect($attendee->fresh()->reminded_3d_at)->not->toBeNull()
        ->and($attendee->fresh()->reminded_24h_at)->toBeNull();
});

it('sends a 24-hour reminder for events within a day', function () {
    $event = eventStartingIn(12 * 3600);
    $attendee = registerFor($event);

    $this->artisan('events:send-reminders')->assertSuccessful();

    Mail::assertSent(EventReminder::class, fn ($mail) => $mail->window === '24-hour');
    expect($attendee->fresh()->reminded_24h_at)->not->toBeNull();
});

it('never double-sends a reminder when run repeatedly', function () {
    $event = eventStartingIn(2 * 86400);
    registerFor($event);

    $this->artisan('events:send-reminders');
    $this->artisan('events:send-reminders');

    Mail::assertSent(EventReminder::class, 1);
});

it('does not remind for events outside both windows', function () {
    registerFor(eventStartingIn(10 * 86400)); // 10 days out

    $this->artisan('events:send-reminders');

    Mail::assertNothingSent();
});

it('can be triggered manually for a single event, ignoring the window', function () {
    // 10 days out — normally nothing would send.
    $event = eventStartingIn(10 * 86400);
    registerFor($event);

    $this->artisan('events:send-reminders', ['--event' => $event->id, '--force' => true])
        ->assertSuccessful();

    // --force sends both windows immediately.
    Mail::assertSent(EventReminder::class, 2);
});

it('does not send anything in pretend mode', function () {
    $attendee = registerFor(eventStartingIn(2 * 86400));

    $this->artisan('events:send-reminders', ['--pretend' => true])->assertSuccessful();

    Mail::assertNothingSent();
    expect($attendee->fresh()->reminded_3d_at)->toBeNull();
});

it('can restrict to a single window', function () {
    registerFor(eventStartingIn(2 * 86400));

    $this->artisan('events:send-reminders', ['--window' => '3-day'])->assertSuccessful();

    Mail::assertSent(EventReminder::class, fn ($mail) => $mail->window === '3-day');
    Mail::assertNotSent(EventReminder::class, fn ($mail) => $mail->window === '24-hour');
});
