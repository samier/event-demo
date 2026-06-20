<?php

namespace App\Http\Controllers;

use App\Mail\EventReminder;
use App\Mail\RegistrationConfirmation;
use App\Models\Attendee;
use App\Models\Event;
use Illuminate\Http\Response;

/**
 * Renders the confirmation and reminder emails in the browser so the email flow can
 * be verified visually — no mail client or log digging required. Linked from the
 * email-activity page ({@see DevController}). Registered only in the local
 * environment (see routes/web.php).
 */
class MailPreviewController extends Controller
{
    public function confirmation(Event $event): Response
    {
        return response((new RegistrationConfirmation($event, $this->sampleAttendee($event)))->render());
    }

    public function reminder(Event $event, string $window): Response
    {
        $window = $window === '24-hour' ? '24-hour' : '3-day';

        return response((new EventReminder($event, $this->sampleAttendee($event), $window))->render());
    }

    /** Use a real attendee if the event has one, otherwise a throwaway sample. */
    private function sampleAttendee(Event $event): Attendee
    {
        return $event->attendees()->first()
            ?? new Attendee(['name' => 'Ada Lovelace', 'email' => 'ada@example.com']);
    }
}
