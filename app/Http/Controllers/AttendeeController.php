<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAttendeeRequest;
use App\Http\Resources\AttendeeResource;
use App\Mail\RegistrationConfirmation;
use App\Models\Attendee;
use App\Models\Event;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class AttendeeController extends Controller
{
    /**
     * Register interest / attendance for an event and email a confirmation.
     *
     * Duplicate registrations are rejected by validation (one email per event), so by
     * the time we get here the email is guaranteed to be new for this event.
     */
    public function store(StoreAttendeeRequest $request, Event $event): JsonResponse
    {
        $validated = $request->validated();

        try {
            /** @var Attendee $attendee */
            $attendee = $event->attendees()->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
            ]);
        } catch (UniqueConstraintViolationException) {
            // Guards the rare race where two concurrent requests pass validation;
            // the database unique index is the source of truth.
            throw ValidationException::withMessages([
                'email' => 'This email is already registered for this event. Please use a different email address.',
            ]);
        }

        Mail::to($attendee->email)->send(new RegistrationConfirmation($event, $attendee));
        $attendee->forceFill(['confirmation_sent_at' => now()])->save();

        return response()->json([
            'message' => "You're in! We've emailed a confirmation to {$attendee->email}.",
            'attendee' => new AttendeeResource($attendee),
            'attendees_count' => $event->attendees()->count(),
        ], 201);
    }
}
