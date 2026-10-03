@component('mail::message')
# Message received

Hello {{ $enquiry->full_name }},

Thank you for contacting OurNear Talent. We have received your message and will respond as soon as we can.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
