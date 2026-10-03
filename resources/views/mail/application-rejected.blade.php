@component('mail::message')
# Application update

Hello {{ $application->full_name }},

Thank you for applying for **{{ $job?->title ?? 'this role' }}**. After careful review, we will not be progressing your application on this occasion.

We appreciate the time you took to apply and wish you well in your search.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
