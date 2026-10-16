@component('mail::message')
# Mail delivery test

This is a diagnostic message from **OurNear Hire** admin email settings.

Requested by: {{ $requestedBy->name }} ({{ $requestedBy->email }})  
Sent at: {{ $sentAt }}

If you received this message, the application mail transport accepted the message for delivery. Inbox placement still depends on SPF, DKIM, and provider reputation.

Thanks,<br>
{{ config('mail.from.name') }}
@endcomponent
