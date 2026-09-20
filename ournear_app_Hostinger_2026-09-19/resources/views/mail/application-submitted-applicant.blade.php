@component('mail::message')
# Application received

Hello {{ $application->full_name }},

Thank you for applying for **{{ $job?->title ?? 'this role' }}**. OurNear Talent has received your application and will review it.

You do not need a candidate account. We will contact you if we need more information.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
