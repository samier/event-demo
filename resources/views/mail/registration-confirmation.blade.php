@component('mail::message')
# You're on the list 🎟️

Hi {{ $attendeeName }},

Thanks for registering for **{{ $eventName }}**. We've saved your spot.

@component('mail::panel')
**When:** {{ $when }}
**Where:** {{ $location }}
@endcomponent

We'll send you a reminder as the event approaches — once **3 days before** and again **24 hours before**.

@component('mail::button', ['url' => $url])
View event details
@endcomponent

See you there,
{{ config('app.name') }}
@endcomponent
