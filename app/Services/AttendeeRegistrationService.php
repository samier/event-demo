<?php

namespace App\Services;

use App\Http\Resources\AttendeeResource;
use App\Mail\RegistrationConfirmation;
use App\Models\Attendee;
use App\Models\Event;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class AttendeeRegistrationService
{
    /**
     * Register interest for an event and email a confirmation.
     *
     * @param  array{name: string, email: string}  $data
     * @return array{message: string, attendee: AttendeeResource, attendees_count: int}
     */
    public function register(Event $event, array $data): array
    {
        try {
            /** @var Attendee $attendee */
            $attendee = $event->attendees()->create([
                'name' => $data['name'],
                'email' => $data['email'],
            ]);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'email' => 'This email is already registered for this event. Please use a different email address.',
            ]);
        }

        Mail::to($attendee->email)->send(new RegistrationConfirmation($event, $attendee));
        $attendee->forceFill(['confirmation_sent_at' => now()])->save();

        return [
            'message' => "You're in! We've emailed a confirmation to {$attendee->email}.",
            'attendee' => new AttendeeResource($attendee),
            'attendees_count' => $event->attendees()->count(),
        ];
    }
}
