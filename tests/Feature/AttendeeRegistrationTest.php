<?php

use App\Mail\RegistrationConfirmation;
use App\Models\Attendee;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    Mail::fake();
});

function publishedEvent(): Event
{
    return Event::factory()->for(User::factory())->create([
        'status' => 'published',
        'created_time' => now()->addDays(5)->timestamp,
        'latitude' => 48.8566,
        'longitude' => 2.3522,
    ]);
}

it('registers an attendee and emails a confirmation', function () {
    $event = publishedEvent();

    $this->postJson(route('events.attendees.store', $event), [
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
    ])
        ->assertCreated()
        ->assertJsonPath('attendees_count', 1)
        // Public name is first name + last initial for privacy.
        ->assertJsonPath('attendee.name', 'Ada L.')
        ->assertJsonPath('attendee.initials', 'AL')
        // Email is masked in the public response so the attendee list never
        // exposes full addresses.
        ->assertJsonPath('attendee.email_masked', 'ad•••@example.com');

    $this->assertDatabaseHas('attendees', [
        'event_id' => $event->id,
        'email' => 'ada@example.com',
        'name' => 'Ada Lovelace',
    ]);

    Mail::assertSent(RegistrationConfirmation::class, fn ($mail) => $mail->hasTo('ada@example.com'));
});

it('exposes the attendee list and count on the event detail page', function () {
    $event = publishedEvent();
    $event->attendees()->create(['name' => 'Grace Hopper', 'email' => 'grace@example.com']);

    $this->get(route('events.show', $event))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Events/Show')
            ->where('event.attendees_count', 1)
            ->has('attendees', 1)
            ->where('attendees.0.name', 'Grace H.') // first name + last initial
            ->where('attendees.0.initials', 'GH')
            ->missing('attendees.0.email') // only the masked form is exposed
        );
});

it('validates the registration input', function () {
    $event = publishedEvent();

    $this->postJson(route('events.attendees.store', $event), ['name' => '', 'email' => 'not-an-email'])
        ->assertStatus(422)
        ->assertJsonStructure(['message', 'errors' => ['name', 'email']]);

    Mail::assertNothingSent();
});

it('rejects a duplicate email for the same event with a clear message', function () {
    $event = publishedEvent();
    $payload = ['name' => 'Grace Hopper', 'email' => 'grace@example.com'];

    $this->postJson(route('events.attendees.store', $event), $payload)->assertCreated();

    $this->postJson(route('events.attendees.store', $event), $payload)
        ->assertStatus(422)
        ->assertJsonPath('errors.email.0', 'This email is already registered for this event. Please use a different email address.');

    // No second row, and the confirmation only went out once.
    expect(Attendee::where('event_id', $event->id)->count())->toBe(1);
    Mail::assertSent(RegistrationConfirmation::class, 1);
});

it('allows the same email to register for a different event', function () {
    $eventA = publishedEvent();
    $eventB = publishedEvent();
    $payload = ['name' => 'Grace Hopper', 'email' => 'grace@example.com'];

    $this->postJson(route('events.attendees.store', $eventA), $payload)->assertCreated();
    $this->postJson(route('events.attendees.store', $eventB), $payload)->assertCreated();

    expect(Attendee::where('email', 'grace@example.com')->count())->toBe(2);
});
