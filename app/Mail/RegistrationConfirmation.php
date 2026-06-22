<?php

namespace App\Mail;

use App\Models\Attendee;
use App\Models\Event;
use App\Models\CityAnchor;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RegistrationConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Event $event,
        public Attendee $attendee,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "You're registered: {$this->event->name()}",
        );
    }

    public function content(): Content
    {
        $address = CityAnchor::resolveAddress($this->event->latitude, $this->event->longitude);

        return new Content(
            markdown: 'mail.registration-confirmation',
            with: [
                'eventName' => $this->event->name(),
                'attendeeName' => $this->attendee->name,
                'location' => $address['label'],
                'when' => $this->event->startsAt()->setTimezone($address['timezone'])->format('l, F j, Y · g:i A T'),
                'url' => route('events.show', $this->event),
            ],
        );
    }
}
