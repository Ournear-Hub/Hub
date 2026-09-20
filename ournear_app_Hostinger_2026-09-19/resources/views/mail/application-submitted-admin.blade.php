@component('mail::message')
# New job application

**{{ $application->full_name }}** applied for **{{ $job?->title ?? 'a role' }}**.

- Email: {{ $application->email }}
- Mobile: {{ $application->mobile }}
- Location: {{ $application->current_location }}

@component('mail::button', ['url' => $adminUrl])
Open in admin
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
