@component('mail::message')
# Recruitment update

Hello,

@if (! empty($introLine))
{{ $introLine }}
@else
You have a recruitment update for **{{ $job?->title ?? 'your job listing' }}**.
@endif

@component('mail::button', ['url' => $actionUrl])
View application
@endcomponent

If the button does not work, copy and paste this link into your browser:

{{ $actionUrl }}

Thanks,<br>
{{ config('app.name') }}
@endcomponent
