@component('mail::message')
# You have been selected

Hello {{ $application->full_name }},

@if (! empty($introLine))
{{ $introLine }}
@else
We are pleased to confirm that you have been selected for **{{ $job?->title ?? 'this role' }}**.
@endif

OurNear Talent will contact you with next steps. This message is not an offer of employment.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
