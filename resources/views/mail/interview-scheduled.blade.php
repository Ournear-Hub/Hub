@component('mail::message')
# Interview scheduled

Hello {{ $application->full_name }},

Your interview for **{{ $job?->title ?? 'this role' }}** has been scheduled.

**Date and time:** {{ $interview->scheduled_at?->timezone(config('app.timezone'))->format('l, j F Y \a\t H:i') }}  
**Mode:** {{ str_replace('_', ' ', $interview->mode->value) }}
@if (filled($interview->location_or_link))
**Location or meeting link:** {{ $interview->location_or_link }}
@endif

Please reply only if you need to reschedule. Bring any identification requested in the job listing.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
