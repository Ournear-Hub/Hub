@component('mail::message')
# New freelancer enquiry

**{{ $freelancer->name }}** submitted a freelancer application.

- Freelancer ID: {{ $freelancer->business_id }}
- Email: {{ $freelancer->email }}
- Mobile: {{ $freelancer->mobile }}
- Primary skill: {{ $freelancer->primary_skill }}

@component('mail::button', ['url' => $adminUrl])
Open in admin
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
