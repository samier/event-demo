@component('mail::message')
# {{ $eventName }} {{ $lead }} ⏰

Hi {{ $attendeeName }},

Just a friendly reminder that **{{ $eventName }}** {{ $lead }}.

@component('mail::panel')
**When:** {{ $when }}
**Where:** {{ $location }}
@endcomponent

@component('mail::button', ['url' => $url])
View event details
@endcomponent

Can't make it anymore? No problem — just let us know.

See you soon,
{{ config('app.name') }}
@endcomponent
