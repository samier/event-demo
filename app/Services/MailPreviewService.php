<?php

namespace App\Services;

use App\Models\Attendee;
use App\Models\Event;

class MailPreviewService
{
    /** Use a real attendee if the event has one, otherwise a throwaway sample. */
    public function sampleAttendee(Event $event): Attendee
    {
        return $event->attendees()->first()
            ?? new Attendee(['name' => 'Ada Lovelace', 'email' => 'ada@example.com']);
    }
}
