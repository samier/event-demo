<?php

namespace App\Mail;

use App\Models\Attendee;
use App\Models\Event;
use App\Support\Geocoder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EventReminder extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  '3-day'|'24-hour'  $window  Which reminder window this email is for.
     */
    public function __construct(
        public Event $event,
        public Attendee $attendee,
        public string $window,
    ) {}

    public function envelope(): Envelope
    {
        $lead = $this->window === '3-day' ? 'In 3 days' : 'Tomorrow';

        return new Envelope(
            subject: "{$lead}: {$this->event->name()}",
        );
    }

    public function content(): Content
    {
        $address = Geocoder::resolve($this->event->latitude, $this->event->longitude);

        return new Content(
            markdown: 'mail.event-reminder',
            with: [
                'eventName' => $this->event->name(),
                'attendeeName' => $this->attendee->name,
                'lead' => $this->window === '3-day' ? 'is just 3 days away' : 'is tomorrow',
                'location' => $address['label'],
                'when' => $this->event->startsAt()->setTimezone($address['timezone'])->format('l, F j, Y · g:i A T'),
                'url' => route('events.show', $this->event),
            ],
        );
    }
}
