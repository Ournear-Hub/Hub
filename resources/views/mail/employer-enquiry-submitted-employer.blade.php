@component('mail::message')
# Enquiry received

Hello {{ $enquiry->contact_person }},

Thank you for contacting OurNear Talent about workforce for **{{ $enquiry->company_name }}**. We have received your request and will be in touch.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
